<?php
/*
|------------------------------------------------------
| File: api/tech_actions.php
| Description: ประมวลผลสถานะงาน และบันทึกข้อมูลทางเทคนิค (Fix Floor/Location)
|------------------------------------------------------
*/

session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// 1. ตรวจสอบสิทธิ์เจ้าหน้าที่ช่าง
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$action  = $_POST['action'] ?? '';
$job_id  = $_POST['job_id'] ?? 0;
$tech_id = $_SESSION['user_id'];

if (!$job_id) {
    echo json_encode(['success' => false, 'error' => 'รหัสงานไม่ถูกต้อง']);
    exit;
}

// -------------------------------------------------------------------
// ส่วนที่ 1: การรับงาน (Accept)
// -------------------------------------------------------------------
if ($action === 'accept') {
    $conn->query("UPDATE repair_requests SET status = 'accepted', technician_id = $tech_id, accepted_at = NOW(), updated_at = NOW() WHERE id = $job_id");
    echo json_encode(['success' => true]);
    exit;
}

// -------------------------------------------------------------------
// ส่วนที่ 2: การอัปเดตข้อมูลรายละเอียด และ เปลี่ยนสถานะ (Update)
// -------------------------------------------------------------------
elseif ($action === 'update_status') {
    $new_status     = $_POST['new_status'] ?? '';
    $tech_note      = trim($_POST['tech_note'] ?? '');
    
    // ข้อมูลจากป๊อปอัปตรวจสอบอุปกรณ์
    $location_input = trim($_POST['location_input'] ?? '');
    $category_name  = trim($_POST['category_name'] ?? '');
    $device_name    = trim($_POST['device_name'] ?? '');
    $fault_name     = trim($_POST['fault_name'] ?? '');
    $serial_number  = trim($_POST['serial_number'] ?? '');

    $conn->begin_transaction();
    try {
        // ดึงข้อมูลเดิมจากฐานข้อมูลมาสำรองไว้กรณีข้อมูลใหม่ว่าง
        $stmt_check = $conn->prepare("SELECT status, floor_name, location_name, category_name, device_name, fault_name, serial_number FROM repair_requests WHERE id = ?");
        $stmt_check->bind_param("i", $job_id);
        $stmt_check->execute();
        $job_data = $stmt_check->get_result()->fetch_assoc();

        if (!$job_data) throw new Exception("ไม่พบข้อมูลใบงานที่ต้องการอัปเดต");

        /**
         * Logic: จัดการข้อมูลสถานที่ (หัวใจที่แก้ไขใหม่)
         */
        $final_floor = $job_data['floor_name'];
        $final_location = $job_data['location_name'];

        if ($location_input != "") {
            if (is_numeric($location_input)) {
                // หากได้รับมาเป็น ID (เช่น ช่างกดเลือกใน Dropdown) ให้ดึงชื่อจริงจากตาราง Locations/Floors
                $stmt_loc = $conn->prepare("SELECT l.location_name, f.floor_name 
                                           FROM locations l 
                                           JOIN floors f ON l.floor_id = f.id 
                                           WHERE l.id = ?");
                $stmt_loc->bind_param("i", $location_input);
                $stmt_loc->execute();
                $res_l = $stmt_loc->get_result()->fetch_assoc();
                
                if ($res_l) {
                    $final_floor = $res_l['floor_name'];
                    $final_location = $res_l['location_name'];
                }
            } else {
                // หากไม่ใช่ตัวเลข (เป็นชื่อห้องใหม่ที่พิมพ์ผ่าน Tags)
                $final_location = $location_input;
            }
        }

        /**
         * Logic: จัดการข้อมูลอุปกรณ์และอาการเสีย
         */
        $final_cat = ($category_name != "") ? $category_name : $job_data['category_name'];
        $final_dev = ($device_name != "")   ? $device_name   : $job_data['device_name'];
        $final_flt = ($fault_name != "")    ? $fault_name    : $job_data['fault_name'];
        $final_sn  = ($serial_number != "") ? $serial_number : $job_data['serial_number'];

        // 3. เตรียมเวลาปิดงาน
        $finish_clause = "";
        $is_closed = false;
        if (in_array($new_status, ['completed', 'cannot_repair'])) {
            $finish_clause = ", finished_at = NOW()";
            $is_closed = true;
        }

        // 4. คำสั่งอัปเดตข้อมูลลงฐานข้อมูลหลัก
        $sql_upd = "UPDATE repair_requests SET 
                    status = ?, 
                    floor_name = ?, 
                    location_name = ?, 
                    category_name = ?, 
                    device_name = ?, 
                    fault_name = ?, 
                    serial_number = ?, 
                    updated_at = NOW() 
                    $finish_clause 
                    WHERE id = ? AND technician_id = ?";
        
        $stmt_upd = $conn->prepare($sql_upd);
        $stmt_upd->bind_param("sssssssii", 
            $new_status, 
            $final_floor, 
            $final_location, 
            $final_cat, 
            $final_dev, 
            $final_flt, 
            $final_sn, 
            $job_id,
            $tech_id
        );
        $stmt_upd->execute();

        // 5. บันทึกประวัติสถานะลง Log (status_logs)
        $old_st = $job_data['status'];
        $stmt_log = $conn->prepare("INSERT INTO repair_status_logs (repair_request_id, status_from, status_to, changed_by) VALUES (?, ?, ?, ?)");
        $stmt_log->bind_param("issi", $job_id, $old_st, $new_status, $tech_id);
        $stmt_log->execute();

        // 6. บันทึกบันทึกช่วยจำช่าง (ถ้ามี) ลงในช่อง description เดิม
        $tech_note = trim($_POST['tech_note'] ?? '');

        // 1. กรองคำว่า "undefined" ที่อาจหลุดมาจาก JS และต้องไม่ใช่ค่าว่าง
        if ($tech_note !== "" && $tech_note !== "undefined") {
            
            // 2. ดึงข้อความปัจจุบันมาเช็คก่อน
            $stmt_find = $conn->prepare("SELECT description FROM repair_requests WHERE id = ?");
            $stmt_find->bind_param("i", $job_id);
            $stmt_find->execute();
            $res_find = $stmt_find->get_result()->fetch_assoc();
            $existing_desc = $res_find['description'] ?? '';

            // 3. ตรวจสอบว่าข้อความใหม่ซ้ำกับที่มีอยู่ในระบบแล้วหรือไม่
            if (strpos($existing_desc, $tech_note) === false) {
                // $note_marker = "--- บันทึกช่าง (" . date('d/m/H:i') . ") ---";
                $note_format = "\n\n" . $note_marker . "\n" . $tech_note;
                
                $stmt_n = $conn->prepare("UPDATE repair_requests SET description = CONCAT(IFNULL(description,''), ?) WHERE id = ?");
                $stmt_n->bind_param("si", $note_format, $job_id);
                $stmt_n->execute();
            }
        }
        
        // 7. จัดการอัปโหลดรูปเพิ่มเติม (ถ้าช่างแนบมาในขั้นตอนกรอกข้อมูล)
        if (isset($_FILES['repair_images']) && !empty(array_filter($_FILES['repair_images']['name']))) {
            $upload_dir = "../uploads/repairs/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            foreach ($_FILES['repair_images']['tmp_name'] as $k => $tmp) {
                if ($_FILES['repair_images']['error'][$k] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['repair_images']['name'][$k], PATHINFO_EXTENSION);
                    $new_name = "TECH_UPDATE_" . $job_id . "_" . uniqid() . "." . $ext;
                    if (move_uploaded_file($tmp, $upload_dir . $new_name)) {
                        $p = "uploads/repairs/" . $new_name;
                        $conn->query("INSERT INTO repair_images (repair_request_id, image_path) VALUES ($job_id, '$p')");
                    }
                }
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'is_closed' => $is_closed]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Action ไม่ถูกต้อง']);
}

$conn->close();