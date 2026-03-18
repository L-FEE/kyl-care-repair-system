<?php
/*
|------------------------------------------------------
| File: api/save_repair_report.php
| Description: บันทึกการแจ้งซ่อม (รองรับเบอร์ที่ทำงาน + Smart Device)
|------------------------------------------------------
*/

session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// 1. รับค่าจากฟอร์ม
$reporter_id   = $_POST['reporter_id'] ?? 0;
$location_id   = $_POST['location_id'] ?? 0;
$device_input  = trim($_POST['device_input'] ?? '');
$fault_text    = trim($_POST['fault_text'] ?? '');
$description   = trim($_POST['description'] ?? '');

// 2. Validation ขั้นพื้นฐาน
if (!$reporter_id) {
    echo json_encode(['success' => false, 'error' => 'กรุณาระบุชื่อผู้แจ้งซ่อม']);
    exit;
}

// 3. เริ่มกระบวนการ Database Transaction
$conn->begin_transaction();

try {
    // 3.1 ดึงข้อมูลผู้แจ้งจากตาราง reporters (ดึงมาครบทั้ง ชื่อ, เบอร์ส่วนตัว, เบอร์ที่ทำงาน, อีเมล)
    $stmt_rep = $conn->prepare("SELECT * FROM reporters WHERE id = ?");
    $stmt_rep->bind_param("i", $reporter_id);
    $stmt_rep->execute();
    $rep = $stmt_rep->get_result()->fetch_assoc();

    if (!$rep) throw new Exception("ไม่พบข้อมูลผู้แจ้งในฐานข้อมูล");

    // 3.2 จัดการข้อมูลสถานที่ (Location)
    $loc_name = "";
    $floor_name = "";
    if ($location_id) {
        $stmt_loc = $conn->prepare("SELECT l.location_name, f.floor_name FROM locations l JOIN floors f ON l.floor_id = f.id WHERE l.id = ?");
        $stmt_loc->bind_param("i", $location_id);
        $stmt_loc->execute();
        $l_data = $stmt_loc->get_result()->fetch_assoc();
        if ($l_data) {
            $loc_name = $l_data['location_name'];
            $floor_name = $l_data['floor_name'];
        }
    }

    // 3.3 จัดการข้อมูลอุปกรณ์ (Smart Device)
    $dev_name = "";
    $cat_name = ""; // ชื่อหมวดหมู่เริ่มต้นที่คุณปรับแก้ใน DB

    if ($device_input != "") {
        if (is_numeric($device_input)) {
            // กรณีเลือกจากรายการเดิม
            $stmt_dev = $conn->prepare("SELECT d.device_name, c.category_name FROM devices d JOIN device_categories c ON d.category_id = c.id WHERE d.id = ?");
            $stmt_dev->bind_param("i", $device_input);
            $stmt_dev->execute();
            $d_data = $stmt_dev->get_result()->fetch_assoc();
            if ($d_data) {
                $dev_name = $d_data['device_name'];
                $cat_name = $d_data['category_name'];
            }
        } else {
            // กรณีพิมพ์ชื่อใหม่ (บันทึกลงตาราง devices ให้อัตโนมัติ)
            $dev_name = $device_input;
            $res_cat = $conn->query("SELECT id FROM device_categories WHERE category_name = 'อื่นๆ' LIMIT 1");
            $cat_id = ($res_cat->num_rows > 0) ? $res_cat->fetch_assoc()['id'] : 1; 

            // บันทึกเข้าตาราง devices เผื่อใช้ครั้งหน้า
            $stmt_ins_dev = $conn->prepare("INSERT INTO devices (category_id, device_name) VALUES (?, ?)");
            $stmt_ins_dev->bind_param("is", $cat_id, $dev_name);
            $stmt_ins_dev->execute();
        }
    }

    // 3.4 สร้างรหัสแจ้งซ่อม
    $request_code = generateRequestCode($conn);

    // 3.5 บันทึกลงตาราง repair_requests (เพิ่มคอลัมน์ office_phone)
    $sql = "INSERT INTO repair_requests 
            (request_code, reporter_name, reporter_phone, office_phone, reporter_email, 
             floor_name, location_name, category_name, device_name, fault_name, 
             description, found_datetime, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')";
    
    $stmt = $conn->prepare($sql);
    // ผูกค่า 11 ตัวแปร (s ทั้งหมด 11 ตัว)
    $stmt->bind_param("sssssssssss", 
        $request_code, 
        $rep['full_name'], 
        $rep['phone'], 
        $rep['office_phone'], // ค่าเบอร์ที่ทำงาน 4 หลัก
        $rep['email'], 
        $floor_name, 
        $loc_name,
        $cat_name, 
        $dev_name, 
        $fault_text,
        $description
    );
    
    if (!$stmt->execute()) {
        throw new Exception("บันทึกรายการแจ้งซ่อมไม่สำเร็จ: " . $stmt->error);
    }
    
    $repair_request_id = $conn->insert_id;

    // 4. บันทึกรูปภาพ (จำกัดไม่เกิน 3 รูปตามโจทย์ใหม่)
    if (isset($_FILES['repair_images']) && !empty(array_filter($_FILES['repair_images']['name']))) {
        $upload_dir = "../uploads/repairs/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $count = 0;
        foreach ($_FILES['repair_images']['tmp_name'] as $key => $tmp_name) {
            if ($count >= 3) break; // บันทึกแค่ 3 รูปแรก
            
            if ($_FILES['repair_images']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['repair_images']['name'][$key], PATHINFO_EXTENSION);
                $filename = "IMG_" . $request_code . "_" . uniqid() . "." . $ext;
                if (move_uploaded_file($tmp_name, $upload_dir . $filename)) {
                    $db_path = "uploads/repairs/" . $filename;
                    $stmt_img = $conn->prepare("INSERT INTO repair_images (repair_request_id, image_path) VALUES (?, ?)");
                    $stmt_img->bind_param("is", $repair_request_id, $db_path);
                    $stmt_img->execute();
                    $count++;
                }
            }
        }
    }

    // 5. บันทึก Log เริ่มต้น
    $stmt_log = $conn->prepare("INSERT INTO repair_status_logs (repair_request_id, status_to) VALUES (?, 'pending')");
    $stmt_log->bind_param("i", $repair_request_id);
    $stmt_log->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'code' => $request_code]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();