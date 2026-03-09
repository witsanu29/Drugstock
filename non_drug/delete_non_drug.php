<?php
require '../includes/db.php';
require '../includes/auth.php';

if ($_SESSION['role'] !== 'admin') {
    die('ไม่มีสิทธิ์ลบข้อมูล');
}

if (!in_array($_SESSION['role'], ['admin','staff'])) {
    die('ไม่มีสิทธิ์เข้าถึง');
}

if ($_SESSION['role'] === 'demo') {
    header("Location: main_non_drug.php?error=no_permission");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("DELETE FROM non_drug_warehouse WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: main_non_drug.php");
exit;
?>
