<?php
/*
|------------------------------------------------------
| File: api/admin_asset_actions.php
| Description: ประมวลผลการ เพิ่ม/แก้ไข/ลบ ข้อมูลสถานที่และอุปกรณ์
|------------------------------------------------------
*/

session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// 1. ตรวจสอบสิทธิ์ (ต้องเป็นแอดมินเท่านั้น)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// 2. รับค่าที่ส่งมาจากฟอร์ม
$action    = $_POST['action'] ?? '';
$type      = $_POST['type'] ?? '';
$id        = $_POST['id'] ?? 0;
$parent_id = $_POST['parent_id'] ?? 0;
$name      = trim($_POST['name'] ?? '');

// 3. กำหนดโครงสร้างตารางและคอลัมน์
$table_map = [
    'floor'    => ['t' => 'floors',            'c' => 'floor_name'],
    'location' => ['t' => 'locations',         'c' => 'location_name', 'p' => 'floor_id'],
    'category' => ['t' => 'device_categories', 'c' => 'category_name'],
    'device'   => ['t' => 'devices',           'c' => 'device_name',   'p' => 'category_id'],
    'fault'    => ['t' => 'faults',            'c' => 'fault_name',    'p' => 'device_id']
];

if (!isset($table_map[$type])) {
    echo json_encode(['success' => false, 'error' => 'ประเภทข้อมูลไม่ถูกต้อง']);
    exit;
}

$target_table = $table_map[$type]['t'];
$target_col   = $table_map[$type]['c'];

// -------------------------------------------------------------------
// ส่วนที่ 1: เพิ่มข้อมูลใหม่ (Add)
// -------------------------------------------------------------------
if ($action === 'add') {
    if ($name === "") {
        echo json_encode(['success' => false, 'error' => 'กรุณาระบุชื่อรายการ']);
        exit;
    }

    try {
        if ($type === 'fault') {
            // กรณีอาการเสีย: ต้องดึง category_id จาก device_id มาบันทึกด้วยเพื่อให้ข้อมูลสมบูรณ์
            $stmt_get_cat = $conn->prepare("SELECT category_id FROM devices WHERE id = ?");
            $stmt_get_cat->bind_param("i", $parent_id);
            $stmt_get_cat->execute();
            $cat_res = $stmt_get_cat->get_result()->fetch_assoc();
            $cat_id = $cat_res['category_id'] ?? 0;

            $stmt = $conn->prepare("INSERT INTO faults (fault_name, device_id, category_id) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $name, $parent_id, $cat_id);
        } 
        elseif (isset($table_map[$type]['p'])) {
            // กรณีที่มีข้อมูลระดับบน (Location, Device)
            $parent_col = $table_map[$type]['p'];
            $stmt = $conn->prepare("INSERT INTO $target_table ($target_col, $parent_col) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $parent_id);
        } 
        else {
            // กรณีข้อมูลระดับบนสุด (Floor, Category)
            $stmt = $conn->prepare("INSERT INTO $target_table ($target_col) VALUES (?)");
            $stmt->bind_param("s", $name);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'ไม่สามารถเพิ่มข้อมูลได้: ' . $e->getMessage()]);
    }
}

// -------------------------------------------------------------------
// ส่วนที่ 2: แก้ไขข้อมูลเดิม (Edit)
// -------------------------------------------------------------------
elseif ($action === 'edit') {
    if ($name === "" || !$id) {
        echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบถ้วน']);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE $target_table SET $target_col = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'ไม่สามารถแก้ไขได้: ' . $e->getMessage()]);
    }
}

// -------------------------------------------------------------------
// ส่วนที่ 3: ลบข้อมูล (Delete)
// -------------------------------------------------------------------
elseif ($action === 'delete') {
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบรหัสที่ต้องการลบ']);
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM $target_table WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            // กรณีติด Foreign Key (มีการใช้งานข้อมูลนี้อยู่ในรายการแจ้งซ่อม)
            throw new Exception("ข้อมูลนี้ถูกนำไปใช้งานอยู่ ไม่สามารถลบได้");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'ลบไม่สำเร็จ: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'error' => 'Action ไม่ถูกต้อง']);
}

$conn->close();