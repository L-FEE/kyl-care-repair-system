<?php
require_once '../config/db.php';
$type = $_GET['type'] ?? '';

// ค้นหาในไฟล์ api/get_master_data.php ส่วนที่ดึงรายชื่อผู้แจ้ง
if ($type == 'reporters') {
    // เพิ่มเงื่อนไข WHERE status = 'active'
    $res = $conn->query("SELECT id, full_name FROM reporters WHERE status = 'active' ORDER BY full_name ASC");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}
elseif ($type == 'all_locations') {
    // ดึงสถานที่ทั้งหมด (รวมชื่อชั้น) เพื่อให้เลือกง่ายในช่องเดียว
    $res = $conn->query("SELECT l.id, CONCAT(f.floor_name, ' - ', l.location_name) as loc_display FROM locations l JOIN floors f ON l.floor_id = f.id ORDER BY f.floor_name, l.location_name");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}
elseif ($type == 'all_devices') {
    $res = $conn->query("SELECT id, device_name FROM devices ORDER BY device_name ASC");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}