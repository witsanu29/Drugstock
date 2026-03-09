<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

/* ===== USER ===== */
$role    = $_SESSION['role'] ?? 'demo';
$unit_id = (int)($_SESSION['unit_id'] ?? 0);

/* ===== FILTER UNIT (ADMIN) ===== */
$filter_unit = '';
if ($role === 'admin' && isset($_GET['unit_id']) && $_GET['unit_id'] !== '') {
	$filter_unit = (int)$_GET['unit_id'];
}

/* ===== PAGINATION ===== */
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = (int)($_GET['limit'] ?? 100);

$allow_limits = [100, 500, 1000, 2000];
if (!in_array($limit, $allow_limits)) $limit = 100;

$offset = ($page - 1) * $limit;

/* ===== COUNT TOTAL ===== */
if ($role === 'admin') {

	if ($filter_unit) {
		$stmt = $conn->prepare(
			"SELECT COUNT(*) total FROM non_drug_warehouse WHERE unit_id = ?"
		);
		$stmt->bind_param("i", $filter_unit);
	} else {
		$stmt = $conn->prepare(
			"SELECT COUNT(*) total FROM non_drug_warehouse"
		);
	}
	$stmt->execute();
	$total = (int)$stmt->get_result()->fetch_assoc()['total'];

} else {

	if ($unit_id === 0) {
		$total = 0;
	} else {
		$stmt = $conn->prepare(
			"SELECT COUNT(*) total FROM non_drug_warehouse WHERE unit_id = ?"
		);
		$stmt->bind_param("i", $unit_id);
		$stmt->execute();
		$total = (int)$stmt->get_result()->fetch_assoc()['total'];
	}
}

$total_pages = max(1, ceil($total / $limit));

/* ===== UNIT LIST (ADMIN) ===== */
$unitRes = null;
if ($role === 'admin') {
	$unitRes = $conn->query(
		"SELECT unit_id, unit_name FROM units ORDER BY unit_name"
	);
}
?>



<?php 
require '../head2.php'; 
require 'bar.php'; 
?>

		<main id="mainContent" class="main-content px-4 py-4">
			<div class="card shadow-sm">

			<div class="card-header bg-orange text-white d-flex justify-content-between">
				<span class="fw-semibold">📊 รายงานคงเหลือเวชภัณฑ์มิใช่ยา</span>
				<?php if ($role !== 'demo'): ?>
				<a href="export_non_drug_excel.php" class="btn btn-success btn-sm">📥 ส่งออก Excel</a>
				<?php endif; ?>
			</div>

				<div class="card-body table-responsive">

					<form method="get" class="row g-2 mb-3">

						<?php if ($role === 'admin'): ?>
						<div class="col-md-4">
						<select name="unit_id" class="form-select" onchange="this.form.submit()">
						<option value="">🔍 ทุกหน่วยบริการ</option>
						<?php while ($u = $unitRes->fetch_assoc()): ?>
						<option value="<?= $u['unit_id'] ?>" <?= $filter_unit==$u['unit_id']?'selected':'' ?>>
						<?= htmlspecialchars($u['unit_name']) ?>
						</option>
						<?php endwhile; ?>
						</select>
						</div>
						<?php endif; ?>

						<div class="col-md-3">
							<select name="limit" class="form-select" onchange="this.form.submit()">
							<?php foreach ([100,500,1000,2000] as $l): ?>
							<option value="<?= $l ?>" <?= $limit==$l?'selected':'' ?>>
							แสดง <?= number_format($l) ?> รายการ
							</option>
							<?php endforeach; ?>
							</select>
						</div>

						<input type="hidden" name="page" value="1">
					</form>

				<table class="table table-bordered table-hover align-middle">
				<thead class="table-dark">
					<tr>
					<th>ลำดับ</th>
					<th>หน่วยบริการ</th>
					<th>ชื่อเวชภัณฑ์มิใช่ยา</th>
					<th class="text-center">คงเหลือ</th>
					<th>หน่วย</th>
					<th>วันหมดอายุ</th>
					<th>สถานะ</th>
					</tr>
				</thead>

					<tbody>
					<?php
					$i = $offset + 1;

					if ($role === 'admin') {

						if ($filter_unit) {
							$sql = "
							SELECT w.*, u.unit_name
							FROM non_drug_warehouse w
							LEFT JOIN units u ON w.unit_id = u.unit_id
							WHERE w.unit_id = ?
							ORDER BY w.remaining ASC
							LIMIT ?, ?";
							$stmt = $conn->prepare($sql);
							$stmt->bind_param("iii", $filter_unit, $offset, $limit);
						} else {
							$sql = "
							SELECT w.*, u.unit_name
							FROM non_drug_warehouse w
							LEFT JOIN units u ON w.unit_id = u.unit_id
							ORDER BY w.remaining ASC
							LIMIT ?, ?";
							$stmt = $conn->prepare($sql);
							$stmt->bind_param("ii", $offset, $limit);
						}

					} else {

						$sql = "
						SELECT w.*, u.unit_name
						FROM non_drug_warehouse w
						LEFT JOIN units u ON w.unit_id = u.unit_id
						WHERE w.unit_id = ?
						ORDER BY w.remaining ASC
						LIMIT ?, ?";
						$stmt = $conn->prepare($sql);
						$stmt->bind_param("iii", $unit_id, $offset, $limit);
					}

					$stmt->execute();
					$res = $stmt->get_result();

					while ($row = $res->fetch_assoc()):
					$status = $row['remaining']==0 ? 'หมด' : ($row['remaining']<=10?'ใกล้หมด':'ปกติ');
					$badge  = $row['remaining']==0 ? 'danger' : ($row['remaining']<=10?'warning':'success');
					?>
					<tr>
					<td><?= $i++ ?></td>
					<td><?= htmlspecialchars($row['unit_name'] ?? '-') ?></td>
					<td><?= htmlspecialchars($row['item_name']) ?></td>
					<td class="text-center"><?= number_format($row['remaining']) ?></td>
					<td><?= htmlspecialchars($row['unit']) ?></td>
					<td><?= $row['expiry_date'] ? date('d/m/',strtotime($row['expiry_date'])) . (date('Y',strtotime($row['expiry_date']))+543) : '-' ?></td>
					<td><span class="badge bg-<?= $badge ?>"><?= $status ?></span></td>
					</tr>
					<?php endwhile; ?>
					</tbody>
				</table>

				<nav class="mt-3">
					<ul class="pagination justify-content-center">
					<li class="page-item <?= $page<=1?'disabled':'' ?>">
					<a class="page-link" href="?page=<?= $page-1 ?>&limit=<?= $limit ?>&unit_id=<?= $filter_unit ?>">◀ ก่อนหน้า</a>
					</li>
					<li class="page-item disabled">
					<span class="page-link">หน้า <?= $page ?> / <?= $total_pages ?></span>
					</li>
					<li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
					<a class="page-link" href="?page=<?= $page+1 ?>&limit=<?= $limit ?>&unit_id=<?= $filter_unit ?>">ถัดไป ▶</a>
					</li>
					</ul>
				</nav>

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
