<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) exit;

$password = $_POST['password'] ?? '';
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$hashed = $stmt->get_result()->fetch_assoc()['password'];

if (password_verify($password, $hashed)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'รหัสผ่านไม่ถูกต้อง']);
}