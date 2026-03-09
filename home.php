<?php
session_start();

if (isset($_SESSION['user_id'])) {
	header("Location: index.php");
	exit;
}
?>



<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>หน้าแรก | ระบบคลังเวชภัณฑ์ยา</title>

	<!-- Bootstrap 5 -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Bootstrap Icons -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="icon" href="assets/img/hospital-icon.png" type="image/png">
	
<style>

:root{
	/* ===== Power BI Theme ===== */
	--pb-primary:#F2C811;   /* เหลือง Power BI */
	--pb-dark:#2b2b2b;
	--pb-bg:#f5f6fa;
	--pb-card:#ffffff;
	--pb-text:#333;
}

body{
	min-height:100vh;
	font-family: 'Sarabun', sans-serif;

	/* ⭐ BG ส้มอ่อนแบบ BI */
	background: linear-gradient(
		135deg,
		#fff4e6 0%,
		#ffe8cc 50%,
		#fff9f2 100%
	);
}

/* ===== Power BI Card ===== */

.hero-card{
	border-radius:14px;
	background:var(--pb-card);
	border:none;
	box-shadow:
		0 1px 2px rgba(0,0,0,0.05),
		0 10px 30px rgba(0,0,0,0.08);
	transition:.25s;
}

.hero-card:hover{
	transform:translateY(-3px);
	box-shadow:
		0 15px 35px rgba(0,0,0,0.12);
}

/* ===== Icon Power BI ===== */

.hero-icon{
	width:90px;
	height:90px;
	border-radius:12px;
	background:var(--pb-primary);
	display:flex;
	align-items:center;
	justify-content:center;
	color:#000;
	font-size:2.5rem;
}

/* ===== Typography ===== */

.hero-title{
	font-weight:600;
	font-size:1.6rem;
	color:var(--pb-dark);
	line-height:1.5;
}

.hero-subtitle{
	font-size:1rem;
	color:#666;
}

.hero-location{
	font-size:0.95rem;
	color:#888;
}

/* ===== Button Power BI ===== */

.btn-org{
	background:var(--pb-primary);
	border:none;
	color:#000;
	border-radius:8px;
	font-weight:600;
	transition:.2s;
}

.btn-org:hover{
	background:#e6bc0f;
	color:#000;
	transform:translateY(-1px);
	box-shadow:0 6px 15px rgba(0,0,0,0.15);
}

</style>


</head>
<body class="d-flex align-items-center">

<div class="container">
	<div class="row justify-content-center">
		<div class="col-lg-6 col-md-8">

			<div class="hero-card p-5 text-center">

				<div class="d-flex justify-content-center mb-4">
					<!-- โลโก้หน่วยงาน -->
					<img src="image/logo4.png"
						 alt="โลโก้หน่วยงาน"
						 style="max-width:400px; height:auto;">
				</div>

				<h3 class="hero-title mb-4">
					ระบบบริหารจัดการเวชภัณฑ์ รพ.สต.<br>
					เพื่อลดการสูญเสีย<br>
					และเพิ่มประสิทธิภาพการเบิกจ่าย
				</h3>

				<p class="hero-subtitle text-muted mb-1">
					สำหรับ
				</p>

				<p class="hero-location text-muted mb-4">
					โรงพยาบาลส่งเสริมสุขภาพตำบล
				</p>

				<p class="hero-location text-muted mb-4">
					...
				</p>

				<a href="login.php" class="btn btn-org btn-lg px-5 py-2 fw-semibold">
					<i class="bi bi-box-arrow-in-right me-1"></i>
					เข้าสู่ระบบ
				</a>

			</div>


		</div>
	</div>
</div>

</body>
</html>
