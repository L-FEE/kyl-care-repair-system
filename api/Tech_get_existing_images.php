<?php
/*
|------------------------------------------------------
| File: api/tech_get_existing_images.php
| Description: ดึงรูปภาพที่มีอยู่ของงาน
|------------------------------------------------------
*/

session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    echo json_encode([]);
    exit;
}

$job_id = $_GET['job_id'] ?? 0;

if (!$job_id) {
    echo json_encode([]);
    exit;
}

try {
    // ตรวจสอบว่างานนี้เป็นของช่างคนนี้
    $stmt_check = $conn->prepare("SELECT id FROM repair_requests WHERE id = ? AND technician_id = ?");
    $stmt_check->bind_param("ii", $job_id, $_SESSION['user_id']);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows == 0) {
        echo json_encode([]);
        exit;
    }

    // ดึงรูปภาพ
    $stmt = $conn->prepare("SELECT image_path FROM repair_images WHERE repair_request_id = ? ORDER BY id ASC");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    
    $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($images);
    
} catch (Exception $e) {
    echo json_encode([]);
}

$conn->close();