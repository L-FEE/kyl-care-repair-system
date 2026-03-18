<?php
/*
|------------------------------------------------------
| File: api/admin_get_user_monitoring_list.php
|------------------------------------------------------
*/
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit;

$id = $_GET['id'] ?? '';
$mode = $_GET['mode'] ?? 'tech';

// กำหนดหัวข้อคอลัมน์สถานะ/ผู้ดูแล
$col_header = ($mode == 'tech') ? "สถานะปัจจุบัน" : "ช่างผู้ดูแลงาน";

if ($mode == 'tech') {
    // ดึงงานค้างที่ช่างคนนี้กำลังทำอยู่
    $sql = "SELECT * FROM repair_requests WHERE technician_id = ? AND status IN ('accepted', 'in_progress', 'waiting_parts') ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
} else {
    // ดึงประวัติงานทั้งหมดของพนักงานคนนี้ (จำกัด 50 รายการล่าสุด)
    $sql = "SELECT * FROM repair_requests WHERE reporter_name = ? ORDER BY created_at DESC LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
}

$stmt->execute();
$res = $stmt->get_result();
?>

<div class="p-1">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.88rem;">
            <thead class="bg-light">
                <tr class="text-muted">
                    <th class="text-center" width="200">รหัส / ผู้แจ้ง</th>
                    <th>อุปกรณ์และอาการเสีย</th>
                    <th class="text-center" width="180"><?= $col_header ?></th>
                    <th class="text-center" width="100">เวลาแจ้ง</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetch_assoc()): ?>
                <tr>
                    <td class="ps-3 py-2">
                        <!-- รหัสงาน (ตัวหนา) -->
                        <div class="fw-bold text-dark mb-0"><?= h($row['reporter_name']) ?>
                    </div>
                        <div class="text-muted small" style="font-size: 0.72rem;">
                            </i><?= $row['request_code'] ?>
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold text-main mb-0"><?= h($row['device_name']) ?></div>
                        <small class="text-danger" style="font-size: 0.7rem;"><i class="bi bi-info-circle me-1"></i><?= h($row['fault_name']) ?></small>
                    </td>
                    <td class="text-center">
                        <?php 
                        if($mode == 'tech') {
                            echo getStatusBadge($row['status']);
                        } else {
                            // แสดงชื่อช่างและสถานะถ้ากำลังดูประวัติของพนักงาน
                            if($row['technician_id']) {
                                $tech_res = $conn->query("SELECT full_name FROM users WHERE id = {$row['technician_id']}");
                                $tech_data = $tech_res->fetch_assoc();
                                echo "<div class='small fw-bold text-secondary mb-1'><i class='bi bi-person-badge'></i> ".h($tech_data['full_name'])."</div>";
                                echo getStatusBadge($row['status']);
                            } else {
                                echo "<span class='text-muted x-small'>รอดำเนินการรับงาน</span>";
                            }
                        }
                        ?>
                    </td>
                    <td class="text-center x-small" style="line-height: 1.3;">
                        <span class="fw-bold text-secondary"><?= date('d/m/y', strtotime($row['created_at'])) ?></span><br>
                        <span class="text-muted small"><?= date('H:i', strtotime($row['created_at'])) ?> น.</span>
                    </td>
                </tr>
                <?php endwhile; if($res->num_rows == 0) echo "<tr><td colspan='4' class='text-center py-5 text-muted'><i class='bi bi-inbox fs-4 d-block mb-2'></i>ไม่มีรายการงานค้างอยู่ในขณะนี้</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</div>