<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/admin_only.php';

/* ================= Pagination ================= */
$page = max(1, intval($_GET['page'] ?? 1));

$per_page_options = [100,300,500,1000,2000];
$per_page = intval($_GET['per_page'] ?? 100);
if (!in_array($per_page, $per_page_options)) {
    $per_page = 100;
}
$offset = ($page - 1) * $per_page;

/* ================= สิทธิ์ ================= */
$role        = $_SESSION['role'] ?? '';
$unit_id     = intval($_SESSION['unit_id'] ?? 0);
$filter_unit = intval($_GET['unit_id'] ?? 0);

$where = [];
if ($role !== 'admin') {
    $where[] = "m.unit_id = $unit_id";
} elseif ($filter_unit > 0) {
    $where[] = "m.unit_id = $filter_unit";
}
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* ================= COUNT ================= */
$count_sql = "
    SELECT COUNT(*) total FROM (
        SELECT 1
        FROM sub_warehouse s
        LEFT JOIN main_warehouse m ON s.drug_id = m.id
        LEFT JOIN units u ON m.unit_id = u.unit_id
        $where_sql
        GROUP BY s.sub_name, u.unit_code, m.drug_name
        HAVING SUM(s.remaining) < 25
    ) x
";
$total_rows  = $conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = max(1, ceil($total_rows / $per_page));

/* ================= DATA ================= */
$i = ($page - 1) * $per_page + 1;

$sql = "
    SELECT 
        s.sub_name,
        u.unit_code,
        COALESCE(m.drug_name,'ไม่ทราบชื่อยา') AS drug_name,
        m.units AS drug_unit,
        SUM(s.remaining) AS total_remaining
    FROM sub_warehouse s
    LEFT JOIN main_warehouse m ON s.drug_id = m.id
    LEFT JOIN units u ON m.unit_id = u.unit_id
    $where_sql
    GROUP BY s.sub_name, u.unit_code, drug_name, m.units
    HAVING total_remaining < 25
    ORDER BY total_remaining ASC
    LIMIT $per_page OFFSET $offset
";
$res = $conn->query($sql);
?>


<?php  
require '../head2.php';
require 'bar.php';
?>

	
		   <!-- ================= Main Content ================= -->
			<main id="mainContent" class="main-content px-4 py-4">
				<div class="d-flex justify-content-between align-items-center mb-4">
						<h3 class="mb-0">
							<i class="bi bi-hospital"></i> เวชภัณฑ์ยาใกล้หมด (ต่ำกว่า 25)
						</h3>

						<div class="d-flex gap-2">
						<span class="badge bg-primary fs-6 px-3 py-2">เวชภัณฑ์ยาใกล้หมด</span>
						
						<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'demo'): ?>
						<a href="export_sub_stock_old_excel.php?unit_id=<?= $_GET['unit_id'] ?? '' ?>"
						   class="btn btn-success btn-sm">
							<i class="bi bi-file-earmark-excel"></i> Excel
						</a>
						<?php endif; ?>
					</div>
				</div>

				<!-- Card -->
				<div class="card shadow-sm">
					<div class="card-header bg-orange text-white">
						<i class="bi bi-table"></i> เวชภัณฑ์ยาใกล้หมด (ต่ำกว่า 25)
					</div>

				<div class="card-body">
				<div class="mb-3">
				
					<form method="get" class="row align-items-center w-100 g-3">

					<!-- 🔹 กรองหน่วยบริการ (admin) -->
					<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
					<div class="col-md-6">
						<div class="d-flex align-items-center gap-2">
							<label class="mb-0 fw-semibold text-nowrap">
								กรองหน่วยบริการ
							</label>
							<select name="unit_id"
									class="form-select form-select-sm"
									style="min-width:220px"
									onchange="this.form.submit()">
								<option value="">-- ทุกหน่วยบริการ --</option>
								<?php
								$u_res = $conn->query("SELECT unit_id, unit_code FROM units ORDER BY unit_code");
								while ($u = $u_res->fetch_assoc()):
								?>
									<option value="<?= $u['unit_id'] ?>"
										<?= (($_GET['unit_id'] ?? '') == $u['unit_id']) ? 'selected' : '' ?>>
										<?= htmlspecialchars($u['unit_code']) ?>
									</option>
								<?php endwhile; ?>
							</select>
						</div>
					</div>
					<?php endif; ?>

					<!-- 🔹 เลือกจำนวนรายการ -->
					<div class="col-md-4 d-flex align-items-center gap-2">
					<label class="form-label mb-0 fw-semibold">แสดง</label>
							<select name="per_page"
									class="form-select form-select-sm w-auto"
									onchange="this.form.submit()">
								<?php foreach ($per_page_options as $n): ?>
									<option value="<?= $n ?>" <?= $per_page == $n ? 'selected' : '' ?>>
										<?= number_format($n) ?>
									</option>
								<?php endforeach; ?>
							</select>
						<span class="fw-semibold">รายการ</span>
					</div>

					<!-- hidden -->
					<input type="hidden" name="page" value="1">
				</form>
				</div>
						
						
				<div class="table-responsive">

				<table class="table table-bordered table-hover align-middle">
					<thead class="table-light text-center">
						<tr>
						<th width="5%">ลำดับ</th>
						<th>คลังย่อย</th>
						<th>หน่วยบริการ</th>
						<th>ชื่อยา</th>
						<th width="15%" class="text-end">คงเหลือ</th>
						<th width="10%">หน่วย</th>
						</tr>
					</thead>
				<tbody>

					<?php if ($res && $res->num_rows > 0): ?>
					<?php while ($r = $res->fetch_assoc()): ?>
						<tr>
						<td class="text-center"><?= $i++ ?></td>
						<td><?= htmlspecialchars($r['sub_name']) ?></td>
						<td><?= htmlspecialchars($r['unit_code']) ?></td>
						<td><?= htmlspecialchars($r['drug_name']) ?></td>
						<td class="text-end fw-bold text-danger">
						<?= number_format($r['total_remaining']) ?>
						</td>
						<td class="text-center"><?= htmlspecialchars($r['drug_unit']) ?></td>
						</tr>
					<?php endwhile; ?>
					<?php else: ?>
						<tr>
						<td colspan="6" class="text-center text-muted">
						ไม่พบรายการยาที่คงเหลือน้อยกว่า 25
						</td>
						</tr>
					<?php endif; ?>

				</tbody>
				</table>

				<!-- ===== Pagination ===== -->
				<div class="col-12 d-flex justify-content-center">
					<ul class="pagination mb-0">

						<li class="page-item <?= $page<=1?'disabled':'' ?>">
							<a class="page-link"
							   href="?page=<?= $page-1 ?>&per_page=<?= $per_page ?><?= $filter_unit?"&unit_id=$filter_unit":"" ?>">
							   ◀ ก่อนหน้า
							</a>
						</li>

						<li class="page-item disabled">
							<span class="page-link">
								หน้า <?= $page ?> / <?= $total_pages ?>
							</span>
						</li>

						<li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
							<a class="page-link"
							   href="?page=<?= $page+1 ?>&per_page=<?= $per_page ?><?= $filter_unit?"&unit_id=$filter_unit":"" ?>">
							   ถัดไป ▶
							</a>
						</li>

					</ul>
				</div>

			</div>
		</div>
		</div>
	</main>
	
	<script>
	document.getElementById('toggleSidebar').addEventListener('click', function () {
	  document.getElementById('sidebar').classList.toggle('collapsed');
	});
	</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
