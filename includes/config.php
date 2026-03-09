<?php
// ==================================================
// DATABASE CONFIG : DRUGSTOCK
// ==================================================
$db_host = "127.0.0.1";
$db_user = "sa";
$db_pass = "sa";
$db_name = "drugstock";

// ===== Connect Drugstock =====
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die('เชื่อมต่อฐาน drugstock ไม่สำเร็จ: ' . mysqli_connect_error());
}
mysqli_set_charset($conn, 'utf8mb4');


// ==================================================
// ดึงข้อมูลโรงพยาบาล (จาก opdconfig)
// ==================================================
$sql = "SELECT hospitalcode, hospitalname FROM opdconfig LIMIT 1";
$resHosp = mysqli_query($conn, $sql);

$hospitalcode = '-';
$hospitalname = '-';

if ($resHosp && mysqli_num_rows($resHosp) > 0) {
    $h = mysqli_fetch_assoc($resHosp);
    $hospitalcode = $h['hospitalcode'] ?? '-';
    $hospitalname = $h['hospitalname'] ?? '-';
}

if (!$resHosp) {
    die('Query opdconfig ล้มเหลว: ' . mysqli_error($conn));
}
?>