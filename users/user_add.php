<?php
require '../includes/auth.php';
require '../includes/admin_only.php'; // 🔐 กัน non-admin
require '../includes/db.php';

/* 🔐 ตรวจซ้ำเพื่อความชัวร์ */
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('⛔ ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $role     = $_POST['role'] ?? '';

    /* ✅ ตรวจข้อมูลพื้นฐาน */
    if ($username === '' || $password === '' || $fullname === '' || $role === '') {
        $msg = '❌ กรุณากรอกข้อมูลให้ครบ';
    } else {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (username, password, fullname, role)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("ssss", $username, $hash, $fullname, $role);
        $stmt->execute();

        $msg = '✅ เพิ่มผู้ใช้เรียบร้อย';
    }
}
?>

<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>เพิ่มผู้ใช้ | ระบบคลังยา รพ.สต.</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="bg-light">

<div class="container py-5">

	<div class="row justify-content-center">
		<div class="col-lg-8">

			<div class="card shadow-sm border-0">
				<div class="card-header bg-primary text-white">
					<h5 class="mb-0">👤 เพิ่มผู้ใช้ใหม่</h5>
				</div>

				<div class="card-body">

					<?php if ($msg): ?>
						<div class="alert alert-success alert-dismissible fade show" role="alert">
							<?= htmlspecialchars($msg) ?>
							<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
						</div>
					<?php endif; ?>

					<form method="post" class="row g-3">

						<div class="col-md-6">
							<label class="form-label fw-semibold">Username</label>
							<input type="text" name="username" class="form-control" required>
						</div>

						<div class="col-md-6">
							<label class="form-label fw-semibold">Password</label>
							<input type="password" name="password" class="form-control" required>
						</div>

						<div class="col-md-6">
							<label class="form-label fw-semibold">ชื่อ - สกุล</label>
							<input type="text" name="fullname" class="form-control">
						</div>

						<div class="col-md-6">
							<label class="form-label fw-semibold">สิทธิ์ผู้ใช้งาน</label>
							<select name="role" class="form-select">
								<option value="staff">👩‍⚕️ Staff</option>
								<option value="admin">🛡 Admin</option>
							</select>
						</div>

						<div class="col-12 text-end mt-4">
							<button type="submit" class="btn btn-success px-4">
								💾 บันทึกข้อมูล
							</button>
							<a href="../dashboard.php" class="btn btn-secondary ms-2">
								↩ กลับ
							</a>
						</div>

					</form>

				</div>
			</div>

		</div>
	</div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
