<?php
require '../includes/db.php';
require '../includes/auth.php';

if ($_SESSION['role'] !== 'admin') {
	die('คุณไม่มีสิทธิ์ลบข้อมูล');
}

$id = (int)($_GET['id'] ?? 0);

// ดึงข้อมูลการใช้
$res = $conn->query("
    SELECT non_drug_id, used_qty
    FROM non_drug_usage
    WHERE id = $id
");
$data = $res->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูล");
}

// คืนสต็อก
$conn->query("
    UPDATE non_drug_warehouse
    SET remaining = remaining + {$data['used_qty']}
    WHERE id = {$data['non_drug_id']}
");

// ลบรายการใช้
$conn->query("DELETE FROM non_drug_usage WHERE id = $id");

header("Location: usage_non_drug.php");
exit;
?>
