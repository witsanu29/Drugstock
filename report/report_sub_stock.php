<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
// require '../includes/admin_only.php';

	/* ================== ตัวแปรพื้นฐาน ================== */
	$role      = $_SESSION['role'] ?? '';
	$unit_id   = intval($_SESSION['unit_id'] ?? 0);
	$unit_code = $_GET['unit_code'] ?? '';

	/* ================== เงื่อนไข WHERE ================== */
	$where = [];

	if ($role !== 'admin') {
		$where[] = "m.unit_id = $unit_id";
	}

	if ($role === 'admin' && $unit_code !== '') {
		$unit_code = $conn->real_escape_string($unit_code);
		$where[] = "u.unit_code = '$unit_code'";
	}

	$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

	/* ================== Pagination ================== */
	$page  = max(1, intval($_GET['page'] ?? 1));
	$limit = intval($_GET['per_page'] ?? 100);

	$allow_limit = [100,300,500,1000,2000];
	if (!in_array($limit, $allow_limit)) {
		$limit = 100;
	}

	$offset = ($page - 1) * $limit;


	/* ================== นับจำนวนทั้งหมด ================== */
	$count_sql = "
		SELECT COUNT(*) AS total FROM (
			SELECT s.sub_name, u.unit_code, m.drug_name
			FROM sub_warehouse s
			LEFT JOIN main_warehouse m ON s.drug_id = m.id
			LEFT JOIN units u ON m.unit_id = u.unit_id
			$where_sql
			GROUP BY s.sub_name, u.unit_code, m.drug_name
		) x
	";

	$total_res   = $conn->query($count_sql);
	$total_rows  = $total_res->fetch_assoc()['total'] ?? 0;
	$total_pages = ceil($total_rows / $limit);

	/* ================== SQL หลัก ================== */
	$sql = "
		SELECT 
			s.sub_name,
			u.unit_code,
			m.drug_name,
			m.units AS drug_unit,
			SUM(s.remaining) AS total_remaining
		FROM sub_warehouse s
		LEFT JOIN main_warehouse m ON s.drug_id = m.id
		LEFT JOIN units u ON m.unit_id = u.unit_id
		$where_sql
		GROUP BY s.sub_name, u.unit_code, m.drug_name, m.units
		ORDER BY u.unit_code, s.sub_name, m.drug_name
		LIMIT $limit OFFSET $offset
	";

	$res = $conn->query($sql);
	
?>



<?php require '../head2.php'; ?>
<?php require 'bar.php'; ?>

	<!-- ================= Main Content ================= -->
	<main id="mainContent" class="main-content px-4 py-4">

			<div class="d-flex justify-content-between align-items-center mb-4">
				<h3><i class="bi bi-hospital"></i> รายงานคงเหลือคลังย่อย</h3>
				
				<div class="d-flex gap-2">
							<span class="badge bg-primary fs-6 px-3 py-2">คลังย่อย</span>
							
							<?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'demo'): ?>
							<a href="export_sub_stock_excel.php?unit_code=<?= urlencode($_GET['unit_code'] ?? '') ?>"
							   class="btn btn-success btn-sm">
							   <i class="bi bi-file-earmark-excel"></i> Excel
							</a>
							<?php endif; ?>
						</div>
			</div>

			<div class="card shadow-sm">
			<div class="card-header bg-orange text-white">
				<i class="bi bi-table"></i> รายการคงเหลือยาในคลังย่อย
			</div>

		<div class="card-body">
		<div class="mb-3">

		<!-- ===== Filter หน่วยบริการ (Admin) ===== -->
			<form method="get" class="row align-items-center w-100 g-3">

					
						<!-- กรองหน่วยบริการ -->
					<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>

					<div class="col-md-6">
						<div class="d-flex align-items-center gap-2">
							<label class="mb-0 fw-semibold text-nowrap">
								กรองหน่วยบริการ
							</label>

							<select name="unit_code"
									class="form-select form-select-sm"
									style="min-width:220px"
									onchange="this.form.submit()">

								<option value="">-- ทุกหน่วยบริการ --</option>

								<?php
								$unit_code = $_GET['unit_code'] ?? '';
								$u = $conn->query("SELECT unit_code FROM units ORDER BY unit_code");
								while ($ur = $u->fetch_assoc()):
									$sel = ($unit_code === $ur['unit_code']) ? 'selected' : '';
								?>
									<option value="<?= htmlspecialchars($ur['unit_code']) ?>" <?= $sel ?>>
										<?= htmlspecialchars($ur['unit_code']) ?>
									</option>
								<?php endwhile; ?>
							</select>
						</div>
					</div>

					<?php endif; ?>


				<!-- เลือกจำนวนแถว -->
				<div class="col-md-4 d-flex align-items-center gap-2">
					<label class="form-label mb-0 fw-semibold">แสดง</label>
					<select name="per_page"
							class="form-select form-select-sm w-auto"
							onchange="this.form.submit()">
						<?php foreach ($allow_limit as $l): ?>
							<option value="<?= $l ?>" <?= $limit == $l ? 'selected' : '' ?>>
								<?= number_format($l) ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="fw-semibold">รายการ</span>
				</div>

				<!-- hidden -->
				<input type="hidden" name="page" value="1">
			</form>
			</div>


		<!-- ===== ตาราง ===== -->
		<div class="table-responsive">
			<table class="table table-bordered table-hover align-middle">
				<thead class="table-light text-center">
				<tr>
					<th width="5%">ลำดับ</th>
					<th>คลังย่อย</th>
					<th>หน่วยบริการ</th>
					<th>ชื่อยา</th>
					<th class="text-end">คงเหลือ</th>
					<th>หน่วยนับ</th>
				</tr>
				</thead>
				<tbody>

				<?php
				$i = ($page - 1) * $limit + 1;
				if ($res && $res->num_rows > 0):
					while ($r = $res->fetch_assoc()):
				?>
				<tr>
					<td class="text-center"><?= $i++ ?></td>
					<td><?= htmlspecialchars($r['sub_name']) ?></td>
					<td><?= htmlspecialchars($r['unit_code']) ?></td>
					<td><?= htmlspecialchars($r['drug_name']) ?></td>
					<td class="text-end fw-bold"><?= number_format($r['total_remaining']) ?></td>
					<td class="text-center"><?= htmlspecialchars($r['drug_unit']) ?></td>
				</tr>
				<?php endwhile; else: ?>
				<tr>
					<td colspan="6" class="text-center text-muted">ไม่พบข้อมูล</td>
				</tr>
				<?php endif; ?>

				</tbody>
			</table>
		</div>


	<!-- ===== เลือกจำนวนแถว + Pagination แถวเดียว ===== -->
		<!-- Pagination -->
		<div class="col-12 d-flex justify-content-center">
			<?php if ($total_rows > 0): ?>
			<nav>
				<ul class="pagination mb-0">

					<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
						<a class="page-link"
						   href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
							◀ ก่อนหน้า
						</a>
					</li>

					<li class="page-item disabled">
						<span class="page-link">
							หน้า <?= $page ?> / <?= max(1, $total_pages) ?>
						</span>
					</li>

					<li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
						<a class="page-link"
						   href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
							ถัดไป ▶
						</a>
					</li>

				</ul>
			</nav>
			<?php endif; ?>
		</div>


		</div>

		</div>
	</div>
	</main>


	<script>
	document.getElementById('toggleSidebar').addEventListener('click', function () {
	  document.getElementById('sidebar').classList.toggle('collapsed');
	});
	</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
