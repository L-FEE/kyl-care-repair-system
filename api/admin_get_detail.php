<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit;

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT r.*, u.full_name as tech_name FROM repair_requests r LEFT JOIN users u ON r.technician_id = u.id WHERE r.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) echo "<div class='p-4 text-center'>ไม่พบข้อมูล</div>";
?>

<div class="p-4">
    <div class="row g-4">
        <div class="col-md-6 border-end">
            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">ข้อมูลผู้แจ้งและสถานที่</h6>
            <table class="table table-sm table-borderless">
                <tr><td width="35%" class="text-muted">รหัสงาน:</td><td><strong><?= $row['request_code'] ?></strong></td></tr>
                <tr><td class="text-muted">ผู้แจ้ง:</td><td><?= h($row['reporter_name']) ?></td></tr>
                <tr><td class="text-muted">มือถือ:</td><td><?= h($row['reporter_phone']) ?></td></tr>
                <tr><td class="text-muted">ที่ทำงาน:</td><td><?= h($row['office_phone'] ?: '-') ?></td></tr>
                <tr><td class="text-muted">อีเมล:</td><td><?= h($row['reporter_email'] ?: '-') ?></td></tr>
                <tr><td class="text-muted">สถานที่:</td><td><?= h($row['floor_name']) ?> (<?= h($row['location_name']) ?>)</td></tr>
                <tr><td class="text-muted">แจ้งเมื่อ:</td><td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">รายละเอียดอุปกรณ์</h6>
            <table class="table table-sm table-borderless">
                <tr><td width="35%" class="text-muted">ประเภท:</td><td><?= h($row['category_name']) ?></td></tr>
                <tr><td class="text-muted">อุปกรณ์:</td><td><?= h($row['device_name']) ?></td></tr>
                <tr><td class="text-muted">อาการเสีย:</td><td class="text-danger"><?= h($row['fault_name']) ?></td></tr>
                <tr><td class="text-muted">S/N:</td><td><?= h($row['serial_number'] ?: '-') ?></td></tr>
                <tr><td class="text-muted">สถานะ:</td><td><?= getStatusBadge($row['status']) ?></td></tr>
                <tr><td class="text-muted">ช่าง:</td><td><?= h($row['tech_name'] ?: 'ยังไม่มีช่างรับงาน') ?></td></tr>
                <tr><td class="text-muted">วันที่ซ่อมเสร็จ:</td><td><?= date('d/m/Y H:i', strtotime($row['finished_at'])) ?></td></tr>
            </table>
        </div>

        <!-- ส่วนแสดง บันทึกจากช่าง -->
        <div class="col-12 mt-3">
            <h6 class="text-primary fw-bold mb-2 small"><i class="bi bi-chat-right-dots-fill me-1"></i> รายละเอียดบันทึกงาน:</h6>
            <div class="p-3 bg-light rounded-4 border border-white shadow-sm d-flex align-items-center" style="min-height: 80px; background-color: #f8f9fa !important;">
                <div class="w-100 text-dark" style="font-size: 0.9rem; line-height: 1.5;">
                    <?php 
                        $desc = trim($row['description'] ?? '');
                        echo $desc ? nl2br(h($desc)) : '<span class="text-muted italic">ไม่มีบันทึกรายละเอียด</span>';
                    ?>
                </div>
            </div>
        </div>

        <div class="col-12">
            <h6 class="text-primary fw-bold mb-2">รูปภาพประกอบ:</h6>
            <div class="row g-2">
                <?php
                $imgs = $conn->query("SELECT image_path FROM repair_images WHERE repair_request_id = $id");
                while($img = $imgs->fetch_assoc()):
                ?>
                <div class="col-3">
                    <img src="../<?= $img['image_path'] ?>" class="img-fluid rounded border shadow-sm" style="height:100px; width:100%; object-fit:cover;">
                </div>
                <?php endwhile; if($imgs->num_rows == 0) echo "<div class='col-12 text-muted small ps-2'>ไม่มีรูปภาพประกอบ</div>"; ?>
            </div>
        </div>
    </div>
</div>