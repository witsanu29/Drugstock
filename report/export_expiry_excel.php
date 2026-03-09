<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/admin_only.php';

/* ===== Session ===== */
$role    = $_SESSION['role'] ?? '';
$unit_id = intval($_SESSION['unit_id'] ?? 0);

/* ===== Filter ===== */
$filter_unit = intval($_GET['unit_code'] ?? 0);

$where = "WHERE 1=1";

/* ไม่ใช่ admin เห็นเฉพาะหน่วยตัวเอง */
if ($role !== 'admin') {
    $where .= " AND m.unit_id = $unit_id";
}

/* admin เลือกกรองหน่วยบริการ */
if ($role === 'admin' && $filter_unit > 0) {
    $where .= " AND m.unit_id = $filter_unit";
}

/* ===== Header สำหรับ Excel (CSV) ===== */
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=expiry_report_" . date('Ymd') . ".csv");
header("Pragma: no-cache");
header("Expires: 0");

/* ===== UTF-8 BOM กันภาษาไทยเพี้ยน ===== */
echo "\xEF\xBB\xBF";

/* ===== หัวข้อรายงาน ===== */
echo "รายงานยาใกล้หมดอายุ (ภายใน 30 วัน)\n";
echo "วันที่ออกรายงาน : " . date('d/m/') . (date('Y') + 543) . "\n\n";

/* ===== หัวตาราง ===== */
echo "ลำดับ,หน่วยบริการ,ชื่อยา,หน่วยนับ,วันที่รับ,วันหมดอายุ,เหลือ(วัน),สถานะ\n";

/* ===== SQL ===== */
$sql = "
    SELECT
        m.drug_name,
        m.units AS drug_unit,
        m.received_date,
        m.expiry_date,
        DATEDIFF(m.expiry_date, CURDATE()) AS days_left,
        u.unit_code
    FROM main_warehouse m
    LEFT JOIN units u ON m.unit_id = u.unit_id
    $where
    AND DATEDIFF(m.expiry_date, CURDATE()) BETWEEN 0 AND 30
    ORDER BY days_left ASC
";

$result = $conn->query($sql);

/* ===== ฟังก์ชัน ===== */
function statusText($days){
    if ($days <= 7)  return "เร่งด่วน";
    if ($days <= 14) return "ใกล้หมด";
    return "เฝ้าระวัง";
}

function thaiDateCSV($date){
    if(!$date) return '';
    return date('d/m/', strtotime($date)) . (date('Y', strtotime($date)) + 543);
}

/* ===== ข้อมูล ===== */
if ($result && $result->num_rows > 0) {

    $no = 1;

    while ($row = $result->fetch_assoc()) {

        $status = statusText((int)$row['days_left']);

        echo $no . ",";
        echo '"' . $row['unit_code'] . '",';
        echo '"' . $row['drug_name'] . '",';
        echo '"' . $row['drug_unit'] . '",';
        echo thaiDateCSV($row['received_date']) . ",";
        echo thaiDateCSV($row['expiry_date']) . ",";
        echo $row['days_left'] . ",";
        echo $status . "\n";

        $no++;
    }

} else {
    echo "0,ไม่พบข้อมูล,,,,,,\n";
}

exit;
