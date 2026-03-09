<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/admin_only.php';

	$role    = $_SESSION['role'] ?? '';
	$unit_id = intval($_SESSION['unit_id'] ?? 0);

	$where = "WHERE 1=1";

	if ($role !== 'admin') {
		$where .= " AND m.unit_id = $unit_id";
	}

	if ($role === 'admin' && !empty($_GET['unit_id'])) {
		$filter_unit = intval($_GET['unit_id']);
		$where .= " AND m.unit_id = $filter_unit";
	}

	// จำนวนรายการต่อหน้า
	$perPageList = [100,300,500,1000,2000];
	$perPage = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $perPageList)
		? (int)$_GET['per_page']
		: 100;

	// หน้าปัจจุบัน
	$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
	$offset = ($page - 1) * $perPage;

	// นับจำนวนทั้งหมด
	$countSql = "
		SELECT COUNT(*) total FROM (
			SELECT u.unit_code, m.drug_name, m.units
			FROM main_warehouse m
			LEFT JOIN units u ON m.unit_id = u.unit_id
			$where
			GROUP BY u.unit_code, m.drug_name, m.units
		) t
	";
	$countRes   = $conn->query($countSql);
	$totalRows  = $countRes->fetch_assoc()['total'];
	$totalPages = max(1, ceil($totalRows / $perPage));


	// query หลัก
	$sql = "
		SELECT 
			u.unit_code,
			m.drug_name,
			m.units AS drug_unit,
			SUM(m.remaining) AS total_remaining
		FROM main_warehouse m
		LEFT JOIN units u ON m.unit_id = u.unit_id
		$where
		GROUP BY u.unit_code, m.drug_name, m.units
		ORDER BY u.unit_code, m.drug_name
		LIMIT $perPage OFFSET $offset
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
						<i class="bi bi-hospital"></i> รายงานคงเหลือคลังใหญ่
					</h3>

					<div class="d-flex gap-2">
					<span class="badge bg-primary fs-6 px-3 py-2">คลังยาใหญ่</span>
					
					<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'demo'): ?>
					<a href="export_main_stock_excel.php<?= !empty($_GET['unit_id']) ? '?unit_id='.(int)$_GET['unit_id'] : '' ?>"
					   class="btn btn-success btn-sm">
						<i class="bi bi-file-earmark-excel"></i> Excel
					</a>
					<?php endif; ?>
					</div>
				</div>


				<!-- Card -->
				<div class="card shadow-sm">
					<div class="card-header bg-orange text-white">
						<i class="bi bi-table"></i> รายงานคงเหลือคลังใหญ่
					</div>
					
					<div class="card-body">

					<form method="get" class="row g-3 align-items-end mb-3">

						<?php if ($_SESSION['role'] === 'admin'): ?>
						<div class="col-md-5">
							<label class="form-label fw-bold mb-1">หน่วยบริการ</label>
							<select name="unit_id"
									class="form-select form-select-sm"
									onchange="this.form.submit()">
								<option value="">-- ทุกหน่วยบริการ --</option>
								<?php
								$u = $conn->query("SELECT unit_id, unit_code FROM units ORDER BY unit_code");
								while ($row = $u->fetch_assoc()):
									$sel = (isset($_GET['unit_id']) && $_GET['unit_id'] == $row['unit_id']) ? 'selected' : '';
								?>
									<option value="<?= $row['unit_id'] ?>" <?= $sel ?>>
										<?= htmlspecialchars($row['unit_code']) ?>
									</option>
								<?php endwhile; ?>
							</select>
						</div>
						<?php endif; ?>

						<div class="col-md-4 d-flex align-items-center gap-2">
							<label class="fw-semibold mb-0">แสดง</label>

							<select name="per_page"
									class="form-select form-select-sm w-auto"
									onchange="this.form.submit()">
								<?php foreach ($perPageList as $n): ?>
									<option value="<?= $n ?>" <?= $perPage == $n ? 'selected' : '' ?>>
										<?= number_format($n) ?>
									</option>
								<?php endforeach; ?>
							</select>

							<span class="fw-semibold">รายการ</span>
							<input type="hidden" name="page" value="1">
						</div>

						</form>

				
						<div class="table-responsive">

						<table class="table table-bordered table-hover align-middle mb-0">
						<thead class="table-light">
						<tr>
							<th class="text-center" style="width:60px;">ลำดับ</th>
							<th>หน่วยบริการ</th>
							<th>ชื่อยา</th>
							<th class="text-end">คงเหลือ</th>
							<th class="text-center">หน่วยนับ</th>
						</tr>
						</thead>
						<tbody>

						<?php if ($res && $res->num_rows > 0): ?>
						<?php $no = $offset + 1; ?>
						<?php while ($r = $res->fetch_assoc()): ?>
						<tr>
							<td class="text-center"><?= $no ?></td>
							<td><?= htmlspecialchars($r['unit_code']) ?></td>
							<td><?= htmlspecialchars($r['drug_name']) ?></td>
							<td class="text-end fw-bold"> <?= number_format($r['total_remaining']) ?> </td>
							<td class="text-center"> <?= htmlspecialchars($r['drug_unit']) ?> </td>
						</tr>
						<?php 
						$no++;
						endwhile; 
						?>
						<?php else: ?>
						<tr>
							<td colspan="5" class="text-center text-muted">ไม่มีข้อมูล</td>
						</tr>
						<?php endif; ?>

						</tbody>
						</table>

						<div class="d-flex flex-wrap justify-content-center align-items-center mt-3">
							<nav>
								<ul class="pagination pagination-sm mb-0">

									<li class="page-item <?= $page<=1?'disabled':'' ?>">
										<a class="page-link"
										   href="?page=<?= $page-1 ?>&per_page=<?= $perPage ?><?= !empty($_GET['unit_id']) ? '&unit_id='.(int)$_GET['unit_id'] : '' ?>">
											◀ ก่อนหน้า
										</a>
									</li>

									<li class="page-item disabled">
										<span class="page-link fw-semibold">
											หน้า <?= $page ?> / <?= $totalPages ?>
										</span>
									</li>

									<li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
										<a class="page-link"
										   href="?page=<?= $page+1 ?>&per_page=<?= $perPage ?><?= !empty($_GET['unit_id']) ? '&unit_id='.(int)$_GET['unit_id'] : '' ?>">
											ถัดไป ▶
										</a>
									</li>
								</ul>
							</nav>
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
