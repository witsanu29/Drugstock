<?php
session_start();

/* ===== CONFIG ===== */
$file = __DIR__ . '/includes/online.json';
$timeout = 1800; // 🔥 ต้องตรงกับ online_track.php
$now = time();

if (!file_exists($file)) {
    echo 'ไม่มีผู้ใช้งานออนไลน์';
    exit;
}

$data = json_decode(file_get_contents($file), true);
if (!is_array($data)) {
    echo 'ไม่มีผู้ใช้งานออนไลน์';
    exit;
}

/* ===== BUILD LIST ===== */
$list = [];
foreach ($data as $u) {
    if (!isset($u['last_active'])) continue;

    if (($now - $u['last_active']) <= $timeout) {

        $icon = '🟢';
        if (($now - $u['last_active']) > 60) {
            $icon = '🟡';
        }

        $list[] = $icon . ' ' . $u['unit_code'] . ' (' . $u['username'] . ')';
    }
}

echo $list ? implode(' , ', $list) : 'ไม่มีผู้ใช้งานออนไลน์';
