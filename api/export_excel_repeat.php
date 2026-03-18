<?php
/*
|------------------------------------------------------
| File: api/export_excel_repeat.php
|------------------------------------------------------
*/
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access");
}

// บังคับดาวน์โหลด
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Repair_Repeat_Summary_".date('d_m_Y').".xls");
echo "\xEF\xBB\xBF"; // UTF-8 BOM สำหรับภาษาไทย

// 2. รับค่าตัวกรอง (สำคัญ: ต้องใช้ชื่อ 'range' ให้ตรงกับหน้าหลัก)
$device_filter = $_GET['device'] ?? 'all';
$range_filter  = $_GET['range'] ?? 'all'; 
$current_tab   = $_GET['tab'] ?? 'active';

// 3. จัดการ SQL WHERE
$dev_where = ($device_filter !== 'all') ? "AND r.device_name = '".$conn->real_escape_string($device_filter)."'" : "";

// 4. จัดการ SQL HAVING (ความลับของปัญหา: เราต้องระบุเงื่อนไขให้ครอบคลุมค่า 'all')
if ($range_filter === 'all') {
    $having = "HAVING total >= 2";
} elseif ($range_filter === '2') {
    $having = "HAVING total = 2";
} elseif ($range_filter === '3-4') {
    $having = "HAVING total BETWEEN 3 AND 4";
} else {
    $having = "HAVING total >= 5";
}

$sql = "SELECT serial_number, device_name, category_name, COUNT(*) as total, 
        (SELECT status FROM repair_requests WHERE serial_number = r.serial_number ORDER BY created_at DESC LIMIT 1) as cur_status 
        FROM repair_requests r 
        WHERE r.serial_number != '' AND r.serial_number != 'ไม่มีหมายเลขซีเรียล' 
        $dev_where 
        GROUP BY r.serial_number 
        $having 
        ORDER BY total DESC";

$res = $conn->query($sql);
?>
<style> .txt { mso-number-format:"\@"; } th { background-color: #003366; color: white; border: 1px solid #000; } td { border: 1px solid #ccc; } </style>

<table border="1">
    <thead>
        <tr>
            <th colspan="5" style="background:#003366; color:#fff; font-size:18px; height:50px;">
                รายงานข้อมูลการแจ้งซ่อมอุปกรณ์ (ความถี่: <?= h($range_filter === 'all' ? 'ทุกระดับ' : $range_filter.' ครั้ง') ?>)
            </th>
        </tr>
        <tr style="background:#f1f1f1;">
            <th colspan="5">ประเภทรายการ: <?= h($current_tab == 'active' ? 'ยังอยู่ในระบบ/ซ่อมใช้ต่อ' : 'เสียถาวร/ซ่อมไม่ได้') ?> | อุปกรณ์: <?= h($device_filter) ?></th>
        </tr>
        <tr>
            <th>Serial Number</th>
            <th>ชื่ออุปกรณ์</th>
            <th>ประเภท</th>
            <th>แจ้งแล้ว (ครั้ง)</th>
            <th>สถานะล่าสุด</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $has_data = false;
        while($row = $res->fetch_assoc()): 
            $is_f = ($row['cur_status'] == 'cannot_repair');
            if(($current_tab == 'active' && !$is_f) || ($current_tab == 'failed' && $is_f)):
                $has_data = true;
        ?>
        <tr>
            <td class="txt" align="center"><?= h($row['serial_number']) ?></td>
            <td><?= h($row['device_name']) ?></td>
            <td><?= h($row['category_name']) ?></td>
            <td align="center"><b><?= $row['total'] ?></b></td>
            <td align="center">
                <?php 
                    $st = [
                        'pending' => 'รอซ่อม', 'accepted' => 'รับงาน', 'in_progress' => 'กำลังซ่อม', 
                        'waiting_parts' => 'รออะไหล่', 'completed' => 'สำเร็จ', 'cannot_repair' => 'ไม่ได้'
                    ];
                    echo $st[$row['cur_status']] ?? $row['cur_status'];
                ?>
            </td>
        </tr>
        <?php endif; endwhile; 

        if (!$has_data) {
            echo "<tr><td colspan='5' align='center'>ไม่พบข้อมูลตามเงื่อนไขที่เลือก</td></tr>";
        }
        ?>
    </tbody>
</table>