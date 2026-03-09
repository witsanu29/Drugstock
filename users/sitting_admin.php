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
		
		.setting-card {
			transition: 0.3s;
			border-radius: 12px;
		}
		.setting-card:hover {
			transform: translateY(-5px);
			box-shadow: 0 10px 20px rgba(0,0,0,0.15);
		}
		
    </style>
</head>

<body>

<?php
require '../head2.php';
require 'bar.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h4 class="fw-bold">
                <i class="bi bi-gear-fill text-primary"></i>
                ตั้งค่าระบบ (Admin Settings)
            </h4>
            <div class="text-muted">
                ผู้ดูแลระบบ : <?= htmlspecialchars($admin_name) ?>
            </div>
        </div>
    </div>

		<div class="row g-4">

			<!-- หน่วยบริการ -->
			<div class="col-md-4 col-lg-3">
				<div class="card shadow-sm setting-card h-100 border-0">
					<div class="card-body text-center">
						<i class="bi bi-hospital fs-1 text-primary"></i>
						<h6 class="mt-3 fw-semibold">จัดการตารางหน่วยบริการ</h6>
						<p class="text-muted small">เพิ่ม / แก้ไข รายชื่อหน่วยงาน</p>
						<a href="unit_manage.php" class="btn btn-outline-primary btn-sm">จัดการ</a>
					</div>
				</div>
			</div>

			<!-- ผู้ใช้งานระบบ -->
			<div class="col-md-4 col-lg-3">
				<div class="card shadow-sm setting-card h-100 border-0">
					<div class="card-body text-center">
						<i class="bi bi-person-gear fs-1 text-success"></i>
						<h6 class="mt-3 fw-semibold">ผู้ใช้งานระบบ</h6>
						<p class="text-muted small">กำหนดสิทธิ์ Admin / Staff</p>
						<a href="edit_username.php" class="btn btn-outline-success btn-sm">จัดการ</a>
					</div>
				</div>
			</div>

			<!-- ตั้งค่า USER_ID -->
			<div class="col-md-4 col-lg-3">
				<div class="card shadow-sm setting-card h-100 border-0">
					<div class="card-body text-center">
						<i class="bi bi-fingerprint fs-1 text-warning"></i>
						<h6 class="mt-3 fw-semibold">ตั้งค่า USER_ID ผู้ใช้</h6>
						<p class="text-muted small">กำหนดรหัสประจำตัวผู้ใช้</p>
						<a href="user_setting_id.php" class="btn btn-outline-warning btn-sm">ตั้งค่า</a>
					</div>
				</div>
			</div>

			<!-- ลงทะเบียนผู้ใช้ -->
			<div class="col-md-4 col-lg-3">
				<div class="card shadow-sm setting-card h-100 border-0">
					<div class="card-body text-center">
						<i class="bi bi-person-plus-fill fs-1 text-info"></i>
						<h6 class="mt-3 fw-semibold">ลงทะเบียนผู้ใช้งาน</h6>
						<p class="text-muted small">สมัครสมาชิกเข้าสู่ระบบ</p>
						<a href="form_register.php" class="btn btn-outline-info btn-sm">ลงทะเบียน</a>
					</div>
				</div>
			</div>

			<!-- รายงานผู้ลงทะเบียน -->
			<div class="col-md-4 col-lg-3">
				<div class="card shadow-sm setting-card h-100 border-0">
					<div class="card-body text-center">
						<i class="bi bi-clipboard-data-fill fs-1 text-danger"></i>
						<h6 class="mt-3 fw-semibold">รายชื่อผู้ลงทะเบียน</h6>
						<p class="text-muted small">ดูรายงานรายชื่อทั้งหมด</p>
						<a href="report.php" class="btn btn-outline-danger btn-sm">ดูรายงาน</a>
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
