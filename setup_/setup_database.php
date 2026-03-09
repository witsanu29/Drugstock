<?php
// ===== รับค่าจากฟอร์ม =====
$host = $_POST['hostname'] ?? '';
$user = $_POST['username'] ?? '';
$pass = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สร้างฐานข้อมูล drugstock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<?php  require 'head.php';  ?>
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
            🗄️ สร้างฐานข้อมูลระบบคลังยา (ใส่ข้อมูล SERVER ที่จะเชื่อม)
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Hostname</label>
                    <input type="text" name="hostname" class="form-control" placeholder="เช่น 192.168.100.10" required>
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
</div>
</div>
</div>

</body>
</html>
<?php
exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // ===== เชื่อมต่อ MySQL =====
    $conn = new mysqli($host, $user, $pass);
    $conn->set_charset("utf8mb4");

    // ===== สร้างฐานข้อมูล =====
    $conn->query("
        CREATE DATABASE IF NOT EXISTS drugstock
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
    ");

    // เลือกฐานข้อมูล
    $conn->select_db("drugstock");

	// ===== ตาราง users =====
	$conn->query("
		CREATE TABLE IF NOT EXISTS users (
			id INT AUTO_INCREMENT PRIMARY KEY,
			username VARCHAR(50) NOT NULL UNIQUE,
			fullname VARCHAR(100) NOT NULL,
			role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
			password VARCHAR(255) NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP
		)
		ENGINE=InnoDB
		DEFAULT CHARSET=tis620
		COLLATE=tis620_thai_ci;
	");


// ===== เพิ่ม admin เริ่มต้น (ถ้ายังไม่มี) =====
$admin_password = password_hash('admin01', PASSWORD_BCRYPT);

$conn->query("
    INSERT IGNORE INTO users (username, fullname, role, password)
    VALUES (
        'admin',
        'ผู้ดูแลระบบ',
        'admin',
        '$admin_password'
    )
");

    // ===== ตาราง main_warehouse =====
    $conn->query("
        CREATE TABLE IF NOT EXISTS main_warehouse (
            id INT AUTO_INCREMENT PRIMARY KEY,
            drug_name VARCHAR(255) NOT NULL,
            units ENUM(
				'เม็ด',
				'แคปซูล',
				'ขวด',
				'หลอด',
				'ซอง',
				'Amp',
				'แผ่น',
				'แท่ง',
				'แผง',
				'กระปุก',
				'อัน',
				'Vial'
			) NOT NULL DEFAULT 'เม็ด',
            quantity INT NOT NULL,
            price DECIMAL(10,2),
            received_date DATE,
			expiry_date DATE,
            remaining INT,
            note TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // ===== ตาราง sub_warehouse =====
    $conn->query("
        CREATE TABLE IF NOT EXISTS sub_warehouse (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sub_name VARCHAR(100) NOT NULL,
            drug_id INT NOT NULL,
            quantity INT NOT NULL,
            received_date DATE,
            remaining INT,
            FOREIGN KEY (drug_id) REFERENCES main_warehouse(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // ===== ตาราง daily_usage =====
    $conn->query("
        CREATE TABLE IF NOT EXISTS daily_usage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sub_id INT NOT NULL,
            quantity_used INT NOT NULL,
            usage_date DATE NOT NULL,
            FOREIGN KEY (sub_id) REFERENCES sub_warehouse(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // ===== สร้างสำเร็จ → redirect =====
    header("Location: setup_index.php");
    exit;

} catch (mysqli_sql_exception $e) {
    echo "<h3 style='color:red'>❌ เกิดข้อผิดพลาด</h3>";
    echo $e->getMessage();
}
