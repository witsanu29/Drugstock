<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname   = trim($_POST["fullname"]);
    $username   = trim($_POST["username"]);
    $password   = trim($_POST["password"]);
    $department = trim($_POST["department"]);
    $hospcode   = trim($_POST["hospcode"]); // ✅ เพิ่มรับค่า
    $district   = trim($_POST["district"]);
    $province   = trim($_POST["province"]);

    // ✅ ตรวจสอบ Hospcode ต้องเป็นตัวเลข 5 หลัก
    if (!preg_match('/^[0-9]{5}$/', $hospcode)) {
        echo "<script>alert('รหัส Hospcode ต้องเป็นตัวเลข 5 หลักเท่านั้น');window.history.back();</script>";
        exit();
    }

    // จัดรูปแบบเบอร์โทร 082-4876668
    $phone_raw = preg_replace('/[^0-9]/', '', $_POST["phone"]);

    if (strlen($phone_raw) == 10) {
        $phone = substr($phone_raw, 0, 3) . "-" .
                 substr($phone_raw, 3, 7);
    } else {
        $phone = $phone_raw;
    }

    $file = "users.csv";
    $file_exists = file_exists($file);

    // ✅ ตรวจสอบ Username ซ้ำ
    if ($file_exists) {

        $rows = array_map('str_getcsv', file($file));

        foreach ($rows as $index => $row) {

            if ($index == 0) continue;

            if (isset($row[1]) && strtolower($row[1]) === strtolower($username)) {

                echo "<script>
                        alert('Username นี้มีอยู่แล้ว');
                        window.history.back();
                      </script>";
                exit();
            }
        }
    }

    $fp = fopen($file, "a");

    // ถ้ายังไม่มีไฟล์ → ใส่ BOM + Header
    if (!$file_exists) {

        fwrite($fp, "\xEF\xBB\xBF");

        fputcsv($fp, [
            "ชื่อ-สกุล",
            "Username",
            "Password",
            "หน่วยงาน",
            "Hospcode",     // ✅ เพิ่มใน header
            "อำเภอ",
            "จังหวัด",
            "เบอร์โทร"
        ]);
    }

    // เพิ่มข้อมูลใหม่
    fputcsv($fp, [
        $fullname,
        $username,
        $password,
        $department,
        $hospcode,     // ✅ เพิ่มในข้อมูล
        $district,
        $province,
        $phone
    ]);

    fclose($fp);

    header("Location: ../home.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แบบฟอร์มลงทะเบียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="icon" href="../assets/img/hospital-icon.png" type="image/png">
	
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white text-center">
            <h4>📋 แบบฟอร์มลงทะเบียนผู้ใช้งาน ระบบคลังยา</h4>
        </div>
        <div class="card-body">

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ชื่อ–สกุล :</label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Username :</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password :</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">ชื่อหน่วยงาน :</label>
                    <input type="text" name="department" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">รหัส Hospcode (5 หลัก) :</label>
                    <input type="text" name="hospcode" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">อำเภอ :</label>
                        <input type="text" name="district" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">จังหวัด :</label>
                        <input type="text" name="province" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">เบอร์โทรศัพท์ :</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">
                        💾 บันทึกข้อมูล
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>