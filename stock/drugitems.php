<?php
// ใช้ไฟล์ config.php เพื่อเชื่อมต่อ DB
require_once "../includes/config.php"; 

?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รายการยา</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<?php  require '../head2.php';  ?>
</head>
<body class="p-3">

<h4>รายการยา - <?= htmlspecialchars($hospitalname) ?> (<?= $hospitalcode ?>)</h4>

<table class="table table-striped table-hover align-middle">
    <thead class="table-dark">
        <tr>
            <th>ลำดับ</th>
            <th>ชื่อยา</th>
            <th>ขนาด</th>
            <th class="text-end">จำนวน</th>
            <th class="text-end">ราคา</th>
            <th>วันที่รับ</th>
            <th class="text-end">คงเหลือ</th>
            <th>หมายเหตุ</th>
        </tr>
    </thead>
    <tbody>
    <?php $no = 1; ?>
    <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['size'] ?? '-') ?></td>
            <td class="text-end"><?= number_format($row['quantity'] ?? 0) ?></td>
            <td class="text-end"><?= number_format($row['price'] ?? 0, 2) ?></td>
            <td><?= $row['received_date'] ?? '-' ?></td>
            <td class="text-end fw-bold text-primary"><?= number_format($row['remaining'] ?? 0) ?></td>
            <td><?= htmlspecialchars($row['note'] ?? '') ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
