<?php
/*
|------------------------------------------------------
| File: api/export_excel_all.php
| Description: ส่งออกข้อมูลรายการแจ้งซ่อมทั้งหมด (กรองตามชื่ออุปกรณ์)
|------------------------------------------------------
*/
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// 1. ตรวจสอบสิทธิ์ (แอดมินเท่านั้น)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access");
}

// 2. รับค่าตัวกรองเหมือนหน้า all_repairs.php ล่าสุด
$month    = $_GET['month'] ?? date('n');
$year     = $_GET['year'] ?? date('Y');
$status   = $_GET['status'] ?? 'all';
$device   = $_GET['device'] ?? 'all'; // เปลี่ยนจาก category มาเป็น device
$search   = trim($_GET['search'] ?? '');

$month_names = ['all' => "ทุกเดือน", 1 => "มกราคม", 2 => "กุมภาพันธ์", 3 => "มีนาคม", 4 => "เมษายน", 5 => "พฤษภาคม", 6 => "มิถุนายน", 7 => "กรกฎาคม", 8 => "สิงหาคม", 9 => "กันยายน", 10 => "ตุลาคม", 11 => "พฤศจิกายน", 12 => "ธันวาคม"];

// 3. เตรียมเงื่อนไข Query (WHERE)
$where_clauses = ["YEAR(r.created_at) = $year"];

if ($month !== 'all') {
    $where_clauses[] = "MONTH(r.created_at) = $month";
}
if ($status !== 'all') {
    $where_clauses[] = "r.status = '$status'";
}
if ($device !== 'all') {
    $safe_dev = $conn->real_escape_string($device);
    $where_clauses[] = "r.device_name = '$safe_dev'"; // เปลี่ยนการกรองตรงนี้
}
if ($search !== '') {
    $safe_search = $conn->real_escape_string($search);
    $where_clauses[] = "(r.request_code LIKE '%$safe_search%' OR r.reporter_name LIKE '%$safe_search%' OR r.serial_number LIKE '%$safe_search%')";
}

$where_sql = "WHERE " . implode(' AND ', $where_clauses);

// 4. Query ข้อมูลพร้อมรายชื่อช่าง
$sql = "SELECT r.*, u.full_name as tech_name 
        FROM repair_requests r 
        LEFT JOIN users u ON r.technician_id = u.id 
        $where_sql 
        ORDER BY r.created_at DESC";

$res = $conn->query($sql);

// 5. ตั้งค่า Header สำหรับดาวน์โหลด Excel
$filename = "KYL_Care_Report_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// สำคัญ: ใส่ BOM เพื่อให้ภาษาไทยไม่เพี้ยน
echo "\xEF\xBB\xBF"; 
?>

<style>
    .text-format { mso-number-format:"\@"; } /* ล็อคให้เบอร์โทร/SN ไม่เพี้ยน */
    th { background-color: #003366; color: #ffffff; border: 1px solid #000; padding: 10px; }
    td { border: 1px solid #ccc; padding: 5px; vertical-align: middle; }
</style>

<table border="1">
    <thead>
        <tr>
            <th colspan="13" style="font-size: 22px; height: 50px; text-align: center;">รายงานการแจ้งซ่อมอุปกรณ์ทั้งหมด</th>
        </tr>
        <tr style="background-color: #f1f1f1; height: 30px;">
            <th colspan="13" style="text-align: center; color: #333;">
                เดือน: <?= $month_names[$month] ?> ปี: <?= $year ?> | อุปกรณ์: <?= $device ?> | สถานะ: <?= $status ?>
            </th>
        </tr>
        <tr>
            <th>รหัสงาน</th>
            <th>วันที่แจ้ง</th>
            <th>ชื่อผู้แจ้ง</th>
            <th>เบอร์มือถือ</th>
            <th>เบอร์ภายใน</th>
            <th>สถานที่ / ห้อง</th>
            <th>ชื่ออุปกรณ์</th>
            <th>ประเภทอุปกรณ์</th>
            <th>Serial Number</th>
            <th>อาการที่พบ</th>
            <th>สถานะล่าสุด</th>
            <th>ชื่อช่างที่ดำเนินการ</th>
            <th>วันที่ซ่อมเสร็จ</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $res->fetch_assoc()): ?>
        <tr>
            <td align="center"><?= $row['request_code'] ?></td>
            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
            <td><?= h($row['reporter_name']) ?></td>
            <td class="text-format" align="center"><?= h($row['reporter_phone']) ?></td>
            <td class="text-format" align="center"><?= h($row['office_phone']) ?></td>
            <td><?= h($row['floor_name']) ?> - <?= h($row['location_name']) ?></td>
            <td><?= h($row['device_name']) ?></td>
            <td><?= h($row['category_name']) ?></td>
            <td class="text-format" align="center"><?= h($row['serial_number']) ?></td>
            <td><?= h($row['fault_name']) ?></td>
            <td align="center"><?php 
                $st_list = [
                    'pending' => 'รอดำเนินการ',
                    'accepted' => 'รับงานแล้ว',
                    'in_progress' => 'กำลังซ่อม',
                    'waiting_parts' => 'รออะไหล่',
                    'completed' => 'สำเร็จ',
                    'cannot_repair' => 'ไม่ได้'
                ];
                echo $st_list[$row['status']] ?? $row['status'];
            ?></td>
            <td><?= h($row['tech_name'] ?: 'รอยืนยัน') ?></td>
            <td><?= ($row['finished_at']) ? date('d/m/Y H:i', strtotime($row['finished_at'])) : '-' ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>