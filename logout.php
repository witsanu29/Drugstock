<?php
session_start();

/* ลบสถานะออนไลน์ */
$removeFile = __DIR__ . '/includes/online_remove.php';
if (file_exists($removeFile)) {
    require_once $removeFile;
}

/* ล้าง session */
session_unset();
session_destroy();

/* ล้าง cookie session */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ออกจากระบบ</title>
    <meta http-equiv="refresh" content="2;url=login.php">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

<div class="card shadow text-center p-4" style="max-width: 400px;">
    <div class="card-body">
        <h4 class="text-danger mb-3">🚪 ออกจากระบบสำเร็จ</h4>
        <p class="text-muted">ระบบกำลังพาคุณกลับไปหน้าเข้าสู่ระบบ</p>
        <div class="spinner-border text-primary my-3"></div>
        <p class="small text-muted">กรุณารอสักครู่...</p>
        <a href="login.php" class="btn btn-outline-primary mt-3">
            กลับไปหน้าเข้าสู่ระบบทันที
        </a>
    </div>
</div>

</body>
</html>
