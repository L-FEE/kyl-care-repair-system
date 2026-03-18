<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'update_photo') {
    if (!empty($_FILES['profile_img']['name'])) {
        $upload_dir = '../uploads/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
        $filename = "UID_" . $user_id . "_" . time() . "." . $ext;
        
        if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $upload_dir . $filename)) {
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $filename, $user_id);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No file selected']);
    }
} 

elseif ($action === 'update_password') {
    $old_pass = $_POST['old_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();

    if (!password_verify($old_pass, $u['password'])) {
        echo json_encode(['success' => false, 'error' => 'รหัสผ่านเดิมไม่ถูกต้อง']);
        exit;
    }

    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
    $stmt_upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt_upd->bind_param("si", $hashed, $user_id);
    
    if ($stmt_upd->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
}

// [เพิ่มเข้าในไฟล์ api/user_actions.php ต่อท้ายจากฟังก์ชันอื่นๆ]

elseif ($action === 'update_profile_info') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    // Validation
    if (!$full_name || !$email || !$phone) {
        echo json_encode(['success' => false, 'error' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
        exit;
    }

    if (!preg_match('/^0[689]\d{8}$/', $phone)) {
        echo json_encode(['success' => false, 'error' => 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง']);
        exit;
    }

    // เช็คอีเมลซ้ำ (ต้องไม่ใช่อีเมลคนอื่น)
    $stmt_c = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt_c->bind_param("si", $email, $user_id);
    $stmt_c->execute();
    if ($stmt_c->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'อีเมลนี้ถูกใช้งานแล้วโดยบุคคลอื่น']);
        exit;
    }

    // บันทึกการเปลี่ยนแปลง
    $stmt_upd = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt_upd->bind_param("sssi", $full_name, $email, $phone, $user_id);
    
    if ($stmt_upd->execute()) {
        // หากคนเปลี่ยนข้อมูลเป็น Admin และเปลี่ยนชื่อ ต้องแก้ค่าใน Session ด้วย เพื่อให้ชื่อใน Header อัปเดตทันที
        $_SESSION['full_name'] = $full_name;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาดในการบันทึก']);
    }
}