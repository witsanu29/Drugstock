<?php
require '../includes/db.php';
require '../includes/auth.php';

/* ตั้งค่า Header ให้ Excel */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=รายงานคงเหลือเวชภัณฑ์มิใช่ยา.xls");
header("Pragma: no-cache");
header("Expires: 0");

/* บังคับ UTF-8 ให้ Excel อ่านภาษาไทย */
echo "\xEF\xBB\xBF";

$sql = "SELECT * FROM non_drug_warehouse ORDER BY remaining ASC";
$res = $conn->query($sql);
?>

<table border="1">
	<thead>
	<tr style="background:#e9ecef;font-weight:bold;">
		<th>ลำดับ</th>
		<th>ชื่อเวชภัณฑ์มิใช่ยา</th>
		<th>คงเหลือ</th>
		<th>หน่วย</th>
		<th>วันหมดอายุ</th>
		<th>สถานะ</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$i = 1;
	while ($row = $res->fetch_assoc()):

		$status = "ปกติ";
		if ($row['remaining'] <= 10) $status = "ใกล้หมด";
		if ($row['remaining'] == 0)  $status = "หมด";

		// แปลงวันหมดอายุเป็น พ.ศ.
		$expiry = "-";
		if (!empty($row['expiry_date'])) {
			$d = new DateTime($row['expiry_date']);
			$d->modify('+543 years');
			$expiry = $d->format('d/m/Y');
		}
	?>
	<tr>
		<td><?= $i++ ?></td>
		<td><?= htmlspecialchars($row['item_name']) ?></td>
		<td><?= $row['remaining'] ?></td>
		<td><?= $row['unit'] ?></td>
		<td><?= $expiry ?></td>
		<td><?= $status ?></td>
	</tr>
	<?php endwhile; ?>
	</tbody>
</table>
