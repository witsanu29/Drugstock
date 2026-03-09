<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/admin_only.php';

$role    = $_SESSION['role'] ?? '';
$unit_id = intval($_SESSION['unit_id'] ?? 0);

/* ===== Pagination ===== */
$page = max(1, intval($_GET['page'] ?? 1));

$allow_per_page = [100,300,500,1000,2000];
$per_page = intval($_GET['per_page'] ?? 100);
if (!in_array($per_page, $allow_per_page)) {
    $per_page = 100;
}

$offset = ($page - 1) * $per_page;

/* ===== Filter ===== */
$filter_unit = intval($_GET['unit_id'] ?? 0);

/* ===== WHERE ===== */
$where = [];

if ($role !== 'admin') {
    $where[] = "m.unit_id = $unit_id";
} elseif ($filter_unit > 0) {
    $where[] = "m.unit_id = $filter_unit";
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* ===== COUNT ===== */
$count_sql = "
    SELECT COUNT(*) AS total
    FROM daily_usage d
    LEFT JOIN sub_warehouse s ON d.sub_id = s.id
    LEFT JOIN main_warehouse m ON s.drug_id = m.id
    LEFT JOIN units u ON m.unit_id = u.unit_id
    $where_sql
";

$total_rows  = $conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = max(1, ceil($total_rows / $per_page));

/* ===== DATA QUERY ===== */
$sql = "
    SELECT 
        d.usage_date,
        s.sub_name,
        u.unit_code,
        m.drug_name,
        d.quantity_used,
        m.units AS drug_unit
    FROM daily_usage d
    LEFT JOIN sub_warehouse s ON d.sub_id = s.id
    LEFT JOIN main_warehouse m ON s.drug_id = m.id
    LEFT JOIN units u ON m.unit_id = u.unit_id
    $where_sql
    ORDER BY d.usage_date DESC
    LIMIT $per_page OFFSET $offset
";

$res = $conn->query($sql);
$no  = $offset + 1;
?>



<?php  
require '../head2.php';
require 'bar.php';
?>

	
		   <!-- ================= Main Content ================= -->
			<main id="mainContent" class="main-content px-4 py-4">
				<div class="d-flex justify-content-between align-items-center mb-4">
						<h3 class="mb-0">
							<i class="bi bi-hospital"></i> รายงานการใช้ยารายวัน
						</h3>

						<div class="d-flex gap-2">
						<span class="badge bg-primary fs-6 px-3 py-2">การใช้ยารายวัน</span>
						
						<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'demo'): ?>
						<a href="export_daily_usage_excel.php?
							unit_id=<?= $_GET['unit_id'] ?? '' ?>
							&per_page=<?= $_GET['per_page'] ?? '' ?>"
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

					<form method="get" class="row g-3 align-items-center mb-3">

						<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
						<!-- กรองหน่วยบริการ -->
						<div class="col-md-4 d-flex align-items-center gap-2">
							<label class="fw-semibold mb-0" style="white-space:nowrap;">
								หน่วยบริการ
							</label>
							<select name="unit_id"
									class="form-select form-select-sm"
									style="min-width:220px"
									onchange="this.form.submit()">
								<option value="">-- ทุกหน่วยบริการ --</option>
								<?php
								$u_sql = "SELECT unit_id, unit_code FROM units ORDER BY unit_code";
								$u_res = $conn->query($u_sql);
								while ($u = $u_res->fetch_assoc()):
								?>
									<option value="<?= $u['unit_id'] ?>"
										<?= (($_GET['unit_id'] ?? '') == $u['unit_id']) ? 'selected' : '' ?>>
										<?= htmlspecialchars($u['unit_code']) ?>
									</option>
								<?php endwhile; ?>
							</select>
						</div>
						<?php endif; ?>

						<!-- แสดงต่อหน้า -->
						<div class="col-md-4 d-flex align-items-center gap-2">
							<label class="fw-semibold mb-0" style="white-space:nowrap;">
								แสดง
							</label>
							<select name="per_page"
									class="form-select form-select-sm"
									style="width:90px"
									onchange="this.form.submit()">
								<?php foreach ([100,300,500,1000,2000] as $n): ?>
									<option value="<?= $n ?>" <?= $per_page == $n ? 'selected' : '' ?>>
										<?= number_format($n) ?>
									</option>
								<?php endforeach; ?>
							</select>
							<span class="fw-semibold" style="white-space:nowrap;">รายการ</span>
						</div>

					</form>


						<div class="table-responsive">
						
							<table class="table table-bordered table-hover align-middle mb-0">
							<thead class="table-light">
							<tr>
								<th style="width:60px;">ลำดับ</th>
								<th>วันที่ใช้</th>
								<th>คลังย่อย</th>
								<th>หน่วยบริการ</th>
								<th>ชื่อยา</th>
								<th class="text-end">จำนวนใช้</th>
								<th class="text-center">หน่วยนับ</th>
							</tr>
							</thead>
							<tbody>
								<?php if ($res && $res->num_rows > 0): ?>
									<?php while ($r = $res->fetch_assoc()): ?>
										<tr>
											<td class="text-center fw-bold"><?= $no++ ?></td>
											<td class="text-center">
												<?= date('d/m/', strtotime($r['usage_date'])) . (date('Y', strtotime($r['usage_date'])) + 543) ?>
											</td>
											<td><?= htmlspecialchars($r['sub_name']) ?></td>
											<td><?= htmlspecialchars($r['unit_code']) ?></td>
											<td><?= htmlspecialchars($r['drug_name']) ?></td>
											<td class="text-end fw-bold text-danger">
												<?= number_format($r['quantity_used']) ?>
											</td>
											<td class="text-center"><?= htmlspecialchars($r['drug_unit']) ?></td>
										</tr>
									<?php endwhile; ?>
								<?php else: ?>
									<tr>
										<td colspan="7" class="text-center text-muted">
											ไม่พบข้อมูลการใช้ยา
										</td>
									</tr>
								<?php endif; ?>
								</tbody>

							</table>

							<div class="mt-3 d-flex justify-content-center">
								<nav>
									<ul class="pagination pagination-sm mb-0">

										<!-- ก่อนหน้า -->
										<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
											<a class="page-link"
											   href="?page=<?= $page - 1 ?>&per_page=<?= $per_page ?><?= isset($_GET['unit_id']) ? '&unit_id='.$_GET['unit_id'] : '' ?>">
												◀ ก่อนหน้า
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
											   href="?page=<?= $page + 1 ?>&per_page=<?= $per_page ?><?= isset($_GET['unit_id']) ? '&unit_id='.$_GET['unit_id'] : '' ?>">
												ถัดไป ▶
											</a>
										</li>

									</ul>
								</nav>
							</div>

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
