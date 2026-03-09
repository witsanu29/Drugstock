<?php
date_default_timezone_set("Asia/Bangkok");

// ===== ตั้งค่าฐานข้อมูล =====
$db_host = "127.0.0.1";
$db_user = "sa";
$db_pass = "sa";
$db_name = "drugstock";

// ===== เชื่อมต่อฐานข้อมูล =====
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("❌ เชื่อมต่อฐานข้อมูลไม่สำเร็จ");
}

// ===== ตั้งค่า charset =====
$conn->set_charset("utf8mb4");

// ===== Log การเชื่อมต่อ =====
function log_db($status, $message) {
    $logDir = __DIR__ . "/logs";
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . "/db.log";
    $date = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[{$date}] {$status} : {$message}" . PHP_EOL, FILE_APPEND);
}

log_db("SUCCESS", "เชื่อมต่อฐานข้อมูลสำเร็จ");