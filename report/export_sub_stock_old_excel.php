<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/admin_only.php';

/* ===============================
   SESSION
================================ */
$role        = $_SESSION['role'] ?? '';
$sessionUnit = intval($_SESSION['unit_id'] ?? 0);

/* ===============================
   FILTER
================================ */
$filter_unit = intval($_GET['unit_id'] ?? 0);

$where = "WHERE 1=1";

/* staff เห็นเฉพาะหน่วยตัวเอง */
if ($role !== 'admin') {
    $where .= " AND s.unit_id = $sessionUnit";
}

/* admin เลือกกรองหน่วยบริการ */
if ($role === 'admin' && $filter_unit > 0) {
    $where .= " AND s.unit_id = $filter_unit";
}

/* ===============================
   SQL
================================ */
$sql = "
    SELECT 
        s.sub_name,
        u.unit_code,
        COALESCE(m.drug_name,'ไม่ทราบชื่อยา') AS drug_name,
        m.units AS drug_unit,
        SUM(s.remaining) AS total_remaining
    FROM sub_warehouse s
    LEFT JOIN main_warehouse m ON s.drug_id = m.id
    LEFT JOIN units u ON s.unit_id = u.unit_id
    $where
    GROUP BY s.sub_name, u.unit_code, drug_name, m.units
    HAVING total_remaining < 25
    ORDER BY total_remaining ASC
";

$res = $conn->query($sql);

/* ===============================
   STATUS FUNCTION
================================ */
function stockStatus($qty){
    if ($qty <= 10) return 'วิกฤต';
    return 'ใกล้หมด';
}

/* ===============================
   HEADER : EXPORT CSV (Excel)
================================ */
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=sub_stock_low_" . date('Ymd') . ".csv");
header("Pragma: no-cache");
header("Expires: 0");

/* UTF-8 BOM กันภาษาไทยเพี้ยน */
echo "\xEF\xBB\xBF";

/* ===============================
   REPORT TITLE
================================ */
echo "รายงานเวชภัณฑ์ยาใกล้หมด (ต่ำกว่า 25)\n";
echo "วันที่ออกรายงาน : " . date('d/m/') . (date('Y') + 543) . "\n\n";

/* ===============================
   TABLE HEADER
================================ */
echo "ลำดับ,หน่วยบริการ,คลังย่อย,ชื่อยา,หน่วยนับ,คงเหลือ,สถานะ\n";

/* ===============================
   DATA
================================ */
if ($res && $res->num_rows > 0) {

    $i = 1;
    while ($r = $res->fetch_assoc()) {

        echo $i++ . ",";
        echo '"' . $r['unit_code'] . '",';
        echo '"' . $r['sub_name'] . '",';
        echo '"' . $r['drug_name'] . '",';
        echo '"' . $r['drug_unit'] . '",';
        echo $r['total_remaining'] . ",";
        echo stockStatus((int)$r['total_remaining']) . "\n";
    }

} else {
    echo "0,ไม่พบข้อมูล,,,,,,\n";
}

exit;

?>

<!-- =====================================================
     🟢 HTML DISPLAY
===================================================== -->
<table border="1" cellpadding="6" width="100%">
    <tr>
        <td colspan="6" align="center" style="font-size:18px;font-weight:bold;">
            รายงานเวชภัณฑ์ยาใกล้หมด (ต่ำกว่า 25)
        </td>
    </tr>
    <tr>
        <td colspan="6" align="center">
            วันที่ออกรายงาน : <?= date('d/m/Y'); ?>
        </td>
    </tr>

    <tr style="background:#f2f2f2;font-weight:bold;text-align:center;">
        <td width="60">ลำดับ</td>
        <td>คลังย่อย</td>
        <td>หน่วยบริการ</td>
        <td>ชื่อยา</td>
        <td>คงเหลือ</td>
        <td>หน่วย</td>
    </tr>

<?php
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
