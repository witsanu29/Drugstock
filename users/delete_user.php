<?php
require '../includes/auth.php';
require '../includes/admin_only.php';
require '../includes/db.php';

/* 🔐 admin เท่านั้น */
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('⛔ ไม่มีสิทธิ์ลบข้อมูล');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {

    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id <= 0) {
        exit('❌ ไม่พบผู้ใช้');
    }

    /* ❌ ห้ามลบตัวเอง */
    if ($user_id == ($_SESSION['user_id'] ?? 0)) {
        exit('❌ ไม่สามารถลบบัญชีของตัวเองได้');
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    header('Location: edit_username.php?delete=success');
    exit;
}
?>

 <?php  
require '../head2.php';
require 'bar1.php';
?>

		<button type="button"
				class="btn btn-danger btn-sm"
				data-bs-toggle="modal"
				data-bs-target="#deleteUserModal"
				data-userid="<?= $row['id'] ?>"
				data-username="<?= htmlspecialchars($row['username']) ?>">
			🗑 ลบ
		</button>

		<!-- ===== Modal ยืนยันลบ ===== -->
		<div class="modal fade" id="deleteUserModal" tabindex="-1">
		  <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content shadow">

			  <div class="modal-header bg-danger text-white">
				<h5 class="modal-title">⚠️ ยืนยันการลบผู้ใช้</h5>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
			  </div>

			  <div class="modal-body">
				<p>
				  คุณต้องการลบผู้ใช้
				  <strong class="text-danger" id="deleteUsername"></strong>
				  ใช่หรือไม่?
				</p>
				<div class="alert alert-warning mb-0">
				  การลบไม่สามารถย้อนกลับได้
				</div>
			  </div>

			  <div class="modal-footer">
				<button class="btn btn-secondary" data-bs-dismiss="modal">
				  ❌ ยกเลิก
				</button>

				<form method="post" action="delete_user.php">
				  <input type="hidden" name="user_id" id="deleteUserId">
				  <button type="submit" name="delete_user" class="btn btn-danger">
					✅ ยืนยันลบ
				  </button>
				</form>
			  </div>

			</div>
		  </div>
		</div>

		<script>
		const deleteModal = document.getElementById('deleteUserModal');

		deleteModal.addEventListener('show.bs.modal', function (event) {
			const button   = event.relatedTarget;
			const userId   = button.getAttribute('data-userid');
			const username = button.getAttribute('data-username');

			document.getElementById('deleteUserId').value = userId;
			document.getElementById('deleteUsername').textContent = username;
		});
		</script>

		<script>
		document.getElementById('toggleSidebar').addEventListener('click', function () {
		  document.getElementById('sidebar').classList.toggle('collapsed');
		});
		</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
