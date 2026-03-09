<?php
require '../includes/auth.php';
require '../includes/admin_only.php';
require '../includes/db.php';

/* 🔐 ล็อกซ้ำเพื่อความชัวร์ */
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('⛔ ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_otp'])) {
    try {
        $conn->begin_transaction();

        $conn->query("DELETE FROM login_otp");
        $conn->query("ALTER TABLE login_otp AUTO_INCREMENT = 1");

        $conn->commit();

        // ✅ ลบสำเร็จ → ไปหน้า create_user.php
        header('Location: reset_table.php?clear_otp=success');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = "❌ เกิดข้อผิดพลาดในการลบข้อมูล";
    }
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ล้างข้อมูล OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/hospital-icon.png" type="image/png">

</head>

<?php
require '../head2.php';
require 'bar0.php';
?>

		<!-- ===== Popup ยืนยันลบ OTP ===== -->
			<div class="modal fade" id="clearOtpModal" tabindex="-1">
			  <div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">

				  <div class="modal-header bg-danger text-white">
					<h5 class="modal-title">⚠️ ยืนยันการลบข้อมูล</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				  </div>

				  <div class="modal-body">
					<p>
					  คุณแน่ใจหรือไม่ว่าต้องการ
					  <strong class="text-danger">ลบข้อมูล OTP ทั้งหมด</strong>
					</p>
					<p class="text-muted mb-0">การกระทำนี้ไม่สามารถย้อนกลับได้</p>
				  </div>

				  <div class="modal-footer">
					<button type="button"
							class="btn btn-secondary"
							data-bs-dismiss="modal">
					  ยกเลิก
					</button>

					<!-- ✅ ฟอร์ม submit จริง -->
					<form method="post">
					  <button type="submit"
							  name="clear_otp"
							  class="btn btn-danger">
						✅ ยืนยันลบ
					  </button>
					</form>
				  </div>

				</div>
			  </div>
			</div>


			<?php if (isset($_GET['clear_otp']) && $_GET['clear_otp'] === 'success'): ?>
				<div class="alert alert-success alert-dismissible fade show">
					✔ ล้างข้อมูล OTP เรียบร้อยแล้ว
					<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
				</div>
			<?php endif; ?>

		<div class="container mt-5">
			<div class="row justify-content-center">
				<div class="col-md-6">

					<div class="card shadow-sm border-0">
						<div class="card-body text-center">
							<h4 class="mb-3 text-danger">🧹 ล้างข้อมูล OTP</h4>
							<p class="text-muted">
								ใช้สำหรับลบ OTP ที่ค้างอยู่ทั้งหมดในระบบ  
								<br><strong class="text-danger">* ไม่สามารถกู้คืนได้ *</strong>
							</p>

							<?php if ($error): ?>
								<div class="alert alert-danger"><?= $error ?></div>
							<?php endif; ?>

							<form method="post" onsubmit="return confirm('⚠️ ยืนยันลบ OTP ทั้งหมด ?');" class="mb-3">
								<button type="button"
										class="btn btn-danger"
										data-bs-toggle="modal"
										data-bs-target="#clearOtpModal">
									🧹 ล้างข้อมูล OTP
								</button>
							</form>

						

						</div>
					</div>

					<div class="text-center mt-3 text-muted small">
						สำหรับผู้ดูแลระบบ (Admin) เท่านั้น
					</div>

				</div>
			</div>
		</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

		<script>
		document.getElementById('toggleSidebar').addEventListener('click', function () {
		  document.getElementById('sidebar').classList.toggle('collapsed');
		});
		</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
