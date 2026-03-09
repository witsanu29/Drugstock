<?php
session_start();
require_once "../includes/session_check.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

// สำหรับ debugging ระหว่างพัฒนา (ถ้าใน production ให้ปิด)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ตรวจสอบว่าเข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['loginname'])) {
    header("Location: login.php");
    exit;
}

$loginname = $_SESSION['loginname'];

// เตรียม SQL
$sql = "SELECT loginname, name, entryposition, cid
        FROM opduser
        WHERE loginname = ?";

// ตรวจสอบการเชื่อมต่อก่อน
if (!isset($conn) || !$conn) {
    die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ โปรดตรวจสอบ includes/db.php");
}

// เตรียม statement และตรวจสอบความผิดพลาดจาก mysqli
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    // แสดง error ที่ชัดเจนสำหรับการ debug
    $err = $conn->error;
    // คุณอาจจะบันทึก $err ลง log แทนการ echo ใน production
    die("Prepare failed: " . htmlspecialchars($err));
}

// ผูกพารามิเตอร์ และรัน
if (!$stmt->bind_param("s", $loginname)) {
    die("bind_param failed: " . htmlspecialchars($stmt->error));
}

if (!$stmt->execute()) {
    die("Execute failed: " . htmlspecialchars($stmt->error));
}

$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;

$stmt->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>โปรไฟล์ผู้ใช้ | Hosxp_Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="icon" href="assets/img/hospital-icon.png" type="image/png">
</head>

<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background:linear-gradient(90deg,#0d6efd,#00bcd4);">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-semibold d-flex align-items-center" href="index.php">
      <i class="bi bi-hospital me-2 fs-4"></i> Hosxp_Report
    </a>
  </div>
</nav>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header text-white text-center fw-bold fs-5 rounded-top-4" style="background-color:#6f42c1;">
          <i class="bi bi-person-circle me-2"></i> โปรไฟล์ผู้ใช้
        </div>
        <div class="card-body p-4">
          <?php if ($user): ?>
            <div class="text-center mb-4">
              <i class="bi bi-person-bounding-box display-1 text-secondary"></i>
              <h5 class="mt-3"><?= htmlspecialchars($user['name']) ?></h5>
              <p class="text-muted"><?= htmlspecialchars($user['entryposition'] ?: 'ไม่ระบุตำแหน่ง') ?></p>
            </div>

            <table class="table table-bordered">
              <tr>
                <th class="bg-light" width="30%">ชื่อผู้ใช้ (Login)</th>
                <td><?= htmlspecialchars($user['loginname']) ?></td>
              </tr>
              <tr>
                <th class="bg-light">ตำแหน่ง</th>
                <td><?= htmlspecialchars($user['entryposition']) ?></td>
              </tr>
              <tr>
                <th class="bg-light">เลขบัตรประชาชน</th>
                <td><?= htmlspecialchars($user['cid']) ?></td>
              </tr>
            </table>

            <div class="text-center mt-4">
              <a href="../dashbord.php" class="btn btn-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left"></i> กลับหน้าหลัก
              </a>
            </div>
          <?php else: ?>
            <div class="alert alert-danger text-center">
              <i class="bi bi-exclamation-triangle"></i> ไม่พบข้อมูลผู้ใช้ในฐานข้อมูล
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
<?php
require_once "../footer.php"; // เชื่อมต่อ HEAD
?>
</html>
