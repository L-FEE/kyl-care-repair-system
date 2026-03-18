<?php
/*
|------------------------------------------------------
| File: api/admin_delete_actions.php
| Description: API จัดการการลบประวัติงานและรูปภาพในเซิร์ฟเวอร์
|------------------------------------------------------
*/
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
}

$action = $_POST['action'] ?? '';
$ids_to_delete = [];

// เตรียมเงื่อนไขไอดีที่จะลบ
if ($action === 'delete_single') {
    $ids_to_delete[] = (int)$_POST['id'];
} elseif ($action === 'delete_selected') {
    $ids_to_delete = array_map('intval', $_POST['ids']);
} elseif ($action === 'delete_range') {
    $m = $_POST['month'];
    $y = (int)$_POST['year'];
    $sql_find = "SELECT id FROM repair_requests WHERE YEAR(created_at) = $y";
    if($m !== 'all') $sql_find .= " AND MONTH(created_at) = " . (int)$m;
    
    $res = $conn->query($sql_find);
    while($r = $res->fetch_assoc()) $ids_to_delete[] = $r['id'];
}

if (empty($ids_to_delete)) {
    echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลที่ต้องการจัดการ']); exit;
}

// --- เริ่มขั้นตอนลบไฟล์รูปภาพในเครื่องก่อน (File System Cleanup) ---
$in_list = implode(',', $ids_to_delete);
$sql_images = "SELECT image_path FROM repair_images WHERE repair_request_id IN ($in_list)";
$res_imgs = $conn->query($sql_images);
while ($row = $res_imgs->fetch_assoc()) {
    $full_path = "../" . $row['image_path'];
    if (file_exists($full_path) && !empty($row['image_path'])) {
        unlink($full_path); // สั่งลบไฟล์จริงทิ้ง
    }
}

// --- เริ่มลบข้อมูลในฐานข้อมูล (MySQL) ---
$conn->begin_transaction();
try {
    // 1. ลบจากตาราง repair_images (ลูก)
    $conn->query("DELETE FROM repair_images WHERE repair_request_id IN ($in_list)");
    // 2. ลบจากตาราง logs (ลูก)
    $conn->query("DELETE FROM repair_status_logs WHERE repair_request_id IN ($in_list)");
    // 3. ลบจากตารางหลัก (แม่)
    $conn->query("DELETE FROM repair_requests WHERE id IN ($in_list)");

    $conn->commit();
    echo json_encode(['success' => true, 'count' => count($ids_to_delete)]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาดในการเข้าถึงฐานข้อมูล: ' . $e->getMessage()]);
}

$conn->close();