<?php
session_start();
require 'includes/auth.php';   // 🔐 ต้อง login เท่านั้น
require 'includes/db.php';
require 'includes/config.php';


if ($_SESSION['role'] === 'admin') {
    // SQL เดิม ไม่ต้อง WHERE unit_id
} else {
    // SQL แบบ WHERE unit_id = ?
}

// หลังจาก login ผ่านแล้ว
$sql = "
    SELECT u.unit_name, u.unit_code
    FROM units u
    WHERE u.unit_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['unit_id']);
$stmt->execute();
$unit = $stmt->get_result()->fetch_assoc();

$_SESSION['unit_name'] = $unit['unit_name'] ?? '';
$_SESSION['unit_code'] = $unit['unit_code'] ?? '';


// ===== pagination คลังใหญ่ =====
// จำนวนต่อหน้า
$main_per_page = 10;
$main_page = max(1, (int)($_GET['page_main'] ?? 1));
$main_offset = ($main_page - 1) * $main_per_page;

/* นับจำนวนรายการ (ชื่อยาที่ไม่ซ้ำ) */
$count_sql = "
    SELECT COUNT(*) AS total
    FROM (
        SELECT drug_name
        FROM main_warehouse
        WHERE unit_id = ?
        GROUP BY drug_name
    ) t
";

$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $_SESSION['unit_id']);
$stmt->execute();
$count_result = $stmt->get_result();
$main_total = $count_result->fetch_assoc()['total'];
$main_total_pages = ceil($main_total / $main_per_page);


/* ดึงข้อมูลจริง */
$sql = "
    SELECT 
        drug_name,
        SUM(remaining) AS remaining
    FROM main_warehouse
    WHERE unit_id = ?
    GROUP BY drug_name
    ORDER BY drug_name ASC
    LIMIT ?, ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iii",
    $_SESSION['unit_id'],
    $main_offset,
    $main_per_page
);
$stmt->execute();
$main_result = $stmt->get_result();


// ===== pagination คลังย่อย =====
$sub_limit = 10;
$sub_page = isset($_GET['page_sub']) ? max(1, (int)$_GET['page_sub']) : 1;
$sub_offset = ($sub_page - 1) * $sub_limit;

/* ===== นับจำนวนแถว หลังรวมชื่อยาซ้ำ ===== */
$count_sql = "
    SELECT COUNT(*) AS total FROM (
        SELECT s.sub_name, m.drug_name
        FROM sub_warehouse s
        LEFT JOIN main_warehouse m ON s.drug_id = m.id
        WHERE s.unit_id = ?
        GROUP BY s.sub_name, m.drug_name
    ) x
";

$stmt = $conn->prepare($count_sql);
$stmt->bind_param("i", $_SESSION['unit_id']);
$stmt->execute();
$sub_count = $stmt->get_result();
$sub_total_rows = $sub_count->fetch_assoc()['total'];
$sub_total_pages = ceil($sub_total_rows / $sub_limit);


/* ===== ดึงข้อมูล (รวมชื่อยาซ้ำ + รวมคงเหลือ) ===== */
$sql = "
    SELECT 
        s.sub_name,
        m.drug_name,
        SUM(s.remaining) AS total_remaining
    FROM sub_warehouse s
    LEFT JOIN main_warehouse m ON s.drug_id = m.id
    WHERE s.unit_id = ?
    GROUP BY s.sub_name, m.drug_name
    ORDER BY s.sub_name, m.drug_name
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iii",
    $_SESSION['unit_id'],
    $sub_limit,
    $sub_offset
);
$stmt->execute();
$sub_result = $stmt->get_result();


// ===== ดึงข้อมูลการใช้ยารายวัน =====
$sql = "
    SELECT d.*, s.sub_name, m.drug_name 
    FROM daily_usage d
    LEFT JOIN sub_warehouse s ON d.sub_id = s.id
    LEFT JOIN main_warehouse m ON s.drug_id = m.id
    WHERE d.unit_id = ?
    ORDER BY d.usage_date DESC
    LIMIT 8
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['unit_id']);
$stmt->execute();
$daily_result = $stmt->get_result();


/* =========================

========================= */


$unit_id = $_SESSION['unit_id'];
$is_admin = ($_SESSION['role'] === 'admin');

/* =========================
   คลังใหญ่
========================= */
$sql_main = "
    SELECT COUNT(*) cnt
    FROM main_warehouse
";
if (!$is_admin) {
    $sql_main .= " WHERE unit_id = ?";
    $stmt = $conn->prepare($sql_main);
    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $count_main = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
} else {
    $count_main = $conn->query($sql_main)->fetch_assoc()['cnt'];
}

/* =========================
   คลังย่อย
========================= */
$sql_sub = "
    SELECT COUNT(*) cnt
    FROM sub_warehouse
";
if (!$is_admin) {
    $sql_sub .= " WHERE unit_id = ?";
    $stmt = $conn->prepare($sql_sub);
    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $count_sub = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
} else {
    $count_sub = $conn->query($sql_sub)->fetch_assoc()['cnt'];
}

/* =========================
   การใช้ยารายวัน
========================= */
$sql_daily = "
    SELECT COUNT(*) cnt
    FROM daily_usage
";
if (!$is_admin) {
    $sql_daily .= " WHERE unit_id = ?";
    $stmt = $conn->prepare($sql_daily);
    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $count_daily = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
} else {
    $count_daily = $conn->query($sql_daily)->fetch_assoc()['cnt'];
}

?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<title>ระบบคลังยา รพ.สต.</title>
<?php require 'head.php';  ?>

<style>

/* ======================
   POWER BI STYLE
====================== */

:root{
    --pb-bg1:#fff4e6;
    --pb-bg2:#ffe8cc;
    --pb-card:#ffffff;
    --pb-shadow:0 10px 30px rgba(0,0,0,.08);
    --pb-radius:14px;
}


/* ===== Navbar Power BI ===== */

.navbar{
    box-shadow:0 4px 10px rgba(0,0,0,.08);
}

/* ===== KPI Cards ===== */

.card{
    border:none;
    border-radius:var(--pb-radius);
    box-shadow:var(--pb-shadow);
    transition:.25s;
}

.card:hover{
    transform:translateY(-4px);
}

/* ===== Dashboard KPI style ===== */

.display-6{
    font-weight:700;
}

/* ===== Table Power BI clean ===== */

.table{
    border-radius:12px;
    overflow:hidden;
}

.table thead{
    background:#2b2b2b;
    color:#fff;
}

/* ===== Sidebar modern ===== */

.offcanvas{
    border-right:none;
    box-shadow:4px 0 20px rgba(0,0,0,.08);
}

/* ===== Pagination modern ===== */

.page-link{
    border-radius:8px;
    margin:0 2px;
}

/* ===== Header glow ===== */

h2{
    font-weight:700;
}

</style>

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #e08414;">
  <div class="container-fluid">

      <!-- ปุ่มเปิด Sidebar -->
    <button class="btn btn-outline-light me-2"
            data-bs-toggle="offcanvas"
            data-bs-target="#sidebarMenu">
      ☰
    </button><a class="navbar-brand" href="index.php">คลังยา รพ.สต.</a>

    <!-- ปุ่มมือถือ -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">

      <!-- เมนูฝั่งซ้าย -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
		 <li class="nav-item">
		  <a class="nav-link" href="stock/main_warehouse.php">🏬 คลังใหญ่</a>
		</li>
		<li class="nav-item">
		  <a class="nav-link" href="stock/sub_warehouse.php">🏪 คลังย่อย</a>
		</li>
		<li class="nav-item">
		  <a class="nav-link" href="stock/daily_usage.php">💊 ใช้ยารายวัน</a>
		</li>
		<li class="nav-item">
		  <a class="nav-link" href="report/reports.php">📊 รายงาน</a>
		</li>

		<li class="nav-item">
		  <a class="nav-link" href="non_drug/main_non_drug.php">
			🧴 คลังเวชภัณฑ์มิใช่ยา
		  </a>

        <!-- เฉพาะ admin -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<li class="nav-item">
	<a class="nav-link btn btn-info btn-sm text-dark px-3"
	   href="users/sitting_admin.php">
		👤 จัดการระบบ
	</a>
	</li>
<?php endif; ?>

      </ul>

      <!-- เมนูฝั่งขวา -->
		<div class="d-flex align-items-center gap-2">

		<?php if (isset($_SESSION['user_id'])): ?>

			<span class="text-white me-1">
				👤 <?= htmlspecialchars($_SESSION['fullname']) ?>
			</span>

			<?php if ($_SESSION['role'] === 'admin'): ?>
				<span class="badge bg-warning text-dark me-2">Admin</span>
			<?php endif; ?>

			<a href="logout.php" class="btn btn-sm btn-light ms-2">
				Logout
			</a>

		<?php else: ?>

			<a href="login.php" class="btn btn-sm btn-outline-light">
				Login
			</a>

		<?php endif; ?>

		</div>

    </div>
  </div>
</nav>
	
		<!-- Sidebar (Offcanvas เมนูฝั่งซ้าย) -->
		<div class="offcanvas offcanvas-start text-bg-light"
			 tabindex="-1"
			 id="sidebarMenu">

		  <div class="offcanvas-header bg-primary text-white">
			<h5 class="offcanvas-title">📦 เมนูระบบ</h5>
			<button type="button"
					class="btn-close btn-close-white"
					data-bs-dismiss="offcanvas"></button>
		  </div>

		  <div class="offcanvas-body p-0">

			<div class="list-group list-group-flush">

			  <a href="dashboard.php" class="list-group-item list-group-item-action">
				🏠 Dashboard
			  </a>

			  <a href="stock/main_warehouse.php" class="list-group-item list-group-item-action">
				🏬 คลังใหญ่
			  </a>

			  <a href="stock/sub_warehouse.php" class="list-group-item list-group-item-action">
				📦 คลังย่อย
			  </a>

			  <a href="stock/daily_usage.php" class="list-group-item list-group-item-action">
				💊 ใช้ยารายวัน
			  </a>

			  <a href="report/reports.php" class="list-group-item list-group-item-action">
				📊 รายงาน
			  </a>
			  
			  <a href="non_drug/main_non_drug.php" class="list-group-item list-group-item-action">
				🧴 คลังเวชภัณฑ์มิใช่ยา
			  </a>
			  
			</div>
		  </div>
		</div>

		<!-- รายการทั้งหมด -->
		<div class="container-fluid px-4 py-4">
			<h2>📊 Dashboard ระบบคลังยา รพ.สต.</h2>

				<h5 class="text-primary fw-bold">
				  หน่วยงาน: <?= htmlspecialchars($_SESSION['unit_name'] ?? '-') ?>
				  <small class="text-muted">
					รหัส: <?= htmlspecialchars($_SESSION['unit_code'] ?? '-') ?>
				  </small>
				</h5>

<div class="row mt-4">

    <!-- คลังใหญ่ -->
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-primary shadow-sm">
            <div class="card-body">
                <h5 class="card-title">คลังใหญ่</h5>
                <p class="card-text display-6"><?= $count_main ?></p>
            </div>
        </div>
    </div>

    <!-- คลังย่อย -->
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-success shadow-sm">
            <div class="card-body">
                <h5 class="card-title">คลังย่อย</h5>
                <p class="card-text display-6"><?= $count_sub ?></p>
            </div>
        </div>
    </div>

    <!-- การใช้ยารายวัน -->
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-warning shadow-sm">
            <div class="card-body">
                <h5 class="card-title">การใช้ยารายวัน</h5>
                <p class="card-text display-6"><?= $count_daily ?></p>
            </div>
        </div>
    </div>

</div>



			<p>สรุปรายการล่าสุด</p>

			<!-- คลังใหญ่ -->
			<div class="card shadow-sm mb-4">
				<div class="card-header bg-primary text-white">
					🏬 คลังใหญ่ (หน้า <?= $main_page ?> / <?= $main_total_pages ?>)
				</div>

				<div class="card-body table-responsive">
					<table class="table table-striped table-hover align-middle">
						<thead class="table-dark">
							<tr>
								<th>ลำดับ</th>
								<th>ชื่อยา</th>
								<th class="text-end">คงเหลือ</th>
							</tr>
						</thead>
						<tbody>
							<?php $no = $main_offset + 1; ?>
							<?php while ($row = $main_result->fetch_assoc()): ?>
							<tr>
								<td><?= $no++ ?></td>
								<td><?= htmlspecialchars($row['drug_name']) ?></td>
								<td class="text-end"><?= number_format($row['remaining']) ?></td>
							</tr>
							<?php endwhile; ?>
						</tbody>
					</table>

					<?php if ($main_total_pages > 1): ?>
					<nav class="mt-3">
						<ul class="pagination justify-content-center">
							<li class="page-item <?= ($main_page <= 1) ? 'disabled' : '' ?>">
								<a class="page-link" href="?page_main=<?= $main_page - 1 ?>">« ก่อนหน้า</a>
							</li>

							<?php for ($i = 1; $i <= $main_total_pages; $i++): ?>
								<li class="page-item <?= ($i == $main_page) ? 'active' : '' ?>">
									<a class="page-link" href="?page_main=<?= $i ?>"><?= $i ?></a>
								</li>
							<?php endfor; ?>

							<li class="page-item <?= ($main_page >= $main_total_pages) ? 'disabled' : '' ?>">
								<a class="page-link" href="?page_main=<?= $main_page + 1 ?>">ถัดไป »</a>
							</li>
						</ul>
					</nav>
					<?php endif; ?>
				</div>
			</div>



			<!-- คลังย่อย -->
			<div class="card shadow-sm mb-4">
				<div class="card-header bg-success text-white">
					📦 คลังย่อย (หน้า <?= $sub_page ?> / <?= $sub_total_pages ?>)
				</div>

				<div class="card-body table-responsive">
					<table class="table table-striped table-hover align-middle">
						<thead class="table-dark">
							<tr>
								<th>ลำดับ</th>
								<th>คลังย่อย</th>
								<th>ชื่อยา</th>
								<th class="text-end">คงเหลือรวม</th>
							</tr>
						</thead>
						<tbody>
							<?php $no = $sub_offset + 1; ?>
							<?php while ($row = $sub_result->fetch_assoc()): ?>
							<tr>
								<td><?= $no++ ?></td>
								<td><?= htmlspecialchars($row['sub_name']) ?></td>
								<td><?= htmlspecialchars($row['drug_name']) ?></td>
								<td class="text-end fw-bold">
									<?= number_format($row['total_remaining']) ?>
								</td>
							</tr>
							<?php endwhile; ?>
						</tbody>
					</table>

					<?php if ($sub_total_pages > 1): ?>
					<nav class="mt-3">
						<ul class="pagination justify-content-center">
							<li class="page-item <?= ($sub_page <= 1) ? 'disabled' : '' ?>">
								<a class="page-link" href="?page_sub=<?= $sub_page - 1 ?>">« ก่อนหน้า</a>
							</li>

							<?php for ($i = 1; $i <= $sub_total_pages; $i++): ?>
							<li class="page-item <?= ($i == $sub_page) ? 'active' : '' ?>">
								<a class="page-link" href="?page_sub=<?= $i ?>"><?= $i ?></a>
							</li>
							<?php endfor; ?>

							<li class="page-item <?= ($sub_page >= $sub_total_pages) ? 'disabled' : '' ?>">
								<a class="page-link" href="?page_sub=<?= $sub_page + 1 ?>">ถัดไป »</a>
							</li>
						</ul>
					</nav>
					<?php endif; ?>
				</div>
			</div>



			<!-- การใช้ยารายวัน -->
			<div class="card shadow-sm mb-4">
				<div class="card-header bg-warning text-dark">💊 การใช้ยารายวัน (ล่าสุด 8 รายการ)</div>
				<div class="card-body table-responsive">
					<table class="table table-striped table-hover align-middle">
						<thead class="table-dark">
							<tr>
								<th>ลำดับ</th>
								<th>วันที่ใช้</th>
								<th>คลังย่อย</th>
								<th>ชื่อยา</th>
								<th class="text-end">จำนวน</th>
							</tr>
						</thead>
						<tbody>
							<?php $no = 1; while($row = $daily_result->fetch_assoc()): ?>
							<tr>
								<td><?= $no++ ?></td>
								<td><?= $row['usage_date'] ?></td>
								<td><?= htmlspecialchars($row['sub_name']) ?></td>
								<td><?= htmlspecialchars($row['drug_name']) ?></td>
								<td class="text-end"><?= number_format($row['quantity_used']) ?></td>
							</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
