<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #e08414;">
  <div class="container-fluid">

      <!-- ปุ่มเปิด Sidebar -->
    <button class="btn btn-outline-light me-2"
            data-bs-toggle="offcanvas"
            data-bs-target="#sidebarMenu">
      ☰
    </button><a class="navbar-brand" href="../dashboard.php">คลังยา รพ.สต.</a>

    <!-- ปุ่มมือถือ -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">

      <!-- เมนูฝั่งซ้าย -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
		 <li class="nav-item">
		  <a class="nav-link" href="main_warehouse.php">🏬 คลังใหญ่</a>
		</li>
		<li class="nav-item">
		  <a class="nav-link" href="sub_warehouse.php">🏪 คลังย่อย</a>
		</li>
		<li class="nav-item">
		  <a class="nav-link" href="daily_usage.php">💊 ใช้ยารายวัน</a>
		</li>
		<li class="nav-item">
		  <a class="nav-link" href="../report/reports.php">📊 รายงาน</a>
		</li>

		<li class="nav-item">
		  <a class="nav-link" href="../non_drug/main_non_drug.php">
			🧴 คลังเวชภัณฑ์มิใช่ยา
		  </a>

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

			<a href="../logout.php" class="btn btn-sm btn-light ms-2">
				Logout
			</a>

		<?php else: ?>

			<a href="../login.php" class="btn btn-sm btn-outline-light">
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

			  <a href="../dashboard.php" class="list-group-item list-group-item-action">
				🏠 Dashboard
			  </a>
			  
			  <a href="main_warehouse.php" class="list-group-item list-group-item-action">
				🏬 คลังใหญ่
			  </a>

			  <a href="sub_warehouse.php" class="list-group-item list-group-item-action">
				📦 คลังย่อย
			  </a>

			  <a href="daily_usage.php" class="list-group-item list-group-item-action">
				💊 ใช้ยารายวัน
			  </a>

			  <a href="../report/reports.php" class="list-group-item list-group-item-action">
				📊 รายงาน
			  </a>
			  
			  <a href="../non_drug/main_non_drug.php" class="list-group-item list-group-item-action">
				🧴 คลังเวชภัณฑ์มิใช่ยา
			  </a>
			  
			</div>

		  </div>
		</div>
		