<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'ไม่มีสิทธิ์เข้าถึงส่วนนี้']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add_tech') {
    // รับค่าและตัดช่องว่าง
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $default_pass = password_hash('password@123', PASSWORD_DEFAULT);

    // 2. ตรวจสอบค่าว่าง
    if (empty($full_name) || empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'error' => 'กรุณากรอกข้อมูลให้ครบทุกช่อง']);
        exit;
    }

    // 3. ตรวจสอบความถูกต้องของอีเมลและเบอร์โทร (ตามโจทย์)
    if (!validateEmail($email)) {
        echo json_encode(['success' => false, 'error' => 'เมลไม่ถูกต้อง']);
        exit;
    }
    if (!validatePhone($phone)) {
        echo json_encode(['success' => false, 'error' => 'เบอร์โทรต้องเป็น 10 หลัก และขึ้นต้นด้วย 06, 08, 09']);
        exit;
    }

    // 4. บันทึกลงฐานข้อมูล
    try {
        // เช็คก่อนว่าอีเมลซ้ำไหม
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'อีเมลนี้ถูกใช้งานแล้วในระบบ']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role, is_first_login, status) VALUES (?, ?, ?, ?, 'technician', 1, 'active')");
        $stmt->bind_param("ssss", $full_name, $email, $phone, $default_pass);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database Insert Error: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'System Error: ' . $e->getMessage()]);
    }
}


// ... ส่วนบนของไฟล์ api/admin_actions.php ...
elseif ($action === 'reset_pass') {
    // ตรวจสอบว่ามี ID ส่งมาหรือไม่
    $target_id = $_POST['id'] ?? 0;

    if (!$target_id) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบรหัสผู้ใช้งานที่ต้องการรีเซ็ต']);
        exit;
    }

    // เข้ารหัส 'password@123'
    $new_password_hashed = password_hash('password@123', PASSWORD_DEFAULT);
    
    // อัปเดตทั้งรหัสผ่านและตั้งค่า is_first_login เป็น 1 (เพื่อให้ช่างไปหน้าเปลี่ยนรหัส)
    $stmt = $conn->prepare("UPDATE users SET password = ?, is_first_login = 1 WHERE id = ?");
    $stmt->bind_param("si", $new_password_hashed, $target_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'ไม่สามารถอัปเดตฐานข้อมูลได้: ' . $conn->error]);
    }
}

// ... (ภายใน api/admin_actions.php) ...

elseif ($action === 'toggle_status') {
    $id = $_POST['id'];
    $status = $_POST['status']; // 'active' หรือ 'inactive'
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    if($stmt->execute()) echo json_encode(['success' => true]);
}

elseif ($action === 'add_admin') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $pass = password_hash('password@123', PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role, is_first_login, status) VALUES (?, ?, ?, ?, 'admin', 1, 'active')");
    $stmt->bind_param("ssss", $full_name, $email, $phone, $pass);
    if($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'error' => 'อีเมลนี้มีในระบบแล้ว']);
}

// [เพิ่มต่อท้ายในส่วน elseif ในไฟล์ api/admin_actions.php]

elseif ($action === 'edit_tech') {
    $target_id = $_POST['id'] ?? 0;
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    if (!$target_id || empty($full_name) || empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'error' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
        exit;
    }

    // 1. ตรวจสอบเบอร์โทร (ตามโจทย์ 10 หลักขึ้นต้น 06, 08, 09)
    if (!preg_match('/^0[689]\d{8}$/', $phone)) {
        echo json_encode(['success' => false, 'error' => 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง']);
        exit;
    }

    // 2. ตรวจสอบอีเมลซ้ำ (ต้องไม่ใช่อีเมลของตัวเองที่ใช้เครื่องปัจจุบันอยู่)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $target_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'อีเมลนี้มีผู้ใช้งานอื่นใช้อยู่แล้ว']);
        exit;
    }

    // 3. ทำการอัปเดต
    $stmt_upd = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?");
    $stmt_upd->bind_param("sssi", $full_name, $email, $phone, $target_id);
    
    if ($stmt_upd->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $conn->error]);
    }
}