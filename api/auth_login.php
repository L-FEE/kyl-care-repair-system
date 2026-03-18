<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'error' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit;
}

$stmt = $conn->prepare("SELECT id, full_name, password, role, is_first_login, status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if ($user['status'] !== 'active') {
        echo json_encode(['success' => false, 'error' => 'บัญชีนี้ถูกระงับการใช้งาน']);
        exit;
    }

    if (password_verify($password, $user['password'])) {
        // บันทึก Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        // ตรวจสอบการเข้าสู่ระบบครั้งแรก
        if ($user['is_first_login'] == 1) {
            echo json_encode(['success' => true, 'redirect' => 'change_password.php']);
        } else {
            $redirect = ($user['role'] == 'admin') ? 'admin/dashboard.php' : 'technician/my_jobs.php';
            echo json_encode(['success' => true, 'redirect' => $redirect]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'รหัสผ่านไม่ถูกต้อง']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ไม่พบอีเมลนี้ในระบบ']);
}