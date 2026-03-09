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
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #e08414;">
  <div class="container-fluid">

    <a class="navbar-brand" href="../dashboard.php">คลังยา รพ.สต.</a>

    <!-- ปุ่มมือถือ -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">

      <!-- เมนูฝั่งซ้าย -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="../stock/main_warehouse.php">คลังใหญ่</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../stock/sub_warehouse.php">คลังย่อย</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../stock/daily_usage.php">ใช้ยารายวัน</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../report/reports.php">รายงาน</a>
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

