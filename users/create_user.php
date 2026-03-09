<?php
require '../includes/auth.php';
require '../includes/admin_only.php';
require '../includes/db.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "
    <script>
        alert('⛔ หน้านี้สำหรับผู้ดูแลระบบ (Admin) เท่านั้น');
        window.location.href = '../index.php';
    </script>
    ";
    exit;
}

$message = '';

/* ================= โหลดหน่วยงาน ================= */
$units = $conn->query("
    SELECT unit_id, unit_name
    FROM units
    WHERE status = 'active'
");

/* ================= บันทึกข้อมูล ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $role     = $_POST['role'] ?? '';
    $unit_id  = $_POST['unit_id'] ?? '';
    $status   = 'active';

    if ($username && $password && $fullname && $role && $unit_id) {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users
            (unit_id, username, password, fullname, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "isssss",
            $unit_id,
            $username,
            $hash,
            $fullname,
            $role,
            $status
        );

        if ($stmt->execute()) {
            $message = "
            <div class='alert alert-success alert-dismissible fade show'>
                ✔ เพิ่มผู้ใช้เรียบร้อย
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
        } else {
            if ($conn->errno == 1062) {
                $message = "
                <div class='alert alert-danger alert-dismissible fade show'>
                    ❌ Username ซ้ำ
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
            } else {
                $message = "
                <div class='alert alert-danger alert-dismissible fade show'>
                    ❌ เกิดข้อผิดพลาด
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
            }
        }

    } else {
        $message = "
        <div class='alert alert-warning alert-dismissible fade show'>
            ⚠ กรุณากรอกข้อมูลให้ครบ
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}


?>



 <?php  
require '../head2.php';
require 'bar1.php';
?>

		<?php if ($isForbidden): ?>
		<div class="modal fade show" style="display:block; background:rgba(0,0,0,.5)">
		  <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
			  <div class="modal-header bg-danger text-white">
				<h5 class="modal-title">⛔ ไม่มีสิทธิ์เข้าถึง</h5>
			  </div>
			  <div class="modal-body">
				หน้านี้สำหรับผู้ดูแลระบบ (Admin) เท่านั้น
			  </div>
			  <div class="modal-footer">
				<a href="../index.php" class="btn btn-secondary">
					กลับหน้าหลัก
				</a>
			  </div>
			</div>
		  </div>
		</div>
		<?php exit; endif; ?>

	     <!-- ================= Main Content ================= -->
		<main id="mainContent" class="main-content px-4 py-4">
			<div class="d-flex align-items-center gap-3 mb-2">
                 👤 เพิ่มผู้ใช้ระบบ
				 <span class="badge bg-dark ms-2">Create_User</span>
                </div>

                <!-- Body -->
				<div class="card mb-4">
					<div class="card-header bg-orange text-white">
					  ️  เพิ่มผู้ใช้ระบบ
					</div>
					
					<div class="card-body">
					<?= $message ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Username :</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password :</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ชื่อ–สกุล :</label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>

					<div class="mb-3">
						<label class="form-label">หน่วยงาน :</label>

						<?php if ($units && $units->num_rows > 0): ?>
							<select name="unit_id" class="form-select" required>
								<option value="">-- เลือกหน่วยงาน --</option>
								<?php while($u = $units->fetch_assoc()): ?>
									<option value="<?= $u['unit_id'] ?>">
										<?= htmlspecialchars($u['unit_name']) ?>
									</option>
								<?php endwhile; ?>
							</select>
						<?php else: ?>
							<div class="alert alert-warning mb-0">
								⚠ ยังไม่มีหน่วยงานในระบบ กรุณาเพิ่มหน่วยงานก่อน
							</div>
						<?php endif; ?>
					</div>


                    <div class="mb-3">
                        <label class="form-label">สิทธิ์ผู้ใช้ :</label>
						<select name="role" class="form-select" required>
							<option value="staff">Staff</option>
							<option value="demo">Demo (ดูอย่างเดียว)</option>
							<option value="admin">Admin</option>
						</select>

					</div>

                    <div class="d-flex justify-content-between">
					<?php if ($units && $units->num_rows > 0): ?>
						<button class="btn btn-success">💾 บันทึก</button>
					<?php endif; ?>

                        <a href="sitting_admin.php" class="btn btn-secondary">
                            ⬅ กลับหน้าแรก
                        </a>
                    </div>
                </form>

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
