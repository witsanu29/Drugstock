<?php
session_start();
// ================== INIT ==================

require '../includes/db.php';
require '../includes/auth.php';
require '../includes/config.php';
require '../includes/admin_only.php';


// จำนวนรายการต่อหน้า (default = 100)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$allow_limits = [100, 300, 500, 1000, 2000];
if (!in_array($limit, $allow_limits)) {
	$limit = 100;
}

// หน้าปัจจุบัน
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;


$unit_id = $_SESSION['unit_id']; // unit ของผู้ใช้ที่ login
$unit_code = $_GET['unit_code'] ?? '';
$role      = $_SESSION['role'] ?? '';
$unit_id   = $_SESSION['unit_id'] ?? null;


// ================== FUNCTION ==================
function thai_date($date){
    if(!$date) return '';
    $t = strtotime($date);
    return date('d/m/', $t) . (date('Y',$t)+543);
}

// ================== FILTER DATE ==================
$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';


/* ==================================================
   ADD SUB WAREHOUSE
================================================== */

$error_popup = '';

if (isset($_POST['add'])) {

    $sub_name      = $_POST['sub_name'];
    $drug_id       = (int)$_POST['drug_id'];
    $quantity      = (int)$_POST['quantity'];
    $received_date = $_POST['received_date'];

    // unit_id มาจาก session เท่านั้น
    $unit_id = $_SESSION['unit_id'];

    $conn->begin_transaction();

	$stmt = $conn->prepare("
		UPDATE main_warehouse 
		SET remaining = remaining - ?
		WHERE id = ? 
		  AND unit_id = ?
		  AND remaining >= ?
	");
	$stmt->bind_param("iisi", $quantity, $drug_id, $unit_id, $quantity);

    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        $conn->rollback();
        $error_popup = "❌ จำนวนยาในคลังใหญ่ไม่เพียงพอ";
    }
    $stmt->close();

    // ✅ ถ้าไม่ error ถึงจะเพิ่มคลังย่อย
    if ($error_popup === '') {

		$stmt = $conn->prepare("
			INSERT INTO sub_warehouse
			( unit_id, sub_name, drug_id, quantity, received_date, remaining)
			VALUES (?,?,?,?,?,?)
		");
		$stmt->bind_param(
			"ssiisi",
			$unit_id,
			$sub_name,
			$drug_id,
			$quantity,
			$received_date,
			$quantity
		);

        $stmt->execute();
        $stmt->close();

        $conn->commit();

        header("Location: sub_warehouse.php");
        exit;
    }
}


/* ==================================================
   EDIT SUB WAREHOUSE (ADMIN / STAFF)
================================================== */
if (isset($_POST['update'])) {
    $unit_id = $_SESSION['unit_id']; // ✅ ถูก
		
		if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','staff'])) {
			die("ไม่มีสิทธิ์แก้ไขข้อมูล");
		}

		$id       = (int)$_POST['id'];
		$quantity = (int)$_POST['quantity'];

    // ดึงข้อมูลเดิม
		$stmt = $conn->prepare("
			SELECT drug_id, quantity, remaining
			FROM sub_warehouse
			WHERE id = ? AND unit_id = ?
		");
		$stmt->bind_param("is", $id, $unit_id);

		$stmt->execute();
		$old = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if (!$old) {
			die("ไม่พบข้อมูล");
		}

    // คำนวณส่วนต่าง
		$diff = $quantity - $old['quantity'];

		$conn->begin_transaction();

		// ปรับคลังใหญ่
		$stmt = $conn->prepare("
			UPDATE main_warehouse
			SET remaining = remaining - ?
			WHERE id = ? AND remaining >= ?
		");
		$stmt->bind_param("iii", $diff, $old['drug_id'], $diff);
		$stmt->execute();

		  if ($diff > 0 && $stmt->affected_rows === 0) {
			$conn->rollback();
			die("คลังใหญ่ไม่เพียงพอ");
		}

		$stmt->close();

    // ปรับคลังย่อย
		$stmt = $conn->prepare("
			UPDATE sub_warehouse
			SET quantity = ?, remaining = remaining + ?
			WHERE id = ? AND unit_id = ?
		");
		$stmt->bind_param(
			"iiis",
			$quantity,
			$diff,
			$id,
			$unit_id
		);

		$stmt->execute();
		$stmt->close();

		$conn->commit();

		header("Location: sub_warehouse.php");
		exit;
	}

		$stmt = $conn->prepare("
			SELECT id, drug_name, remaining, expiry_date
			FROM main_warehouse
			WHERE unit_id = ?
			  AND remaining > 0
			ORDER BY drug_name
		");
		$stmt->bind_param("s", $unit_id);
		$stmt->execute();
		$result_drug = $stmt->get_result();


	/* ==================================================
	   QUERY DATA
	================================================== */

	$where  = "1=1";
	$params = [];
	$types  = "";

	$role     = $_SESSION['role'] ?? '';
	$unit_id  = $_SESSION['unit_id'] ?? null;
	$unit_code = $_GET['unit_code'] ?? '';
	$from_date = $_GET['from_date'] ?? '';
	$to_date   = $_GET['to_date'] ?? '';

	/* 🔒 staff / demo เห็นเฉพาะหน่วยตัวเอง */
	if ($role !== 'admin') {
		$where   .= " AND s.unit_id = ?";
		$params[] = $unit_id;
		$types   .= "i";
	}

	/* 👑 admin เลือกกรองหน่วยบริการ */
	if ($role === 'admin' && !empty($unit_code)) {
		$where   .= " AND u.unit_code = ?";
		$params[] = $unit_code;
		$types   .= "s";
	}

	/* 📅 กรองวันที่ */
	if (!empty($from_date)) {
		$where   .= " AND s.received_date >= ?";
		$params[] = $from_date;
		$types   .= "s";
	}

	if (!empty($to_date)) {
		$where   .= " AND s.received_date <= ?";
		$params[] = $to_date;
		$types   .= "s";
	}

	/* ================= SQL ================= */
	$sql = "
		SELECT 
			s.id,
			s.sub_name,
			s.quantity,
			s.remaining,
			s.received_date,
			m.drug_name,
			u.unit_code
		FROM sub_warehouse s
		JOIN main_warehouse m ON s.drug_id = m.id
		JOIN units u ON s.unit_id = u.unit_id
		WHERE $where
		ORDER BY s.received_date DESC
		LIMIT $limit OFFSET $offset
	";
	$result = $conn->query($sql);

	$stmt = $conn->prepare($sql);
	if (!$stmt) {
		die("Prepare failed: " . $conn->error);
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
	$total_sql = "SELECT COUNT(*) AS total FROM sub_warehouse";
	$total_result = $conn->query($total_sql)->fetch_assoc();
	$total_rows = $total_result['total'];
	$total_pages = ceil($total_rows / $limit);


?>


<?php  
require '../head2.php';
require 'bar0.php';
?>

	   <!-- ================= Main Content ================= -->
		<main id="mainContent" class="main-content px-4 py-4">
		<h3>📦 คลังย่อย</h3>
		<div class="text-muted mb-3">
			หน่วยงาน: <strong><?= htmlspecialchars($unit_name) ?></strong>
			(<?= htmlspecialchars($unit_code) ?>)
		</div>

		 <!-- ฟอร์มเพิ่มยา -->
				  <div class="card mb-4">
					<div class="card-header bg-orange text-white">
					  ➕ เพิ่มรายการยาเข้าคลังย่อย
					</div>
					<div class="card-body">
					
					<form method="post" class="row g-3 mb-4">
						  <div class="row align-items-end g-2">
							<div class="col-md-2">
								<label class="form-label fw-semibold">ห้องจ่ายยา (อัตโนมัติ)</label>
								<input type="text" name="sub_name" class="form-control"
									   value="ห้องจ่ายยา" readonly>
							</div>
							
							<input type="hidden" name="unit_id" value="<?= $_SESSION['unit_id'] ?>">

							<div class="col-md-3">
								<label class="form-label fw-semibold">รายการยา</label>
								<select name="drug_id" class="form-select" required>
									<option value="">-- เลือกยา --</option>

									<?php while ($d = $result_drug->fetch_assoc()): ?>

										<?php
										$expireText = '';
										$expireClass = '';

										if (!empty($d['expiry_date'])) {
											$today  = new DateTime();
											$expire = new DateTime($d['expiry_date']);
											$daysLeft = (int)$today->diff($expire)->format('%r%a');

											if ($daysLeft <= 30) {
												$expireClass = 'text-danger';
												if ($daysLeft < 0) {
													$expireText = " | หมดอายุแล้ว";
												} else {
													$expireText = " | ใกล้หมดอายุ {$daysLeft} วัน";
												}
											}
										}
										?>

										<option value="<?= $d['id'] ?>" class="<?= $expireClass ?>">
											<?= htmlspecialchars($d['drug_name']) ?>
											(คงเหลือ <?= number_format($d['remaining']) ?>)
											<?= $expireText ?>
										</option>

									<?php endwhile; ?>

								</select>
							</div>

							<div class="col-md-1">
								<label class="form-label fw-semibold">จำนวน</label>
								<input type="number" name="quantity" class="form-control"
									   min="1" required>
							</div>

							<div class="col-md-2">
								<label class="form-label fw-semibold">วันที่รับ</label>
								<input type="date" name="received_date" class="form-control" required>
							</div>

							<div class="col-md-2">
								<button type="submit" name="add"
										class="btn btn-success w-100 px-3">
									💾 รับเข้าคลังย่อย
								</button>
							</div>
						</div>

					</form>


				<!-- ฟอร์มกรองวันที่รับ -->
				<form method="get" class="row align-items-end g-2 mb-3">

					<div class="col-md-3">
						<label class="form-label">วันที่รับ (เริ่ม)</label>
						<input type="date" name="from_date" class="form-control"
							   value="<?= $_GET['from_date'] ?? '' ?>">
					</div>

					<div class="col-md-3">
						<label class="form-label">วันที่รับ (สิ้นสุด)</label>
						<input type="date" name="to_date" class="form-control"
							   value="<?= $_GET['to_date'] ?? '' ?>">
					</div>

					<!-- 🔽 กรองรหัสหน่วยงาน -->
					<?php if ($_SESSION['role'] === 'admin'): ?>
					<div class="col-md-3">
						<label class="form-label">หน่วยบริการ</label>
						<select name="unit_code" class="form-select">
							<option value="">-- ทุกหน่วยบริการ --</option>
							<?php
							$u = $conn->query("
								SELECT unit_code, unit_name 
								FROM units 
								ORDER BY unit_code
							");
							while ($r = $u->fetch_assoc()):
							?>
								<option value="<?= htmlspecialchars($r['unit_code']) ?>"
									<?= ($_GET['unit_code'] ?? '') === $r['unit_code'] ? 'selected' : '' ?>>
									<?= htmlspecialchars($r['unit_code']) ?> - <?= htmlspecialchars($r['unit_name']) ?>
								</option>
							<?php endwhile; ?>
						</select>
					</div>
					<?php endif; ?>
				
					<div class="col-md-3 d-flex align-items-end">
						<button type="submit" class="btn btn-primary me-2">
							🔍 กรองข้อมูล
						</button>
						<a href="?" class="btn btn-secondary">
							🔄 ล้างค่า
						</a>
					</div>

				</form>
				
			</div>
		</div>

				
		<!-- ตารางรายการยา -->
			<div class="card">
				<div class="card-header bg-secondary text-white">
				  📋 รายการยาในคลังย่อย
				</div>
				
				<div class="card-body table-responsive">
				
				<form method="get" class="mb-3">
					<label class="fw-bold">แสดงต่อหน้า:</label>
					<select name="limit" class="form-select d-inline w-auto" onchange="this.form.submit()">
						<?php foreach ([100,300,500,1000,2000] as $l): ?>
							<option value="<?= $l ?>" <?= $limit==$l?'selected':'' ?>>
								<?= number_format($l) ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input type="hidden" name="page" value="1">
				</form>
				
				<table class="table table-bordered table-striped">
					<thead>
					<tr>
						<th>ลำดับ</th>
						<th>คลังย่อย</th>
						<th>หน่วยบริการ</th>
						<th>ชื่อยา</th>
						<th>จำนวน</th>
						<th>วันที่รับ</th>
						<th>คงเหลือ</th>
						<th>จัดการ</th>
					</tr>
					</thead>
					<tbody>
					<?php 
					$no = ($page - 1) * $limit + 1;
					$total_remaining = 0; // 🔹 ตัวแปรรวมคงเหลือ

					while ($row = $result->fetch_assoc()): 
						$total_remaining += (int)$row['remaining']; // 🔹 รวมยอด
					?>
					<tr>
						<td><?= $no++ ?></td>
						<td><?= htmlspecialchars($row['sub_name']) ?></td>
						<td><?= $row['unit_code'] ?: '-' ?></td>
						<td><?= htmlspecialchars($row['drug_name']) ?></td>
						<td><?= $row['quantity'] ?></td>
						<td><?= thai_date($row['received_date']) ?></td>
						<td class="text-end"><?= number_format($row['remaining']) ?></td> 
							<?php $role = $_SESSION['role'] ?? ''; ?>
						<td>
							<?php if ($role === 'admin'): ?>

								<a href="edit_sub_warehouse.php?edit=<?= (int)$row['id'] ?>"
								   class="btn btn-sm btn-warning">
								   ✏️ แก้ไข
								</a>

								<form action="delete_sub_warehouse.php"
									  method="post"
									  class="d-inline"
									  onsubmit="return confirm('ลบรายการนี้?');">

									<input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

									<button type="submit"
											class="btn btn-sm btn-danger">
										🗑 ลบ
									</button>
								</form>

							<?php elseif ($role === 'staff'): ?>

								<a href="edit_sub_warehouse.php?edit=<?= (int)$row['id'] ?>"
								   class="btn btn-sm btn-warning">
								   ✏️ แก้ไข
								</a>

							<?php elseif ($role === 'demo'): ?>
								<!-- demo ดูได้อย่างเดียว -->
								<span class="text-muted">🔒 ดูข้อมูลเท่านั้น</span>

							<?php endif; ?>
						</td>
						</tr>
					<?php endwhile; ?>

					<!-- 🔹 แถวสรุปยอด -->
					<tr class="table-success fw-bold">
						<td colspan="6" class="text-end">รวมคงเหลือทั้งหมด</td>
						<td class="text-end"><?= number_format($total_remaining) ?></td>
						<td></td>
					</tr>

					</tbody>
					</table>
								
						
						<nav>
					<ul class="pagination justify-content-center">

						<!-- ก่อนหน้า -->
						<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
							<a class="page-link"
							   href="?page=<?= $page-1 ?>&limit=<?= $limit ?>">
							   ⬅️ ก่อนหน้า
							</a>
						</li>

						<li class="page-item disabled">
							<span class="page-link">
								หน้า <?= $page ?> / <?= $total_pages ?>
							</span>
						</li>

						<!-- ถัดไป -->
						<li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
							<a class="page-link"
							   href="?page=<?= $page+1 ?>&limit=<?= $limit ?>">
							   ถัดไป ➡️
							</a>
						</li>

					</ul>
				</nav>

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
