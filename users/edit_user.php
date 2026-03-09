<?php
session_start();
require '../includes/auth.php';
require '../includes/db.php';

/* ================= ตรวจสิทธิ์ ================= */
if (!in_array($_SESSION['role'], ['admin', 'staff'])) {
    exit('❌ ไม่มีสิทธิ์เข้าถึง');
}

/* ================= รับ ID ================= */
$id = $_GET['id'] ?? null;
if (!$id) {
    exit('ไม่พบ ID ผู้ใช้');
}

/* ================= โหลดข้อมูลผู้ใช้ ================= */
$stmt = $conn->prepare("
    SELECT id, username, fullname, role, unit_id
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    exit('ไม่พบข้อมูลผู้ใช้');
}

/* ================= staff ห้ามแก้ user ต่างหน่วย ================= */
if (
    $_SESSION['role'] === 'staff' &&
    $_SESSION['unit_id'] != $user['unit_id']
) {
    exit('❌ ไม่มีสิทธิ์แก้ไขผู้ใช้งานต่างหน่วย');
}

/* ================= โหลดหน่วยงาน ================= */
$units = $conn->query("
    SELECT unit_id, unit_name
    FROM units
    WHERE status = 'active'
");

/* ================= บันทึกการแก้ไข ================= */
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $unit_id  = $_POST['unit_id'];

    // staff แก้ role ไม่ได้
    if ($_SESSION['role'] === 'admin') {
        $role = $_POST['role'];
    } else {
        $role = $user['role'];
    }

    if ($username && $fullname && $unit_id) {

        $stmt = $conn->prepare("
            UPDATE users
            SET username = ?, fullname = ?, role = ?, unit_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssii",
            $username,
            $fullname,
            $role,
            $unit_id,
            $id
        );

			if ($stmt->execute()) {
				header("Location: edit_username.php?id={$id}&success=1");
				exit;
			}

		else {
            if ($conn->errno == 1062) {
                $message = "
                <div class='alert alert-danger'>❌ Username ซ้ำ</div>";
            } else {
                $message = "
                <div class='alert alert-danger'>❌ เกิดข้อผิดพลาด</div>";
            }
        }
    }
}

/* ================= เปลี่ยนรหัสผ่าน ================= */
/* ================= เปลี่ยนรหัสผ่าน ================= */
if (isset($_POST['change_password'])) {

    $newpass  = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($newpass === '' || $confirm === '') {
        $message = "<div class='alert alert-danger'>❌ กรุณากรอกรหัสผ่านให้ครบ</div>";
    } elseif ($newpass !== $confirm) {
        $message = "<div class='alert alert-danger'>❌ รหัสผ่านไม่ตรงกัน</div>";
    } elseif (strlen($newpass) < 4) {
        $message = "<div class='alert alert-danger'>❌ รหัสผ่านต้องอย่างน้อย 4 ตัวอักษร</div>";
    } else {

        $hash = password_hash($newpass, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");
        $stmt->bind_param("si", $hash, $id);
        $stmt->execute();

        // ✅ เปลี่ยนเส้นทางหลังสำเร็จ
        header("Location: edit_user.php?id={$id}&pwd=success");
        exit;
    }
}

?>

<!doctype html>
<html lang="th">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>แก้ไขผู้ใช้</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="icon" href="../assets/img/hospital-icon.png" type="image/png">
</head>
<body>

<?php require '../head2.php'; ?>
<?php require 'bar.php'; ?>

<main class="main-content px-4 py-4">

    <h4 class="mb-3">✏️ แก้ไขผู้ใช้ระบบ</h4>

		<?php if (isset($_GET['success'])): ?>
		<div class="alert alert-success alert-dismissible fade show">
			✔ บันทึกข้อมูลเรียบร้อย
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
		<?php endif; ?>


    <?= $message ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username"
                           class="form-control"
                           value="<?= htmlspecialchars($user['username']) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">ชื่อ–สกุล</label>
                    <input type="text" name="fullname"
                           class="form-control"
                           value="<?= htmlspecialchars($user['fullname']) ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">หน่วยงาน</label>
                    <select name="unit_id" class="form-select" required>
                        <?php while($u = $units->fetch_assoc()): ?>
                            <option value="<?= $u['unit_id'] ?>"
                                <?= $u['unit_id'] == $user['unit_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['unit_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="mb-3">
                    <label class="form-label">สิทธิ์ผู้ใช้</label>
                    <select name="role" class="form-select">
                        <option value="staff" <?= $user['role']=='staff'?'selected':'' ?>>Staff</option>
                        <option value="demo"  <?= $user['role']=='demo'?'selected':'' ?>>Demo</option>
                        <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
                    </select>
                </div>
                <?php else: ?>
                    <div class="alert alert-secondary">
                        🔒 Staff ไม่สามารถแก้ไขสิทธิ์ผู้ใช้ได้
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between">
                    <button class="btn btn-primary">
                        💾 บันทึก
                    </button>
                    <a href="edit_username.php" class="btn btn-secondary">
                        ⬅ กลับ
                    </a>
                </div>

            </form>

			<div class="card shadow-sm mt-4 border-warning">
				<div class="card-header bg-warning">
					🔐 เปลี่ยนรหัสผ่าน
				</div>
				<div class="card-body">

					<form method="post">

						<div class="mb-3">
							<label class="form-label">รหัสผ่านใหม่</label>
							<input type="password"
								   name="new_password"
								   class="form-control"
								   required>
						</div>

						<div class="mb-3">
							<label class="form-label">ยืนยันรหัสผ่านใหม่</label>
							<input type="password"
								   name="confirm_password"
								   class="form-control"
								   required>
						</div>

						<button class="btn btn-warning" name="change_password">
							🔑 เปลี่ยนรหัสผ่าน
						</button>

					</form>

				</div>
			</div>

        </div>
    </div>
</main>

	<script>
		document.getElementById('toggleSidebar').addEventListener('click', function () {
		  document.getElementById('sidebar').classList.toggle('collapsed');
		});
		</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
