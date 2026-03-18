<?php
/*
|------------------------------------------------------
| File: includes/functions.php
| Description: Global helper functions for kyl_care
|------------------------------------------------------
*/

/**
 * ตรวจสอบความถูกต้องของเบอร์โทรศัพท์
 * เงื่อนไข: เริ่มต้นด้วย 06, 08, 09 และมีความยาว 10 หลัก
 */
function validatePhone($phone) {
    return preg_match('/^0[689]\d{8}$/', $phone);
}

/**
 * ตรวจสอบความถูกต้องของอีเมล
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * ฟังก์ชันสร้างรหัสแจ้งซ่อม (Request Code)
 * รูปแบบ: RE-20260105-001 (RE-YYYYMMDD-ลำดับ)
 */
function generateRequestCode($conn) {
    $today = date('Ymd');
    $sql = "SELECT COUNT(id) as total FROM repair_requests WHERE DATE(created_at) = CURDATE()";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $next_number = str_pad($row['total'] + 1, 3, '0', STR_PAD_LEFT);
    return "RE-" . $today . "-" . $next_number;
}

/**
 * จัดการแสดงผล Badge ของสถานะงานซ่อม
 */
function getStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-secondary">รอดำเนินการ</span>';
        case 'accepted':
            return '<span class="badge bg-primary">รับงานแล้ว</span>';
        case 'in_progress':
            return '<span class="badge bg-info text-dark">กำลังซ่อม</span>';
        case 'waiting_parts':
            return '<span class="badge" style="background-color: #ff6f00; color: white;">รออะไหล่</span>';
        case 'completed':
            return '<span class="badge bg-success">ซ่อมเสร็จสิ้น</span>';
        case 'cannot_repair':
            return '<span class="badge bg-danger">ซ่อมไม่ได้</span>';
        default:
            return '<span class="badge bg-dark">' . $status . '</span>';
    }
}

/**
 * ฟังก์ชันความปลอดภัย: ป้องกัน XSS
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>