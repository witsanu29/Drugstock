<?php
session_start();

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';     // $conn (mysqli)
require_once __DIR__ . '/../includes/config.php';

	/* 🔐 admin เท่านั้น */
	if (($_SESSION['role'] ?? '') !== 'admin') {
		exit('⛔ สำหรับผู้ดูแลระบบเท่านั้น');
	}

	$error   = '';
	$success = '';
	$edit    = null;

	/* ================== ลบข้อมูล ================== */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM units WHERE unit_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: unit_manage.php");
    exit;
}

	/* ================== ดึงข้อมูลแก้ไข ================== */
	if (isset($_GET['edit'])) {
    $id   = (int)$_GET['edit'];
    $res  = $conn->query("SELECT * FROM units WHERE unit_id = $id");

    if ($res && $res->num_rows > 0) {
        $edit = $res->fetch_assoc();
    }
}


	/* ================== บันทึก (เพิ่ม / แก้ไข) ================== */
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

		$unit_id   = $_POST['unit_id'] ?? '';
		$unit_code = trim($_POST['unit_code']);
		$unit_name = trim($_POST['unit_name']);
		$status    = $_POST['status'];

		if ($unit_code === '' || $unit_name === '') {
			$error = 'กรุณากรอกข้อมูลให้ครบ';
		} else {

			if ($unit_id) {
				// แก้ไข
				$stmt = $conn->prepare("
					UPDATE units
					SET unit_code = ?, unit_name = ?, status = ?
					WHERE unit_id = ?
				");
				$stmt->bind_param("sssi", $unit_code, $unit_name, $status, $unit_id);
				$stmt->execute();
				$success = '✏️ แก้ไขข้อมูลเรียบร้อยแล้ว';
			} else {
				// เพิ่มใหม่
				$stmt = $conn->prepare("
					INSERT INTO units (unit_code, unit_name, status)
					VALUES (?, ?, ?)
				");
				$stmt->bind_param("sss", $unit_code, $unit_name, $status);
				$stmt->execute();
				$success = '✅ เพิ่มหน่วยงานเรียบร้อยแล้ว';
			}

			header("Location: unit_manage.php");
			exit;
		}
	}

	/* ================== ดึงข้อมูลทั้งหมด ================== */
	$units  = [];
	$result = $conn->query("SELECT * FROM units ORDER BY unit_id ASC");
	while ($row = $result->fetch_assoc()) {
		$units[] = $row;
	}
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการหน่วยงานบริการ</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
	<link rel="icon" href="../assets/img/hospital-icon.png" type="image/png">

</head>
<body>

<?php require '../head2.php'; ?>
<?php require 'bar1.php'; ?>

		<div class="container mt-4">

			<h4 class="fw-bold mb-3">
				<i class="bi bi-building"></i> จัดการหน่วยงาน บริการ
			</h4>

			<?php if ($error): ?>
				<div class="alert alert-danger"><?= $error ?></div>
			<?php endif; ?>

			<?php if ($success): ?>
				<div class="alert alert-success"><?= $success ?></div>
			<?php endif; ?>

			<!-- ===== ฟอร์มเพิ่ม ===== -->
			<div class="card mb-4 shadow-sm">
				<div class="card-header bg-primary text-white">
					เพิ่มหน่วยงาน
				</div>
				<div class="card-body">
				
				   <form method="post" class="row g-3">

					<input type="hidden" name="unit_id" value="<?= $edit['unit_id'] ?? '' ?>">

					<div class="col-md-4">
						<label class="form-label">รหัสหน่วยงาน</label>
						<input type="text"
							   name="unit_code"
							   class="form-control"
							   value="<?= $edit['unit_code'] ?? '' ?>"
							   required>
					</div>

					<div class="col-md-5">
						<label class="form-label">ชื่อหน่วยงาน</label>
						<input type="text"
							   name="unit_name"
							   class="form-control"
							   value="<?= $edit['unit_name'] ?? '' ?>"
							   required>
					</div>

					<div class="col-md-3">
						<label class="form-label">สถานะ</label>
						<select name="status" class="form-select">
							<option value="active" <?= ($edit['status'] ?? '')=='active'?'selected':'' ?>>
								ใช้งาน
							</option>
							<option value="inactive" <?= ($edit['status'] ?? '')=='inactive'?'selected':'' ?>>
								ไม่ใช้งาน
							</option>
						</select>
					</div>

					<div class="col-12">
						<button class="btn btn-<?= $edit?'warning':'success' ?>">
							<i class="bi bi-<?= $edit?'pencil':'save' ?>"></i>
							<?= $edit ? 'แก้ไขข้อมูล' : 'บันทึก' ?>
						</button>

						<?php if ($edit): ?>
							<a href="unit_manage.php" class="btn btn-secondary ms-2">ยกเลิก</a>
						<?php endif; ?>
					</div>

				</form>
					
				</div>
			</div>

			<!-- ===== ตารางแสดง ===== -->
			<div class="card shadow-sm">
				<div class="card-header bg-light">
					รายการหน่วยงาน
				</div>
				<div class="card-body p-0">
					<table class="table table-bordered table-hover mb-0">
						<thead class="table-secondary">
							<tr class="text-center">
								<th width="80">ลำดับ</th>
								<th>รหัสหน่วยงาน</th>
								<th>ชื่อหน่วยงาน</th>
								<th width="120">สถานะ</th>
								<th width="140">จัดการ</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($units as $u): ?>
								<tr>
									<td class="text-center"><?= $u['unit_id'] ?></td>
									<td><?= htmlspecialchars($u['unit_code']) ?></td>
									<td><?= htmlspecialchars($u['unit_name']) ?></td>
									<td class="text-center">
										<?php if ($u['status'] === 'active'): ?>
											<span class="badge bg-success">ใช้งาน</span>
										<?php else: ?>
											<span class="badge bg-secondary">ไม่ใช้งาน</span>
										<?php endif; ?>
									</td>
									<td class="text-center">
										<a href="?edit=<?= $u['unit_id'] ?>"
										   class="btn btn-sm btn-warning">
											<i class="bi bi-pencil"></i>
										</a>

										<a href="?delete=<?= $u['unit_id'] ?>"
										   class="btn btn-sm btn-danger"
										   onclick="return confirm('ยืนยันลบหน่วยงานนี้ ?')">
											<i class="bi bi-trash"></i>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>

							<?php if (count($units) === 0): ?>
								<tr>
									<td colspan="4" class="text-center text-muted">
										ยังไม่มีข้อมูล
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

		</div>

		<script>
		document.getElementById('toggleSidebar').addEventListener('click', function () {
		  document.getElementById('sidebar').classList.toggle('collapsed');
		});
		</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
