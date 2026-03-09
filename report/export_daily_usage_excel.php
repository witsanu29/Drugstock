<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require '../includes/admin_only.php';

$role    = $_SESSION['role'] ?? '';
$unit_id = intval($_SESSION['unit_id'] ?? 0);

/* ===== Filter ===== */
$filter_unit = intval($_GET['unit_id'] ?? 0);

/* ===== WHERE ===== */
$where = [];

if ($role !== 'admin') {
    $where[] = "m.unit_id = $unit_id";
} elseif ($filter_unit > 0) {
    $where[] = "m.unit_id = $filter_unit";
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* ===== Header Excel ===== */
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=รายงานการใช้ยารายวัน.xls");
header("Pragma: no-cache");
header("Expires: 0");

/* ป้องกันภาษาไทยเพี้ยน */
echo "\xEF\xBB\xBF";

/* ===== Query ===== */
$sql = "
    SELECT 
        d.usage_date,
        s.sub_name,
        u.unit_code,
        m.drug_name,
        d.quantity_used,
        m.units AS drug_unit
    FROM daily_usage d
    LEFT JOIN sub_warehouse s ON d.sub_id = s.id
    LEFT JOIN main_warehouse m ON s.drug_id = m.id
    LEFT JOIN units u ON m.unit_id = u.unit_id
    $where_sql
    ORDER BY d.usage_date DESC
";

$res = $conn->query($sql);
?>

<table border="1" cellpadding="5">
    <thead>
        <!-- 🔹 ชื่อรายงาน -->
        <tr>
            <th colspan="7" style="font-size:16px; text-align:center; font-weight:bold;">
                รายงานการใช้ยารายวัน
            </th>
        </tr>
        <tr>
            <th colspan="7" style="text-align:center;">
                วันที่ออกรายงาน :
                <?= date('d/m/') . (date('Y') + 543); ?>
            </th>
        </tr>

        <!-- 🔹 หัวตาราง -->
        <tr style="background:#e9ecef; font-weight:bold;">
            <th>ลำดับ</th>
            <th>วันที่ใช้</th>
            <th>คลังย่อย</th>
            <th>หน่วยบริการ</th>
            <th>ชื่อยา</th>
            <th>จำนวนใช้</th>
            <th>หน่วยนับ</th>
        </tr>
    </thead>

    <tbody>
    <?php
    $no = 1;
    if ($res && $res->num_rows > 0):
        while ($r = $res->fetch_assoc()):
            $thai_date = date('d/m/', strtotime($r['usage_date']))
                       . (date('Y', strtotime($r['usage_date'])) + 543);
    ?>
        <tr>
            <td align="center"><?= $no++ ?></td>
            <td align="center"><?= $thai_date ?></td>
            <td><?= htmlspecialchars($r['sub_name']) ?></td>
            <td><?= htmlspecialchars($r['unit_code']) ?></td>
            <td><?= htmlspecialchars($r['drug_name']) ?></td>
            <td align="right"><?= number_format($r['quantity_used']) ?></td>
            <td align="center"><?= htmlspecialchars($r['drug_unit']) ?></td>
        </tr>
    <?php
        endwhile;
    else:
    ?>
        <tr>
            <td colspan="7" align="center">ไม่พบข้อมูลการใช้ยา</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
