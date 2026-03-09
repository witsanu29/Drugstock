<?php
session_start();
// ================== INIT ==================

require '../includes/db.php';
require '../includes/auth.php';
require '../includes/config.php';
require '../includes/admin_only.php';


// ======================
// Pagination setting
// ======================
$per_page_list = [100,300,500,1000,2000];

// จำนวนแถวต่อหน้า
$limit = (isset($_GET['limit']) && in_array((int)$_GET['limit'], $per_page_list))
    ? (int)$_GET['limit']
    : 100;

// หน้าปัจจุบัน
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;


/* ======================
   หน่วยยา (มาตรฐาน)
====================== */
$UNIT_LIST = [
    'เม็ด','แคปซูล','ขวด','หลอด','ซอง',
    'Amp','แผ่น','แท่ง','แผง','กระปุก','อัน','Vial'
];

/* ======================
   เพิ่มข้อมูลยาเข้าคลัง
====================== */
if (isset($_POST['add'])) {

    $unit_id       = $_SESSION['unit_id'];
    $drug_name     = trim($_POST['drug_name']);
	$lot_no 	   = $_POST['lot_no'];
    $units         = $_POST['units'];
    $quantity      = (int)$_POST['quantity'];
    $price         = (float)$_POST['price'];
    $received_date = $_POST['received_date'];
    $expiry_date   = $_POST['expiry_date'] ?: null;
    $note          = trim($_POST['note']);
    $remaining     = $quantity;

    if (!in_array($units, $UNIT_LIST)) {
        die('❌ หน่วยยาไม่ถูกต้อง');
    }

$unit_id = $_SESSION['unit_id']; // 👈 หน่วยที่ login

if ($expiry_date === null) {

		$stmt = $conn->prepare("
			INSERT INTO main_warehouse
			(unit_id, drug_name, lot_no, units, quantity, price, received_date, remaining, note)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
		");

		$stmt->bind_param(
			"isssidiss",
			$unit_id,
			$drug_name,
			$lot_no,
			$units,
			$quantity,
			$price,
			$received_date,
			$remaining,
			$note
		);

		} else {

		$stmt = $conn->prepare("
			INSERT INTO main_warehouse
			(unit_id, drug_name, lot_no, units, quantity, price, received_date, expiry_date, remaining, note)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		");

		$stmt->bind_param(
			"isssidssis",
			$unit_id,
			$drug_name,
			$lot_no,
			$units,
			$quantity,
			$price,
			$received_date,
			$expiry_date,
			$remaining,
			$note
		);


}

$stmt->execute();
$stmt->close();


    header("Location: main_warehouse.php");
    exit;
}


/* ======================
   ลบข้อมูลยา (ปลอดภัย)
====================== */
if (isset($_POST['delete'])) {

    if ($_SESSION['role'] !== 'admin') {
        die("❌ ไม่มีสิทธิ์ลบข้อมูล");
    }

    $id = (int)$_POST['delete'];

    $stmt = $conn->prepare("
        DELETE FROM main_warehouse
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: main_warehouse.php");
    exit;
}


/* ======================
   รับค่ากรองวันที่
====================== */
$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';

/* ======================
   ดึงข้อมูลแสดงผล กรองวันที่รับยา 
====================== */
$where  = "1=1";
$params = [];
$types  = "";

/* 🔒 staff / demo เห็นเฉพาะหน่วยตัวเอง */
if ($_SESSION['role'] !== 'admin') {
    $where   .= " AND mw.unit_id = ?";
    $types   .= "i";
    $params[] = $_SESSION['unit_id'];
}

/* 📅 กรองวันที่ */
if ($from_date && $to_date) {
    $where .= " AND mw.received_date BETWEEN ? AND ?";
    $types .= "ss";
    $params[] = $from_date;
    $params[] = $to_date;
} elseif ($from_date) {
    $where .= " AND mw.received_date >= ?";
    $types .= "s";
    $params[] = $from_date;
} elseif ($to_date) {
    $where .= " AND mw.received_date <= ?";
    $types .= "s";
    $params[] = $to_date;
}

/* ======================
   กรองรหัสหน่วยงาน
====================== */
$unit_code = $_GET['unit_code'] ?? '';

if ($unit_code !== '') {
    $where   .= " AND u.unit_code LIKE ?";
    $types   .= "s";
    $params[] = "%{$unit_code}%";
}


/* ======================
   QUERY หลัก
====================== */
$sql = "
    SELECT
        mw.id,
        mw.unit_id,
        u.unit_code,
        u.unit_name,
        mw.drug_name,
		mw.lot_no,
        mw.units,
        mw.quantity,
        mw.price,
        mw.received_date,
        mw.expiry_date,
        mw.remaining,
        mw.note
    FROM main_warehouse mw
    LEFT JOIN units u ON mw.unit_id = u.unit_id
    WHERE $where
    ORDER BY mw.id DESC
    LIMIT ? OFFSET ?
";


$types .= "ii";
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();



/* ======================
   จำนวนหน้า
====================== */
$count_sql = "
    SELECT COUNT(*) AS total
    FROM main_warehouse mw
    LEFT JOIN units u ON mw.unit_id = u.unit_id
    WHERE $where
";

$count_stmt = $conn->prepare($count_sql);
if ($types !== '') {
    // ตัด ii (limit, offset) ออก
    $count_types  = substr($types, 0, strlen($types) - 2);
    $count_params = array_slice($params, 0, -2);
    $count_stmt->bind_param($count_types, ...$count_params);
}

$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);



?>


<?php if ($_GET['delete'] ?? '' === 'success'): ?>
    <div class="alert alert-success">🗑 ลบรายการเรียบร้อยแล้ว</div>
<?php elseif ($_GET['delete'] ?? '' === 'error'): ?>
    <div class="alert alert-danger">❌ ไม่สามารถลบข้อมูลได้</div>
<?php elseif ($_GET['delete'] ?? '' === 'forbidden'): ?>
    <div class="alert alert-warning">⛔ ไม่มีสิทธิ์ลบข้อมูล</div>
<?php endif; ?>


 <?php  
require '../head2.php';
require 'bar.php';
?>
	     <!-- ================= Main Content ================= -->
		<main id="mainContent" class="main-content px-4 py-4 pbi-container">
			<div class="d-flex align-items-center gap-3 mb-2 flex-wrap">

			<h3 class="mb-0 fw-semibold">
				📦 คลังยาใหญ่
				<small class="text-muted fs-6 ms-2">Dataset View</small>
			</h3>

			<?php if (in_array($_SESSION['role'], ['admin', 'staff'])): ?>
				<div class="d-flex gap-2">

					<a href="import_drug.php" class="btn btn-success">
						<i class="bi bi-upload"></i> ➕ เพิ่ม / นำเข้ายา CSV
					</a>

					<a href="template_drug.csv" class="btn btn-outline-primary" download>
						<i class="bi bi-download"></i> 📥 ดาวน์โหลดแบบฟอร์ม
					</a>

				</div>
			<?php endif; ?>

		</div>

			    <!-- ฟอร์มเพิ่มยา -->
				  <div class="card mb-4">
					<div class="card-header bg-orange text-white">
					  ➕ เพิ่มรายการยาเข้าคลังใหญ่
					</div>
					<div class="card-body">
					<form method="post" class="row g-3">

						<!-- ชื่อยา -->
						<div class="col-md-3">
							<label class="form-label fw-semibold">ชื่อยา</label>
							<input type="text" name="drug_name" class="form-control" required>
						</div>

						<!-- Lot ยา -->
						<div class="col-md-2">
							<label class="form-label fw-semibold">Lot. ยา</label>
							<input type="text" name="lot_no" class="form-control" required>
						</div>

						<!-- หน่วย -->
						<div class="col-md-2">
							<label class="form-label fw-semibold">หน่วย</label>
							<select name="units" class="form-select" required>
								<option value="">-- เลือกหน่วย --</option>
								<option value="เม็ด">เม็ด</option>
								<option value="แคปซูล">แคปซูล</option>
								<option value="ขวด">ขวด</option>
								<option value="หลอด">หลอด</option>
								<option value="ซอง">ซอง</option>
								<option value="Amp">Amp</option>
								<option value="แผ่น">แผ่น</option>
								<option value="แท่ง">แท่ง</option>
								<option value="แผง">แผง</option>
								<option value="กระปุก">กระปุก</option>
								<option value="อัน">อัน</option>
								<option value="Vial">Vial</option>
							</select>
						</div>

						<!-- จำนวน -->
						<div class="col-md-1">
							<label class="form-label fw-semibold">จำนวน</label>
							<input type="number" name="quantity" class="form-control" min="1" required>
						</div>

						<!-- ราคา -->
						<div class="col-md-1">
							<label class="form-label fw-semibold">ราคา</label>
							<input type="number" name="price" step="0.01" class="form-control" min="0">
						</div>

						<!-- วันที่รับ -->
						<div class="col-md-1">
							<label class="form-label fw-semibold">วันที่รับ</label>
							<input type="date" name="received_date" class="form-control" required>
						</div>

						<!-- วันหมดอายุ -->
						<div class="col-md-2">
							<label class="form-label fw-semibold">วันหมดอายุ</label>
							<input type="date" name="expiry_date" class="form-control">
						</div>

						<!-- หมายเหตุ -->
						<div class="col-md-10">
							<label class="form-label fw-semibold">หมายเหตุ</label>
							<textarea name="note" class="form-control" rows="2"></textarea>
						</div>

						<!-- ปุ่มบันทึก -->
						<div class="col-md-2 d-flex align-items-end justify-content-end">
							<button type="submit" name="add" class="btn btn-success px-4">
								💾 บันทึกข้อมูล
							</button>
						</div>

					</form>

				</div>
			</div>



			<!-- ฟอร์มกรองวันที่รับ -->
			<form method="get" class="row g-2 mb-3">

				<!-- วันที่รับ (เริ่ม) -->
				<div class="col-md-3">
					<label class="form-label">วันที่รับ (เริ่ม)</label>
					<input type="date" name="from_date" class="form-control"
						   value="<?= $_GET['from_date'] ?? '' ?>">
				</div>

				<!-- วันที่รับ (สิ้นสุด) -->
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


				<!-- ปุ่ม -->
				<div class="col-md-3 d-flex align-items-end">
					<button type="submit" class="btn btn-primary me-2">
						🔍 กรองข้อมูล
					</button>
					<a href="?" class="btn btn-secondary">
						🔄 ล้างค่า
					</a>
				</div>

			</form>


		<!-- ตารางรายการยา -->
		<div class="card">
			<div class="card-header bg-secondary text-white">
				  📋 รายการยาในคลังใหญ่
			</div>
				
			<div class="card-body table-responsive">
			
					<form method="get" class="mb-2">
						แสดง
						<select name="limit" onchange="this.form.submit()">
							<?php foreach ($per_page_list as $l): ?>
								<option value="<?= $l ?>" <?= $limit==$l?'selected':'' ?>>
									<?= number_format($l) ?>
								</option>
							<?php endforeach; ?>
						</select>
						รายการ
					</form>					

				<table class="table table-bordered table-striped">
					<thead>
							<tr>
								<th>ลำดับ</th>
								<th>หน่วยบริการ</th>
								<th>ชื่อยา</th>
								<th>Lot.</th>
								<th>หน่วย</th>
								<th class="text-end">จำนวน</th>
								<th class="text-end">ราคา</th>
								<th>วันที่รับ</th>
								<th>วันหมดอายุ</th>
								<th class="text-end">คงเหลือ</th>
								<th>หมายเหตุ</th>
								<th class="text-center">จัดการ</th>
							</tr>
						</thead>
						<tbody>

						<?php 
						$no = ($page - 1) * $limit + 1; 
						$total_remaining = 0; // 🔹 ตัวแปรรวมคงเหลือ
						?>

						<?php while($row = $result->fetch_assoc()): ?>
							<?php $total_remaining += $row['remaining']; // 🔹 สะสมยอด ?>
							<tr>
								<td><?= $no++ ?></td>
								<td>
									<?= htmlspecialchars($row['unit_code']) ?><br>
									<small class="text-muted"><?= htmlspecialchars($row['unit_name']) ?></small>
								</td>
								<td><?= htmlspecialchars($row['drug_name']) ?></td>
								<td><?= htmlspecialchars($row['lot_no']) ?></td>
								<td><?= $UNIT_LIST[$row['units']] ?? $row['units'] ?></td>
								<td class="text-end"><?= number_format($row['quantity']) ?></td>
								<td class="text-end"><?= number_format($row['price'],2) ?></td>
								<td>
										<?=
											date('d/m/', strtotime($row['received_date'])) .
											(date('Y', strtotime($row['received_date'])) + 543)
										?>
										</td>

										<td>
											<?php
											if (!empty($row['expiry_date'])) {
												$date = new DateTime($row['expiry_date']);
												echo $date->format('d/m/') . ($date->format('Y') + 543);
											} else {
												echo '-';
											}
											?>
										</td>

								<td class="text-end fw-bold text-primary"><?= number_format($row['remaining']) ?></td>
								<td><?= htmlspecialchars($row['note']) ?></td>
								<td class="text-center">

									<?php if (
										$_SESSION['role'] === 'admin' ||
										($_SESSION['role'] === 'staff' && $row['unit_id'] == $_SESSION['unit_id'])
									): ?>
										<a href="edit_main_warehouse.php?id=<?= (int)$row['id'] ?>"
										   class="btn btn-sm btn-warning me-1">
										   ✏️ แก้ไข
										</a>
									<?php endif; ?>


									<?php if ($_SESSION['role'] === 'admin'): ?>
										<a href="delete_main_warehouse.php?id=<?= (int)$row['id'] ?>"
										   class="btn btn-sm btn-danger"
										   onclick="return confirm('ยืนยันการลบรายการนี้?')">
											🗑 ลบ
										</a>
									<?php endif; ?>


									<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'demo'): ?>
										<!-- demo ดูได้อย่างเดียว -->
										<span class="text-muted">🔒 ดูข้อมูลเท่านั้น</span>
									<?php endif; ?>

								</td>
							</tr>
						<?php endwhile; ?>

						</tbody>

						<!-- 🔹 แถวสรุป -->
						<tfoot>
							<tr class="table-warning fw-bold">
								<td colspan="9" class="text-end">รวมคงเหลือทั้งหมด</td>
								<td class="text-end text-danger"><?= number_format($total_remaining) ?></td>
								<td colspan="2"></td>
							</tr>
						</tfoot>
						
					</table>

					<nav>
					<ul class="pagination justify-content-center">

						<!-- ก่อนหน้า -->
						<li class="page-item <?= $page<=1?'disabled':'' ?>">
							<a class="page-link"
							   href="?page=<?= $page-1 ?>&limit=<?= $limit ?>">⬅️ ก่อนหน้า</a>
						</li>

						<!-- หน้า -->
						<li class="page-item disabled">
							<span class="page-link">
								หน้า <?= $page ?> / <?= $total_pages ?>
							</span>
						</li>

						<!-- ถัดไป -->
						<li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
							<a class="page-link"
							   href="?page=<?= $page+1 ?>&limit=<?= $limit ?>">ถัดไป ➡️</a>
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
