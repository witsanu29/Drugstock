<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===============================
   CONFIG
================================ */
$timeout = 1800; // 30 นาที

// ยังไม่ login → ออกก่อน
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/* ===============================
   ONLINE TRACKER
   (เรียกหลัง login เท่านั้น)
================================ */
$tracker = __DIR__ . '/../includes/online_track.php';
if (file_exists($tracker)) {
    require_once $tracker;
} else {
    error_log('Missing online_track.php at ' . $tracker);
}

/* ===============================
   HELPER
================================ */
function isAdmin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function isStaff() {
    return in_array($_SESSION['role'] ?? '', ['staff','demo'], true);
}

/* ===============================
   SESSION TIMEOUT
================================ */
if (
    isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity']) > $timeout
) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?timeout=1");
    exit;
}

// อัปเดตเวลาใช้งานล่าสุด
$_SESSION['last_activity'] = time();
