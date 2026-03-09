<?php
require '../includes/db.php';
require '../includes/auth.php';

if (!in_array($_SESSION['role'], ['admin','staff'])) {
    die('⛔ ไม่มีสิทธิ์อัปโหลด');
}

$UNIT_LIST = [
    'เม็ด','แคปซูล','ขวด','หลอด','ซอง',
    'Amp','แผ่น','แท่ง','แผง','กระปุก','อัน','Vial'
];

$success = 0;
$errors  = [];

if (isset($_POST['import']) && isset($_FILES['csv_file'])) {

    $unit_id = $_SESSION['unit_id'];
    $file    = $_FILES['csv_file']['tmp_name'];

    if (!is_uploaded_file($file)) {
        $errors[] = 'ไม่พบไฟล์ CSV';
    } else {

        if (($handle = fopen($file, "r")) !== FALSE) {

            $row = 1;
            fgetcsv($handle); // ข้าม header

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;

                if (count($data) < 8) {
                    $errors[] = "แถวที่ $row : จำนวนคอลัมน์ไม่ครบ";
                    continue;
                }

                [
                    $drug_name,
                    $lot_no,
                    $units,
                    $quantity,
                    $price,
                    $received_date,
                    $expiry_date,
                    $note
                ] = $data;

                if (!$drug_name || !$lot_no || !$units || !$quantity || !$received_date) {
                    $errors[] = "แถวที่ $row : ข้อมูลจำเป็นไม่ครบ";
                    continue;
                }

                if (!in_array($units, $UNIT_LIST)) {
                    $errors[] = "แถวที่ $row : หน่วยยาไม่ถูกต้อง ($units)";
                    continue;
                }

                $quantity     = (int)$quantity;
                $price        = (float)$price;
                $remaining    = $quantity;
                $expiry_date  = $expiry_date ?: null;

                if ($expiry_date === null) {
                    $stmt = $conn->prepare("
                        INSERT INTO main_warehouse
                        (unit_id, drug_name, lot_no, units, quantity, price, received_date, remaining, note)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param(
                        "isssidiss",
                        $unit_id, $drug_name, $lot_no, $units,
                        $quantity, $price, $received_date, $remaining, $note
                    );
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO main_warehouse
                        (unit_id, drug_name, lot_no, units, quantity, price, received_date, expiry_date, remaining, note)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param(
                        "isssidssis",
                        $unit_id, $drug_name, $lot_no, $units,
                        $quantity, $price, $received_date, $expiry_date, $remaining, $note
                    );
                }

                if ($stmt->execute()) {
                    $success++;
                } else {
                    $errors[] = "แถวที่ $row : บันทึกไม่สำเร็จ";
                }

                $stmt->close();
            }

            fclose($handle);
        }
    }
}
?>



<?php
require '../head2.php';
require 'bar.php';
?>

<div class="container py-4">
<div class="card shadow">
    <div class="card-header bg-success text-white">
        📥 นำเข้ายาเข้าคลัง (CSV)
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>CSV Columns</strong><br>
        </div>

<?php if ($success > 0): ?>
<div class="alert alert-success">
    ✅ นำเข้าสำเร็จ <?= $success ?> รายการ
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <strong>พบข้อผิดพลาด</strong>
    <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>


<form method="post" enctype="multipart/form-data">
    <input type="file"
           name="csv_file"
           accept=".csv"
           class="form-control mb-3"
           required>

    <button class="btn btn-success" name="import">
        <i class="bi bi-upload"></i> นำเข้า CSV
    </button>

    <a href="main_warehouse.php" class="btn btn-secondary">
        ⬅ กลับ
    </a>
</form>


    </div>
</div>
</div>

<?php include '../includes/footer.php'; ?>
