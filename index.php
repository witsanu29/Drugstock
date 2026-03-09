<?php
// ===== dashboard code ปกติ =====

	//* ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);
	// error_reporting(E_ALL); *//

	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}


if (!isset($_SESSION['user_id'])) {
	// แสดงหน้า public แทน dashboard
	require 'home.php';
	exit;
}

	/* ===============================
	   ตรวจสอบสิทธิ์การเข้าใช้งาน
	================================ */

	// ต้อง login ก่อน
	if (!isset($_SESSION['user_id'])) {
		header("Location: login.php");
		exit;
	}

	// helper เช็คสิทธิ์
	function isAdmin() {
		return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
	}

	function isStaff() {
		return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin','staff']);
	}


	/* 	===============================
				ตรวจสอบว่าตั้งค่าแล้วหรือยัง
		================================ */
	if (!file_exists(__DIR__ . "/includes/config.php")) {
		header("Location: setup_index.php");
		exit;
	}
	require_once __DIR__ . "/includes/config.php";


	 // ทดสอบเชื่อมต่อ DB 
	$conn_test = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
	if (!$conn_test) {
		header("Location: setup_index.php");
		exit;
	}

	mysqli_close($conn_test);  


	/* ==================================================
	   ตัวแปรสถานะระบบ
	================================================== */
	$drugstock_created = false;
	$db_created        = false;
	$db_exists         = false;


	/* ==================================================
	   สรุปข้อมูล
	================================================== */
	$main_total_items = $main_total_remaining = 0;
	$sub_total_items  = $sub_total_remaining  = 0;
	$total_used_today = $total_used_items     = 0;

	$low_stock_count  = 0;
	$low_stock_list   = [];
	$chart_labels     = [];
	$chart_data       = [];

	$expiry_date = 30;
	$lowExpireList = [];


	/* ==================================================
	   เชื่อมต่อ MySQL
	================================================== */
	if (!$conn) {
		die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ");
	}


	/* ==================================================
	   ตรวจสอบฐานข้อมูล
	================================================== */
	$res = mysqli_query($conn, "SHOW DATABASES LIKE '$db_name'");
	if (!$res || mysqli_num_rows($res) === 0) {
		mysqli_close($conn);
		return;
	}

	$drugstock_created = $db_created = $db_exists = true;
	mysqli_select_db($conn, $db_name);
	mysqli_set_charset($conn, "utf8mb4");


	/* ==================================================
	   ข้อมูลหน่วยงานที่ login
	================================================== */
		$unit_id = (int)($_SESSION['unit_id'] ?? 0);

		$stmt = $conn->prepare("
			SELECT unit_name, unit_code
			FROM units
			WHERE unit_id = ?
		");
		$stmt->bind_param("i", $unit_id);
		$stmt->execute();
		$r = $stmt->get_result()->fetch_assoc();

		$unit_name = $r['unit_name'] ?? '-';
		$unit_code = $r['unit_code'] ?? '-';
		$stmt->close();

	/* ==================================================
	   การดึงข้อมูล daily_usage ตามสิทธิ์
	================================================== */

	if (isAdmin()) {

		// admin เห็นทั้งหมด
		$stmt = $conn->prepare("
			SELECT *
			FROM daily_usage
			ORDER BY usage_date DESC
		");

	} else {

		// staff เห็นเฉพาะหน่วยตัวเอง
		if (empty($unit_id)) {
			die('ไม่พบหน่วยงานผู้ใช้งาน');
		}

		$stmt = $conn->prepare("
			SELECT *
			FROM daily_usage
			WHERE unit_id = ?
			ORDER BY usage_date DESC
		");
		$stmt->bind_param("i", $unit_id);
	}

	$stmt->execute();
	$result = $stmt->get_result();


	/* ==================================================
	   คลังใหญ่
	================================================== */
	if (isAdmin() || isStaff()) {

		$sql = "
			SELECT COUNT(id) items,
				   COALESCE(SUM(remaining),0) remaining
			FROM main_warehouse
		";

		if (!isAdmin()) {
			$sql .= " WHERE unit_id = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i", $unit_id);
			$stmt->execute();
			$res = $stmt->get_result();
		} else {
			$res = $conn->query($sql);
		}

		if ($row = $res->fetch_assoc()) {
			$main_total_items     = (int)$row['items'];
			$main_total_remaining = (int)$row['remaining'];
		}
	}


	/* ==================================================
	   คลังย่อย
	================================================== */
	if (isAdmin() || isStaff()) {

		$sql = "
			SELECT COUNT(id) items,
				   COALESCE(SUM(remaining),0) remaining
			FROM sub_warehouse
		";

		if (!isAdmin()) {
			$sql .= " WHERE unit_id = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i", $unit_id);
			$stmt->execute();
			$res = $stmt->get_result();
			$stmt->close();
		} else {
			$res = $conn->query($sql);
		}

		if ($row = $res->fetch_assoc()) {
			$sub_total_items     = (int)$row['items'];
			$sub_total_remaining = (int)$row['remaining'];
		}
	}


	/* ==================================================
	   การใช้ยาวันนี้ (ปลอดภัย + พร้อมใช้งาน)
	================================================== */

	$today  = date('Y-m-d');
	$unit_id = (int)($_SESSION['unit_id'] ?? 0);

	$sql = "
		SELECT 
			COALESCE(SUM(quantity_used),0) AS total_used,
			COUNT(id) AS total_items
		FROM daily_usage
		WHERE usage_date = ?
	";

	if (!isAdmin()) {
		$sql .= " AND unit_id = ?";
	}

	$stmt = $conn->prepare($sql);
	if (!$stmt) {
		die("SQL ERROR : " . $conn->error);
	}

	if (isAdmin()) {
		$stmt->bind_param("s", $today);
	} else {
		$stmt->bind_param("si", $today, $unit_id);
	}

	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();

	$total_used_today = (int)$row['total_used'];   // จำนวนยาที่ใช้
	$total_used_items = (int)$row['total_items']; // จำนวนรายการ

	$stmt->close();


	/* ==================================================
		   การใช้เวชภัณฑ์มิใช่ยาวันนี้
		================================================== */
	$perPage = 10;
	$offset  = 0;

	$role = $_SESSION['role'] ?? '';
	
	if ($role === 'admin') {

		// ✅ admin เห็นทั้งหมด
		$sql = "
			SELECT 
				u.id,
				u.used_qty,
				u.used_date,
				w.item_name,
				w.unit,
				un.unit_name
			FROM non_drug_usage u
			JOIN non_drug_warehouse w ON u.non_drug_id = w.id
			LEFT JOIN units un ON w.unit_id = un.unit_id
			ORDER BY u.id DESC
			LIMIT ? OFFSET ?
		";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ii", $perPage, $offset);

	} else {

		// ✅ staff / demo เฉพาะหน่วยตัวเอง
		$sql = "
			SELECT 
				u.id,
				u.used_qty,
				u.used_date,
				w.item_name,
				w.unit,
				un.unit_name
			FROM non_drug_usage u
			JOIN non_drug_warehouse w ON u.non_drug_id = w.id
			LEFT JOIN units un ON w.unit_id = un.unit_id
			WHERE w.unit_id = ?
			ORDER BY u.id DESC
			LIMIT ? OFFSET ?
		";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("iii", $unit_id, $perPage, $offset);
	}

	$stmt->execute();
	$res = $stmt->get_result();


	/* ==================================================
	   การใช้เวชภัณฑ์มิใช่ยาวันนี้
	================================================== */

	$today   = date('Y-m-d');
	$unit_id = (int)($_SESSION['unit_id'] ?? 0);

	$sql = "
		SELECT 
			COALESCE(SUM(u.used_qty), 0) AS total_used,
			COUNT(u.id) AS total_items
		FROM non_drug_usage u
		JOIN non_drug_warehouse w ON u.non_drug_id = w.id
		WHERE DATE(u.used_date) = ?
	";

	if (!isAdmin()) {
		$sql .= " AND w.unit_id = ?";
	}

	$stmt = $conn->prepare($sql);
	if (!$stmt) {
		die("SQL ERROR : " . $conn->error);
	}

	if (isAdmin()) {
		$stmt->bind_param("s", $today);
	} else {
		$stmt->bind_param("si", $today, $unit_id);
	}

	$stmt->execute();
	$row = $stmt->get_result()->fetch_assoc();

	$non_drug_used_today  = (int)$row['total_used'];
	$non_drug_used_items = (int)$row['total_items'];

	$stmt->close();


	/* ---------- ดึงชื่อหน่วยบริการ ---------- */

	$unit_code = '';

	if (isStaff() && $unit_id > 0) {
		$stmt = $conn->prepare("
			SELECT unit_code
			FROM units
			WHERE unit_id = ?
		");
		if ($stmt) {
			$stmt->bind_param("i", $unit_id);
			$stmt->execute();
			$unit_code = $stmt->get_result()->fetch_assoc()['unit_code'] ?? '';
			$stmt->close();
		}
	}


	/* ==================================================
	   ยาใกล้หมดสต๊อก (จากคลังย่อย)
	================================================== */

	$drug_low_stock_list = [];
	$unit_id = (int)($_SESSION['unit_id'] ?? 0);

	/* ===== QUERY ===== */
	$sql = "
		SELECT 
			COALESCE(m.drug_name,'ไม่ทราบชื่อยา') AS drug_name,
			SUM(s.remaining) AS total_remaining
		FROM sub_warehouse s
		LEFT JOIN main_warehouse m ON s.drug_id = m.id
	";

	if (!isAdmin()) {
		$sql .= " WHERE s.unit_id = ?";
	}

	$sql .= "
		GROUP BY drug_name
		HAVING total_remaining < 25
		ORDER BY total_remaining ASC
	";

	$stmt = $conn->prepare($sql);
	if (!$stmt) {
		die('SQL ERROR : ' . $conn->error);
	}

	if (!isAdmin()) {
		$stmt->bind_param("i", $unit_id);
	}

	$stmt->execute();
	$result = $stmt->get_result();

	/* ===== BUILD ARRAY ===== */
	while ($row = $result->fetch_assoc()) {

		if ($row['total_remaining'] == 0) {
			$row['status'] = 'หมด';
			$row['badge']  = 'danger';
		} elseif ($row['total_remaining'] <= 10) {
			$row['status'] = 'ใกล้หมด';
			$row['badge']  = 'warning';
		} else {
			$row['status'] = 'เฝ้าระวัง';
			$row['badge']  = 'info';
		}

		$drug_low_stock_list[] = $row;
	}

	$stmt->close();

	/* ===== PAGINATION : DRUG LOW STOCK ===== */
	$limit_drug = 10;

	$page_drug = (isset($_GET['page_drug']) && $_GET['page_drug'] > 0)
		? (int)$_GET['page_drug']
		: 1;

	$total_items_drug = count($drug_low_stock_list);
	$total_pages_drug = ceil($total_items_drug / $limit_drug);

	// กัน page เกิน
	$page_drug = max(1, min($page_drug, $total_pages_drug));

	$offset_drug = ($page_drug - 1) * $limit_drug;

	// ตัดข้อมูลตามหน้า
	$drug_low_stock_page = array_slice(
		$drug_low_stock_list,
		$offset_drug,
		$limit_drug
	);

	$drug_low_stock_count = $total_items_drug;


	/* ==================================================
	   เวชภัณฑ์มิใช่ยาใกล้หมดสต๊อก
	================================================== */

	$non_drug_low_stock_list = [];
	$unit_id = (int)($_SESSION['unit_id'] ?? 0);

	$sql = "
		SELECT *
		FROM non_drug_warehouse
		WHERE remaining <= 20
	";

	if (!isAdmin()) {
		$sql .= " AND unit_id = ?";
	}

	$sql .= " ORDER BY remaining ASC";

	$stmt = $conn->prepare($sql);

	if (!$stmt) {
		die('SQL ERROR : ' . $conn->error);
	}

	if (!isAdmin()) {
		$stmt->bind_param("i", $unit_id);
	}

	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {

		if ($row['remaining'] == 0) {
			$row['status'] = 'หมด';
			$row['badge']  = 'danger';
		} elseif ($row['remaining'] <= 10) {
			$row['status'] = 'ใกล้หมด';
			$row['badge']  = 'warning';
		} else {
			$row['status'] = 'เฝ้าระวัง';
			$row['badge']  = 'info';
		}

		$non_drug_low_stock_list[] = $row;
	}

	$non_drug_low_stock_count = count($non_drug_low_stock_list);

	$stmt->close();


	/* ===== PAGINATION : NON DRUG ===== */

	$limit_non = 10;

	$page_non = (isset($_GET['page_non_drug']) && $_GET['page_non_drug'] > 0)
		? (int)$_GET['page_non_drug']
		: 1;

	$total_items_non = count($non_drug_low_stock_list);
	$total_pages_non = ceil($total_items_non / $limit_non);

	// กัน page เกิน
	$page_non = max(1, min($page_non, $total_pages_non));

	$offset_non = ($page_non - 1) * $limit_non;

	// ตัดข้อมูล
	$non_drug_low_stock_page = array_slice(
		$non_drug_low_stock_list,
		$offset_non,
		$limit_non
	);

	$non_drug_low_stock_count = $total_items_non;


	/* ==================================================
	   กราฟใช้ยา 7 วัน
	================================================== */

	$chart_labels = [];
	$chart_data   = [];
	$unit_id = (int)($_SESSION['unit_id'] ?? 0);

	$sql = "
		SELECT 
			usage_date,
			SUM(quantity_used) AS total_used
		FROM daily_usage
		WHERE usage_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
	";

	if (!isAdmin()) {
		$sql .= " AND unit_id = ?";
	}

	$sql .= "
		GROUP BY usage_date
		ORDER BY usage_date ASC
	";

	$stmt = $conn->prepare($sql);

	if (!$stmt) {
		die('SQL ERROR : ' . $conn->error);
	}

	if (!isAdmin()) {
		$stmt->bind_param("i", $unit_id);
	}

	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		$chart_labels[] = $row['usage_date'];          // เช่น 2026-01-28
		$chart_data[]   = (int)$row['total_used'];     // จำนวนที่ใช้
	}

	$stmt->close();


/* ==================================================
   กราฟยาใกล้หมดอายุ (วันนี้ → อีก 30 วัน)
================================================== */
if (isAdmin() || isStaff()) {

	date_default_timezone_set('Asia/Bangkok');

	$drug_names = [];
	$days_left  = [];
	$colors     = [];

	$unit_id = (int)($_SESSION['unit_id'] ?? 0);

	$sql = "
		SELECT 
			drug_name,
			DATEDIFF(expiry_date, CURDATE()) AS days_left
		FROM main_warehouse
		WHERE expiry_date IS NOT NULL
		  AND DATEDIFF(expiry_date, CURDATE()) BETWEEN 0 AND 30
	";

	if (!isAdmin()) {
		$sql .= " AND unit_id = ?";
	}

	$sql .= " ORDER BY days_left ASC";

	$stmt = $conn->prepare($sql);
	if (!$stmt) {
		die("SQL ERROR : " . $conn->error);
	}

	if (!isAdmin()) {
		$stmt->bind_param("i", $unit_id);
	}

	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {

		$days = (int)$row['days_left'];

		if ($days <= 7) {
			$color = 'rgba(220,53,69,0.85)';   // 🔴
		} elseif ($days <= 15) {
			$color = 'rgba(255,159,64,0.85)';  // 🟠
		} else {
			$color = 'rgba(255,193,7,0.85)';   // 🟡
		}

		$drug_names[] = $row['drug_name'];
		$days_left[]  = $days;
		$colors[]     = $color;
	}

	$stmt->close();

	// ⭐ สำคัญมาก
	$exp_count = count($drug_names);
}


	/* ==================================================
	   ยาใกล้หมดอายุ (วันนี้ → อีก 30 วัน) แยกรายการ
	================================================== */
	$rows = [];

	$sql = "
		SELECT 
			drug_name,
			expiry_date,
			DATEDIFF(expiry_date, CURDATE()) AS days_left
		FROM main_warehouse
		WHERE expiry_date IS NOT NULL
		  AND DATEDIFF(expiry_date, CURDATE()) BETWEEN -365 AND 30
	";

	if (!isAdmin()) {
		$sql .= " AND unit_id = ?";
	}

	$sql .= " ORDER BY days_left ASC";

	$stmt = $conn->prepare($sql);
	if (!$stmt) {
		die('SQL ERROR: ' . $conn->error);
	}

	if (!isAdmin()) {
		$stmt->bind_param("i", $_SESSION['unit_id']);
	}

	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		$rows[] = $row;
	}

	$stmt->close();

	$exp_count = count($rows);



	/* ===== PAGINATION (ยาใกล้หมดอายุ) ===== */
	$limit_exp = 10;

	$page_exp = (isset($_GET['page_exp']) && $_GET['page_exp'] > 0)
		? (int)$_GET['page_exp']
		: 1;

	$exp_count  = count($rows);
	$total_pages_exp = ceil($exp_count / $limit_exp);

	// กัน page เกิน
	$page_exp = max(1, min($page_exp, $total_pages_exp));

	$offset_exp = ($page_exp - 1) * $limit_exp;

	// ตัดข้อมูลตามหน้า
	$rows_page = array_slice($rows, $offset_exp, $limit_exp);



	/* ==================================================
	   เวชภัณฑ์มิใช่ยาใกล้หมดอายุ
	================================================== */

	$expiry_list = [];
	$unit_id = (int)($_SESSION['unit_id'] ?? 0);

	$sql = "
		SELECT *,
			   DATEDIFF(expiry_date, CURDATE()) AS days_left
		FROM non_drug_warehouse
		WHERE expiry_date IS NOT NULL
		  AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
	";

	if (!isAdmin()) {
		$sql .= " AND unit_id = ?";
	}

	$sql .= " ORDER BY expiry_date ASC";

	$stmt = $conn->prepare($sql);

	if (!$stmt) {
		die('SQL ERROR : ' . $conn->error);
	}

	if (!isAdmin()) {
		$stmt->bind_param("i", $unit_id);
	}

	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		$expiry_list[] = $row;
	}

	$expiry_count = count($expiry_list);

	$stmt->close();


	/* ==================================================
	   Pagination : เวชภัณฑ์มิใช่ยาใกล้หมดอายุ
	================================================== */

	$limit = 10;

	// ใช้ชื่อเฉพาะ
	$page_non_drug_exp = (isset($_GET['page_non_drug_exp']) && $_GET['page_non_drug_exp'] > 0)
		? (int)$_GET['page_non_drug_exp']
		: 1;

	$total_items = count($expiry_list);
	$total_pages = ($total_items > 0) ? ceil($total_items / $limit) : 1;

	// กัน page เกิน
	$page_non_drug_exp = max(1, min($page_non_drug_exp, $total_pages));

	$offset = ($page_non_drug_exp - 1) * $limit;

	// ตัด array ตามหน้า
	$expiry_page = array_slice($expiry_list, $offset, $limit);


	/* ==================================================
	   ปิดการเชื่อมต่อ
	================================================== */
	mysqli_close($conn);
?>


<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<title>ระบบคลังยา รพ.สต.</title>
<?php  require 'head.php';  ?>

</head>

<body>
	
	<!-- เมนู NAV -->
		<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #e08414;">
		  <div class="container-fluid">

			<a class="navbar-brand" href="index.php">คลังยา รพ.สต.</a>

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
				</li>
				<li class="nav-item">
				  <a class="nav-link" href="non_drug/usage_non_drug.php">
					📋 ใช้เวชภัณฑ์มิใช่ยา
				  </a>
				</li>
				<li class="nav-item">
				  <a class="nav-link" href="non_drug/report_non_drug_stock.php">
					📊 รายงานคงเหลือเวชภัณฑ์
				  </a>
				</li>
				<li class="nav-item">
				  <a class="nav-link" href="non_drug/report_non_drug_expire.php">
					⏰ เวชภัณฑ์ใกล้หมดอายุ
				  </a>
				</li>

				<!-- เฉพาะ admin -->
		  
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


			<!-- ===== Header ===== -->
		<div class="container mt-4">
		
			<div class="row align-items-center mb-4"
				 style="font-family:'Sarabun', sans-serif;">

				<!-- โลโก้ -->
				<div class="col-md-2 text-center mb-3 mb-md-0">
					<img src="image/logo.png"
						 alt="โลโก้ รพ.สต."
						 class="img-fluid rounded-circle shadow"
						 style="max-height:150px;">
				</div>

				<!-- ชื่อระบบ -->
				<div class="col-md-10">
					<h2 class="fw-bold mb-2"
						style="font-size:36px; color:#1f3c88; letter-spacing:1px;">
						ระบบคลังยา รพ.สต.
					</h2>

					<p class="mb-0"
					   style="font-size:19px; color:#333; line-height:1.6;">
						บริหารจัดการคลังยา คลังย่อย การใช้ยา และคลังเวชภัณฑ์มิใช่ยา อย่างมีประสิทธิภาพ
					</p>
				</div>
			</div>

			<!-- เว้นระยะห่างก่อนแสดงหน่วยงาน -->
			<div class="row mb-4">
				<div class="col-12">
					<div class="alert alert-primary py-2">
						🏥 หน่วยงาน :
						<strong><?= htmlspecialchars($unit_name) ?></strong>
						(<?= htmlspecialchars($unit_code) ?>)
					</div>
				</div>
			</div>


			<!-- ===== Summary Cards ===== -->
				<div class="row g-4 mb-4 justify-content-center">

					<!-- 🔐 คลังใหญ่ : admin เท่านั้น -->
					<?php if (isAdmin() || isStaff()): ?>
					<div class="col-12 col-sm-6 col-md-4 col-lg-3">
						<div class="card shadow-sm border-0 h-100">
							<div class="card-body text-center">
								<h6 class="text-muted">รายการยาคลังใหญ่</h6>

								<h4 class="text-primary mb-1">
									<?= number_format($main_total_remaining) ?>
								</h4>
								<small class="text-muted">จำนวนยาทั้งหมด</small>

								<hr class="my-2">

								<h5 class="text-success mb-0">
									<?= number_format($main_total_items) ?>
								</h5>
								<small class="text-muted">รายการ</small>
							</div>
						</div>
					</div>
					<?php endif; ?>


					<!-- 👩‍⚕️ คลังย่อย : admin + staff -->
					<?php if (isAdmin() || isStaff()): ?>
					<div class="col-12 col-sm-6 col-md-4 col-lg-3">
						<div class="card shadow-sm border-0 h-100">
							<div class="card-body text-center">
								<h6 class="text-muted">รายการยาคลังย่อย</h6>

								<h4 class="text-primary mb-1">
									<?= number_format($sub_total_remaining) ?>
								</h4>
								<small class="text-muted">จำนวนยาทั้งหมด</small>

								<hr class="my-2">

								<h5 class="text-success mb-0">
									<?= number_format($sub_total_items) ?>
								</h5>
								<small class="text-muted">รายการ</small>
							</div>
						</div>
					</div>
					<?php endif; ?>


					<!-- 💊 การใช้ยาวันนี้ : admin + staff -->
					<?php if (isAdmin() || isStaff()): ?>
					<div class="col-12 col-sm-6 col-md-4 col-lg-3">
						<div class="card shadow-sm border-0 h-100">
							<div class="card-body text-center">
								<h6 class="text-muted">รายการใช้ยาวันนี้</h6>

								<h4 class="text-primary mb-1">
									<?= number_format($total_used_today) ?>
								</h4>
								<small class="text-muted">จำนวนยาที่ใช้</small>

								<hr class="my-2">

								<h5 class="text-success mb-0">
									<?= number_format($total_used_items) ?>
								</h5>
								<small class="text-muted">จำนวนรายการ</small>
							</div>
						</div>
					</div>
					<?php endif; ?>


					<!-- 🧰 เวชภัณฑ์มิใช่ยา : admin + staff -->
				<?php if (isAdmin() || isStaff()): ?>
				<div class="col-12 col-sm-6 col-md-4 col-lg-3">
					<div class="card shadow-sm border-0 h-100">
						<div class="card-body text-center">

							<h6 class="text-muted mb-1">
								ใช้เวชภัณฑ์มิใช่ยาวันนี้
							</h6>

							<?php if (isAdmin()): ?>
								<small class="text-danger d-block mb-2">
									ทุกหน่วยบริการ
								</small>
							<?php else: ?>
								<small class="text-secondary d-block mb-2">
									<?= htmlspecialchars($unit_code) ?>
								</small>
							<?php endif; ?>

							<h4 class="text-primary mb-1">
								<?= number_format($non_drug_used_today) ?>
							</h4>
							<small class="text-muted">จำนวนที่ใช้ทั้งหมด</small>

							<hr class="my-2">

							<h5 class="text-success mb-0">
								<?= number_format($non_drug_used_items) ?>
							</h5>
							<small class="text-muted">รายการ</small>

						</div>
					</div>
				</div>
				<?php endif; ?>

				</div>


				<!-- ===== เวชภัณฑ์ยา ใกล้หมดสต๊อก ===== -->
				<div class="card shadow-sm border-0 mt-3">
					<div class="card-body">

						<h6 class="fw-bold mb-3 text-danger" style="text-shadow: 0 1px 1px rgba(0,0,0,0.15);">
							<i class="bi bi-exclamation-triangle-fill me-1"></i>
							รายการเวชภัณฑ์ยา ใกล้หมดสต๊อก (คลังย่อย)
						</h6>

						<?php if ($drug_low_stock_count > 0): ?>

							<ul class="list-group list-group-flush">

								<?php $i = $offset_drug + 1; ?>
								<?php foreach ($drug_low_stock_page as $item): ?>
									<li class="list-group-item d-flex justify-content-between align-items-center">
										<div>
											<strong><?= $i ?>. <?= htmlspecialchars($item['drug_name']) ?></strong><br>
											<small class="text-muted">
												คงเหลือ <?= number_format($item['total_remaining']) ?>
											</small>
										</div>

										<span class="badge bg-<?= $item['badge'] ?> rounded-pill">
											<?= $item['status'] ?>
										</span>
									</li>
								<?php $i++; endforeach; ?>

							</ul>

							<!-- ===== Pagination ===== -->
							<?php if ($total_pages_drug > 1): ?>
							<nav class="mt-3">
							<ul class="pagination pagination-sm justify-content-center mb-0">

								<li class="page-item <?= ($page_drug <= 1) ? 'disabled' : '' ?>">
									<a class="page-link" href="?page_drug=<?= $page_drug - 1 ?>">« ก่อนหน้า</a>
								</li>

								<li class="page-item active">
									<span class="page-link">
										หน้า <?= $page_drug ?> / <?= $total_pages_drug ?>
									</span>
								</li>

								<li class="page-item <?= ($page_drug >= $total_pages_drug) ? 'disabled' : '' ?>">
									<a class="page-link" href="?page_drug=<?= $page_drug + 1 ?>">ถัดไป »</a>
								</li>

							</ul>
							</nav>
							<?php endif; ?>

							<?php else: ?>
							<div class="alert alert-success mb-0 text-center">
								✅ ไม่มีรายการยาใกล้หมด
							</div>
							<?php endif; ?>

					</div>
				</div>

							
				<!-- ===== เวชภัณฑ์มิใช่ยา ใกล้หมดสต๊อก ===== -->
				<div class="card shadow-sm border-0 mt-3">
					<div class="card-body">

						<h6 class="fw-bold mb-3 text-danger" style="text-shadow: 0 1px 1px rgba(0,0,0,0.15);">
							<i class="bi bi-exclamation-triangle-fill me-1"></i>
							รายการเวชภัณฑ์มิใช่ยา ใกล้หมดสต๊อก
						</h6>

						<?php if ($non_drug_low_stock_count > 0 && count($non_drug_low_stock_page) > 0): ?>

								<ul class="list-group list-group-flush">
									<?php $i = $offset_non + 1; ?>
									<?php foreach ($non_drug_low_stock_page as $item): ?>
										<li class="list-group-item d-flex justify-content-between align-items-center">
											<div>
												<strong>
													<?= $i ?>. <?= htmlspecialchars($item['item_name']) ?>
												</strong><br>
												<small class="text-muted">
													คงเหลือ <?= $item['remaining'] ?> <?= htmlspecialchars($item['unit']) ?>
												</small>
											</div>

											<span class="badge bg-<?= $item['badge'] ?> rounded-pill">
												<?= $item['status'] ?>
											</span>
										</li>
									<?php $i++; endforeach; ?>
								</ul>


							<!-- ===== Pagination ===== -->
								<?php if ($total_pages_non > 1): ?>
								<nav class="mt-3">
									<ul class="pagination pagination-sm justify-content-center mb-0">

										<li class="page-item <?= ($page_non <= 1) ? 'disabled' : '' ?>">
											<a class="page-link" href="?page_non_drug=<?= $page_non - 1 ?>">
												« ก่อนหน้า
											</a>
										</li>

										<li class="page-item active">
											<span class="page-link">
												หน้า <?= $page_non ?> / <?= $total_pages_non ?>
											</span>
										</li>

										<li class="page-item <?= ($page_non >= $total_pages_non) ? 'disabled' : '' ?>">
											<a class="page-link" href="?page_non_drug=<?= $page_non + 1 ?>">
												ถัดไป »
											</a>
										</li>

									</ul>
								</nav>
								<?php endif; ?>


						<?php else: ?>

							<div class="alert alert-success mb-0 text-center">
								✅ ไม่มีเวชภัณฑ์มิใช่ยาที่ใกล้หมด
							</div>

						<?php endif; ?>

					</div>
				</div>

		
			<!-- ===== Charts กราฟ เส้น / แท่ง ===== -->
			<div class="row g-4">

				<!-- การใช้ยา 7 วันล่าสุด -->
				<div class="col-md-6">
					<div class="card shadow-sm border-0 h-100">
						<div class="card-header bg-white fw-bold">
							📊 การใช้ยา 7 วันล่าสุด
						</div>
						<div class="card-body">
							<canvas id="usageChart" height="120"></canvas>
						</div>
					</div>
				</div>

				<!-- ยาใกล้หมดอายุ -->
				<div class="col-md-6">
					<div class="card shadow-sm border-0 h-100">
						<div class="card-header bg-white fw-bold">
							💊 ยาใกล้หมดอายุ (≤ 30 วัน)
						</div>
						<div class="card-body">
							<?php if ($exp_count > 0): ?>
								<canvas id="expiryChart" height="220"></canvas>
							<?php else: ?>
								<div class="alert alert-success text-center">
									✅ ไม่พบยาใกล้หมดอายุ
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				
			</div>


	
			<!-- ===== เวชภัณฑ์ยา ใกล้หมดอายุ ===== -->
			<div class="card shadow-sm border-0 mt-3">
				<div class="card-body">

					<h6 class="fw-bold mb-3 text-danger"
						style="text-shadow: 0 1px 1px rgba(0,0,0,0.15);">
						<i class="bi bi-exclamation-triangle-fill me-1"></i>
						เวชภัณฑ์ยา ใกล้หมดอายุ (ภายใน 30 วัน)
					</h6>

					<?php if ($exp_count > 0 && count($rows_page) > 0): ?>

							<ul class="list-group list-group-flush">

								<?php $i = $offset_exp + 1; ?>
								<?php foreach ($rows_page as $r): ?>

								<?php
								$days = (int)$r['days_left'];

								if ($days < 0) {
									$badge_class = 'bg-dark';
									$badge_text  = 'หมดอายุแล้ว';
									$day_text    = 'เกิน ' . abs($days) . ' วัน';
								} elseif ($days === 0) {
									$badge_class = 'bg-danger';
									$badge_text  = 'หมดอายุวันนี้';
									$day_text    = 'วันนี้';
								} elseif ($days <= 7) {
									$badge_class = 'bg-danger';
									$badge_text  = 'เร่งด่วน';
									$day_text    = $days . ' วัน';
								} elseif ($days <= 15) {
									$badge_class = 'bg-warning text-dark';
									$badge_text  = 'เฝ้าระวัง';
									$day_text    = $days . ' วัน';
								} else {
									$badge_class = 'bg-info';
									$badge_text  = 'ปกติ';
									$day_text    = $days . ' วัน';
								}

								$expiry_ts = strtotime($r['expiry_date']);
								$expiry_th = date('d/m/', $expiry_ts) . (date('Y', $expiry_ts) + 543);
								?>

								<li class="list-group-item d-flex justify-content-between align-items-center">
									<div>
										<strong><?= $i ?>. <?= htmlspecialchars($r['drug_name']) ?></strong><br>
										<small class="text-muted">
											หมดอายุ: <?= $expiry_th ?>
										</small>
									</div>

									<span class="badge <?= $badge_class ?> rounded-pill">
										<?= $badge_text ?> (<?= $day_text ?>)
									</span>
								</li>

								<?php $i++; ?>
							<?php endforeach; ?>

						</ul>

						<!-- ===== Pagination ===== -->
							<?php if ($total_pages_exp > 1): ?>
							<nav class="mt-3">
								<ul class="pagination pagination-sm justify-content-center mb-0">

									<li class="page-item <?= ($page_exp <= 1) ? 'disabled' : '' ?>">
										<a class="page-link" href="?page_exp=<?= $page_exp - 1 ?>">« ก่อนหน้า</a>
									</li>

									<li class="page-item active">
										<span class="page-link">
											หน้า <?= $page_exp ?> / <?= $total_pages_exp ?>
										</span>
									</li>

									<li class="page-item <?= ($page_exp >= $total_pages_exp) ? 'disabled' : '' ?>">
										<a class="page-link" href="?page_exp=<?= $page_exp + 1 ?>">ถัดไป »</a>
									</li>

								</ul>
							</nav>
							<?php endif; ?>

					<?php else: ?>

						<div class="alert alert-success mb-0 text-center">
							✅ ไม่มีเวชภัณฑ์ยาที่ใกล้หมดอายุ
						</div>

					<?php endif; ?>

				</div>
			</div>




			<!-- ===== เวชภัณฑ์มิใช่ยา ใกล้หมดอายุ===== -->
			<div class="card shadow-sm border-0 mt-3">
				<div class="card-body">

					<h6 class="fw-bold mb-3 text-danger" style="text-shadow: 0 1px 1px rgba(0,0,0,0.15);">
						<i class="bi bi-exclamation-triangle-fill me-1"></i>
						เวชภัณฑ์มิใช่ยา ใกล้หมดอายุ (ภายใน 30 วัน)
					</h6>

					<?php if ($expiry_count > 0 && count($expiry_page) > 0): ?>

						<ul class="list-group list-group-flush">
							<?php $i = $offset + 1; ?>
							<?php foreach ($expiry_page as $item): ?>

								<?php
								$days = (int)$item['days_left'];

								if ($days < 0) {
									$badge_class = 'bg-dark';
									$badge_text  = 'หมดอายุแล้ว';
									$day_text    = 'เกิน ' . abs($days) . ' วัน';
								} elseif ($days <= 7) {
									$badge_class = 'bg-danger';
									$badge_text  = 'ใกล้หมดอายุ';
									$day_text    = $days . ' วัน';
								} elseif ($days <= 15) {
									$badge_class = 'bg-warning text-dark';
									$badge_text  = 'เฝ้าระวัง';
									$day_text    = $days . ' วัน';
								} else {
									$badge_class = 'bg-info';
									$badge_text  = 'เตือนล่วงหน้า';
									$day_text    = $days . ' วัน';
								}
								?>

								<li class="list-group-item d-flex justify-content-between align-items-center">
									<div>
										<strong><?= $i ?>. <?= htmlspecialchars($item['item_name']) ?></strong><br>
										<small class="text-muted">
											หมดอายุ: <?= date('d/m/Y', strtotime($item['expiry_date'])) ?>
										</small>
									</div>

									<span class="badge <?= $badge_class ?> rounded-pill">
										<?= $badge_text ?> (<?= $day_text ?>)
									</span>
								</li>

							<?php $i++; endforeach; ?>
						</ul>

						<!-- ===== Pagination ===== -->
						<?php if ($total_pages > 1): ?>
						<nav class="mt-3">
							<ul class="pagination pagination-sm justify-content-center mb-0">

								<li class="page-item <?= ($page_non_drug_exp <= 1) ? 'disabled' : '' ?>">
									<a class="page-link"
									   href="?page_non_drug_exp=<?= $page_non_drug_exp - 1 ?>">
										« ก่อนหน้า
									</a>
								</li>

								<li class="page-item active">
									<span class="page-link">
										หน้า <?= $page_non_drug_exp ?> / <?= $total_pages ?>
									</span>
								</li>

								<li class="page-item <?= ($page_non_drug_exp >= $total_pages) ? 'disabled' : '' ?>">
									<a class="page-link"
									   href="?page_non_drug_exp=<?= $page_non_drug_exp + 1 ?>">
										ถัดไป »
									</a>
								</li>

							</ul>
						</nav>
						<?php endif; ?>
						
					<?php else: ?>

						<div class="alert alert-success mb-0 text-center">
							✅ ไม่มีเวชภัณฑ์มิใช่ยาที่ใกล้หมดอายุ
						</div>

					<?php endif; ?>

				</div>
			</div>



			<!-- ===== Illustration ===== -->
			<div class="row mt-5">
				<div class="col text-center">
					<img src="image/medicine.png" class="img-fluid" style="max-height:80px;">
					<p class="text-muted mt-3">
						ระบบช่วยให้การบริหารจัดการระบบคลังเวชภัณฑ์ยา / มิใช่ยา เป็นเรื่องง่าย รวดเร็ว และตรวจสอบได้
					</p>
				</div>
			</div>


			<!-- ===== สถานะการเข้าชม ===== -->
			<div class="d-flex justify-content-center mt-3">
				<div id="online" class="alert alert-success fw-bold mb-0"></div>
			</div>

			<script>
			setInterval(() => {
				fetch('online.php')
					.then(res => res.text())
					.then(data => {
						document.getElementById('online').innerHTML =
							'👥 ผู้ใช้งานออนไลน์: ' + data;
					});
			}, 2500);
			</script>


<!-- ===== สร้าง ตาราง 
	<div class="d-flex justify-content-center gap-3 flex-wrap">

		<div class="step-box">
			<?php if ($drugstock_created): ?>
				<button class="btn btn-danger w-100" disabled>
					✅ 1. ตารางข้อมูลระบบคลังยา ถูกสร้างแล้ว
				</button>
			<?php else: ?>
				<a href="setup_database.php"
				   class="btn btn-success w-100"
				   onclick="return confirm('ยืนยันการสร้างฐานข้อมูล drugstock ?');">
					1. 🗄️ สร้างตารางข้อมูลระบบคลังยา
				</a>
			<?php endif; ?>
		</div>

		<div class="step-box">
			<?php if ($db_created): ?>
				<button class="btn btn-danger w-100" disabled>
					✅ 2. ฐานข้อมูล drugstock ถูกสร้างแล้ว
				</button>
			<?php else: ?>
				<a href="setup_db.php"
				   class="btn btn-success w-100"
				   onclick="return confirm('ยืนยันการเชื่อม DB กับฐานข้อมูล drugstock ?');">
					2. 🗄️ ยืนยันการเชื่อม Databases กับฐานข้อมูล
				</a>
			<?php endif; ?>
		</div>

		<div class="step-box">
			<?php if ($db_exists): ?>
				<button class="btn btn-danger w-100" disabled>
					✅ 3. Config & ฐานข้อมูล drugstock ถูกสร้างแล้ว
				</button>
			<?php else: ?>
				<a href="setup_config.php"
				   class="btn btn-success w-100"
				   onclick="return confirm('ยืนยันการสร้าง Config กับฐานข้อมูล drugstock ?');">
					3. 🗄️ ยืนยันการสร้าง Config กับฐานข้อมูล
				</a>
			<?php endif; ?>
		</div>
	</div>  ===== -->


</div>

	<!-- ===== Chart.js ===== -->
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

	<script>
	const ctx = document.getElementById('usageChart');

	new Chart(ctx, {
		type: 'line',
		data: {
			labels: <?= json_encode($chart_labels) ?>,
			datasets: [{
				label: 'จำนวนใช้ (เม็ด)',
				data: <?= json_encode($chart_data) ?>,
				tension: 0.4,        // ความโค้งของเส้น
				fill: false,         // ไม่ถมสีใต้เส้น
				pointRadius: 5,      // ขนาดจุด
				pointHoverRadius: 7, // ขนาดจุดเมื่อ hover
				borderWidth: 2
			}]
		},
		options: {
			responsive: true,
			plugins: {
				legend: {
					display: true
				},
				tooltip: {
					callbacks: {
						label: function(context) {
							return context.parsed.y.toLocaleString() + ' เม็ด';
						}
					}
				}
			},
			scales: {
				y: {
					beginAtZero: true,
					ticks: {
						precision: 0
					}
				}
			}
		}
	});
	</script>


	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

	<script>
	<?php if ($exp_count > 0): ?>
	document.addEventListener('DOMContentLoaded', function () {
		const ctx = document.getElementById('expiryChart');
		if (!ctx) return;

		new Chart(ctx, {
			type: 'bar',
			data: {
				labels: <?= json_encode($drug_names, JSON_UNESCAPED_UNICODE) ?>,
				datasets: [{
					data: <?= json_encode($days_left) ?>,
					backgroundColor: <?= json_encode($colors) ?>,
					borderRadius: 10,
					barThickness: 28
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: {
						callbacks: {
							label: ctx => 'เหลืออีก ' + ctx.raw + ' วัน'
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						title: {
							display: true,
							text: 'จำนวนวันก่อนหมดอายุ'
						}
					}
				}
			}
		});
	});
	<?php endif; ?>
	</script>


<?php include 'includes/footer.php'; ?>
</body>
</html>