<?php

session_start();
require '../includes/db.php';
require '../includes/auth.php';

	/* unit ที่ login */
	$role    = $_SESSION['role'];     // admin | staff
$unit_id = $_SESSION['unit_id'];  // unit ของ staff

if (!in_array($_SESSION['role'], ['admin','staff'])) {
    die('ไม่มีสิทธิ์เข้าถึง');
}

	/* ดึงชื่อหน่วยบริการ */
	if ($unit_id) {
    $stmtU = $conn->prepare(
        "SELECT unit_name FROM units WHERE unit_id = ?"
    );

    if (!$stmtU) {
        die("Prepare unit failed: " . $conn->error);
    }

    $stmtU->bind_param("i", $unit_id);
    $stmtU->execute();
    $stmtU->bind_result($unit_name);
    $stmtU->fetch();
    $stmtU->close();
}

	 // บันทึกข้อมูล
	if (isset($_POST['save'])) {

    $item_name     = $_POST['item_name'];
    $unit          = $_POST['unit'];
    $quantity      = (int)$_POST['quantity'];
    $price         = (float)$_POST['price'];
    $received_date = $_POST['received_date'];
    $expiry_date   = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $note          = $_POST['note'];

    // admin เลือกหน่วย / staff ใช้ session
    if ($role === 'admin') {
        $unit_id = (int)$_POST['unit_id'];
    } else {
        $unit_id = (int)$_SESSION['unit_id'];
    }

		$stmt = $conn->prepare("
			INSERT INTO non_drug_warehouse
			(unit_id, item_name, unit, quantity, price, received_date, expiry_date, remaining, note)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
		");

		if (!$stmt) {
			die("Prepare failed: " . $conn->error);
		}


			// ✅ type ถูกต้อง 100%
		$stmt->bind_param(
			"issidssis",
			$unit_id,          // i
			$item_name,        // s
			$unit,             // s
			$quantity,         // i
			$price,            // d
			$received_date,    // s
			$expiry_date,      // s (nullable ได้)
			$quantity,         // i  👉 remaining = quantity
			$note              // s
		);

		$stmt->execute();
		$stmt->close();

    header("Location: main_non_drug.php?success=1");
    exit;
}

?>

<?php  
require '../head2.php';
require 'bar.php';
?>


	<!-- ================= Main Content ================= -->
			<main id="mainContent" class="main-content px-4 py-4">

			<div class="card shadow-lg border-0">
				<div class="card-header bg-orange text-white">
					🧴 เพิ่มเวชภัณฑ์มิใช่ยา
				</div>

				<div class="card-body">
					<form method="post">

					<div class="row g-3">

						<!-- ===== หน่วยบริการ ===== -->

						<?php if ($role !== 'admin'): ?>
							<div class="col-md-6">
								<label class="form-label">หน่วยบริการ</label>
								<input type="text"
									   class="form-control"
									   value="<?= htmlspecialchars($unit_name) ?>"
									   readonly>

								<!-- 🔴 ส่ง unit_id แบบ hidden -->
								<input type="hidden" name="unit_id" value="<?= (int)$unit_id ?>">
							</div>
						<?php endif; ?>

					<?php if ($_SESSION['role'] === 'admin'): ?>
					<div class="mb-3">
						<label class="form-label">หน่วยบริการ</label>
						<select name="unit_id" class="form-select" required>
							<option value="">-- เลือกหน่วยบริการ --</option>
							<?php
							$u = $conn->query("SELECT unit_id, unit_name FROM units ORDER BY unit_name");
							while ($r = $u->fetch_assoc()):
							?>
								<option value="<?= $r['unit_id'] ?>">
									<?= htmlspecialchars($r['unit_name']) ?>
								</option>
							<?php endwhile; ?>
						</select>
					</div>
					<?php endif; ?>


						<!-- ===== ข้อมูลเวชภัณฑ์ ===== -->

						<div class="col-md-6">
							<label class="form-label">ชื่อเวชภัณฑ์มิใช่ยา</label>
							<input type="text" name="item_name" class="form-control" required>
						</div>

						<div class="col-md-3">
							<label class="form-label">หน่วยนับ</label>
							<input type="text" name="unit" class="form-control" required>
						</div>

						<div class="col-md-3">
							<label class="form-label">จำนวนรับเข้า</label>
							<input type="number" name="quantity" class="form-control" min="1" required>
						</div>

						<div class="col-md-3">
							<label class="form-label">ราคา / หน่วย</label>
							<input type="number" step="0.01" name="price" class="form-control">
						</div>

						<div class="col-md-3">
							<label class="form-label">วันที่รับ</label>
							<input type="date" name="received_date" class="form-control" required>
						</div>

						<div class="col-md-3">
							<label class="form-label">วันหมดอายุ</label>
							<input type="date" name="expiry_date" class="form-control">
						</div>

						<div class="col-md-12">
							<label class="form-label">หมายเหตุ</label>
							<textarea name="note" class="form-control" rows="3"></textarea>
						</div>

					</div>

					<hr>

					<div class="d-flex justify-content-between">
						<a href="main_non_drug.php" class="btn btn-secondary">
							⬅ กลับหน้าคลัง
						</a>
						<button type="submit" name="save" class="btn btn-success px-4">
							💾 บันทึกข้อมูล
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
