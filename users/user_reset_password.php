<?php
session_start();

require '../includes/auth.php';
require '../includes/admin_only.php';
require '../includes/db.php';

$error = '';
$success = '';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    exit('ไม่พบผู้ใช้');
}

/* ดึงข้อมูลผู้ใช้ */
$res = $conn->query("SELECT username, fullname FROM users WHERE id = $id");
$user = $res->fetch_assoc();
if (!$user) {
    exit('ไม่พบข้อมูลผู้ใช้');
}

/* ===== SAVE PASSWORD ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($password === '' || $password2 === '') {
        $error = 'กรุณากรอกรหัสผ่านให้ครบ';
    } elseif ($password !== $password2) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } elseif (strlen($password) < 4) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร';
    } else {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");
        $stmt->bind_param("si", $hash, $id);
        $stmt->execute();

        header("Location: user_setting_id.php?reset=success");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รีเซ็ตรหัสผ่าน</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<?php require '../head2.php'; ?>
<?php require 'bar0.php'; ?>

<div class="container mt-4" style="max-width:600px">

<div class="card shadow-sm">
<div class="card-header bg-warning fw-bold">
<i class="bi bi-key"></i> ตั้งรหัสผ่านใหม่
</div>

<div class="card-body">

<p class="mb-3">
<b>ผู้ใช้:</b> <?= htmlspecialchars($user['fullname']) ?>  
(<span class="text-muted"><?= htmlspecialchars($user['username']) ?></span>)
</p>

<?php if ($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="post">

<div class="mb-3">
<label class="form-label">รหัสผ่านใหม่</label>
<input type="password" name="password" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">ยืนยันรหัสผ่าน</label>
<input type="password" name="password2" class="form-control" required>
</div>

<div class="d-flex gap-2">
<button class="btn btn-success">
<i class="bi bi-save"></i> บันทึก
</button>

<a href="user_setting_id.php" class="btn btn-secondary">
<i class="bi bi-arrow-left"></i> กลับ
</a>
</div>

</form>

</div>
</div>

</div>
</body>
</html>
