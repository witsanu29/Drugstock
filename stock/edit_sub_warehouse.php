<?php
require '../includes/db.php';
require '../includes/auth.php';

/* ======================
   ตรวจสอบสิทธิ์
====================== */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','staff'])) {
    die("ไม่มีสิทธิ์แก้ไขข้อมูล");
}

/* ======================
   รับค่า ID
====================== */
$id = (int)($_GET['edit'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    die("ID ไม่ถูกต้อง");
}

/* ======================
   UPDATE
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)$_POST['id'];
    $quantity = (int)$_POST['quantity'];
    $received_date = $_POST['received_date'] ?? null;

    $stmt = $conn->prepare("
        UPDATE sub_warehouse
        SET quantity = ?, received_date = ?
        WHERE id = ?
    ");

    $stmt->bind_param("isi", $quantity, $received_date, $id);
    $stmt->execute();

    echo "Updated rows: ".$stmt->affected_rows;
	
	header("Location: sub_warehouse.php?edit=".$id."&success=1");

    exit;
}


/* ======================
   โหลดข้อมูล
====================== */
$stmt = $conn->prepare("
    SELECT sw.*, mw.drug_name
    FROM sub_warehouse sw
    LEFT JOIN main_warehouse mw ON sw.drug_id = mw.id
    WHERE sw.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("ไม่พบข้อมูล");
}
?>



 <?php  
require '../head2.php';
require 'bar.php';
?>

	     <!-- ================= Main Content ================= -->
		<main id="mainContent" class="main-content px-4 py-4">
			<div class="d-flex align-items-center gap-3 mb-2">
                    ✏️ แก้ไขรายการคลังย่อย
					<span class="badge bg-dark ms-2">Sub Warehouse</span>
                </div>

        		<div class="card mb-4">
					<div class="card-header bg-orange text-white">
					  ️ แก้ไขข้อมูลยา
					</div>
					<div class="card-body">

                    <form method="post" >

                        <!-- ID -->
                        <input type="hidden" name="id" value="<?= $data['id'] ?>">

                        <!-- คลังย่อย -->
                        <div class="mb-3">
                            <label class="form-label">คลังย่อย</label>
                            <input type="text"
                                   class="form-control"
                                   value="<?= htmlspecialchars($data['sub_name']) ?>"
                                   readonly>
                        </div>

                        <!-- ชื่อยา -->
                        <div class="mb-3">
                            <label class="form-label">ชื่อยา</label>
                            <input type="text"
                                   class="form-control"
                                   value="<?= htmlspecialchars($data['drug_name']) ?>"
                                   readonly>
                        </div>

                        <!-- จำนวน -->
                        <div class="mb-3">
                            <label class="form-label">จำนวน</label>
                            <input type="number"
                                   name="quantity"
                                   class="form-control"
                                   min="1"
                                   value="<?= (int)$data['quantity'] ?>"
                                   required>
                        </div>

						<!-- วันที่รับ -->
						<div class="mb-3">
							<label class="form-label">วันที่รับ</label>
							<input type="date"
								   name="received_date"
								   class="form-control"
								   value="<?= !empty($data['received_date']) ? date('Y-m-d', strtotime($data['received_date'])) : '' ?>">
						</div>

                        <!-- ปุ่ม -->
                        <div class="d-flex justify-content-between">
                            <a href="sub_warehouse.php" class="btn btn-secondary">
                                ⬅ กลับหน้าคลัง
                            </a>

                            <button type="submit"
                                    name="update"
                                    class="btn btn-primary">
                                💾 บันทึก
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

</div>

				<!-- ======================
					 Modal แจ้งเตือน
				====================== -->
				<div class="modal fade" id="errorModal" tabindex="-1">
				  <div class="modal-dialog modal-dialog-centered">
					<div class="modal-content border-danger">
					  <div class="modal-header bg-danger text-white">
						<h5 class="modal-title">แจ้งเตือน</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
					  </div>
					  <div class="modal-body text-center fs-5">
						<?= htmlspecialchars($error_popup) ?>
					  </div>
					  <div class="modal-footer">
						<button class="btn btn-secondary" data-bs-dismiss="modal">
							ปิด
						</button>
					  </div>
					</div>
				  </div>
				</div>
		</main> <!-- end main -->
		
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($error_popup)) : ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    let modal = new bootstrap.Modal(
        document.getElementById('errorModal')
    );
    modal.show();
});
</script>
<?php endif; ?>
		

<script>
document.getElementById('toggleSidebar').addEventListener('click', function () {
  document.getElementById('sidebar').classList.toggle('collapsed');
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
