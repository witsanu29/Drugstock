<?php

require '../includes/auth.php';
require '../includes/admin_only.php'; // admin เท่านั้น
require '../includes/db.php';


$sql = "
    SELECT u.id, u.username, u.fullname, u.role, un.unit_name
    FROM users u
    LEFT JOIN units un ON u.unit_id = un.unit_id
";
$result = $conn->query($sql);

$isAdmin = ($_SESSION['role'] === 'admin');


?>


 <?php  
require '../head2.php';
require 'bar1.php';
?>

		<!-- ===== Popup ยืนยันลบผู้ใช้ ===== -->
		<div class="modal fade" id="deleteUserModal" tabindex="-1">
		  <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">

			  <div class="modal-header bg-danger text-white">
				<h5 class="modal-title">⚠️ ยืนยันการลบผู้ใช้</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			  </div>

			  <div class="modal-body">
				<p>
				  คุณต้องการลบผู้ใช้
				  <strong class="text-danger" id="deleteUsername"></strong>
				  ใช่หรือไม่?
				</p>
				<p class="text-muted mb-0">การกระทำนี้ไม่สามารถย้อนกลับได้</p>
			  </div>

			  <div class="modal-footer">
				<button class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>

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

	     <!-- ================= Main Content ================= -->
		<main id="mainContent" class="main-content px-4 py-4">
		<div class="d-flex align-items-center gap-3 mb-2">
        <h4 class="mb-0">
            👤 จัดการผู้ใช้งานระบบ
        </h4>
        <a href="create_user.php" class="btn btn-success btn-sm">
            ➕ เพิ่มผู้ใช้
        </a>
    </div>

    <!-- ===== Card ===== -->
    <div class="card shadow-sm rounded-4">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-dark text-center">
                        <tr>
                            <th width="60">ลำดับ</th>
                            <th>Username</th>
                            <th>ชื่อ–สกุล</th>
                            <th>หน่วยงาน</th>
                            <th width="100">สิทธิ์</th>
                            <th width="120">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $i=1; while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center"><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($row['unit_name'] ?? '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?=
                                    $row['role']=='admin' ? 'danger' :
                                    ($row['role']=='staff' ? 'primary' : 'secondary')
                                ?>">
                                    <?= strtoupper($row['role']) ?>
                                </span>
                            </td>
                            <td class="text-center">

                               <!-- ปุ่มแก้ไข (admin เท่านั้น) -->
								<?php if ($isAdmin): ?>
									<a href="edit_user.php?id=<?= $row['id'] ?>"
									   class="btn btn-warning btn-sm">
									   ✏️ แก้ไข
									</a>
								<?php else: ?>
									<button class="btn btn-secondary btn-sm" disabled>
										🔒 แก้ไข
									</button>
								<?php endif; ?>

							<?php if ($isAdmin): ?>
							<button type="button"
									class="btn btn-danger btn-sm"
									data-bs-toggle="modal"
									data-bs-target="#deleteUserModal"
									data-userid="<?= $row['id'] ?>"
									data-username="<?= htmlspecialchars($row['username']) ?>">
								🗑 ลบ
							</button>
							<?php endif; ?>

                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                ⚠ ไม่พบข้อมูลผู้ใช้งาน
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

		</main> <!-- end main -->

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
