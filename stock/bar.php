
	<div class="container-fluid">
	  <div class="d-flex">

		<!-- ================= Sidebar ================= -->
		<nav id="sidebar" class="sidebar bg-orange min-vh-100">

		  <div class="sidebar-inner">
			
			<!-- ปุ่มยุบเมนู -->
			<button id="toggleSidebar"
					class="btn btn-sm btn-light w-100 mb-3">
			  ☰ เมนู
			</button>

			<h5 class="text-white text-center mb-4 sidebar-title">
			  🏥 คลังยา รพ.สต.
			</h5>

			<ul class="nav flex-column">

			  <li class="nav-item">
				<a class="nav-link text-white" href="../dashboard.php">
				  🏠 <span class="menu-text">Dashboard</span>
				</a>
			  </li>

			  <li class="nav-item">
				<a class="nav-link text-white" href="main_warehouse.php">
				  🏬 <span class="menu-text">คลังใหญ่</span>
				</a>
			  </li>

			  <li class="nav-item">
				<a class="nav-link text-white" href="sub_warehouse.php">
				  🏪 <span class="menu-text">คลังย่อย</span>
				</a>
			  </li>

			  <li class="nav-item">
				<a class="nav-link text-white" href="daily_usage.php">
				  💊 <span class="menu-text">ใช้ยารายวัน</span>
				</a>
			  </li>

			  <li class="nav-item">
				<a class="nav-link text-white" href="../report/reports.php">
				  📊 <span class="menu-text">รายงาน</span>
				</a>
			  </li>

			  <li class="nav-item">
				<a class="nav-link text-white" href="../non_drug/main_non_drug.php">
				  🧴 <span class="menu-text">เวชภัณฑ์มิใช่ยา</span>
				</a>
			  </li>

			</ul>

			<hr class="text-secondary">

			<div class="d-flex flex-column align-items-start gap-1">
			<?php if (isset($_SESSION['user_id'])): ?>

				<span class="text-white fw-semibold">
					👤 <?= htmlspecialchars($_SESSION['fullname']) ?>
				</span>

				<?php if ($_SESSION['role'] === 'admin'): ?>
					<span class="badge bg-warning text-dark">
						Admin
					</span>
				<?php endif; ?>

				<a href="../logout.php" class="btn btn-sm btn-light mt-1">
					Logout
				</a>

			<?php else: ?>

				<a href="../login.php" class="btn btn-sm btn-outline-light">
					Login
				</a>

			<?php endif; ?>
			</div>

		  </div>
		</nav>