<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

	/* ===== Session ===== */
	$role    = $_SESSION['role'] ?? 'demo';
	$unit_id = (int)($_SESSION['unit_id'] ?? 0);

	/* ===== Filter (admin) ===== */
	$filter_unit = ($role === 'admin')
		? (int)($_GET['unit_id'] ?? 0)
		: $unit_id;


	/* ===== Pagination ===== */
	$limitOptions = [100, 300, 500, 1000, 2000];

	$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limitOptions)
		? (int)$_GET['limit']
		: 100;

	$page = isset($_GET['page']) && (int)$_GET['page'] > 0
		? (int)$_GET['page']
		: 1;

	$offset = ($page - 1) * $limit;

/* ===== รวมคงเหลือ ===== */
if ($role === 'admin') {

    if ($filter_unit > 0) {
        $stmt = $conn->prepare(
            "SELECT SUM(remaining) AS total_remaining
             FROM non_drug_warehouse
             WHERE unit_id = ?"
        );
        $stmt->bind_param("i", $filter_unit);
        $stmt->execute();
        $sumRes = $stmt->get_result();
    } else {
        $sumRes = $conn->query(
            "SELECT SUM(remaining) AS total_remaining
             FROM non_drug_warehouse"
        );
    }

} else {

    $stmt = $conn->prepare(
        "SELECT SUM(remaining) AS total_remaining
         FROM non_drug_warehouse
         WHERE unit_id = ?"
    );
    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $sumRes = $stmt->get_result();
}

$total_remaining = (int)($sumRes->fetch_assoc()['total_remaining'] ?? 0);


/* ===== นับจำนวนแถวทั้งหมด ===== */
if ($role === 'admin') {

    if ($filter_unit > 0) {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM non_drug_warehouse
             WHERE unit_id = ?"
        );
        $stmt->bind_param("i", $filter_unit);
        $stmt->execute();
        $totalRows = (int)$stmt->get_result()->fetch_assoc()['total'];
    } else {
        $totalRows = (int)$conn
            ->query("SELECT COUNT(*) AS total FROM non_drug_warehouse")
            ->fetch_assoc()['total'];
    }

} else {

    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM non_drug_warehouse
         WHERE unit_id = ?"
    );
    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $totalRows = (int)$stmt->get_result()->fetch_assoc()['total'];
}

$totalPages = max(1, ceil($totalRows / $limit));


/* ===== ดึงข้อมูลตามหน้า ===== */
if ($role === 'admin') {

    if ($filter_unit > 0) {
        $sql = "
            SELECT w.*, u.unit_name
            FROM non_drug_warehouse w
            LEFT JOIN units u ON w.unit_id = u.unit_id
            WHERE w.unit_id = ?
            ORDER BY w.id DESC
            LIMIT ?, ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $filter_unit, $offset, $limit);
    } else {
        // ✅ admin ดูทุกหน่วย
        $sql = "
            SELECT w.*, u.unit_name
            FROM non_drug_warehouse w
            LEFT JOIN units u ON w.unit_id = u.unit_id
            ORDER BY w.id DESC
            LIMIT ?, ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $offset, $limit);
    }

} else {

    $sql = "
        SELECT w.*, u.unit_name
        FROM non_drug_warehouse w
        LEFT JOIN units u ON w.unit_id = u.unit_id
        WHERE w.unit_id = ?
        ORDER BY w.id DESC
        LIMIT ?, ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $unit_id, $offset, $limit);
}

$stmt->execute();
$res = $stmt->get_result();

/* ===== หน่วยบริการ (admin) ===== */
if ($role === 'admin') {
    $unitRes = $conn->query("
        SELECT unit_id, unit_name
        FROM units
        ORDER BY unit_name
    ");
}

?>



<?php  
require '../head2.php';
require 'bar.php';
?>

		<?php
		function thai_date($date) {
			if (!$date || $date === '0000-00-00') {
				return '-';
			}
			$d = date('d', strtotime($date));
			$m = date('m', strtotime($date));
			$y = date('Y', strtotime($date)) + 543;

			return "$d/$m/$y";
		}
		?>

		   <!-- ================= Main Content ================= -->
			<main id="mainContent" class="main-content px-4 py-4">
				<div class="card shadow-sm">
				<div class="card-header bg-orange text-white d-flex align-items-center justify-content-between">

					<!-- ฝั่งซ้าย -->
					<div class="d-flex align-items-center gap-3">
						<span class="fw-semibold">🧴 คลังใหญ่เวชภัณฑ์มิใช่ยา</span>

						<?php if ($role === 'admin' || $role === 'staff'): ?>
							<a href="../non_drug/import_non_drug.php" class="btn btn-success btn-outline-light">
								➕ เพิ่ม / นำเข้ายา
							</a>
						<?php endif; ?>
					</div>

					<!-- ฝั่งขวา -->
					<?php if ($role === 'admin' || $role === 'staff'): ?>
						<a href="add_non_drug.php" class="btn btn-success">
							➕ เพิ่มเวชภัณฑ์
						</a>
					<?php endif; ?>
				</div>

			<div class="card-body table-responsive">
			
					<form method="get" class="row g-2 mb-3">

						<?php if ($role === 'admin'): ?>
						<div class="col-md-4">
						<select name="unit_id" class="form-select" onchange="this.form.submit()">
						<option value="">🔍 ทุกหน่วยบริการ</option>
						<?php while ($u = $unitRes->fetch_assoc()): ?>
						<option value="<?= $u['unit_id'] ?>" <?= $filter_unit==$u['unit_id']?'selected':'' ?>>
						<?= htmlspecialchars($u['unit_name']) ?>
						</option>
						<?php endwhile; ?>
						</select>
						</div>
						<?php endif; ?>

						<div class="col-md-3">
							<select name="limit" class="form-select" onchange="this.form.submit()">
							<?php foreach ([100,300,500,1000,2000] as $l): ?>
								<option value="<?= $l ?>" <?= $limit==$l?'selected':'' ?>>
									แสดง <?= number_format($l) ?> รายการ
								</option>
							<?php endforeach; ?>
							</select>
						</div>

						<input type="hidden" name="page" value="1">
					</form>

				<table class="table table-striped table-hover align-middle">
						<thead class="table-dark">
							<tr>
							<th>ลำดับ</th>
							<th>หน่วยบริการ</th>
							<th>ชื่อเวชภัณฑ์มิใช่ยา</th>
							<th>จำนวน</th>
							<th>ราคา</th>
							<th>วันที่รับ</th>
							<th>วันหมดอายุ</th>
							<th>คงเหลือ</th>
							<th>หน่วยนับ</th>
							<th>หมายเหตุ</th>
							<th class="text-center">จัดการ</th>
							</tr>
						</thead>
						<tbody>
						
						<?php $i = $offset + 1; ?>
						<?php while ($row = $res->fetch_assoc()): ?>
						<tr>
							<td><?= $i++ ?></td>
							<td><?= htmlspecialchars($row['unit_name'] ?? '-') ?></td>
							<td><?= htmlspecialchars($row['item_name']) ?></td>
							<td><?= number_format($row['quantity']) ?></td>
							<td><?= number_format($row['price'], 2) ?></td>
							<td><?= thai_date($row['received_date']) ?></td>
							<td><?= thai_date($row['expiry_date']) ?></td>
							<td><?= number_format($row['remaining']) ?></td>
							<td><?= htmlspecialchars($row['unit']) ?></td>
							<td><?= htmlspecialchars($row['note']) ?></td>
							<td class="text-center">
								<div class="btn-group btn-group-sm">
									<?php if ($role !== 'demo'): ?>
										<a href="edit_non_drug.php?id=<?= $row['id'] ?>"
										   class="btn btn-warning">✏️</a>
									<?php endif; ?>
									<?php if ($role === 'admin'): ?>
										<a href="delete_non_drug.php?id=<?= $row['id'] ?>"
										   class="btn btn-danger"
										   onclick="return confirm('ยืนยันการลบ?')">🗑</a>
									<?php endif; ?>
								</div>
							</td>
						</tr>
						<?php endwhile; ?>


						</tbody>
						
						<tfoot>
						<tr class="table-info fw-bold">
							<td colspan="7" class="text-center fs-6">รวมคงเหลือทั้งหมด</td>
							<td class="text-start"><?= number_format($total_remaining) ?></td>
							<td colspan="3"></td>
						</tr>
						</tfoot>
				</table>
				
				<nav class="mt-3">
					<ul class="pagination justify-content-center">

						<!-- ก่อนหน้า -->
						<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
							<a class="page-link"
							   href="?page=<?= $page+1 ?>&limit=<?= $limit ?><?= $filter_unit ? '&unit_id='.$filter_unit : '' ?>">
							   ◀ ก่อนหน้า
							</a>
						</li>

						<!-- แสดงเลขหน้า -->
						<?php
						$start = max(1, $page - 2);
						$end   = min($totalPages, $page + 2);
						?>

						<?php for ($p = $start; $p <= $end; $p++): ?>
							<li class="page-item <?= $p == $page ? 'active' : '' ?>">
								<a class="page-link"
								   href="?page=<?= $p ?>&limit=<?= $limit ?>">
									<?= $p ?>
								</a>
							</li>
						<?php endfor; ?>

						<!-- ถัดไป -->
						<li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
							<a class="page-link"
							   href="?page=<?= $page+1 ?>&limit=<?= $limit ?><?= $filter_unit ? '&unit_id='.$filter_unit : '' ?>">
							   ถัดไป ▶
							</a>
						</li>
					</ul>
				</nav>

			</div>
			
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
