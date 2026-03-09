<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    return;
}

$file = __DIR__ . '/online.json';
$timeout    = 1800; // 30 นาที
$idle_limit = 60;   // 1 นาที
$now = time();

$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'] ?? 'unknown';
$unit_code = $_SESSION['unit_code'] ?? ' ';

/* โหลดข้อมูล */
$data = [];
if (file_exists($file)) {
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) $data = [];
}

/* ลบคน timeout */
foreach ($data as $k => $v) {
    if (!isset($v['last_active']) || ($now - $v['last_active']) > $timeout) {
        unset($data[$k]);
    }
}

/* สถานะ */
$status = 'online';
if (isset($data[$user_id]['last_active']) &&
    ($now - $data[$user_id]['last_active']) > $idle_limit) {
    $status = 'idle';
}

/* บันทึกตัวเอง */
$data[$user_id] = [
    'username'    => $username,
    'unit_code'   => $unit_code,
    'last_active' => $now,
    'status'      => $status
];

file_put_contents(
    $file,
    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);
