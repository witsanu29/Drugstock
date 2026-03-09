<?php
session_start();
require 'includes/db.php';
require 'send_telegram.php';

if (!isset($_SESSION['otp_user'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$user_id = (int)$_SESSION['otp_user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otp = trim($_POST['otp']);

    $sql = "
        SELECT *
        FROM login_otp
        WHERE user_id = ?
          AND otp_code = ?
          AND expire_at >= NOW()
        ORDER BY id DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $otp);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 1) {

        // ดึง user
        $sql = "
            SELECT u.*, un.unit_name, un.unit_code
            FROM users u
            LEFT JOIN units un ON u.unit_id = un.unit_id
            WHERE u.id = ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // ✅ Login สำเร็จ
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['fullname']  = $user['fullname'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['unit_id']   = $user['unit_id'];
        $_SESSION['unit_name'] = $user['unit_name'];

        date_default_timezone_set('Asia/Bangkok');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

        // 🔔 แจ้งเตือน Telegram หลัง OTP ผ่าน
        $message = "🔐 <b>เข้าสู่ระบบคลังยา รพ.สต. สำเร็จ </b>\n"
            . "🌐 IP : <code>{$ip_address}</code>\n"
            . "👤 Username : <b>{$user['username']}</b>\n"
            . "🏷 Unit Code : <b>{$user['unit_code']}</b>\n"
            . "🏢 หน่วยงาน : <b>{$user['unit_name']}</b>\n"
            . "📅 วันที่ : " . date('d/m/Y') . "\n"
            . "⏰ เวลา : " . date('H:i:s');

        sendTelegram($message);

        unset($_SESSION['otp_user']);

        header("Location: index.php");
        exit;
    }

    $error = "รหัส OTP ไม่ถูกต้อง หรือหมดอายุ";
}
?>




<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>ยืนยัน OTP | ระบบคลังยา</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    min-height:100vh;
    background: linear-gradient(135deg,#fff4e6,#ffe8cc);
}
.card{
    border-radius:1rem;
}
.otp-input{
    font-size:1.6rem;
    letter-spacing:.4rem;
    text-align:center;
}
</style>
</head>



<body class="d-flex align-items-center">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4 col-sm-10">

            <div class="card shadow-lg">
                <div class="card-header text-center bg-warning text-white py-3">
                    <h4 class="mb-0">
                        <i class="bi bi-shield-lock-fill"></i>
                        ยืนยันรหัส OTP
                    </h4>
                </div>

                <div class="card-body p-4">

                    <p class="text-center mb-3">
                        ระบบได้ส่งรหัส OTP ไปยังอีเมลของคุณแล้ว  
                        <br>
                        <small class="text-muted">กรุณากรอกภายใน 5 นาที</small>
                    </p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center small">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

<?php if (isset($_SESSION['debug_otp'])): ?>
<div class="alert alert-warning text-center">
    🔑 <strong>OTP (DEV MODE):</strong>
    <?= $_SESSION['debug_otp'] ?>
</div>
<?php endif; ?>



<?php if (!isset($_SESSION['otp_user'])): ?>

<!-- ================= LOGIN FORM ================= -->
<form method="post">

    <div class="mb-3">
        <label class="form-label">ชื่อผู้ใช้</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
            <input type="text" name="username" class="form-control" required>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">รหัสผ่าน</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" name="password" class="form-control" required>
        </div>
    </div>

    <button class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ
    </button>
</form>

<?php else: ?>

<!-- ================= OTP FORM ================= -->
<form method="post">

    <div class="text-center mb-3">
        <i class="bi bi-shield-lock-fill fs-1 text-warning"></i>
        <p class="mt-2 mb-0">
            กรุณากรอกรหัส OTP  
            <br><small class="text-muted">ภายใน 5 นาที</small>
        </p>
    </div>

    <div class="mb-4">
        <input type="text"
               name="otp"
               maxlength="6"
               class="form-control text-center fs-4"
               placeholder="● ● ● ● ● ●"
               required>
    </div>

    <button class="btn btn-warning w-100 py-2 fw-semibold">
        <i class="bi bi-check-circle-fill"></i> ยืนยัน OTP
    </button>

</form>

<?php endif; ?>


                    <hr>

                    <a href="login.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left"></i> กลับหน้าเข้าสู่ระบบ
                    </a>

                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
