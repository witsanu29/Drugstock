<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/admin_only.php';

/* ===== Session ===== */
$role    = $_SESSION['role'] ?? '';
$unit_id = intval($_SESSION['unit_id'] ?? 0);

/* ===== Filter ===== */
$filter_unit = intval($_GET['unit_code'] ?? 0);

$where = "WHERE 1=1";

/* ไม่ใช่ admin เห็นเฉพาะหน่วยตัวเอง */
if ($role !== 'admin') {
    $where .= " AND m.unit_id = $unit_id";
}

/* admin เลือกกรองหน่วยบริการ */
if ($role === 'admin' && $filter_unit > 0) {
    $where .= " AND m.unit_id = $filter_unit";
}

/* ===== Pagination ===== */
$page = max(1, intval($_GET['page'] ?? 1));

$limit = intval($_GET['limit'] ?? 100);
$allow_limits = [100,300,500,1000,2000];
if (!in_array($limit, $allow_limits)) {
    $limit = 100;
}

$offset = ($page - 1) * $limit;

/* ===== Count Total ===== */
$count_sql = "
    SELECT COUNT(*) AS total
    FROM main_warehouse m
    LEFT JOIN units u ON m.unit_id = u.unit_id
    $where
    AND DATEDIFF(m.expiry_date, CURDATE()) BETWEEN 0 AND 30
";
$count_rs    = $conn->query($count_sql);
$total_rows = $count_rs->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

/* ===== Main Query ===== */
$sql = "
    SELECT 
        m.drug_name,
        m.units AS drug_unit,
        m.received_date,
        m.expiry_date,
        DATEDIFF(m.expiry_date, CURDATE()) AS days_left,
        u.unit_code
    FROM main_warehouse m
    LEFT JOIN units u ON m.unit_id = u.unit_id
    $where
    AND DATEDIFF(m.expiry_date, CURDATE()) BETWEEN 0 AND 30
    ORDER BY days_left ASC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

/* ===== Thai Date ===== */
function thaiDate($date){
    if(!$date) return '-';
    return date('d/m/', strtotime($date)) . (date('Y', strtotime($date)) + 543);
}
?>


<?php  
require '../head2.php';
require 'bar.php';
?>

	
		   <!-- ================= Main Content ================= -->
			<main id="mainContent" class="main-content px-4 py-4">
				<div class="d-flex justify-content-between align-items-center mb-4">
						<h3 class="mb-0">
							<i class="bi bi-hospital"></i> ยาใกล้หมดอายุ (ภายใน 30 วัน)
						</h3>

						<div class="d-flex gap-2">
						<span class="badge bg-primary fs-6 px-3 py-2">ยาใกล้หมดอายุ</span>
						
						<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'demo'): ?>
							<a href="export_expiry_excel.php?unit_code=<?= $_GET['unit_code'] ?? '' ?>"
							   class="btn btn-success btn-sm">
							   <i class="bi bi-file-earmark-excel"></i> Excel
							</a>
						<?php endif; ?>

					</div>
				</div>


				<!-- Card -->
				<div class="card shadow-sm">
					<div class="card-header bg-orange text-white">
						<i class="bi bi-table"></i> รายงานการใช้ยารายวัน
					</div>

					<div class="card-body">

						<form method="get" class="row g-2 mb-3 align-items-end">
							
						<?php if ($_SESSION['role'] === 'admin'): ?>
						<div class="col-md-4">
							<label class="form-label">กรองหน่วยบริการ</label>
							<select name="unit_code"
								class="form-select form-select-sm"
								style="min-width:220px"
								onchange="this.form.submit()">

								<option value="">-- ทุกหน่วยบริการ --</option>

								<?php
								$unit_rs = $conn->query("SELECT unit_id, unit_code FROM units ORDER BY unit_code");
								while ($u = $unit_rs->fetch_assoc()):
								?>
									<option value="<?= $u['unit_id'] ?>"
										<?= ($_GET['unit_code'] ?? '') == $u['unit_id'] ? 'selected' : '' ?>>
										<?= htmlspecialchars($u['unit_code']) ?>
									</option>
								<?php endwhile; ?>
							</select>
						</div>
						<?php endif; ?>

							<div class="col-md-3">
								<label class="form-label">แสดงต่อหน้า</label>
								<select name="limit" class="form-select" onchange="this.form.submit()">
									<?php foreach ([100,300,500,1000,2000] as $l): ?>
										<option value="<?= $l ?>" <?= $limit == $l ? 'selected' : '' ?>>
											<?= number_format($l) ?> รายการ
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<input type="hidden" name="page" value="1">
						</form>


						<div class="table-responsive">
						
						<table class="table table-bordered table-hover align-middle mb-0">
						<thead class="table-light">
						<tr>
							<th width="60">ลำดับ</th>
							<th>หน่วยบริการ</th>
							<th>ชื่อยา</th>
							<th width="10%">หน่วยนับ</th>
							<th>วันที่รับ</th>
							<th>วันหมดอายุ</th>
							<th>เหลือ (วัน)</th>
							<th>สถานะ</th>
						</tr>
						</thead>
						<tbody>

						<?php if ($result && $result->num_rows > 0): ?>
						<?php $i = ($page - 1) * $limit + 1; ?>
						<?php while ($row = $result->fetch_assoc()): ?>
						<?php
							$days = (int)$row['days_left'];
							if ($days <= 7) {
								$badge = "danger";
								$status = "เร่งด่วน";
							} elseif ($days <= 14) {
								$badge = "warning";
								$status = "ใกล้หมด";
							} else {
								$badge = "info";
								$status = "เฝ้าระวัง";
							}
						?>
						<tr>
							<td class="text-center fw-bold"><?= $i++ ?></td>
							<td><?= htmlspecialchars($row['unit_code']) ?></td>
							<td><?= htmlspecialchars($row['drug_name']) ?></td>
							<!-- ✅ แก้ตรงนี้ -->
							<td class="text-center"> <?= htmlspecialchars($row['drug_unit']) ?> </td>
							<td class="text-center"><?= thaiDate($row['received_date']) ?></td>
							<td class="text-center"><?= thaiDate($row['expiry_date']) ?></td>
							<td class="text-center fw-bold"><?= $days ?> วัน</td>
							<td class="text-center">
								<span class="badge bg-<?= $badge ?>">
									<?= $status ?>
								</span>
							</td>
						</tr>
						<?php endwhile; ?>

						<?php else: ?>
							<tr>
								<td colspan="8" class="text-center text-muted">
									🎉 ไม่มียาใกล้หมดอายุ
								</td>
							</tr>
						<?php endif; ?>

						</tbody>
						</table>

							<?php if ($total_pages > 1): ?>
							<nav class="mt-3">
							<ul class="pagination justify-content-center">

								<!-- ก่อนหน้า -->
								<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
									<a class="page-link"
									   href="?page=<?= $page-1 ?>&limit=<?= $limit ?>&unit_code=<?= $_GET['unit_code'] ?? '' ?>">
									   ⬅ ก่อนหน้า
									</a>
								</li>

								<li class="page-item disabled">
									<span class="page-link">
										หน้า <?= $page ?> / <?= $total_pages ?>
									</span>
								</li>

								<!-- ถัดไป -->
								<li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
									<a class="page-link"
									   href="?page=<?= $page-1 ?>&limit=<?= $limit ?>&unit_code=<?= $_GET['unit_code'] ?? '' ?>">

									   ถัดไป ➡
									</a>
								</li>

							</ul>
							</nav>
							<?php endif; ?>

						</div>
					</div>
				</div>

			</main> <!-- end main -->

	
	<script>
	document.getElementById('toggleSidebar').addEventListener('click', function () {
	  document.getElementById('sidebar').classList.toggle('collapsed');
	});
	</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
