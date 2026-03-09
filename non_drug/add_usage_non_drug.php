<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

/* ===== AUTH ===== */
if (!in_array($_SESSION['role'], ['admin','staff'])) {
    die('⛔ ไม่มีสิทธิ์เข้าถึง');
}

$role    = $_SESSION['role'];
$unit_id = (int)($_SESSION['unit_id'] ?? 0);

/* ===== ดึงชื่อหน่วยบริการ ===== */
$unit_name = '';

if ($unit_id > 0) {
	$stmtU = $conn->prepare("
		SELECT unit_name FROM units WHERE unit_id = ?
	");


    if (!$stmtU) {
        die("Prepare unit failed: " . $conn->error);
    }

    $stmtU->bind_param("i", $unit_id);
    $stmtU->execute();
    $stmtU->bind_result($unit_name);
    $stmtU->fetch();
    $stmtU->close();
}

/* ===== บันทึกการใช้ ===== */
$error = '';

if (isset($_POST['save'])) {

    $non_drug_id = (int)$_POST['non_drug_id'];
    $used_qty    = (int)$_POST['used_qty'];
    $used_date   = $_POST['used_date']; // YYYY-mm-dd
    $unit_id     = (int)($_SESSION['unit_id'] ?? 0);

    if ($non_drug_id <= 0) {
        $error = "❌ กรุณาเลือกเวชภัณฑ์";
    } elseif ($used_qty <= 0) {
        $error = "❌ จำนวนที่ใช้ไม่ถูกต้อง";
    } elseif ($unit_id <= 0) {
        $error = "❌ ไม่พบหน่วยบริการผู้ใช้";
    } else {

        $conn->begin_transaction();

        try {
            /* 🔎 ตรวจสอบคงเหลือ */
            $stmt = $conn->prepare("
                SELECT remaining
                FROM non_drug_warehouse
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $non_drug_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row || $row['remaining'] < $used_qty) {
                throw new Exception("❌ จำนวนคงเหลือไม่เพียงพอ");
            }

            /* ➕ บันทึกการใช้ (✔ unit ✔ date) */
            $stmt = $conn->prepare("
                INSERT INTO non_drug_usage
                (non_drug_id, unit_id, used_qty, used_date)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iiis",
                $non_drug_id,
                $unit_id,
                $used_qty,
                $used_date
            );
            $stmt->execute();
            $stmt->close();

            /* ➖ ตัด stock */
            $stmt = $conn->prepare("
                UPDATE non_drug_warehouse
                SET remaining = remaining - ?
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $used_qty, $non_drug_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            header("Location: usage_non_drug.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}


/* ===== ดึงรายการเวชภัณฑ์ตามสิทธิ์ ===== */
if ($role === 'admin') {

    $sql = "
        SELECT 
            n.id,
            n.item_name,
            n.unit,
            n.remaining,
            u.unit_name
        FROM non_drug_warehouse n
        LEFT JOIN units u ON n.unit_id = u.unit_id
        WHERE n.remaining > 0
        ORDER BY n.item_name
    ";
    $list = $conn->query($sql);

} else {

    $stmt = $conn->prepare("
        SELECT 
            n.id,
            n.item_name,
            n.unit,
            n.remaining,
            u.unit_name
        FROM non_drug_warehouse n
        LEFT JOIN units u ON n.unit_id = u.unit_id
        WHERE n.unit_id = ?
          AND n.remaining > 0
        ORDER BY n.item_name
    ");

    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $list = $stmt->get_result();
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
							📋 บันทึกการใช้เวชภัณฑ์มิใช่ยา
						</div>

						<div class="card-body">

						<?php if ($error): ?>
						<div class="alert alert-danger"><?= $error ?></div>
						<?php endif; ?>


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
							<input type="hidden" name="unit_id" value="<?= (int)$unit_id ?>">
						</div>
					<?php endif; ?>

					<?php if ($role === 'admin'): ?>
						<div class="col-md-6">
							<label class="form-label">หน่วยบริการ</label>
							<select name="unit_id" class="form-select" required>
								<option value="">-- เลือกหน่วยบริการ --</option>
								<?php
								$u = $conn->query("SELECT unit_id, unit_name FROM units ORDER BY unit_name");
								while ($r = $u->fetch_assoc()):
								?>
									<option value="<?= $r['id'] ?>">
										<?= htmlspecialchars($r['unit_name']) ?>
									</option>
								<?php endwhile; ?>
							</select>
						</div>
					<?php endif; ?>

					<!-- ===== เลือกเวชภัณฑ์ ===== -->
					<div class="col-md-12">
						<label class="form-label fw-semibold">เลือกเวชภัณฑ์</label>
						<select name="non_drug_id"
								id="nonDrugSelect"
								class="form-select"
								required>
							<option value="">-- เลือกเวชภัณฑ์ --</option>
							<?php while ($row = $list->fetch_assoc()): ?>
								<option value="<?= $row['id'] ?>"
										data-unit="<?= htmlspecialchars($row['unit']) ?>">
									<?= htmlspecialchars($row['item_name']) ?>
									(<?= htmlspecialchars($row['unit_name'] ?? '-') ?>
									| คงเหลือ <?= number_format($row['remaining']) ?>
									<?= htmlspecialchars($row['unit']) ?>)
								</option>
							<?php endwhile; ?>
						</select>
					</div>

					<!-- ===== จำนวนใช้ ===== -->
					<div class="col-md-6">
						<label class="form-label fw-semibold">จำนวนที่ใช้</label>
						<div class="input-group">
							<input type="number"
								   name="used_qty"
								   class="form-control"
								   min="1"
								   required>
							<span class="input-group-text">หน่วย</span>
							<input type="text"
								   id="unitField"
								   class="form-control"
								   style="max-width:120px;background:#f8f9fa"
								   readonly>
						</div>
					</div>

					<!-- ===== วันที่ใช้ ===== -->
					<div class="col-md-6">
						<label class="form-label fw-semibold">วันที่ใช้</label>
						<input type="date"
							   name="used_date"
							   class="form-control"
							   value="<?= date('Y-m-d') ?>"
							   required>
					</div>

					<!-- ===== ปุ่ม ===== -->
					<div class="col-12 d-flex justify-content-between mt-3">
						<a href="usage_non_drug.php" class="btn btn-secondary">
							⬅ กลับหน้าคลัง
						</a>
						<button type="submit" name="save" class="btn btn-success">
							💾 บันทึกการใช้
						</button>
					</div>

					</div>
					</form>

						</div>
					</div>

		</main> <!-- end main -->
		
	<script>
	document.getElementById('toggleSidebar')?.addEventListener('click', () => {
		document.getElementById('sidebar')?.classList.toggle('collapsed');
	});
	</script>

	<script>
	const select = document.getElementById('nonDrugSelect');
	const unitField = document.getElementById('unitField');

	if (select && unitField) {
		select.addEventListener('change', () => {
			const opt = select.options[select.selectedIndex];
			unitField.value = opt?.getAttribute('data-unit') || '';
		});

		window.addEventListener('DOMContentLoaded', () => {
			if (select.value) select.dispatchEvent(new Event('change'));
		});
	}
	</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
