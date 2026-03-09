<?php
require '../includes/db.php';
require '../includes/auth.php';
// require '../includes/admin_only.php';

if (!isset($_GET['id'])) {
    header("Location: daily_usage.php");
    exit;
}


$id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT unit_id
    FROM daily_usage
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    exit('ไม่พบข้อมูล');
}

// staff / demo ห้ามข้ามหน่วย
if ($_SESSION['role'] !== 'admin' &&
    $data['unit_id'] != $_SESSION['unit_id']) {
    exit('ไม่มีสิทธิ์เข้าถึง');
}

// demo ห้ามแก้ไข
if ($_SESSION['role'] === 'demo') {
    exit('DEMO ดูข้อมูลได้เท่านั้น');
}

/* ==============================
   ดึงข้อมูลเดิม
================================ */
$stmt = $conn->prepare("
    SELECT d.*, s.sub_name, s.remaining, m.drug_name
    FROM daily_usage d
    LEFT JOIN sub_warehouse s ON d.sub_id = s.id
    LEFT JOIN main_warehouse m ON s.drug_id = m.id
    WHERE d.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("ไม่พบข้อมูล");
}

/* ==============================
   บันทึกการแก้ไข
================================ */
if (isset($_POST['save'])) {

    $new_qty   = (int)$_POST['quantity_used'];
    $new_date  = $_POST['usage_date'];

    $old_qty   = (int)$data['quantity_used'];
    $sub_id    = (int)$data['sub_id'];

    $diff = $new_qty - $old_qty; // ส่วนต่าง

    // ถ้าใช้เพิ่ม ต้องเช็คสต๊อก
    if ($diff > 0 && $data['remaining'] < $diff) {
        $error = "❌ จำนวนคงเหลือไม่เพียงพอ";
    } else {

        // ปรับสต๊อก
        $stmt = $conn->prepare("
            UPDATE sub_warehouse
            SET remaining = remaining - ?
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $diff, $sub_id);
        $stmt->execute();
        $stmt->close();

        // อัปเดตการใช้ยา
        $stmt = $conn->prepare("
            UPDATE daily_usage
            SET quantity_used = ?, usage_date = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $new_qty, $new_date, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: daily_usage.php");
        exit;
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
                 ✏️ แก้ไขรายการใช้ยา
				 <span class="badge bg-dark ms-2">Daily_Usage</span>
                </div>

                <!-- Body -->
				<div class="card mb-4">
					<div class="card-header bg-orange text-white">
					  ️ แก้ไขรายการใช้ยา
					</div>
					<div class="card-body">

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post">

                        <!-- ชื่อยา -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">ชื่อยา</label>
                            <input type="text"
                                   class="form-control bg-light"
                                   value="<?= htmlspecialchars($data['drug_name']) ?>"
                                   readonly>
                        </div>

                        <!-- คลังย่อย -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">คลังย่อย</label>
                            <input type="text"
                                   class="form-control bg-light"
                                   value="<?= htmlspecialchars($data['sub_name']) ?>"
                                   readonly>
                        </div>

                        <!-- จำนวนที่ใช้ -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">จำนวนที่ใช้</label>
                            <input type="number"
                                   name="quantity_used"
                                   class="form-control"
                                   value="<?= (int)$data['quantity_used'] ?>"
                                   min="1"
                                   required>

                            <div class="form-text">
                                คงเหลือปัจจุบัน :
                                <span class="fw-bold text-success">
                                    <?= $data['remaining'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- วันที่ใช้ -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">วันที่ใช้ยา</label>
                            <input type="date"
                                   name="usage_date"
                                   class="form-control"
                                   value="<?= $data['usage_date'] ?>"
                                   required>
                        </div>

                        <!-- ปุ่ม -->
                        <div class="d-flex justify-content-between">
                            <a href="daily_usage.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> ⬅ กลับหน้าคลัง
                            </a>
                            <button type="submit" name="save" class="btn btn-warning fw-bold">
                                <i class="bi bi-save"></i> บันทึกการแก้ไข
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

