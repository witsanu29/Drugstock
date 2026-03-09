<?php
require '../includes/db.php';
require '../includes/auth.php';

// ====== Excel UTF-8 ======
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=เวชภัณฑ์ใกล้หมดอายุ.xls");
header("Pragma: no-cache");
header("Expires: 0");

// BOM สำหรับ Excel ภาษาไทย
echo "\xEF\xBB\xBF";

		// query ข้อมูล
		$sql = "
		SELECT item_name, remaining, expiry_date,
		DATEDIFF(expiry_date, CURDATE()) AS days_left
		FROM non_drug_warehouse
		WHERE expiry_date IS NOT NULL
		AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
		ORDER BY expiry_date ASC
		";

$res = $conn->query($sql);
?>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<table border="1">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th>ชื่อเวชภัณฑ์มิใช่ยา</th>
            <th>คงเหลือ</th>
            <th>วันหมดอายุ (พ.ศ.)</th>
            <th>เหลือ (วัน)</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $res->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['item_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $row['remaining'] ?></td>
            <td>
                <?php
                if (!empty($row['expiry_date'])) {
                    $date = new DateTime($row['expiry_date']);
                    echo $date->modify('+543 years')->format('d/m/Y');
                } else {
                    echo '-';
                }
                ?>
            </td>
            <td><?= $row['days_left'] ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
