<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

$role    = $_SESSION['role'] ?? 'demo';
$unit_id = (int)($_SESSION['unit_id'] ?? 0);

/* 🔐 admin ไม่ผูกกับ unit */
if ($role === 'admin') {
    $unit_id = 0;
}

/* ================= Filter ================= */
$filter_unit = intval($_GET['unit_id'] ?? 0);

/* ================= Pagination ================= */
$page  = max(1, intval($_GET['page'] ?? 1));
$limit = intval($_GET['limit'] ?? 100);

$allowLimits = [100,300,500,1000,2000];
if (!in_array($limit, $allowLimits)) {
    $limit = 100;
}

$offset = ($page - 1) * $limit;

/* ================= COUNT ================= */
if ($role === 'admin') {

    if ($filter_unit > 0) {
        $countSql = "
            SELECT COUNT(*) total
            FROM non_drug_usage u
            JOIN non_drug_warehouse w ON u.non_drug_id = w.id
            WHERE w.unit_id = ?
        ";
        $countStmt = $conn->prepare($countSql);
        $countStmt->bind_param("i", $filter_unit);
    } else {
        $countSql = "
            SELECT COUNT(*) total
            FROM non_drug_usage u
            JOIN non_drug_warehouse w ON u.non_drug_id = w.id
        ";
        $countStmt = $conn->prepare($countSql);
    }

} else {

    $countSql = "
        SELECT COUNT(*) total
        FROM non_drug_usage u
        JOIN non_drug_warehouse w ON u.non_drug_id = w.id
        WHERE w.unit_id = ?
    ";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("i", $unit_id);
}

$countStmt->execute();
$totalRows  = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalRows / $limit));

/* ================= DATA ================= */
if ($role === 'admin') {

    if ($filter_unit > 0) {
        $sql = "
            SELECT u.id, u.used_qty, u.used_date,
                   w.item_name, w.unit,
                   un.unit_name
            FROM non_drug_usage u
            JOIN non_drug_warehouse w ON u.non_drug_id = w.id
            LEFT JOIN units un ON w.unit_id = un.unit_id
            WHERE w.unit_id = ?
            ORDER BY u.id DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $filter_unit, $limit, $offset);
    } else {
        $sql = "
            SELECT u.id, u.used_qty, u.used_date,
                   w.item_name, w.unit,
                   un.unit_name
            FROM non_drug_usage u
            JOIN non_drug_warehouse w ON u.non_drug_id = w.id
            LEFT JOIN units un ON w.unit_id = un.unit_id
            ORDER BY u.id DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
    }

} else {

    $sql = "
        SELECT u.id, u.used_qty, u.used_date,
               w.item_name, w.unit,
               un.unit_name
        FROM non_drug_usage u
        JOIN non_drug_warehouse w ON u.non_drug_id = w.id
        LEFT JOIN units un ON w.unit_id = un.unit_id
        WHERE w.unit_id = ?
        ORDER BY u.id DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $unit_id, $limit, $offset);
}

$stmt->execute();
$res = $stmt->get_result();

/* ลำดับแถว */
$i = $offset + 1;

/* ================= หน่วยบริการ (admin filter) ================= */
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

				<!-- ================= Main Content ================= -->
				<main id="mainContent" class="main-content px-4 py-4">
				<div class="card shadow-sm">

					<div class="card-header bg-orange text-white d-flex justify-content-between align-items-center">
						<span>📋 รายการใช้เวชภัณฑ์มิใช่ยา</span>

						<!-- ➕ เพิ่ม : admin + staff เท่านั้น -->
						<?php if ($role === 'admin' || $role === 'staff'): ?>
							<a href="add_usage_non_drug.php" class="btn btn-light btn-sm">
								➕ บันทึกการใช้
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
								<th>จำนวนใช้</th>
								<th width="10%">หน่วยนับ</th>
								<th>วันที่ใช้</th>
								<th class="text-center">จัดการ</th>
							</tr>
						</thead>
						<tbody>

						<?php if ($res->num_rows > 0): ?>
						<?php while ($row = $res->fetch_assoc()): ?>
						<tr>
							<td><?= $i++ ?></td>
							<td><?= htmlspecialchars($row['unit_name'] ?? '-') ?></td>
							<td><?= htmlspecialchars($row['item_name']) ?></td>
							<td><?= number_format($row['used_qty']) ?></td>
							<td><?= htmlspecialchars($row['unit']) ?></td>
							<td>
								<?= date('d/m/', strtotime($row['used_date'])) .
								   (date('Y', strtotime($row['used_date'])) + 543) ?>
							</td>
							<td class="text-center">
								<div class="btn-group btn-group-sm">
									<?php if ($role === 'admin' || $role === 'staff'): ?>
										<a href="edit_usage_non_drug.php?id=<?= $row['id'] ?>" class="btn btn-warning">
											✏️ แก้ไข
										</a>
									<?php endif; ?>

									<?php if ($role === 'admin'): ?>
										<a href="delete_usage_non_drug.php?id=<?= $row['id'] ?>"
										   class="btn btn-danger"
										   onclick="return confirm('ยืนยันการลบ?')">
										   🗑 ลบ
										</a>
									<?php endif; ?>

									<?php if ($role === 'demo'): ?>
										<span class="badge bg-secondary">ดูอย่างเดียว</span>
									<?php endif; ?>
								</div>
							</td>
						</tr>
						<?php endwhile; ?>
						<?php else: ?>
						<tr>
							<td colspan="7" class="text-center text-muted">
								ไม่พบข้อมูล
							</td>
						</tr>
						<?php endif; ?>

						</tbody>

						</table>
		
						<nav class="mt-3">
						<ul class="pagination justify-content-center">

							<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
								<a class="page-link"
								   href="?page=<?= $page-1 ?>&limit=<?= $limit ?><?= $filter_unit ? '&unit_id='.$filter_unit : '' ?>">

									◀ ก่อนหน้า
								</a>
							</li>

							<li class="page-item disabled">
								<span class="page-link">
									หน้า <?= $page ?> / <?= $totalPages ?>
								</span>
							</li>

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
			</main>

	<script>
	document.getElementById('toggleSidebar')
	?.addEventListener('click', function () {
		document.getElementById('sidebar').classList.toggle('collapsed');
	});
	</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
