<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

$role    = $_SESSION['role'] ?? 'demo';
$unit_id = (int)($_SESSION['unit_id'] ?? 0);

/* ================= FILTER ================= */
$filter_unit = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;

/* ================= PAGINATION ================= */
$allowed_limits = [100, 500, 1000, 2000];

// กัน undefined index + กันค่าแปลก
$limit_get = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

$per_page = in_array($limit_get, $allowed_limits)
    ? $limit_get
    : 100;

$page  = isset($_GET['page']) && (int)$_GET['page'] > 0
    ? (int)$_GET['page']
    : 1;

$start = ($page - 1) * $per_page;


/* ================= WHERE ================= */
$where = [
    "nd.expiry_date IS NOT NULL",
    "nd.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
];

$params = [];
$types  = "";

/* staff เห็นเฉพาะของตัวเอง */
if ($role !== 'admin') {
    $where[]  = "nd.unit_id = ?";
    $params[] = $unit_id;
    $types   .= "i";
}

/* admin + filter */
if ($role === 'admin' && $filter_unit > 0) {
    $where[]  = "nd.unit_id = ?";
    $params[] = $filter_unit;
    $types   .= "i";
}

$where_sql = implode(" AND ", $where);

/* ================= COUNT ================= */
$sql_count = "
    SELECT COUNT(*) AS total
    FROM non_drug_warehouse nd
    WHERE $where_sql
";
$stmt = $conn->prepare($sql_count);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRow   = $stmt->get_result()->fetch_assoc()['total'];
$total_page = ($per_page > 0)
    ? max(1, ceil($totalRow / $per_page))
    : 1;


/* ================= DATA ================= */
$sql = "
SELECT 
    nd.*,
    u.unit_name,
    DATEDIFF(nd.expiry_date, CURDATE()) AS days_left
FROM non_drug_warehouse nd
LEFT JOIN units u ON nd.unit_id = u.unit_id
WHERE $where_sql
ORDER BY nd.expiry_date ASC
LIMIT ?, ?
";

$paramsData   = $params;
$typesData    = $types . "ii";
$paramsData[] = $start;
$paramsData[] = $per_page;

$stmt = $conn->prepare($sql);
$stmt->bind_param($typesData, ...$paramsData);
$stmt->execute();
$res = $stmt->get_result();

/* ================= UNIT LIST (ADMIN) ================= */
$unitRes = null;
if ($role === 'admin') {
    $unitRes = $conn->query("SELECT unit_id, unit_name FROM units ORDER BY unit_name");
}
?>




<?php require '../head2.php'; ?>
<?php require 'bar.php'; ?>

	<main id="mainContent" class="main-content px-4 py-4">
		<div class="card shadow-sm">

		<div class="card-header bg-orange text-white d-flex justify-content-between align-items-center">
			⏰ เวชภัณฑ์มิใช่ยาใกล้หมดอายุ (30 วัน)

			<?php if ($role !== 'demo'): ?>
				<a href="export_expiry_non_drug.php" class="btn btn-success btn-sm">
					📥 ส่งออก Excel
				</a>
			<?php endif; ?>
		</div>

		<div class="card-body table-responsive">

		<!-- ================= FILTER FORM ================= -->
		<form method="get" class="row g-2 mb-3">

		<?php if ($role === 'admin'): ?>
		<div class="col-md-4">
			<select name="unit_id" class="form-select">
				<option value="">🔍 ทุกหน่วยบริการ</option>
				<?php while ($u = $unitRes->fetch_assoc()): ?>
					<option value="<?= $u['unit_id'] ?>" <?= ($filter_unit == $u['unit_id']) ? 'selected' : '' ?>>
						<?= htmlspecialchars($u['unit_name']) ?>
					</option>
				<?php endwhile; ?>
			</select>
		</div>
		<?php endif; ?>

		<div class="col-md-3">
			<select name="limit" class="form-select">
				<?php foreach ([100,500,1000,2000] as $l): ?>
					<option value="<?= $l ?>" <?= ($per_page == $l) ? 'selected' : '' ?>>
						แสดง <?= number_format($l) ?> รายการ
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="col-md-2">
			<button class="btn btn-primary w-100">แสดง</button>
		</div>

		</form>

		<!-- ================= TABLE ================= -->
		<table class="table table-striped table-hover">
		<thead class="table-dark">
			<tr>
				<th width="60">ลำดับ</th>
				<th>หน่วยบริการ</th>
				<th>ชื่อเวชภัณฑ์มิใช่ยา</th>
				<th class="text-center">คงเหลือ</th>
				<th>หน่วย</th>
				<th>วันหมดอายุ</th>
				<th class="text-center">เหลือ (วัน)</th>
			</tr>
		</thead>
			<tbody>

			<?php $i = $start + 1; while ($row = $res->fetch_assoc()): ?>
			<tr>
				<td><?= $i++ ?></td>
				<td><?= htmlspecialchars($row['unit_name'] ?? '-') ?></td>
				<td><?= htmlspecialchars($row['item_name']) ?></td>
				<td class="text-center"><?= number_format($row['remaining']) ?></td>
				<td><?= htmlspecialchars($row['unit']) ?></td>
				<td>
					<?= date('d/m/', strtotime($row['expiry_date'])) . (date('Y', strtotime($row['expiry_date'])) + 543) ?>
				</td>
				<td class="text-center">
					<span class="badge bg-danger"><?= $row['days_left'] ?> วัน</span>
				</td>
			</tr>
			<?php endwhile; ?>

			</tbody>
		</table>

		<!-- ================= PAGINATION ================= -->
			<nav>
				<ul class="pagination justify-content-center">

				<li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
				<a class="page-link"
				   href="?page=<?= $page-1 ?>&limit=<?= $per_page ?>&unit_id=<?= $filter_unit ?>">
				   ◀ ก่อนหน้า
				</a>
				</li>

				<li class="page-item disabled">
				<span class="page-link">
					หน้า <?= $page ?> / <?= $total_page ?>
				</span>
				</li>

				<li class="page-item <?= ($page >= $total_page) ? 'disabled' : '' ?>">
				<a class="page-link"
				   href="?page=<?= $page+1 ?>&limit=<?= $per_page ?>&unit_id=<?= $filter_unit ?>">
				   ถัดไป ▶
				</a>
				</li>

				</ul>
			</nav>

		</div>
		</div>
	</main>

<?php include '../includes/footer.php'; ?>

</body>
</html>
