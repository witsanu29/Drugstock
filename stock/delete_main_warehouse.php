<?php
require '../includes/db.php';
require '../includes/auth.php'; // ตรวจ login + session

// ตรวจสิทธิ์ admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: main_warehouse.php?delete=forbidden");
    exit;
}

// ตรวจ id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: main_warehouse.php");
    exit;
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("DELETE FROM main_warehouse WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: main_warehouse.php?delete=success");
} else {
    header("Location: main_warehouse.php?delete=error");
}

$stmt->close();
$conn->close();
exit;
