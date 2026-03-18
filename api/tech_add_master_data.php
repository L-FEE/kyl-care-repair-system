<?php
/*
|------------------------------------------------------
| File: api/tech_add_master_data.php
| Description: API สำหรับช่างเทคนิคเพิ่มข้อมูล master data
|------------------------------------------------------
*/

session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$type = $_POST['type'] ?? '';
$name = trim($_POST['name'] ?? '');
$parent_id = $_POST['parent_id'] ?? 0;
$category_name = trim($_POST['category_name'] ?? '');
$device_name = trim($_POST['device_name'] ?? '');

if (!$name) {
    echo json_encode(['success' => false, 'error' => 'กรุณาระบุชื่อ']);
    exit;
}

try {
    $conn->begin_transaction();
    
    if ($type === 'category') {
        // เพิ่มประเภทอุปกรณ์
        $stmt = $conn->prepare("INSERT INTO device_categories (category_name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $new_id = $conn->insert_id;
        
    } elseif ($type === 'device') {
        // เพิ่มอุปกรณ์
        // ถ้าไม่มี parent_id ให้หาจากชื่อ category
        if (!$parent_id && $category_name) {
            $stmt_find = $conn->prepare("SELECT id FROM device_categories WHERE category_name = ? LIMIT 1");
            $stmt_find->bind_param("s", $category_name);
            $stmt_find->execute();
            $result = $stmt_find->get_result()->fetch_assoc();
            $parent_id = $result['id'] ?? 0;
        }
        
        if (!$parent_id) {
            throw new Exception('ไม่พบประเภทอุปกรณ์');
        }
        
        $stmt = $conn->prepare("INSERT INTO devices (category_id, device_name) VALUES (?, ?)");
        $stmt->bind_param("is", $parent_id, $name);
        $stmt->execute();
        $new_id = $conn->insert_id;
        
    } elseif ($type === 'fault') {
        // เพิ่มอาการเสีย - faults เชื่อมกับ category_id ไม่ใช่ device_id
        if (!$parent_id && $device_name) {
            $stmt_find = $conn->prepare("SELECT id FROM devices WHERE device_name = ? LIMIT 1");
            $stmt_find->bind_param("s", $device_name);
            $stmt_find->execute();
            $result = $stmt_find->get_result()->fetch_assoc();
            if ($result) {
                $parent_id = $result['id'];
            }
        }
        
        if (!$parent_id) {
            throw new Exception('ไม่พบอุปกรณ์');
        }
        
        // หา category_id จาก device_id
        $stmt_cat = $conn->prepare("SELECT category_id FROM devices WHERE id = ?");
        $stmt_cat->bind_param("i", $parent_id);
        $stmt_cat->execute();
        $cat_result = $stmt_cat->get_result()->fetch_assoc();
        
        if (!$cat_result) {
            throw new Exception('ไม่พบข้อมูลประเภทอุปกรณ์');
        }
        
        $category_id = $cat_result['category_id'];
        
        // บันทึกอาการเสีย โดยใช้ทั้ง category_id และ device_id
        $stmt = $conn->prepare("INSERT INTO faults (category_id, device_id, fault_name) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $category_id, $parent_id, $name);
        $stmt->execute();
        $new_id = $conn->insert_id;
        
    } else {
        throw new Exception('ข้อมูลไม่ถูกต้อง');
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'new_id' => $new_id]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();