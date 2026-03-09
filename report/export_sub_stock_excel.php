<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

/* ================== ตัวแปร ================== */
$role      = $_SESSION['role'] ?? '';
$unit_id   = intval($_SESSION['unit_id'] ?? 0);   // สำหรับ staff
$unit_code = $_GET['unit_code'] ?? '';            // สำหรับ admin

/* ================== เงื่อนไข WHERE ================== */
$where = [];

/* staff เห็นเฉพาะหน่วยตัวเอง */
if ($role !== 'admin') {
    $where[] = "m.unit_id = $unit_id";
}

/* admin กรองตาม unit_code */
if ($role === 'admin' && $unit_code !== '') {
    $unit_code = $conn->real_escape_string($unit_code);
    $where[] = "u.unit_code = '$unit_code'";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ================== Header Excel ================== */
$filename = "รายงานคงเหลือคลังย่อย_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

/* UTF-8 BOM */
echo "\xEF\xBB\xBF";
?>

<table border="1" width="100%">

<tr>
    <th colspan="6" style="
        font-size:18px;
        font-weight:bold;
        text-align:center;
        padding:10px;
        background-color:#BDD7EE;">
        รายงานคงเหลือคลังย่อย
    </th>
</tr>

<tr>
    <th colspan="6" style="text-align:center;">
        วันที่ออกรายงาน : <?= date('d/m/') . (date('Y') + 543); ?>
    </th>
</tr>

<tr style="background-color:#D9E1F2;font-weight:bold;text-align:center;">
    <th>ลำดับ</th>
    <th>คลังย่อย</th>
    <th>หน่วยบริการ</th>
    <th>ชื่อยา</th>
    <th>คงเหลือ</th>
    <th>หน่วยนับ</th>
</tr>

<?php
$sql = "
    SELECT 
        s.sub_name,
        u.unit_code,
        m.drug_name,
        m.units AS drug_unit,
        SUM(s.remaining) AS total_remaining
    FROM sub_warehouse s
    LEFT JOIN main_warehouse m ON s.drug_id = m.id
    LEFT JOIN units u ON m.unit_id = u.unit_id
    $where_sql
    GROUP BY s.sub_name, u.unit_code, m.drug_name, m.units
    ORDER BY u.unit_code, s.sub_name, m.drug_name
";

$res = $conn->query($sql);
$i = 1;

if ($res && $res->num_rows > 0):
    while ($r = $res->fetch_assoc()):
?>
<tr>
    <td align="center"><?= $i++ ?></td>
    <td><?= htmlspecialchars($r['sub_name']) ?></td>
    <td><?= htmlspecialchars($r['unit_code']) ?></td>
    <td><?= htmlspecialchars($r['drug_name']) ?></td>
    <td align="right"><?= number_format($r['total_remaining']) ?></td>
    <td align="center"><?= htmlspecialchars($r['drug_unit']) ?></td>
</tr>
<?php endwhile; else: ?>
<tr>
    <td colspan="6" align="center">ไม่พบข้อมูล</td>
</tr>
<?php endif; ?>

</table>
<?php exit; ?>
