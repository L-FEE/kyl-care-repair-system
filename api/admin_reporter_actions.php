<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? 0;

if ($action === 'add_reporter' || $action === 'edit_reporter') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $office_phone = trim($_POST['office_phone']);
    $email = trim($_POST['email']);

    // Validation
    if (!validatePhone($phone)) {
        echo json_encode(['success' => false, 'error' => 'เบอร์ส่วนตัวต้องขึ้นต้นด้วย 06,08,09 และมี 10 หลัก']); exit;
    }
    if (strlen($office_phone) !== 4) {
        echo json_encode(['success' => false, 'error' => 'เบอร์ที่ทำงานต้องเป็นตัวเลข 4 หลัก']); exit;
    }

    if ($action === 'add_reporter') {
        $stmt = $conn->prepare("INSERT INTO reporters (full_name, phone, office_phone, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $phone, $office_phone, $email);
    } else {
        $stmt = $conn->prepare("UPDATE reporters SET full_name=?, phone=?, office_phone=?, email=? WHERE id=?");
        $stmt->bind_param("ssssi", $full_name, $phone, $office_phone, $email, $id);
    }

    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'error' => $conn->error]);
}

elseif ($action === 'delete_reporter') {
    $stmt = $conn->prepare("DELETE FROM reporters WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'error' => 'ไม่สามารถลบได้เนื่องจากชื่อนี้มีประวัติการแจ้งซ่อมอยู่']);
}

if ($action === 'toggle_status') {
    $id = $_POST['id'];
    $status = $_POST['status']; 
    $stmt = $conn->prepare("UPDATE reporters SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    if($stmt->execute()) echo json_encode(['success' => true]);
    exit;
}