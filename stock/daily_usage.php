<?php

// ================== INIT ==================

require '../includes/db.php';
require '../includes/auth.php';
require '../includes/config.php';
require '../includes/admin_only.php';

		$unit_id = $_SESSION['unit_id'] ?? null;
		$role    = $_SESSION['role'] ?? '';

		if ($role !== 'admin' && empty($unit_id)) {
			exit('❌ ไม่พบข้อมูลหน่วยงาน (unit_id)');
		}


	/* ================== PAGINATION ================== */
	$per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
	$per_page = in_array($per_page, [100,300,500,1000,2000]) ? $per_page : 100;

	$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
	$offset = ($page - 1) * $per_page;

	/* =================================================
	   1) เพิ่มรายการใช้ยา
	================================================= */
	if (isset($_POST['add'])) {

		if ($role === 'demo') {
			exit('❌ DEMO ไม่สามารถบันทึกข้อมูลได้');
		}

		$drug_id       = (int)$_POST['drug_id'];
		$quantity_used = (int)$_POST['quantity_used'];
		$usage_date    = $_POST['usage_date'] ?? '';

		if ($drug_id <= 0 || $quantity_used <= 0 || empty($usage_date)) {
			exit('❌ ข้อมูลไม่ครบถ้วน');
		}

		// ตรวจสอบรูปแบบวันที่
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $usage_date)) {
			exit('❌ รูปแบบวันที่ไม่ถูกต้อง');
		}

		// ห้ามย้อนหลังเกิน 7 วัน
		$today  = new DateTime();
		$useDay = new DateTime($usage_date);
		if ((int)$today->diff($useDay)->format('%r%a') < -7) {
			exit('❌ ไม่อนุญาตให้บันทึกย้อนหลังเกิน 7 วัน');
		}

		// ตรวจ stock รวม
		$stmt = $conn->prepare("
			SELECT SUM(`remaining`) AS total
			FROM sub_warehouse
			WHERE drug_id = ? AND unit_id = ?
		");

		if (!$stmt) {
			die('SQL ERROR (check stock): ' . $conn->error);
		}

		$stmt->bind_param("ii", $drug_id, $unit_id);
		$stmt->execute();

		$row   = $stmt->get_result()->fetch_assoc();
		$total = (int)($row['total'] ?? 0);

		$stmt->close();

		if ($total < $quantity_used) {
			header("Location: daily_usage.php?error=stock");
			exit;
		}


		// ดึง lot แบบ FIFO
$stmt = $conn->prepare("
    SELECT 
        s.id,
        s.remaining
    FROM sub_warehouse s
    LEFT JOIN main_warehouse m ON s.drug_id = m.id
    WHERE s.drug_id = ?
      AND s.unit_id = ?
      AND s.remaining > 0
    ORDER BY 
        CASE WHEN m.expiry_date IS NULL THEN 1 ELSE 0 END,
        m.expiry_date ASC
");

if (!$stmt) {
    die('SQL ERROR (FIFO SELECT): ' . $conn->error);
}

$stmt->bind_param("ii", $drug_id, $unit_id);
$stmt->execute();
$lots = $stmt->get_result();

$need = $quantity_used;
$conn->begin_transaction();

try {

    while ($lot = $lots->fetch_assoc()) {

        if ($need <= 0) break;

        $use = min((int)$lot['remaining'], $need);

        $upd = $conn->prepare("
            UPDATE sub_warehouse
            SET remaining = remaining - ?
            WHERE id = ? AND remaining >= ?
        ");

        if (!$upd) {
            throw new Exception('SQL ERROR (UPDATE): ' . $conn->error);
        }

        $upd->bind_param("iii", $use, $lot['id'], $use);
        $upd->execute();

        if ($upd->affected_rows === 0) {
            throw new Exception('ตัดสต๊อกไม่สำเร็จ');
        }
        $upd->close();

		$ins = $conn->prepare("
			INSERT INTO daily_usage
			(unit_id, sub_id, quantity_used, usage_date)
			VALUES (?, ?, ?, ?)
		");

		if (!$ins) {
			throw new Exception('SQL ERROR (INSERT): ' . $conn->error);
		}

		$ins->bind_param(
			"iiis",
			$unit_id,
			$lot['id'],
			$use,
			$usage_date
		);

		
        $ins->execute();
        $ins->close();

        $need -= $use;
    }

    $conn->commit();
    header("Location: daily_usage.php?success=1");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die('❌ ' . $e->getMessage());
}

} // 👈👈👈 ปิด if (isset($_POST['add']))



	/* =================================================
	   2) ลบรายการใช้ยา (ADMIN)
	================================================= */
	if (isset($_GET['delete']) && $role === 'admin') {

		$id = (int)$_GET['delete'];

		$stmt = $conn->prepare("
			SELECT sub_id, quantity_used
			FROM daily_usage
			WHERE id = ?
		");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if ($row) {
			$conn->begin_transaction();

			$upd = $conn->prepare("
				UPDATE sub_warehouse
				SET remaining = remaining + ?
				WHERE id = ?
			");
			$upd->bind_param("ii", $row['quantity_used'], $row['sub_id']);
			$upd->execute();
			$upd->close();

			$del = $conn->prepare("DELETE FROM daily_usage WHERE id = ?");
			$del->bind_param("i", $id);
			$del->execute();
			$del->close();

			$conn->commit();
		}

		header("Location: daily_usage.php");
		exit;
	}


/* =================================================
   3) FILTER + QUERY
================================================= */
$where  = " WHERE 1 ";
$params = [];
$types  = "";

// วันที่เริ่ม
if (!empty($_GET['start_date'])) {
    $where   .= " AND d.usage_date >= ? ";
    $params[] = $_GET['start_date'];
    $types   .= "s";
}

// วันที่สิ้นสุด
if (!empty($_GET['end_date'])) {
    $where   .= " AND d.usage_date <= ? ";
    $params[] = $_GET['end_date'];
    $types   .= "s";
}

/* 🔒 จำกัดหน่วยของตัวเอง (เฉพาะ non-admin) */
if ($_SESSION['role'] !== 'admin') {

    if (empty($_SESSION['unit_id'])) {
        die('❌ ไม่พบข้อมูลหน่วยงาน (unit_id)');
    }

    $where   .= " AND d.unit_id = ? ";
    $params[] = (int)$_SESSION['unit_id'];
    $types   .= "i";
}


/* ===== COUNT ===== */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM daily_usage d
    $where
");

if (!$stmt) {
    die("SQL ERROR (COUNT): " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$total_rows  = (int)$stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);
$stmt->close();


/* ===== MAIN DATA ===== */
$sql = "
    SELECT 
        d.*,
        m.drug_name,
        u.unit_code,
        s.sub_name
    FROM daily_usage d
    LEFT JOIN sub_warehouse s ON d.sub_id = s.id
    LEFT JOIN main_warehouse m ON s.drug_id = m.id
    LEFT JOIN units u ON d.unit_id = u.unit_id
    $where
    ORDER BY d.usage_date DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL ERROR (MAIN): " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();



	/* ================== หน่วยงาน ================== */
	$stmt = $conn->prepare("
		SELECT unit_name, unit_code
		FROM units
		WHERE unit_id = ?
	");
	if (!$stmt) {
		die("SQL PREPARE ERROR (units): " . $conn->error);
	}

	$stmt->bind_param("i", $unit_id);
	$stmt->execute();
	$unit = $stmt->get_result()->fetch_assoc();
	$stmt->close();

	$unit_name = $unit['unit_name'] ?? '-';
	$unit_code = $unit['unit_code'] ?? '-';


	/* ======================
	   จำนวนหน้า
	====================== */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM daily_usage d
    $where
");

if (!$stmt) {
    die("SQL ERROR (count1): " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$total_rows  = (int)$stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);
$stmt->close();


?>


<?php  
require '../head2.php';
require 'bar1.php';
?>

	<!-- ================= Main Content ================= -->
	<main id="mainContent" class="main-content px-4 py-4">

		<h3 class="mb-1">💊 ใช้ยารายวัน</h3>
		<div class="text-muted mb-3">
			หน่วยงาน: <strong><?= htmlspecialchars($unit_name) ?></strong>
			(<?= htmlspecialchars($unit_code) ?>)
		</div>


			<!-- ฟอร์มใช้ยารายวัน -->

		<?php if (in_array($_SESSION['role'], ['admin','staff'])): ?>

		<?php
		$subs = $conn->prepare("
			SELECT 
				s.drug_id,
				m.drug_name,
				SUM(s.remaining) AS total_remaining,
				MIN(m.expiry_date) AS nearest_expiry
			FROM sub_warehouse s
			LEFT JOIN main_warehouse m ON s.drug_id = m.id
			WHERE s.remaining > 0
			  AND s.unit_id = ?
			GROUP BY s.drug_id, m.drug_name
			ORDER BY m.drug_name
		");
		$subs->bind_param("i", $_SESSION['unit_id']);
		$subs->execute();
		$res = $subs->get_result();


		?>

<div class="card mb-4">
    <div class="card-header bg-orange text-white">
        ➕ ใช้ยารายวัน
    </div>

    <div class="card-body">
        <form method="post"
              class="row gx-2 gy-0 align-items-end flex-nowrap">

            <!-- รายการยา -->
            <div class="col-5">
                <label class="form-label fw-semibold">รายการยา</label>
                <select name="drug_id" class="form-select" required>
                    <option value="">-- เลือกยา --</option>

                    <?php while ($s = $res->fetch_assoc()): ?>
                        <?php
                        $expireText  = '';
                        $expireClass = '';

                        if (!empty($s['nearest_expiry'])) {
                            $daysLeft = (int)(new DateTime())->diff(
                                new DateTime($s['nearest_expiry'])
                            )->format('%r%a');

                            if ($daysLeft <= 30) {
                                $expireClass = 'text-danger';
                                $expireText = $daysLeft < 0
                                    ? ' | ❌ หมดอายุแล้ว'
                                    : " | ⚠ ใกล้หมดอายุ {$daysLeft} วัน";
                            }
                        }
                        ?>
                        <option value="<?= $s['drug_id'] ?>" class="<?= $expireClass ?>">
                            <?= htmlspecialchars($s['drug_name']) ?>
                            (คงเหลือ <?= number_format($s['total_remaining']) ?>)
                            <?= $expireText ?>
                        </option>
                    <?php endwhile; ?>

                </select>
            </div>

            <!-- จำนวน -->
            <div class="col-2">
                <label class="form-label fw-semibold">จำนวน</label>
                <input type="number"
                       name="quantity_used"
                       class="form-control"
                       min="1"
                       required>
            </div>

            <!-- วันที่ -->
            <div class="col-3">
                <label class="form-label fw-semibold">วันที่ใช้</label>
                <input type="date"
                       name="usage_date"
                       class="form-control"
                       value="<?= date('Y-m-d') ?>"
                       required>
            </div>

            <!-- ปุ่ม -->
            <div class="col-2 d-grid">
                <button type="submit"
                        name="add"
                        class="btn btn-success">
                    💾 บันทึก
                </button>
            </div>

        </form>
    </div>
</div>


		<?php else: ?>
		<div class="alert alert-secondary">
			🔒 สิทธิ์ DEMO ดูข้อมูลได้เท่านั้น
		</div>
		<?php endif; ?>




            <!-- ฟอร์มกรองวันที่ -->
		<form method="get" class="row g-3 mb-4">
			<div class="col-md-3">
				<label class="form-label">วันที่เริ่มต้น</label>
				<input type="date" name="start_date" class="form-control"
					   value="<?= $_GET['start_date'] ?? '' ?>">
			</div>

			<div class="col-md-3">
				<label class="form-label">วันที่สิ้นสุด</label>
				<input type="date" name="end_date" class="form-control"
					   value="<?= $_GET['end_date'] ?? '' ?>">
			</div>

			<div class="col-md-3 align-self-end">
				<button type="submit" class="btn btn-primary">🔍 กรองข้อมูล</button>
				<a href="daily_usage.php" class="btn btn-secondary">♻ ล้างค่า</a>
			</div>
		</form>


 
    <!-- ตารางรายการยา -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            📋 รายการใช้ยารายวัน
        </div>

        <div class="card-body table-responsive">
		
			<form method="get" class="d-flex align-items-center gap-2 mb-3">
				<label class="fw-bold">แสดงต่อหน้า</label>

				<?php foreach ([100,300,500,1000,2000] as $l): ?>
					<button type="submit"
							name="limit"
							value="<?= $l ?>"
							class="btn btn-sm <?= $per_page == $l ? 'btn-primary' : 'btn-outline-primary' ?>">
						<?= $l ?>
					</button>
				<?php endforeach; ?>

				<input type="hidden" name="page" value="1">
			</form>

            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                <tr>
                    <th width="60">ลำดับ</th>
                    <th>คลังย่อย</th>
					<th>หน่วยบริการ</th>
                    <th>ชื่อยา</th>
                    <th class="text-end">จำนวนใช้</th>
                    <th width="120">วันที่ใช้</th>
                    <th width="160" class="text-center">จัดการ</th>
                </tr>
                </thead>
                <tbody>
					<?php

					$no = ($page - 1) * $per_page + 1;
					$total_quantity = 0;

					$prev_date = null;
					$daily_total = 0; // ⭐ รวมรายวัน

					while ($row = $result->fetch_assoc()):

					$total_quantity += $row['quantity_used'];

					$current_date = $row['usage_date'];

					// ⭐ ถ้าวันเปลี่ยน และไม่ใช่ครั้งแรก
					if ($prev_date !== null && $prev_date !== $current_date):
					?>
						<!-- แสดงรวมของวันก่อนหน้า -->
						<tr class="table-warning fw-bold">
							<td colspan="4" class="text-end">รวมวันที่ <?= date('d/m/', strtotime($prev_date)) . (date('Y', strtotime($prev_date)) + 543) ?></td>
							<td class="text-end"><?= number_format($daily_total) ?></td>
							<td colspan="2"></td>
						</tr>
					<?php
						$daily_total = 0; // reset ใหม่
					endif;
					?>

					<?php if ($prev_date !== $current_date): ?>
					<tr class="table-primary fw-bold">
						<td colspan="7">
							📅 วันที่ใช้ :
							<?= date('d/m/', strtotime($current_date)) .
							   (date('Y', strtotime($current_date)) + 543) ?>
						</td>
					</tr>
					<?php endif; ?>

					<tr>
						<td><?= $no++ ?></td>
						<td><?= htmlspecialchars($row['sub_name']) ?></td>
						<td><?= $row['unit_code'] ?: '-' ?></td>
						<td><?= htmlspecialchars($row['drug_name']) ?></td>
						<td class="text-end"><?= number_format($row['quantity_used']) ?></td>
						<td>
							<?= date('d/m/', strtotime($row['usage_date'])) .
							   (date('Y', strtotime($row['usage_date'])) + 543) ?>
							</td>
							<td class="text-center">
								<div class="btn-group btn-group-sm">

									<?php if (in_array($_SESSION['role'], ['admin','staff'])): ?>
										<?php if ($_SESSION['role'] === 'admin' || $row['unit_id'] == $_SESSION['unit_id']): ?>
											<a href="edit_daily_usage.php?id=<?= $row['id'] ?>"
											   class="btn btn-warning">✏️</a>
										<?php endif; ?>
									<?php endif; ?>

									<?php if ($_SESSION['role'] === 'admin'): ?>
										<a href="daily_usage.php?delete=<?= $row['id'] ?>"
										   class="btn btn-danger"
										   onclick="return confirm('ยืนยันการลบ?')">🗑</a>
									<?php endif; ?>

								</div>
							</td>
						</tr>

						<?php
						$daily_total += $row['quantity_used']; // ⭐ บวกยอดรายวัน
						$prev_date = $current_date;

						endwhile;

						// ⭐ แสดงรวมของวันสุดท้าย
						if ($prev_date !== null):
						?>
						<tr class="table-warning fw-bold">
							<td colspan="4" class="text-end">รวมวันที่ <?= date('d/m/', strtotime($prev_date)) . (date('Y', strtotime($prev_date)) + 543) ?></td>
							<td class="text-end"><?= number_format($daily_total) ?></td>
							<td colspan="2"></td>
						</tr>
						<?php endif; ?>

					</tbody>

                <tfoot>
                <tr class="table-success fw-bold">
                    <td colspan="4" class="text-center">รวมจำนวนใช้ทั้งหมด</td>
                    <td class="text-end"><?= number_format($total_quantity) ?> </td>
                    <td colspan="2"></td>
                </tr>
                </tfoot>
            </table>
			
			<div class="d-flex justify-content-between align-items-center mt-3">

				<!-- ก่อนหน้า -->
				<a class="btn btn-outline-secondary <?= $page <= 1 ? 'disabled' : '' ?>"
				   href="?page=<?= $page - 1 ?>&limit=<?= $per_page ?>">
					⬅ ก่อนหน้า
				</a>

				<div class="fw-bold">
					หน้า <?= $page ?> / <?= $total_pages ?>
				</div>

				<!-- ถัดไป -->
				<a class="btn btn-outline-secondary <?= $page >= $total_pages ? 'disabled' : '' ?>"
				   href="?page=<?= $page + 1 ?>&limit=<?= $per_page ?>">
					ถัดไป ➡
				</a>

			</div>


        </div>
    </div>

    </div>
</div>

</main>

	<script>
	const toggle = document.getElementById('toggleSidebar');
	if (toggle) {
		toggle.addEventListener('click', function () {
			document.getElementById('sidebar')?.classList.toggle('collapsed');
		});
	}
	</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
