
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
    <a class="nav-link text-white" href="sitting_admin.php">
      🏠 <span class="menu-text">หน้าจัดการระบบ</span>
    </a>
  </li>

  <li class="nav-item mt-2">
    <?php if ($_SESSION['role'] === 'admin'): ?>
     <div class="d-grid gap-2">

		<a href="../users/edit_username.php?id=<?= $_SESSION['user_id'] ?>"
		   class="btn btn-warning btn-sm">
		   ✏️ แก้ไขข้อมูลผู้ใช้
		</a>

		<?php if (false): ?>
			<a href="../users/clear_resettable.php?id=<?= $_SESSION['user_id'] ?>"
			   class="btn btn-warning btn-sm">
			   🧹 ล้างข้อมูลผู้ใช้
			</a>
		<?php endif; ?>

	</div>
    <?php else: ?>
      <button class="btn btn-secondary btn-sm w-100" disabled>
        🔒 ไม่มีสิทธิ์แก้ไข
      </button>
    <?php endif; ?>
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