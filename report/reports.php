<?php
require '../includes/db.php';
require '../includes/auth.php';
// require '../includes/admin_only.php'; // ❌ ไม่ต้อง ถ้า staff/demo ต้องเข้าได้

$role    = $_SESSION['role'] ?? '';
$unit_id = $_SESSION['unit_id'] ?? null;

/* ===== เงื่อนไขตามสิทธิ์ ===== */
$where = "WHERE 1=1";

if ($role !== 'admin') {
    if (empty($unit_id)) {
        $where .= " AND 1=0"; // กันข้อมูลรั่ว
    } else {
        $where .= " AND m.unit_id = ".intval($unit_id);
    }
}
?>



<?php  
require '../head2.php';
require 'bar.php';
?>

	
		   <!-- ================= Main Content ================= -->
			<main id="mainContent" class="main-content px-4 py-4">
				<div class="card mb-4">
				<div class="card-header bg-orange text-white">
					<h3>📊 รายงานคงเหลือ & การใช้ยา</h3>
				</div>
			
	
				<div class="list-group">
					<br>
					<a href="report_main_stock.php" class="list-group-item list-group-item-action">
						<h5 class="mb-1">
							💊 🏥 คงเหลือคลังใหญ่
						</h5>
						<small>แสดงจำนวนยาคงเหลือทั้งหมดในคลังใหญ่</small>
					</a>

					<a href="report_sub_stock.php" class="list-group-item list-group-item-action">
						<h5 class="mb-1">
							💊 🏬 คงเหลือคลังย่อย
						</h5>
						<small>แสดงยาคงเหลือแยกตามคลังย่อย</small>
					</a>

					<a href="report_daily_usage.php" class="list-group-item list-group-item-action">
						<h5 class="mb-1">
							💊 📊 การใช้ยารายวัน
						</h5>
						<small>แสดงประวัติการใช้ยาในแต่ละวัน</small>
					</a>

					<a href="report_sub_stock_old.php" class="list-group-item list-group-item-action">
						<h5 class="mb-1 text-danger">
							💊 ⏰ ยาใกล้หมดสต๊อก
						</h5>
						<small>แสดงรายการยาที่ใกล้หมดสต๊อกุ</small>
					</a>
					
					<a href="report_expiry.php" class="list-group-item list-group-item-action">
						<h5 class="mb-1 text-danger">
							💊 ⏰ ยาใกล้หมดอายุ
						</h5>
						<small>แสดงรายการยาที่ใกล้หมดอายุ</small>
					</a>
				</div>


				<div class="container-fluid px-4 py-4">
				
					<!-- ================= คงเหลือคลังใหญ่ ================= -->
					<h4>คงเหลือคลังใหญ่ (ล่าสุด 6 รายการ)</h4>
					
						<?php
						$i = 1;
						$current_unit = '';

						$role    = $_SESSION['role'] ?? '';
						$unit_id = intval($_SESSION['unit_id'] ?? 0);

						/* ===== เงื่อนไขสิทธิ์ (ใช้ใน SQL) ===== */
						$where_unit = "";
						if ($role !== 'admin') {
							$where_unit = "WHERE unit_id = $unit_id";
						}

						/* ===== SQL ===== */
						$sql = "
						SELECT
							drug_name,
							remaining,
							unit_code
						FROM (
							SELECT
								m.drug_name,
								m.remaining,
								u.unit_code,
								m.unit_id,
								@rn := IF(@prev_unit = m.unit_id, @rn + 1, 1) AS rn,
								@prev_unit := m.unit_id
							FROM main_warehouse m
							JOIN (
								SELECT unit_id
								FROM main_warehouse
								$where_unit
								GROUP BY unit_id
								ORDER BY unit_id
								LIMIT 11
							) lim ON m.unit_id = lim.unit_id
							LEFT JOIN units u ON m.unit_id = u.unit_id
							CROSS JOIN (SELECT @rn := 0, @prev_unit := NULL) vars
							ORDER BY m.unit_id, m.drug_name
						) t
						WHERE rn <= 6
						ORDER BY unit_code, drug_name
						";

						$res = $conn->query($sql);
						?>


						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th width="20" class="text-center">ลำดับ</th>
									<th>หน่วยบริการ</th>
									<th>ชื่อยา</th>
									<th width="120" class="text-end">คงเหลือ</th>
								</tr>
							</thead>
							<tbody>

						<?php if ($res && $res->num_rows > 0): ?>
						<?php while ($r = $res->fetch_assoc()): ?>

							<!-- ===== แสดงหัวกลุ่ม unit_code ===== -->
							<?php if ($current_unit !== $r['unit_code']): 
								$current_unit = $r['unit_code'];
							?>
							<tr class="table-secondary">
								<td colspan="4"><strong>หน่วยบริการ : <?= htmlspecialchars($current_unit) ?></strong></td>
							</tr>
							<?php endif; ?>

							<tr>
								<td class="text-center"><?= $i ?></td>
								<td><?= htmlspecialchars($r['unit_code']) ?></td>
								<td><?= htmlspecialchars($r['drug_name']) ?></td>
								<td class="text-end"><?= number_format($r['remaining']) ?></td>
							</tr>

						<?php 
							$i++;
						endwhile; 
						?>
						<?php else: ?>
							<tr>
								<td colspan="4" class="text-center text-muted">ไม่พบข้อมูล</td>
							</tr>
						<?php endif; ?>

							</tbody>
						</table>



					<!-- ================= คงเหลือคลังย่อย ================= -->

						<?php
						$role    = $_SESSION['role'] ?? '';
						$unit_id = intval($_SESSION['unit_id'] ?? 0);

						/* ===== เงื่อนไขสิทธิ์ ===== */
						$where_sub = "";
						$where_use = "";

						if ($role !== 'admin') {
							$where_sub = "WHERE m.unit_id = $unit_id";
							$where_use = "WHERE m.unit_id = $unit_id";
						}
						 ?>
						 
						<h4>คงเหลือคลังย่อย (ล่าสุด 6 รายการ)</h4>
						<table class="table table-bordered table-striped">
						<thead>
						<tr>
							<th width="60" class="text-center">ลำดับ</th>
							<th>คลังย่อย</th>
							<th>หน่วยบริการ</th>
							<th>ชื่อยา</th>
							<th width="120" class="text-end">คงเหลือ</th>
						</tr>
						</thead>
						<tbody>

						<?php
						$i = 1;
						$sql = "
							SELECT 
								s.sub_name,
								u.unit_code,
								m.drug_name,
								s.remaining
							FROM sub_warehouse s
							LEFT JOIN main_warehouse m ON s.drug_id = m.id
							LEFT JOIN units u ON m.unit_id = u.unit_id
							$where_sub
							ORDER BY s.sub_name
							LIMIT 6
						";
						$res = $conn->query($sql);

						if ($res && $res->num_rows > 0):
						while ($r = $res->fetch_assoc()):
						?>
						<tr>
							<td class="text-center"><?= $i ?></td>
							<td><?= htmlspecialchars($r['sub_name']) ?></td>
							<td><?= htmlspecialchars($r['unit_code']) ?></td>
							<td><?= htmlspecialchars($r['drug_name']) ?></td>
							<td class="text-end"><?= number_format($r['remaining']) ?></td>
						</tr>
						<?php
						$i++;
						endwhile;
						else:
						?>
						<tr>
							<td colspan="5" class="text-center text-muted">ไม่พบข้อมูล</td>
						</tr>
						<?php endif; ?>

						</tbody>
						</table>



					<!-- ================= การใช้ยารายวัน ================= -->
						
						<?php
						$role    = $_SESSION['role'] ?? '';
						$unit_id = intval($_SESSION['unit_id'] ?? 0);

						/* ===== เงื่อนไขสิทธิ์ ===== */
						$where_sub = "";
						$where_use = "";

						if ($role !== 'admin') {
							$where_sub = "WHERE m.unit_id = $unit_id";
							$where_use = "WHERE m.unit_id = $unit_id";
						}
						 ?>
						 
						<h4>การใช้ยารายวัน (ล่าสุด 6 รายการ)</h4>
						<table class="table table-bordered table-striped">
						<thead>
						<tr>
							<th width="60" class="text-center">ลำดับ</th>
							<th width="120" class="text-center">วันที่ใช้</th>
							<th>คลังย่อย</th>
							<th>หน่วยบริการ</th>
							<th>ชื่อยา</th>
							<th width="120" class="text-end">จำนวนใช้</th>
						</tr>
						</thead>
						<tbody>

						<?php
						$i = 1;
						$sql = "
							SELECT 
								d.usage_date,
								s.sub_name,
								u.unit_code,
								m.drug_name,
								d.quantity_used
							FROM daily_usage d
							LEFT JOIN sub_warehouse s ON d.sub_id = s.id
							LEFT JOIN main_warehouse m ON s.drug_id = m.id
							LEFT JOIN units u ON m.unit_id = u.unit_id
							$where_use
							ORDER BY d.usage_date DESC
							LIMIT 6
						";
						$res = $conn->query($sql);

						if ($res && $res->num_rows > 0):
						while ($r = $res->fetch_assoc()):
						?>
						<tr>
							<td class="text-center"><?= $i ?></td>
							<td class="text-center"><?= htmlspecialchars($r['usage_date']) ?></td>
							<td><?= htmlspecialchars($r['sub_name']) ?></td>
							<td><?= htmlspecialchars($r['unit_code']) ?></td>
							<td><?= htmlspecialchars($r['drug_name']) ?></td>
							<td class="text-end"><?= number_format($r['quantity_used']) ?></td>
						</tr>
						<?php
						$i++;
						endwhile;
						else:
						?>
						<tr>
							<td colspan="6" class="text-center text-muted">ไม่พบข้อมูล</td>
						</tr>
						<?php endif; ?>

						</tbody>
						</table>


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
