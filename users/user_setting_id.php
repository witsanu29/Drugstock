<?php
session_start();

/* ================== LOAD CORE ================== */
require '../includes/db.php';
require '../includes/auth.php';   // เช็ก login
require '../includes/config.php';

/* ================== CHECK ADMIN ================== */
if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    http_response_code(403);
    exit('⛔ หน้านี้สำหรับผู้ดูแลระบบ (admin) เท่านั้น');
}


$filter_unit = (int)($_GET['unit_id'] ?? 0);

/* ================== INIT ================== */
$error   = '';
$success = '';
$edit    = null;

/* ================== DELETE ================== */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // ❌ ห้ามลบตัวเอง
    if ($id == ($_SESSION['user_id'] ?? 0)) {
        $error = '❌ ไม่สามารถลบบัญชีของตัวเองได้';
    } else {

        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && $user['role'] === 'admin') {
            $error = '❌ ไม่สามารถลบผู้ดูแลระบบ (admin) ได้';
        } else {
            $del = $conn->prepare("DELETE FROM users WHERE id = ?");
            $del->bind_param("i", $id);
            $del->execute();

            header("Location: user_setting_id.php");
            exit;
        }
    }
}


/* ================== EDIT ================== */
if (isset($_GET['edit'])) {
    $id  = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM users WHERE id = $id");
    $edit = $res->fetch_assoc();
}

/* ================== RESET PASSWORD ================== */
if (isset($_GET['reset'])) {
    $id = (int)$_GET['reset'];

    $newpass = password_hash('Abc12345', PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param("si", $newpass, $id);
    $stmt->execute();

    header("Location: user_setting_id.php");
    exit;
}


/* ================== SAVE ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id       = $_POST['id'] ?? '';
    $unit_id  = (int)$_POST['unit_id'];
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $role     = $_POST['role'];
    $status   = $_POST['status'];

    if ($username === '' || $fullname === '') {
        $error = 'กรุณากรอกข้อมูลให้ครบ';
    } else {

        if ($id) {
            // UPDATE
            $stmt = $conn->prepare("
                UPDATE users
                SET unit_id=?, username=?, fullname=?, role=?, status=?
                WHERE id=?
            ");
            $stmt->bind_param(
                "issssi",
                $unit_id, $username, $fullname, $role, $status, $id
            );
            $stmt->execute();
        } else {
            // INSERT (password เริ่มต้น 1234)
            $hash = password_hash('1234', PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (unit_id, username, fullname, role, status, password)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "isssss",
                $unit_id, $username, $fullname, $role, $status, $hash
            );
            $stmt->execute();
        }

        header("Location: user_setting_id.php");
        exit;
    }
}

/* ================== LOAD USERS ================== */
$where = '';
if ($filter_unit > 0) {
    $where = "WHERE u.unit_id = $filter_unit";
}

$res = $conn->query("
    SELECT u.*, un.unit_name
    FROM users u
    LEFT JOIN units un ON u.unit_id = un.unit_id
    $where
    ORDER BY u.id ASC
");

while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}

/* ================== LOAD UNITS ================== */
$units = [];
$u = $conn->query("SELECT * FROM units WHERE status='active'");
while ($r = $u->fetch_assoc()) {
    $units[] = $r;
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
	<title>จัดการผู้ใช้</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/hospital-icon.png" type="image/png">
</head>
<body>

<?php require '../head2.php'; ?>
<?php require 'bar0.php'; ?>

	<div class="container mt-4">

		<h4 class="fw-bold mb-3">
		<i class="bi bi-people"></i> จัดการผู้ใช้ระบบ
		</h4>
		
		<div class="card shadow-sm mb-3">
			<div class="card-body">
				<form method="get" class="row g-2 align-items-end">

					<div class="col-md-4">
						<label class="form-label fw-semibold">หน่วยงาน</label>
						<select name="unit_id" class="form-select">
							<option value="0">— ทุกหน่วยงาน —</option>
							<?php foreach ($units as $u): ?>
								<option value="<?= $u['unit_id'] ?>"
									<?= $filter_unit==$u['unit_id']?'selected':'' ?>>
									<?= $u['unit_name'] ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="col-md-2">
						<button class="btn btn-primary w-100">
							<i class="bi bi-search"></i> ค้นหา
						</button>
					</div>

				</form>
			</div>
		</div>

		<?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

		<!-- ===== FORM ===== -->
		<div class="card shadow-sm mb-4">
		<div class="card-header bg-primary text-white">ข้อมูลผู้ใช้</div>
		<div class="card-body">

		<form method="post" class="row g-3">
		<input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">

		<div class="col-md-3">
		<label class="form-label">หน่วยงาน</label>
		<select name="unit_id" class="form-select" required>
		<?php foreach ($units as $u): ?>
		<option value="<?= $u['unit_id'] ?>"
		<?= ($edit['unit_id'] ?? '')==$u['unit_id']?'selected':'' ?>>
		<?= $u['unit_name'] ?>
		</option>
		<?php endforeach; ?>
		</select>
		</div>

		<div class="col-md-3">
		<label class="form-label">Username</label>
		<input type="text" name="username" class="form-control"
		value="<?= $edit['username'] ?? '' ?>" required>
		</div>

		<div class="col-md-3">
		<label class="form-label">ชื่อ-สกุล</label>
		<input type="text" name="fullname" class="form-control"
		value="<?= $edit['fullname'] ?? '' ?>" required>
		</div>

		<div class="col-md-2">
		<label class="form-label">สิทธิ์</label>
		<select name="role" class="form-select">
		<?php foreach (['admin','staff','demo'] as $r): ?>
		<option value="<?= $r ?>"
		<?= ($edit['role'] ?? '')==$r?'selected':'' ?>>
		<?= strtoupper($r) ?>
		</option>
		<?php endforeach; ?>
		</select>
		</div>

		<div class="col-md-1">
		<label class="form-label">สถานะ</label>
		<select name="status" class="form-select">
		<option value="active" <?= ($edit['status'] ?? '')=='active'?'selected':'' ?>>ใช้งาน</option>
		<option value="inactive" <?= ($edit['status'] ?? '')=='inactive'?'selected':'' ?>>ปิด</option>
		</select>
		</div>

		<div class="col-12">
		<button class="btn btn-success">
		<i class="bi bi-save"></i> บันทึก
		</button>
		</div>

		</form>
		</div>
		</div>

		<!-- ===== TABLE ===== -->
		<div class="card shadow-sm">
			<div class="card-body p-0">
				<table class="table table-bordered table-hover mb-0">
					<thead class="table-secondary text-center">
						<tr>
							<th width="60">ลำดับ</th>
							<th>ID</th>
							<th>หน่วยงาน</th>
							<th>Username</th>
							<th>ชื่อ</th>
							<th>Role</th>
							<th>Status</th>
							<th>จัดการ</th>
						</tr>
					</thead>

					<tbody>
					<?php $i = 1; foreach ($users as $u): ?>
						<tr>
							<td class="text-center"><?= $i++ ?></td>
							<td class="text-center"><?= $u['id'] ?></td>
							<td><?= $u['unit_name'] ?></td>
							<td><?= $u['username'] ?></td>
							<td><?= $u['fullname'] ?></td>
							<td>
								<span class="badge bg-info"><?= $u['role'] ?></span>
							</td>
							<td>
								<?= $u['status']=='active'
									? '<span class="badge bg-success">ใช้งาน</span>'
									: '<span class="badge bg-secondary">ปิด</span>' ?>
							</td>
							<td class="text-center">
								<a href="?edit=<?= $u['id'] ?>" class="btn btn-warning btn-sm">
									<i class="bi bi-pencil"></i>
								</a>

								<a href="?reset=<?= $u['id'] ?>"
								   onclick="return confirm('รีเซ็ตรหัสผ่านเป็น Abc12345 ?')"
								   class="btn btn-secondary btn-sm">
								   <i class="bi bi-key"></i>
								</a>

								<?php if ($u['role'] !== 'admin'): ?>
									<a href="?delete=<?= $u['id'] ?>"
									   onclick="return confirm('ยืนยันลบผู้ใช้ ?')"
									   class="btn btn-danger btn-sm">
									   <i class="bi bi-trash"></i>
									</a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>

				</table>
			</div>
		</div>

	</div>

	<script>
		document.getElementById('toggleSidebar').addEventListener('click', function () {
		  document.getElementById('sidebar').classList.toggle('collapsed');
		});
		</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
