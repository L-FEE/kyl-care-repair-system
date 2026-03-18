<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit;

$type = $_GET['type'] ?? '';
$parent_id = $_GET['parent_id'] ?? 0;

if ($type == 'floor') {
    echo json_encode($conn->query("SELECT * FROM floors ORDER BY floor_name ASC")->fetch_all(MYSQLI_ASSOC));
} 

elseif ($type == 'location') {
    $stmt = $conn->prepare("SELECT * FROM locations WHERE floor_id = ? ORDER BY location_name ASC");
    $stmt->bind_param("i", $parent_id); 
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
} 

elseif ($type == 'category') {
    // แก้ไข Logic การเรียงลำดับ: 
    // (category_name = 'อื่นๆ') จะคืนค่า 1 ถ้าจริง และ 0 ถ้าเท็จ 
    // ดังนั้นรายการปกติ (0) จะขึ้นก่อน และ 'อื่นๆ' (1) จะลงไปอยู่ล่างสุด
    $sql = "SELECT * FROM device_categories 
            ORDER BY (category_name = 'อื่นๆ') ASC, category_name ASC";
    echo json_encode($conn->query($sql)->fetch_all(MYSQLI_ASSOC));
} 

elseif ($type == 'device') {
    $stmt = $conn->prepare("SELECT * FROM devices WHERE category_id = ? ORDER BY device_name ASC");
    $stmt->bind_param("i", $parent_id); 
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}
elseif ($type == 'fault') {
    $stmt = $conn->prepare("SELECT * FROM faults WHERE device_id = ? ORDER BY fault_name ASC");
    $stmt->bind_param("i", $parent_id); 
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}