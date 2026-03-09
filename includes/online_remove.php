<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$file = __DIR__ . '/online.json';

if (!isset($_SESSION['user_id'])) {
    return;
}

$user_id = $_SESSION['user_id'];

if (!file_exists($file)) {
    return;
}

$data = json_decode(file_get_contents($file), true);
if (!is_array($data)) {
    return;
}

/* ลบตัวเองออก */
if (isset($data[$user_id])) {
    unset($data[$user_id]);
    file_put_contents(
        $file,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}
