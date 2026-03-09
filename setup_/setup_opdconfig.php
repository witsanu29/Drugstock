<?php
require __DIR__ . '/includes/db.php';


	/* ===============================
	   1) สร้างฐานข้อมูล
	================================ */
	$conn->query("
		CREATE DATABASE IF NOT EXISTS drugstock
		CHARACTER SET utf8mb4
		COLLATE utf8mb4_unicode_ci
	");

	/* ===============================
	   2) เลือกฐานข้อมูล
	================================ */
	$conn->select_db("drugstock");

	/* ===============================
	   3) สร้างตาราง opdconfig
	================================ */
	$createTableSQL = "
	CREATE TABLE IF NOT EXISTS opdconfig (
		id INT AUTO_INCREMENT PRIMARY KEY,
		hospitalcode VARCHAR(10) NOT NULL,
		hospitalname VARCHAR(255) NOT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB
	DEFAULT CHARSET=utf8mb4
	COLLATE=utf8mb4_general_ci
	";
	$conn->query($createTableSQL);

	/* ===============================
	   4) ข้อมูลหน่วยบริการ
	================================ */
	$hospitals = [
		['code' => '02725', 'name' => 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงบ้านซึม'],
		['code' => '02726', 'name' => 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงสัมฤทธิ์พัฒนา'],
		['code' => '02730', 'name' => 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงท่าหลวง'],
		['code' => '02731', 'name' => 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงจาร์ตำรา'],
		['code' => '02735', 'name' => 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงนิคมฯ 1'],
		['code' => '02736', 'name' => 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงหนองหญ้าขาว'],
		['code' => '02737', 'name' => 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงดงน้อย'],
		['code' => '02738', 'name' => 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงดงใหญ่'],
		['code' => '02740', 'name' => 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงมะค่าระเว'],
		['code' => '02741', 'name' => 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงหนองขาม'],
		['code' => '02742', 'name' => 'โรงพยาบาลส่งเสริมสุขภาพตำบลหนองระเวียงหนองระเวียง'],
	];

	/* ===============================
	   5) โหลดค่าปัจจุบัน
	================================ */
	$current = $conn->query("SELECT * FROM opdconfig LIMIT 1")->fetch_assoc();
	$currentCode = $current['hospitalcode'] ?? '';

	/* ===============================
	   5.5) ตัวแปรข้อความ
	================================ */
	$message = '';

	/* ===============================
	   6) บันทึกข้อมูล
	================================ */
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

		$hospitalcode = $_POST['hospitalcode'] ?? '';
		$hospitalname = '';

		foreach ($hospitals as $h) {
			if ($h['code'] === $hospitalcode) {
				$hospitalname = $h['name'];
				break;
			}
		}

		if ($hospitalcode && $hospitalname) {
			
			// ให้มีข้อมูลเดียวทั้งระบบ
			$conn->query("DELETE FROM opdconfig");

			$stmt = $conn->prepare(
				"INSERT INTO opdconfig (hospitalcode, hospitalname) VALUES (?, ?)"
			);
			$stmt->bind_param("ss", $hospitalcode, $hospitalname);
			$stmt->execute();

			// ✅ บันทึกเสร็จ → เด้งไปหน้า setup_index.php
			header("Location: setup_index.php");
			exit;
		}
	}

?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตั้งค่าหน่วยบริการ OPD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">ตั้งค่าหน่วยบริการ OPD</h5>
        </div>

        <div class="card-body">

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">หน่วยบริการ</label>
                    <select name="hospitalcode" class="form-select" required>
                        <option value="">-- กรุณาเลือกหน่วยบริการ --</option>
                        <?php foreach ($hospitals as $h): ?>
                            <option value="<?= $h['code'] ?>"
                                <?= ($h['code'] === $currentCode) ? 'selected' : '' ?>>
                                <?= $h['code'] ?> - <?= $h['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-success">
                    💾 บันทึกการตั้งค่า
                </button>
            </form>

            <?= $message ?>

        </div>
    </div>
</div>

</body>
</html>
