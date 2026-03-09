<?php
session_start();
require '../includes/db.php';
require '../includes/config.php';
require '../includes/auth.php';

// อนุญาตเฉพาะ admin / staff
if (!in_array($_SESSION['role'], ['admin','staff'])) {
    die('ไม่มีสิทธิ์ใช้งาน');
}

$role = $_SESSION['role'];

if (isset($_POST['import'])) {

    if (!empty($_FILES['csv_file']['tmp_name'])) {

        // 🔐 กำหนด unit_id ตามสิทธิ์
        if ($role === 'admin') {
            if (empty($_POST['unit_id'])) {
                die('กรุณาเลือกหน่วยบริการ');
            }
            $unit_id = (int)$_POST['unit_id'];
        } else {
            // staff fix unit จาก session
            $unit_id = (int)$_SESSION['unit_id'];
        }

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        fgetcsv($file); // ข้าม header

        $conn->set_charset("utf8mb4");

        $stmt = $conn->prepare("
            INSERT INTO non_drug_warehouse
            (unit_id, item_name, unit, quantity, price, received_date, expiry_date, remaining, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {

            $item_name     = $data[0];
            $unit          = $data[1];
            $quantity      = (int)$data[2];
            $price         = (float)$data[3];
            $received_date = $data[4];
            $expiry_date   = !empty($data[5]) ? $data[5] : null;
            $remaining     = $quantity;
            $note          = $data[7] ?? '';

            $stmt->bind_param(
                "issidssis",
                $unit_id,
                $item_name,
                $unit,
                $quantity,
                $price,
                $received_date,
                $expiry_date,
                $remaining,
                $note
            );

            $stmt->execute();
        }

        fclose($file);
        $stmt->close();

        echo "<script>
            alert('✅ นำเข้าไฟล์ CSV สำเร็จ');
            window.location='../non_drug/main_non_drug.php';
        </script>";
    }
}
?>



 <?php  
require '../head2.php';
require 'bar.php';
?>
	    
		<!-- ================= Main Content ================= -->
		<main id="mainContent" class="main-content px-4 py-4">
			<div class="d-flex align-items-center gap-3 mb-2">

				<!-- ฟอร์มนำเข้าข้อมูลยา -->
					<div class="container-fluid">

						<div class="card mb-4 px-4 py-4 w-100">

							<div class="card-header bg-orange text-white fs-5">
								➕ 📥 นำเข้าเวชภัณฑ์มิใช่ยา (CSV)
							</div>

                <div class="card-body">
                    <div class="alert alert-info small">
                        📌 <strong>เงื่อนไขไฟล์ CSV</strong>
                        <ul class="mb-0">
                            <li>เข้ารหัส UTF-8 (UTF-8 CSV)</li>
                            <li>รูปแบบวันที่: <code>YYYY-MM-DD</code></li>
                            <li>ไม่ต้องมีคอลัมน์ ID</li>
                        </ul>
                    </div>

                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                เลือกไฟล์ CSV
                            </label>
                            <input type="file"
                                   name="csv_file"
                                   class="form-control"
                                   accept=".csv"
                                   required>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="../non_drug/main_non_drug.php"
                               class="btn btn-secondary">
                                ⬅ กลับหน้าคลัง
                            </a>

                            <button type="submit"
                                    name="import"
                                    class="btn btn-success">
                                📥 นำเข้าไฟล์
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card-footer text-muted small text-center">
                    ระบบคลังเวชภัณฑ์มิใช่ยา | รองรับ UTF-8 / utf8mb4
                </div>
            </div>

        </div>
    </div>
</div>

		</main> <!-- end main -->


<script>
document.getElementById('toggleSidebar').addEventListener('click', function () {
  document.getElementById('sidebar').classList.toggle('collapsed');
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
