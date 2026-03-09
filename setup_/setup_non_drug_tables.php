<?php
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $hostname = trim($_POST['hostname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $dbname   = "drugstock";

    if ($hostname === "" || $username === "") {
        $error = "กรุณากรอก Hostname และ Username";
    } else {

        // 🔹 ทดสอบเชื่อมต่อฐานข้อมูล
        $conn = @mysqli_connect($hostname, $username, $password, $dbname);

        if (!$conn) {
            $error = "เชื่อมต่อฐานข้อมูลไม่สำเร็จ : " . mysqli_connect_error();
        } else {

            mysqli_set_charset($conn, "utf8mb4");

            /* ================================
               SQL สร้างตาราง
            ================================ */
            $sqlWarehouse = "
            CREATE TABLE IF NOT EXISTS non_drug_warehouse (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_name VARCHAR(255),
                unit VARCHAR(50),
                quantity INT,
                price DECIMAL(10,2),
                received_date DATE,
                expiry_date DATE,
                remaining INT,
                note TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_general_ci
            ";

            $sqlUsage = "
            CREATE TABLE IF NOT EXISTS non_drug_usage (
                id INT AUTO_INCREMENT PRIMARY KEY,
                non_drug_id INT,
                used_qty INT,
                used_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_non_drug
                    FOREIGN KEY (non_drug_id)
                    REFERENCES non_drug_warehouse(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_general_ci
            ";

            $ok = true;

            if (!$conn->query($sqlWarehouse)) {
                $error .= "❌ สร้างตาราง non_drug_warehouse ไม่สำเร็จ<br>";
                $error .= $conn->error . "<br>";
                $ok = false;
            }

            if (!$conn->query($sqlUsage)) {
                $error .= "❌ สร้างตาราง non_drug_usage ไม่สำเร็จ<br>";
                $error .= $conn->error . "<br>";
                $ok = false;
            }

            if ($ok) {
                $success = "🎉 สร้างตารางเวชภัณฑ์มิใช่ยาเรียบร้อยแล้ว";
            }

    // ===== สร้างสำเร็จ → redirect =====
    header("Location: setup_index.php");
    exit;
	
            mysqli_close($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สร้างฐานข้อมูลเวชภัณฑ์มิใช่ยา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    🗄️ สร้างฐานข้อมูลคลังเวชภัณฑ์มิใช่ยา
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
                            <input type="text" name="hostname" class="form-control"
                                   placeholder="เช่น localhost หรือ 192.168.100.10" required>
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
                                ✅ สร้างตารางเวชภัณฑ์มิใช่ยา
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
