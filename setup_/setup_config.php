<?php
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $hostname = trim($_POST['hostname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($hostname === "" || $username === "") {
        $error = "กรุณากรอก Hostname และ Username";
    } else {

        // 🔹 ทดสอบการเชื่อมต่อฐาน drugstock
        $conn = @mysqli_connect($hostname, $username, $password, "drugstock");

        if (!$conn) {
            $error = "เชื่อมต่อฐานข้อมูลไม่สำเร็จ : " . mysqli_connect_error();
        } else {

            mysqli_set_charset($conn, "utf8mb4");

            // 🔹 เนื้อหาไฟล์ config.php (ตัด HOSXP ออกแล้ว)
            $config = "<?php
// ==================================================
// DATABASE CONFIG : DRUGSTOCK
// ==================================================
\$db_host = \"$hostname\";
\$db_user = \"$username\";
\$db_pass = \"$password\";
\$db_name = \"drugstock\";

// ===== Connect Drugstock =====
\$conn = mysqli_connect(\$db_host, \$db_user, \$db_pass, \$db_name);
if (!\$conn) {
    die('เชื่อมต่อฐาน drugstock ไม่สำเร็จ: ' . mysqli_connect_error());
}
mysqli_set_charset(\$conn, 'utf8mb4');


// ==================================================
// ดึงข้อมูลโรงพยาบาล (จาก opdconfig)
// ==================================================
\$sql = \"SELECT hospitalcode, hospitalname FROM opdconfig LIMIT 1\";
\$resHosp = mysqli_query(\$conn, \$sql);

\$hospitalcode = '-';
\$hospitalname = '-';

if (\$resHosp && mysqli_num_rows(\$resHosp) > 0) {
    \$h = mysqli_fetch_assoc(\$resHosp);
    \$hospitalcode = \$h['hospitalcode'] ?? '-';
    \$hospitalname = \$h['hospitalname'] ?? '-';
}

if (!\$resHosp) {
    die('Query opdconfig ล้มเหลว: ' . mysqli_error(\$conn));
}
?>";

            // 🔹 สร้างโฟลเดอร์ includes ถ้ายังไม่มี
            if (!is_dir("includes")) {
                mkdir("includes", 0777, true);
            }

            // 🔹 เขียนไฟล์ config.php
            if (file_put_contents("includes/config.php", $config)) {
                $success = "✅ สร้างไฟล์ includes/config.php สำเร็จแล้ว";

                mysqli_close($conn);

                // ✅ เด้งไปหน้า index.php
                header("Location: index.php");
                exit;

            } else {
                $error = "❌ ไม่สามารถเขียนไฟล์ config.php ได้";
            }

            mysqli_close($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ติดตั้งระบบคลังยา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php require 'head.php'; ?>
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">🔧 สร้างไฟล์ Config.php อัตโนมัติ</h5>
                </div>

                <div class="card-body">

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Hostname</label>
                            <input type="text" name="hostname" class="form-control" placeholder="localhost" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="root" required>
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
                                ✅ สร้างไฟล์ config
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
