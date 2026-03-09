<?php
session_start();

require_once '../includes/db.php';
require_once '../includes/auth.php';


/* 🔐 admin + staff เท่านั้น */
if (!in_array($_SESSION['role'], ['admin', 'staff'])) {
    die('ไม่มีสิทธิ์เข้าถึง');
}

/* 🔹 รับ id */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('ไม่พบข้อมูล');
}

/* 🔹 ดึงข้อมูลเดิม */
$stmt = $conn->prepare("
    SELECT 
        u.id,
        u.non_drug_id,
        u.used_qty,
        u.used_date,
        w.item_name,
        w.unit,
        w.remaining
    FROM non_drug_usage u
    JOIN non_drug_warehouse w ON u.non_drug_id = w.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die('ไม่พบข้อมูล');
}

/* ================== บันทึกการแก้ไข ================== */
$error = "";

if (isset($_POST['update'])) {

    $new_used_qty = (int)$_POST['used_qty'];
    $used_date    = $_POST['used_date'];

    if ($new_used_qty <= 0) {
        $error = "❌ จำนวนที่ใช้ไม่ถูกต้อง";
    } else {

        $old_used_qty = (int)$data['used_qty'];
        $non_drug_id  = (int)$data['non_drug_id'];

        /* ส่วนต่าง */
        $diff = $new_used_qty - $old_used_qty;

        $conn->begin_transaction();

        try {
            /* 🔒 lock คลัง */
            $chk = $conn->prepare("
                SELECT remaining
                FROM non_drug_warehouse
                WHERE id = ?
                FOR UPDATE
            ");
            $chk->bind_param("i", $non_drug_id);
            $chk->execute();
            $remain = (int)$chk->get_result()->fetch_assoc()['remaining'];
            $chk->close();

            /* ❌ คงเหลือไม่พอ */
            if ($remain - $diff < 0) {
                throw new Exception('❌ จำนวนคงเหลือไม่เพียงพอ');
            }

            /* 🔁 ปรับ stock */
            $updStock = $conn->prepare("
                UPDATE non_drug_warehouse
                SET remaining = remaining - ?
                WHERE id = ?
            ");
            $updStock->bind_param("ii", $diff, $non_drug_id);
            $updStock->execute();
            $updStock->close();

            /* ✏️ อัปเดตการใช้ */
            $updUsage = $conn->prepare("
                UPDATE non_drug_usage
                SET used_qty = ?, used_date = ?
                WHERE id = ?
            ");
            $updUsage->bind_param("isi", $new_used_qty, $used_date, $id);
            $updUsage->execute();
            $updUsage->close();

            $conn->commit();

			header("Location: usage_non_drug.php");
			exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>


 <?php  
require '../head2.php';
require 'bar.php';
?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">
        ✅ บันทึกการแก้ไขเรียบร้อยแล้ว
    </div>
<?php endif; ?>

	     <!-- ================= Main Content ================= -->
		<main id="mainContent" class="main-content px-4 py-4">
			<div class="d-flex align-items-center gap-3 mb-2">
                 ✏️ แก้ไขการใช้เวชภัณฑ์มิใช่ยา
				 <span class="badge bg-dark ms-2">Usage_Non_Drug</span>
                </div>

                <!-- Body -->
				<div class="card mb-4">
					<div class="card-header bg-orange text-white">
					  ️ แก้ไขการใช้เวชภัณฑ์มิใช่ยา
					</div>
					<div class="card-body">

						<?php if (!empty($error)): ?>   
							<div class="alert alert-danger"><?= $error ?></div>
						<?php endif; ?>

						<form method="post">

							<div class="mb-3">
								<label class="form-label fw-semibold">เวชภัณฑ์</label>
								<input type="text"
									   class="form-control"
									   value="<?= htmlspecialchars($data['item_name']) ?>"
									   readonly>
							</div>

							<div class="mb-3">
								<label class="form-label fw-semibold">จำนวนที่ใช้</label>
								<div class="input-group">
									<input type="number"
										   name="used_qty"
										   class="form-control"
										   min="1"
										   value="<?= $data['used_qty'] ?>"
										   required>

									<span class="input-group-text"><?= htmlspecialchars($data['unit']) ?></span>
								</div>
								<small class="text-muted">
									คงเหลือปัจจุบัน: <?= $data['remaining'] ?> <?= htmlspecialchars($data['unit']) ?>
								</small>
							</div>

							<div class="mb-3">
								<label class="form-label fw-semibold">วันที่ใช้</label>
								<input type="date"
									   name="used_date"
									   class="form-control"
									   value="<?= $data['used_date'] ?>"
									   required>
							</div>

							<div class="text-end">
								<a href="usage_non_drug.php" class="btn btn-secondary">
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
