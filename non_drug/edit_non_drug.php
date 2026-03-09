<?php
require '../includes/db.php';
require '../includes/auth.php';

// 🔐 admin เท่านั้น
if (!in_array($_SESSION['role'], ['admin', 'staff'])) {
    die('ไม่มีสิทธิ์เข้าถึง');
}

if ($_SESSION['role'] === 'demo') {
    header("Location: main_non_drug.php?error=no_permission");
    exit;
}

$id = (int)($_GET['id'] ?? 0);


	// ดึงค่าเดิมก่อน
	$old_quantity  = (int)$data['quantity'];
	$old_remaining = (int)$data['remaining'];


// ดึงข้อมูลเดิม
$res = $conn->query("SELECT * FROM non_drug_warehouse WHERE id = $id");
$data = $res->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูล");
}


// บันทึกการแก้ไข
if (isset($_POST['update'])) {

    $item_name     = $_POST['item_name'];
    $unit          = $_POST['unit'];
    $price         = (float)$_POST['price'];
    $received_date = $_POST['received_date'];
    $expiry_date   = $_POST['expiry_date'] ?: null;
    $note          = $_POST['note'];

	// admin แก้ไขได้
if ($_SESSION['role'] === 'admin') {

    $quantity = (int)$_POST['quantity'];

    // คำนวณจำนวนที่ถูกใช้ไปแล้ว
    $used = $old_quantity - $old_remaining;
    if ($used < 0) $used = 0;

    // คำนวณ remaining ใหม่
    $remaining = max(0, $quantity - $used);

    $stmt = $conn->prepare("
        UPDATE non_drug_warehouse
        SET item_name = ?, unit = ?, price = ?, received_date = ?, expiry_date = ?, note = ?,
            quantity = ?, remaining = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "ssdsssiii",
        $item_name,
        $unit,
        $price,
        $received_date,
        $expiry_date,
        $note,
        $quantity,
        $remaining,
        $id
    );

} else {


    // staff แก้ไขไม่ได้
	$stmt = $conn->prepare("
		UPDATE non_drug_warehouse
		SET item_name = ?, unit = ?, price = ?, received_date = ?, expiry_date = ?, note = ?
		WHERE id = ?
	");
	$stmt->bind_param(
		"ssdsssi",
		$item_name,
		$unit,
		$price,
		$received_date,
		$expiry_date,
		$note,
		$id
	);

}

$stmt->execute();

    header("Location: main_non_drug.php");
    exit;
}

?>


 <?php  
require '../head2.php';
require 'bar.php';
?>

	     <!-- ================= Main Content ================= -->
		<main id="mainContent" class="main-content px-4 py-4">
			<div class="d-flex align-items-center gap-3 mb-2">
                 ✏️ แก้ไขเวชภัณฑ์มิใช่ยา
				 <span class="badge bg-dark ms-2">Non_Drug</span>
                </div>

                <!-- Body -->
				<div class="card mb-4">
					<div class="card-header bg-orange text-white">
					  ️ แก้ไขเวชภัณฑ์มิใช่ยา
					</div>
					<div class="card-body">

                    <form method="post">  

                        <!-- ชื่อเวชภัณฑ์ -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">ชื่อเวชภัณฑ์มิใช่ยา</label>
                            <input type="text"
                                   name="item_name"
                                   class="form-control"
                                   value="<?= htmlspecialchars($data['item_name']) ?>"
                                   required>
                        </div>


                        <!-- หน่วยนับ / ราคา -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">หน่วยนับ</label>
                                <input type="text"
                                       name="unit"
                                       class="form-control"
                                       value="<?= htmlspecialchars($data['unit']) ?>"
                                       required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">ราคา (บาท)</label>
                                <input type="number"
                                       step="0.01"
                                       name="price"
                                       class="form-control"
                                       value="<?= $data['price'] ?>">
                            </div>
                        </div>
						
						
						<!-- วันที่รับ / วันหมดอายุ -->
						<div class="row">
							<div class="col-md-6 mb-3">
								<label class="form-label fw-semibold">วันที่รับ</label>
								<input type="date"
									   name="received_date"
									   class="form-control"
									   value="<?= $data['received_date'] ?>"
									   required>
							</div>

							<div class="col-md-6 mb-3">
								<label class="form-label fw-semibold">วันหมดอายุ</label>
								<input type="date"
									   name="expiry_date"
									   class="form-control"
									   value="<?= $data['expiry_date'] ?>">
								<div class="form-text text-muted">
									* เว้นว่างได้ (ถ้าไม่มีวันหมดอายุ)
								</div>
							</div>
						</div>


                        <!-- ข้อมูลคงเหลือ (แสดงอย่างเดียว admin แก้ได้) -->
                        <div class="mb-3">
							<?php if ($_SESSION['role'] === 'admin'): ?>
								<div class="row">
									<div class="col-md-6 mb-3">
										<label class="form-label fw-semibold">จำนวนตั้งต้น (Quantity)</label>
										<input type="number"
											   name="quantity"
											   class="form-control"
											   min="0"
											   value="<?= $data['quantity'] ?>"
											   required>
									</div>

									<div class="col-md-6 mb-3">
									<label class="form-label fw-semibold">คงเหลือปัจจุบัน</label>
									<input type="text"
										   class="form-control bg-light"
										   value="<?= $data['remaining'] ?>"
										   disabled>

									<!-- ข้อความแจ้งเตือน -->
									<div class="form-text text-danger">
										* ไม่สามารถแก้ไขตัวเลขได้
									</div>
								</div>

								</div>
								<?php else: ?>
								<div class="mb-3">
									<label class="form-label fw-semibold">คงเหลือ</label>
									<input type="text"
										   class="form-control bg-light"
										   value="<?= number_format($data['remaining']) . ' ' . $data['unit'] ?>"
										   disabled>
								</div>
								<!-- ข้อความแจ้งเตือน -->
								<div class="form-text text-danger">
									* ไม่สามารถแก้ไขตัวเลขได้
								</div>
								<?php endif; ?>
								
								
						</div>


                        <!-- หมายเหตุ -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">หมายเหตุ</label>
                            <textarea name="note"
                                      class="form-control"
                                      rows="3"><?= htmlspecialchars($data['note']) ?></textarea>
                        </div>


                        <!-- ปุ่ม -->
                        <div class="d-flex justify-content-between">
                            <a href="main_non_drug.php" class="btn btn-secondary">
                                ⬅ กลับหน้าคลัง
                            </a>
                            <button type="submit" name="update" class="btn btn-warning">
                                💾 บันทึกการแก้ไข
                            </button>
                        </div>

                    </form>

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
