<?php
require 'includes/db.php';
date_default_timezone_set('Asia/Bangkok');

$sql = "
SELECT 
    drug_name,
    received_date,
    expiry_date,
    DATEDIFF(expiry_date, received_date) AS days_left
FROM main_warehouse
WHERE expiry_date IS NOT NULL
  AND DATEDIFF(expiry_date, received_date) BETWEEN 0 AND 30
ORDER BY days_left ASC
";

$result = $conn->query($sql);
if (!$result) {
    die("SQL Error: " . $conn->error);
}

$drug_names = [];
$days_left  = [];
$colors     = [];
$rows       = [];

while ($row = $result->fetch_assoc()) {
    $days = (int)$row['days_left'];

    if ($days <= 7) {
        $color = 'rgba(220,53,69,0.85)';      // แดง
    } elseif ($days <= 15) {
        $color = 'rgba(255,159,64,0.85)';     // ส้ม
    } else {
        $color = 'rgba(255,193,7,0.85)';      // เหลือง
    }

    $drug_names[] = $row['drug_name'];
    $days_left[]  = $days;
    $colors[]     = $color;
    $rows[]       = $row;
}

$exp_count = count($rows);
?>

<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<title>ยาใกล้หมดอายุ</title>
<?php  require 'head.php';  ?>
</head>


<body class="bg-light">
<div class="container mt-4">

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white fw-bold text-danger">
        💊 ยาใกล้หมดอายุ (≤ 30 วัน)
    </div>
    <div class="card-body">
        <?php if ($exp_count > 0): ?>
            <canvas id="expiryChart" height="220"></canvas>
        <?php else: ?>
            <div class="alert alert-success text-center">
                ✅ ไม่พบยาใกล้หมดอายุ
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="table-responsive">
<table class="table table-bordered table-hover align-middle">
    <thead class="table-dark">
        <tr>
            <th>ลำดับ</th>
            <th>ชื่อยา</th>
            <th>วันหมดอายุ</th>
            <th class="text-end">เหลืออีก (วัน)</th>
            <th>สถานะ</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($exp_count > 0): $no=1; foreach ($rows as $r): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($r['drug_name']) ?></td>
            <td><?= htmlspecialchars($r['expiry_date']) ?></td>
            <td class="text-end fw-bold"><?= $r['days_left'] ?></td>
            <td>
                <?php if ($r['days_left'] <= 7): ?>
                    <span class="badge bg-danger">เร่งด่วน</span>
                <?php elseif ($r['days_left'] <= 15): ?>
                    <span class="badge bg-warning text-dark">เฝ้าระวัง</span>
                <?php else: ?>
                    <span class="badge bg-success">ปกติ</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; else: ?>
        <tr>
            <td colspan="5" class="text-center text-muted">ไม่มีข้อมูล</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if ($exp_count > 0): ?>
new Chart(document.getElementById('expiryChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($drug_names, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            data: <?= json_encode($days_left) ?>,
            backgroundColor: <?= json_encode($colors) ?>,
            borderRadius: 10,
            barThickness: 28
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => `เหลืออีก ${ctx.raw} วัน`
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 },
                title: {
                    display: true,
                    text: 'จำนวนวันก่อนหมดอายุ'
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
