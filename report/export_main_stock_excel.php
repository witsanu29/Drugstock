<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

/* ===== Session ===== */
$role    = $_SESSION['role'] ?? '';
$unit_id = intval($_SESSION['unit_id'] ?? 0);

/* ===== Filter ===== */
$filter_unit = intval($_GET['unit_id'] ?? 0);

$where = "WHERE 1=1";

/* ไม่ใช่ admin เห็นเฉพาะหน่วยตัวเอง */
if ($role !== 'admin') {
    $where .= " AND m.unit_id = $unit_id";
}

/* admin เลือกกรองหน่วย */
if ($role === 'admin' && $filter_unit > 0) {
    $where .= " AND m.unit_id = $filter_unit";
}

/* ===== Header สำหรับ Excel ===== */
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=รายงานคงเหลือคลังใหญ่_" . date('Ymd') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

/* กันภาษาไทยเพี้ยน */
echo "\xEF\xBB\xBF";
?>

<table border="1" width="100%">
    <thead>
        <tr>
            <th colspan="5" style="text-align:center; font-size:16px; font-weight:bold;">
                รายงานคงเหลือคลังใหญ่
            </th>
        </tr>
        <tr>
            <th colspan="5" style="text-align:center;">
                วันที่ออกรายงาน :
                <?= date('d/m/') . (date('Y') + 543); ?>
            </th>
        </tr>
        <tr style="background:#f0f0f0; font-weight:bold;">
            <th>ลำดับ</th>
            <th>หน่วยบริการ</th>
            <th>ชื่อยา</th>
            <th>คงเหลือ</th>
            <th>หน่วยนับ</th>
        </tr>
    </thead>

    <tbody>
    <?php
    $sql = "
        SELECT 
            u.unit_code,
            m.drug_name,
            m.units AS drug_unit,
            SUM(m.remaining) AS total_remaining
        FROM main_warehouse m
        LEFT JOIN units u ON m.unit_id = u.unit_id
        $where
        GROUP BY u.unit_code, m.drug_name, m.units
        ORDER BY u.unit_code, m.drug_name
    ";

    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        $no = 1;
        while ($r = $res->fetch_assoc()) {
            echo "<tr>
                <td align='center'>{$no}</td>
                <td>".htmlspecialchars($r['unit_code'])."</td>
                <td>".htmlspecialchars($r['drug_name'])."</td>
                <td align='right'>".number_format($r['total_remaining'])."</td>
                <td align='center'>".htmlspecialchars($r['drug_unit'])."</td>
            </tr>";
            $no++;
        }
    } else {
        echo "<tr>
            <td colspan='5' align='center'>ไม่มีข้อมูล</td>
        </tr>";
    }
    ?>
    </tbody>
</table>
