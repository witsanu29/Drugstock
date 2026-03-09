<?php
require '../includes/db.php';
require '../includes/auth.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("ไม่มีสิทธิ์ลบข้อมูล");
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) die("ID ไม่ถูกต้อง");

$conn->begin_transaction();

/* ดึงข้อมูลคลังย่อย */
$stmt = $conn->prepare("
    SELECT drug_id, remaining 
    FROM sub_warehouse 
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    $conn->rollback();
    die("ไม่พบข้อมูล");
}

/* คืนยาเข้าคลังใหญ่ */
$stmt = $conn->prepare("
    UPDATE main_warehouse
    SET remaining = remaining + ?
    WHERE id = ?
");
$stmt->bind_param("ii", $data['remaining'], $data['drug_id']);
$stmt->execute();
$stmt->close();

/* ลบคลังย่อย */
$stmt = $conn->prepare("
    DELETE FROM sub_warehouse
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

$conn->commit();

header("Location: sub_warehouse.php");
exit;
