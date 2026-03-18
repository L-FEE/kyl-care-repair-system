<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) exit;

$new_password = $_POST['new_password'] ?? '';
$user_id = $_SESSION['user_id'];

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'error' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร']);
    exit;
}

$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ?, is_first_login = 0 WHERE id = ?");
$stmt->bind_param("si", $hashed_password, $user_id);

if ($stmt->execute()) {
    $redirect = ($_SESSION['role'] == 'admin') ? 'admin/dashboard.php' : 'technician/my_jobs.php';
    echo json_encode(['success' => true, 'redirect' => $redirect]);
} else {
    echo json_encode(['success' => false, 'error' => 'ไม่สามารถเปลี่ยนรหัสผ่านได้']);
}