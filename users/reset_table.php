<?php 
session_start();

/* ================== LOAD CORE ================== */
require '../includes/auth.php';        // ต้อง login
require '../includes/admin_only.php';
require '../includes/db.php';
require '../includes/config.php';

/* ================== CHECK ADMIN ================== */
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('⛔ หน้านี้สำหรับผู้ดูแลระบบ (Admin) เท่านั้น');
}

/* ================== USER INFO ================== */
$admin_name = $_SESSION['fullname'] ?? 'ผู้ดูแลระบบ';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตั้งค่าระบบ (Admin)</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/hospital-icon.png" type="image/png">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .setting-card:hover {
            transform: translateY(-3px);
            transition: 0.2s;
        }
    </style>
</head>

<body>

<?php
require '../head2.php';
require 'bar1.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h4 class="fw-bold">
                <i class="bi bi-gear-fill text-primary"></i>
                จัดการตารางข้อมูล (Admin Settings)
            </h4>
            <div class="text-muted">
                ผู้ดูแลระบบ : <?= htmlspecialchars($admin_name) ?>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <div class="col-md-4 col-lg-3">
            <div class="card shadow-sm setting-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-sliders fs-1 text-warning"></i>
                    <h6 class="mt-3 fw-semibold">จัดการ ลบตารางข้อมูล OTP</h6>
                    <p class="text-muted small">ประเภท login_otp ให้เคลียร์ทุก 1 เดือน</p>
                    <a href="clear_resettable.php" class="btn btn-outline-danger btn-sm">จัดการ</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-lg-3">
            <div class="card shadow-sm setting-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-building fs-1 text-primary"></i>
                    <h6 class="mt-3 fw-semibold">จัดการ ลบตารางข้อมูล (เฉพาะเริ่มระบบใหม่)</h6>
                    <p class="text-muted small">ตารางในระบบ ไม่จำเป็น (อย่าเข้าไปยุ่ง)  </p>
                    <a href="delete_table.php" class="btn btn-outline-warning btn-sm">จัดการ</a>
                </div>
            </div>
        </div>

    </div>
</div>

		<script>
		document.getElementById('toggleSidebar').addEventListener('click', function () {
		  document.getElementById('sidebar').classList.toggle('collapsed');
		});
		</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
