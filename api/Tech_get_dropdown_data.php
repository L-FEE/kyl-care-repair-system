<?php
/*
|------------------------------------------------------
| File: api/tech_get_dropdown_data.php
| Description: ดึงข้อมูล dropdown สำหรับช่างเทคนิค (locations, categories, devices, faults)
|------------------------------------------------------
*/

session_start();
require_once __DIR__ . '/../config/db.php';
// require_once '../config/db.php';
header('Content-Type: application/json');

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$type = $_GET['type'] ?? '';
$parent_id = $_GET['parent_id'] ?? 0;

try {
    // ดึงข้อมูลสถานที่ทั้งหมด (รวมชั้นและห้อง)
    if ($type == 'locations') {
        $sql = "SELECT l.id, f.floor_name, l.location_name, 
                CONCAT(f.floor_name, ' - ', l.location_name) as display_name
                FROM locations l 
                JOIN floors f ON l.floor_id = f.id 
                ORDER BY f.floor_name ASC, l.location_name ASC";
        $result = $conn->query($sql);
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    }
    
    // ดึงประเภทอุปกรณ์ทั้งหมด
    elseif ($type == 'categories') {
        // เรียงลำดับโดยให้ 'อื่นๆ' อยู่ท้ายสุด
        $sql = "SELECT id, category_name 
                FROM device_categories 
                ORDER BY (category_name = 'อื่นๆ') ASC, category_name ASC";
        $result = $conn->query($sql);
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    }
    
    // ดึงอุปกรณ์ตามประเภทที่เลือก
    elseif ($type == 'devices' && $parent_id > 0) {
        $stmt = $conn->prepare("SELECT id, device_name 
                                FROM devices 
                                WHERE category_id = ? 
                                ORDER BY device_name ASC");
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
    
    // ดึงอาการเสียตามอุปกรณ์ที่เลือก
    elseif ($type == 'faults' && $parent_id > 0) {
        $stmt = $conn->prepare("SELECT id, fault_name 
                                FROM faults 
                                WHERE device_id = ? 
                                ORDER BY fault_name ASC");
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
    
    else {
        echo json_encode([]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();