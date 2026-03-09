<?php
session_start();

require '../includes/auth.php';
require '../includes/admin_only.php';
require '../includes/db.php';
require '../includes/config.php';

$success='';
$error='';

/* ================== ACTION MAP ================== */

$action_map = [

    /* คลังยา */
    'drug_usage' => ['daily_usage'],
    'drug_sub'   => ['sub_warehouse'],
    'drug_main'  => ['main_warehouse'],
    'drug_all'   => ['daily_usage','sub_warehouse','main_warehouse'],

    /* มิใช่ยา */
    'delete_usage' => ['non_drug_usage'],
    'delete_warehouse' => ['non_drug_warehouse'],
    'non_drug' => ['non_drug_usage','non_drug_warehouse']

];

/* ================== DELETE PROCESS ================== */

if($_SERVER['REQUEST_METHOD']==='POST'){

    $action = $_POST['action'] ?? '';
    $unit_code = $_POST['unit_code'] ?? '';

    if(empty($unit_code)){
        header("Location: delete_table.php?error=unit");
        exit;
    }

    $stmt = $conn->prepare("SELECT unit_id FROM units WHERE unit_code=?");
    $stmt->bind_param("s",$unit_code);
    $stmt->execute();
    $unit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if(!$unit){
        header("Location: delete_table.php?error=notfound");
        exit;
    }

    $delete_order = $action_map[$action] ?? [];

    if(!empty($delete_order)){

        $conn->begin_transaction();

        try{

            foreach($delete_order as $table){

                $sql="DELETE FROM `$table` WHERE unit_id=?";
                $stmt=$conn->prepare($sql);
                $stmt->bind_param("i",$unit['unit_id']);
                $stmt->execute();
                $stmt->close();

            }

            $conn->commit();

            header("Location: delete_table.php?success=".$action);
            exit;

        }catch(Exception $e){

            $conn->rollback();
            header("Location: delete_table.php?error=".$action);
            exit;

        }

    }

}


?>

<?php
if(isset($_GET['success'])){

    $msg=[
        'drug_usage'=>'ลบใช้ยารายวันสำเร็จ',
        'drug_sub'=>'ลบคลังย่อยสำเร็จ',
        'drug_main'=>'ลบคลังใหญ่สำเร็จ',
        'drug_all'=>'ลบคลังยาทั้งหมดสำเร็จ',
        'delete_usage'=>'ลบการใช้เวชภัณฑ์สำเร็จ',
        'delete_warehouse'=>'ลบคลังมิใช่ยาสำเร็จ',
        'non_drug'=>'ลบคลังมิใช่ยาทั้งหมดสำเร็จ'
    ];

    $success=$msg[$_GET['success']] ?? '';
}

 ?>
 
 
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ล้างข้อมูลระบบ </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
	<link rel="icon" href="../assets/img/hospital-icon.png" type="image/png">
</head>
<body>

<?php require '../head2.php'; ?>
<?php require 'bar0.php'; ?>

		<div class="container mt-4">

		<h4 class="fw-bold text-danger mb-3">
		<i class="bi bi-exclamation-triangle"></i>
		ล้างข้อมูลระบบ (Admin Only)
		</h4>

		<div class="alert alert-warning">
		⚠️ ลบแล้วกู้คืนไม่ได้ โปรดระมัดระวังในการลบตาราง
		</div>

		<?php if ($success): ?>
		<div class="alert alert-success"><?= $success ?></div>
		<?php endif; ?>

		<?php if ($error): ?>
		<div class="alert alert-danger"><?= $error ?></div>
		<?php endif; ?>


		<!-- ===== เลือกหน่วยบริการ ===== -->
		<div class="card mb-4 shadow-sm border-primary">

		<div class="card-body">

		<label for="unit_select" class="fw-bold mb-2">
		<i class="bi bi-hospital"></i> เลือกหน่วยบริการ
		</label>

		<select id="unit_select" class="form-select">

		<option value="">-- เลือกหน่วยบริการ --</option>

		<?php
		$sql = "SELECT unit_code, unit_name 
				FROM units 
				ORDER BY unit_code";

		$q = $conn->query($sql);

		while($row = $q->fetch_assoc()):
		?>

		<option value="<?= htmlspecialchars($row['unit_code']) ?>">
		<?= htmlspecialchars($row['unit_name']) ?>
		(<?= htmlspecialchars($row['unit_code']) ?>)
		</option>

		<?php endwhile; ?>

		</select>

		<small class="text-muted">
		เลือกก่อนกดล้างข้อมูล
		</small>

		</div>
		</div>


<?php
$drug_tables = [

    'drug_usage' => 'ลบ 💊 ใช้ยารายวัน',
    'drug_sub'   => 'ลบ 🏪 คลังย่อย',
    'drug_main'  => 'ลบ 🏬 คลังใหญ่',

];
?>
<!-- ===== คลังยา ===== -->
<div class="card shadow-sm mb-4 border-danger">

    <div class="card-header bg-danger text-white fw-bold">
        <i class="bi bi-capsule"></i> คลังยา
    </div>

    <div class="card-body">

        <div class="row g-3">

        <?php foreach ($drug_tables as $action => $label): ?>

            <div class="col-md-3">

                <div class="border rounded p-3 text-center h-100">

                    <div class="fw-bold mb-2"><?= $label ?></div>

                    <form method="post"
                          onsubmit="return confirm('ยืนยันลบข้อมูลคลังยา ?');">

                        <!-- ✅ ส่ง action ใหม่ -->
                        <input type="hidden" name="action" value="<?= $action ?>">

                        <!-- ✅ unit_code ต้องเป็นหน่วยบริการจริง -->
                        <input type="hidden" name="unit_code" value="<?= $unit_code ?>">

                        <button class="btn btn-danger delete-btn w-100" disabled>
                            <i class="bi bi-trash"></i> ลบข้อมูล
                        </button>

                    </form>

                </div>

            </div>

        <?php endforeach; ?>


        <!-- ⭐ ปุ่มลบทั้งหมด -->
        <div class="col-md-3">

            <div class="border rounded p-3 text-center h-100">

                <div class="fw-bold mb-2">🔥 ลบคลังยาทั้งหมด</div>

                <form method="post"
                      onsubmit="return confirm('ยืนยันลบคลังยาทั้งหมด ?');">

                    <input type="hidden" name="action" value="drug_all">
                    <input type="hidden" name="unit_code" value="<?= $unit_code ?>">

                    <button class="btn btn-dark delete-btn w-100" disabled>
                        <i class="bi bi-trash"></i> ลบทั้งหมด
                    </button>

                </form>

            </div>

        </div>

        </div>

    </div>
</div>



<!-- ===== คลังมิใช่ยา ===== -->
<div class="card shadow-sm border-secondary">

    <div class="card-header bg-secondary text-white fw-bold">
        <i class="bi bi-box-seam"></i> คลังมิใช่ยา
    </div>

    <div class="card-body">

        <div class="row g-3">

            <!-- ===== ปุ่มที่ 1 ===== -->
            <div class="col-md-4">
                <div class="border rounded p-3 text-center h-100">

                    <div class="fw-bold mb-2">ลบ การใช้เวชภัณฑ์มิใช่ยา</div>

                    <form method="post" onsubmit="return confirm('ยืนยันลบข้อมูล non_drug_usage ?');">

                        <input type="hidden" name="action" value="delete_usage">
                        <input type="hidden" name="unit_code">

                        <button class="btn btn-secondary w-100 delete-btn" disabled>
                            <i class="bi bi-trash"></i> ลบ การใช้เวชภัณฑ์มิใช่ยา
                        </button>

                    </form>

                </div>
            </div>

            <!-- ===== ปุ่มที่ 2 ===== -->
            <div class="col-md-4">
                <div class="border rounded p-3 text-center h-100">

                    <div class="fw-bold mb-2">ลบ คลังใหญ่เวชภัณฑ์มิใช่ยา</div>

                    <form method="post" onsubmit="return confirm('ยืนยันลบข้อมูล non_drug_warehouse ?');">

                        <input type="hidden" name="action" value="delete_warehouse">
                        <input type="hidden" name="unit_code">

                        <button class="btn btn-secondary w-100 delete-btn" disabled>
                            <i class="bi bi-trash"></i> ลบ คลังใหญ่เวชภัณฑ์มิใช่ยา
                        </button>

                    </form>

                </div>
            </div>

            <!-- ===== ปุ่มที่ 3 ===== -->
            <div class="col-md-4">
                <div class="border rounded p-3 text-center h-100">

                    <div class="fw-bold mb-2">🔥 ลบคลังใหญ่เวชภัณฑ์มิใช่ยาทั้งหมด</div>

                    <form method="post" onsubmit="return confirm('ยืนยันลบข้อมูลคลังมิใช่ยาทั้งหมด ?');">

                        <input type="hidden" name="action" value="non_drug">
                        <input type="hidden" name="unit_code">

                        <button class="btn btn-danger w-100 delete-btn" disabled>
                            <i class="bi bi-trash"></i> ลบทั้งหมด
                        </button>

                    </form>

                </div>
            </div>

        </div>

    </div>
</div>


	</div>

		<script>

		const selectUnit = document.getElementById('unit_select');

		selectUnit.addEventListener('change', function(){

			let unit = this.value;

			document.querySelectorAll('.delete-btn').forEach(btn=>{

				let form = btn.closest('form');

				if(unit){
					btn.disabled = false;
					form.querySelector('[name=unit_code]').value = unit;
				}else{
					btn.disabled = true;
					form.querySelector('[name=unit_code]').value = '';
				}

			});

		});

		</script>

		<script>
		document.getElementById('toggleSidebar').addEventListener('click', function () {
		  document.getElementById('sidebar').classList.toggle('collapsed');
		});
		</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
