<?php
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/config.php';

$id = (int)($_GET['id'] ?? 0);

// ================== ดึงข้อมูลเดิม ==================
$res = $conn->query("SELECT * FROM main_warehouse WHERE id = $id");
$data = $res->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูล");
}

function date_for_input($date){
    if (empty($date)) return '';
    return date('Y-m-d', strtotime($date));
}

// ================== บันทึกการแก้ไข ==================
if (isset($_POST['update'])) {

    $drug_name     = $_POST['drug_name'];
    $lot_no        = $_POST['lot_no'];
    $units         = $_POST['units'];
    $quantity      = (int)$_POST['quantity'];
    $price         = (float)$_POST['price'];
    $received_date = $_POST['received_date']; // Y-m-d
    $expiry_date   = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $note          = $_POST['note'];

    $remaining = $quantity;

    $stmt = $conn->prepare("
        UPDATE main_warehouse SET
            drug_name = ?,
            lot_no = ?,
            units = ?,
            quantity = ?,
            price = ?,
            received_date = ?,
            expiry_date = ?,
            remaining = ?,
            note = ?
        WHERE id = ?
    ");

    // ✅ ชนิดข้อมูลถูกต้องทั้งหมด
    $stmt->bind_param(
        "sssidssisi",
        $drug_name,
        $lot_no,
        $units,
        $quantity,
        $price,
        $received_date,
        $expiry_date,
        $remaining,
        $note,
        $id
    );

    if (!$stmt->execute()) {
        die("❌ UPDATE ผิดพลาด: " . $stmt->error);
    }

    $stmt->close();
    header("Location: main_warehouse.php");
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
                    ✏️ แก้ไขข้อมูลยา
                    <span class="badge bg-dark ms-2">Main Warehouse</span>
                </div>

				<div class="card mb-4">
					<div class="card-header bg-orange text-white">
					  ️ แก้ไขข้อมูลยา
					</div>
                <div class="card-body">
                    <form method="post" class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">ชื่อยา</label>
                            <input type="text" name="drug_name" class="form-control"
                                   value="<?= htmlspecialchars($data['drug_name']) ?>" required>
                        </div>

				   <!-- Lot ยา -->
					<div class="col-md-2">
						<label class="form-label fw-semibold">Lot. ยา</label>
						<input type="text" name="lot_no" class="form-control"
							   value="<?= htmlspecialchars($data['lot_no']) ?>" required>
					</div>
					
					<div class="col-md-2">
							<label class="form-label fw-semibold">หน่วย</label>
						<select name="units" class="form-select" required>
							<option value="">-- เลือกหน่วย --</option>

							<option value="เม็ด" <?= ($data['units']=='เม็ด')?'selected':'' ?>>เม็ด</option>
							<option value="แคปซูล" <?= ($data['units']=='แคปซูล')?'selected':'' ?>>แคปซูล</option>
							<option value="ขวด" <?= ($data['units']=='ขวด')?'selected':'' ?>>ขวด</option>
							<option value="หลอด" <?= ($data['units']=='หลอด')?'selected':'' ?>>หลอด</option>
							<option value="ซอง" <?= ($data['units']=='ซอง')?'selected':'' ?>>ซอง</option>
							<option value="Amp" <?= ($data['units']=='Amp')?'selected':'' ?>>Amp</option>
							<option value="แผ่น" <?= ($data['units']=='แผ่น')?'selected':'' ?>>แผ่น</option>
							<option value="แท่ง" <?= ($data['units']=='แท่ง')?'selected':'' ?>>แท่ง</option>
							<option value="แผง" <?= ($data['units']=='แผง')?'selected':'' ?>>แผง</option>
							<option value="กระปุก" <?= ($data['units']=='กระปุก')?'selected':'' ?>>กระปุก</option>
							<option value="อัน" <?= ($data['units']=='อัน')?'selected':'' ?>>อัน</option>
							<option value="Vial" <?= ($data['units']=='Vial')?'selected':'' ?>>Vial</option>
						</select>
					</div>

						 <div class="col-md-2">
							<label class="form-label fw-semibold">จำนวน</label>

							<input type="number"
								   name="quantity"
								   class="form-control"
								   value="<?= (int)$data['quantity'] ?>"
								   required
								   <?= ($_SESSION['role'] === 'admin') ? '' : 'readonly' ?>>

							<?php if ($_SESSION['role'] !== 'admin'): ?>
								<div class="form-text text-danger">
									* ไม่่สามารถแก้ไขตัวเลขได้
								</div>
							<?php endif; ?>
						</div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold">ราคา</label>
                            <input type="number" step="0.01" name="price" class="form-control"
                                   value="<?= $data['price'] ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold">วันที่รับ</label>
							<input type="date" name="received_date" class="form-control"
								   value="<?= date_for_input($data['received_date']) ?>" required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold">วันหมดอายุ</label>
							<input type="date" name="expiry_date" class="form-control"
								   value="<?= date_for_input($data['expiry_date']) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">หมายเหตุ</label>
                            <textarea name="note" rows="3"
                                      class="form-control"><?= htmlspecialchars($data['note']) ?></textarea>
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                            <a href="main_warehouse.php" class="btn btn-outline-secondary">
                                ⬅ กลับหน้าคลัง
                            </a>
                            <button type="submit" name="update" class="btn btn-success px-4">
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
