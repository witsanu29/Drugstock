<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* ================= ตรวจสิทธิ์เข้าไฟล์ ================= */
if (
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'staff', 'demo'])
) {
    exit('ไม่มีสิทธิ์เข้าถึง');
}

/* ================= เตรียมตัวแปรกลาง ================= */
$role    = $_SESSION['role'];
$unit_id = $_SESSION['unit_id'] ?? null;

/* ================= เงื่อนไข SQL ตามสิทธิ์ ================= */
/* ตัวอย่าง: ใช้ต่อกับ SELECT ... FROM ... */
$where = "WHERE 1=1";

if ($role !== 'admin') {

    // staff / demo ต้องมี unit_id เท่านั้น
    if (empty($unit_id)) {
        $where .= " AND 1=0"; // กันข้อมูลรั่ว
    } else {
        $where .= " AND unit_id = ".intval($unit_id);
    }
}

/* ================= บันทึกข้อมูล ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ===== demo ห้ามบันทึก ===== */
    if ($role === 'demo') {
        echo "<script>
            alert('สิทธิ์ DEMO ดูข้อมูลได้เท่านั้น ไม่สามารถบันทึกข้อมูลได้');
            history.back();
        </script>";
        exit;
    }

    /* ===== admin + staff ===== */
    // บันทึกฐานข้อมูลจริง
}
