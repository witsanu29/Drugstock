<?php
session_start();
require 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "
        SELECT *
        FROM users
        WHERE username = ? AND status = 'active'
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {

        // 🔐 สร้าง OTP
        $otp    = rand(100000, 999999);
        $expire = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $conn->prepare("
            INSERT INTO login_otp (user_id, otp_code, expire_at)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $user['id'], $otp, $expire);
        $stmt->execute();

        $_SESSION['debug_otp'] = $otp;  // DEV only
        $_SESSION['otp_user']  = $user['id'];

        header("Location: otp_verify.php");
        exit;
    }

    $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}
?>



<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>เข้าสู่ระบบ | ระบบคลังยา</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>

:root{
    --blue-main:#4dabf7;
    --blue-dark:#339af0;
    --blue-light:#e7f5ff;

    /* ⭐ Soft Blue Glass Feeling */
    --pb-bg1:#f1f9ff;
    --pb-bg2:#e3f2fd;
    --pb-glass:rgba(255,255,255,.70);
}

/* ===== Background Soft Blue ===== */

body{
    min-height:100vh;
    font-family:'Sarabun',sans-serif;

    background:
        radial-gradient(circle at 10% 20%, rgba(77,171,247,.15), transparent 40%),
        radial-gradient(circle at 90% 80%, rgba(77,171,247,.12), transparent 40%),
        linear-gradient(135deg,var(--pb-bg1),var(--pb-bg2));
}

/* ===== Card Glass ===== */

.login-card{
    border-radius:16px;
    border:none;

    background:var(--pb-glass);
    backdrop-filter:blur(12px);

    box-shadow:
        0 10px 30px rgba(0,0,0,.08),
        0 0 0 1px rgba(255,255,255,.4);

    transition:.25s;
}

.login-card:hover{
    transform:translateY(-3px);
}

/* ===== Header ===== */

.login-header{
    background:linear-gradient(135deg,var(--blue-main),var(--blue-dark));
    border-radius:16px 16px 0 0;
}

/* ===== Button ===== */

.btn-primary{
    background:linear-gradient(135deg,var(--blue-main),var(--blue-dark));
    border:none;
    border-radius:8px;
    transition:.2s;
}

.btn-primary:hover{
    transform:translateY(-1px);
    box-shadow:0 8px 18px rgba(77,171,247,.35);
}

/* ===== Input ===== */

.input-group-text{
    background-color:var(--blue-light);
    color:var(--blue-main);
    border:none;
}

.form-control{
    border-left:none;
}

.form-control:focus{
    border-color:var(--blue-main);
    box-shadow:0 0 0 .15rem rgba(77,171,247,.25);
}

/* ===== Logo ===== */

.login-header img{
    background:#fff;
    padding:4px;
    border-radius:50%;
    box-shadow:
        0 6px 15px rgba(0,0,0,.15);
    transition:.25s;
}

.login-header img:hover{
    transform:translateY(-3px) scale(1.02);
    box-shadow:
        0 10px 25px rgba(77,171,247,.35),
        0 0 0 4px rgba(255,255,255,.65);
}

/* ===== Text Shadow ===== */

.login-header h3{
    text-shadow:0 3px 6px rgba(0,0,0,.35);
}

.login-header h5{
    text-shadow:0 1px 2px rgba(0,0,0,.25);
}

</style>

<?php  require 'head.php';  ?>
</head>

<body class="d-flex align-items-center">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4 col-sm-10">

			<?php if (isset($_GET['timeout'])): ?>
			<div class="alert alert-warning text-center">
				⏰ หมดเวลาใช้งาน กรุณาเข้าสู่ระบบใหม่
			</div>
			<?php endif; ?>

            <div class="card shadow-lg login-card">
				<div class="card-header text-center text-white login-header py-4">

					<!-- โลโก้หน่วยงาน -->
					<img src="image/logo1.jpg"
						 alt="โลโก้หน่วยงาน"
						 class="mb-2"
						 style="width:150px;height:auto;">

					<h3 class="fw-semibold mb-1">
						ระบบเวชภัณฑ์ยา / มิใช่ยา
					</h3>
					<h5 class="opacity-75">โรงพยาบาลส่งเสริมสุขภาพตำบล </h5>

				</div>


                <div class="card-body p-4">

                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center small">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">

                        <div class="mb-3">
                            <label class="form-label">ชื่อผู้ใช้</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person-fill"></i>
                                </span>
                                <input type="text" name="username" class="form-control" placeholder="Username" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">รหัสผ่าน</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>
                        </div>

                        <button class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ
                        </button>
                    </form>

                    <hr>

                    <a href="home.php" class="btn btn-outline-secondary w-100">
						<i class="bi bi-arrow-left"></i> กลับหน้าแรก
					</a>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
