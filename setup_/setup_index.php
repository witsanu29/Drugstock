<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>ตั้งค่าระบบคลังยา</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.step-card:hover {
    transform: translateY(-5px);
    transition: 0.2s;
}
</style>
<?php  require 'head.php';  ?>
</head>


<body>
<div class="container-fluid px-4 py-4">

    <div class="text-center mb-4">
        <h3 class="fw-bold">⚙️ ตั้งค่าระบบคลังยา</h3>
        <p class="text-muted">
            กรุณาดำเนินการตามขั้นตอนจากซ้ายไปขวา ก่อนเริ่มใช้งานระบบ
        </p>
    </div>

    <div class="container">
    
    <!-- ===== ROW 1 : 3 คอลัมน์ ===== -->
    <div class="row g-4 justify-content-center">

        <!-- STEP 1 -->
        <div class="col-md-4">
            <div class="card step-card shadow-sm h-100 border-0">
                <div class="card-body text-center">
                    <div class="display-6 mb-3">①</div>
                    <h5 class="fw-bold">สร้าง Database คลังเวชภัณฑ์ยา</h5>
                    <p class="text-muted small">
                        ชื่อฐาน, สร้างตารางคลังยา, คลังย่อย และการใช้ยา
                    </p>
                    <a href="setup_database.php" class="btn btn-primary w-100">
                        🗄️ สร้าง Database คลังเวชภัณฑ์ยา
                    </a>
                </div>
            </div>
        </div>

        <!-- STEP 2 -->
        <div class="col-md-4">
            <div class="card step-card shadow-sm h-100 border-0">
                <div class="card-body text-center">
                    <div class="display-6 mb-3">②</div>
                    <h5 class="fw-bold">สร้าง Database คลังเวชภัณฑ์มิใช่ยา</h5>
                    <p class="text-muted small">
                        คลังเวชภัณฑ์มิใช่ยา, ใช้เวชภัณฑ์, รายงานคงเหลือ, ใกล้หมดอายุ
                    </p>
                    <a href="setup_non_drug_tables.php" class="btn btn-success w-100">
                        📦 สร้าง Database คลังเวชภัณฑ์มิใช่ยา
                    </a>
                </div>
            </div>
        </div>

        <!-- STEP 3 -->
		<div class="col-md-4">
            <div class="card step-card shadow-sm h-100 border-0">
                <div class="card-body text-center">
                    <div class="display-6 mb-3">③</div>
                    <h5 class="fw-bold">เชื่อมตารางข้อมูล</h5>
                    <p class="text-muted small">
                        ตรวจสอบการเชื่อมต่อฐานข้อมูลสำหรับระบบ
                    </p>
                    <a href="setup_db.php" class="btn btn-success w-100">
                        🔗 เชื่อมตารางข้อมูล
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- ===== ROW 2 : 2 คอลัมน์ (กึ่งกลาง) ===== -->
    <div class="row g-4 justify-content-center mt-1">

        <!-- STEP 4 -->
                <div class="col-md-4">
            <div class="card step-card shadow-sm h-100 border-0">
                <div class="card-body text-center">
                    <div class="display-6 mb-3">④</div>
                    <h5 class="fw-bold">สร้าง Database opdconfig</h5>
                    <p class="text-muted small">
                        กำหนดหน่วยบริการสำหรับระบบ
                    </p>
                    <a href="setup_opdconfig.php" class="btn btn-success w-100">
                        🏥 สร้าง Database opdconfig
                    </a>
                </div>
            </div>
        </div>
		
        <!-- STEP 5 -->
        <div class="col-md-4">
            <div class="card step-card shadow-sm h-100 border-0">
                <div class="card-body text-center">
                    <div class="display-6 mb-3">⑤</div>
                    <h5 class="fw-bold">ตั้งค่า Config</h5>
                    <p class="text-muted small">
                        กำหนดข้อมูล สำหรับเชื่อมระบบ functions ต่างๆ
                    </p>
                    <a href="setup_config.php" class="btn btn-warning w-100 text-dark">
                        🔧 ตั้งค่า Config
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>


    <div class="alert alert-info mt-4 text-center">
        💡 เมื่อทำครบทุกขั้นตอนแล้ว ระบบจะพาเข้าสู่หน้า <strong>Dashboard</strong> อัตโนมัติ
    </div>

</div>

</body>
</html>
