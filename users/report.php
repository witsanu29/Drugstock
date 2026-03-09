<?php
session_start();
require '../includes/auth.php';
require '../includes/admin_only.php';

header('Content-Type: text/html; charset=utf-8');
ini_set('default_charset', 'UTF-8');

$file = "users.csv";
$data = [];

if (file_exists($file)) {

    $rows = array_map('str_getcsv', file($file));

    if (!empty($rows)) {
        $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0]);
    }

    // ✅ ตรวจสอบว่ามีคอลัมน์ Hospcode หรือไม่
    if (!in_array("Hospcode", $rows[0])) {
        array_splice($rows[0], 4, 0, "Hospcode"); // เพิ่ม header ตำแหน่งที่ 5
    }

    $data = $rows;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานข้อมูลผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/hospital-icon.png" type="image/png">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white text-center">
            <h4>📊 รายงานข้อมูลผู้ใช้งาน</h4>
        </div>
        <div class="card-body">

            <?php if (!empty($data)): ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ลำดับที่</th>
                                <?php foreach ($data[0] as $header): ?>
                                    <th><?php echo htmlspecialchars($header); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            for ($i = 1; $i < count($data); $i++): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>

                                    <?php 
                                    foreach ($data[$i] as $cell): ?>
                                        <td><?php echo htmlspecialchars($cell); ?></td>
                                    <?php endforeach; ?>

                                    <?php 
                                    // ถ้าข้อมูลเก่าไม่มี hospcode → เติมค่าว่าง
                                    if (count($data[$i]) < count($data[0])): ?>
                                        <td>-</td>
                                    <?php endif; ?>

                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="alert alert-warning text-center">
                    ยังไม่มีข้อมูลในระบบ
                </div>

            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="../home.php" class="btn btn-secondary">
                    ⬅ กลับหน้า Home
                </a>
            </div>

        </div>
    </div>
</div>

</body>
</html>