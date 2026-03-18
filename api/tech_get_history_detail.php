<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') exit;

$id = (int)($_GET['id'] ?? 0);
$tech_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT r.*, u.full_name as tech_name FROM repair_requests r LEFT JOIN users u ON r.technician_id = u.id WHERE r.id = ? AND r.technician_id = ?");
$stmt->bind_param("ii", $id, $tech_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) { echo "<div class='p-5 text-center'>ไม่พบข้อมูล</div>"; exit; }
?>

<div class="p-4">
    <div class="row g-4">
        <!-- ฝั่งซ้าย: ข้อมูลผู้แจ้งและสถานที่ -->
        <div class="col-md-6 border-end">
            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">ข้อมูลผู้แจ้งและสถานที่</h6>
            <table class="table table-sm table-borderless align-middle">
                <tr><td width="35%" class="text-muted small">รหัสงาน:</td><td><strong class="text-dark"><?= $row['request_code'] ?></strong></td></tr>
                <tr><td class="text-muted small">ผู้แจ้ง:</td><td><?= h($row['reporter_name']) ?></td></tr>
                <tr><td class="text-muted small">มือถือ:</td><td class="text-success fw-bold"><?= h($row['reporter_phone']) ?></td></tr>
                <tr><td class="text-muted small">ที่ทำงาน:</td><td class="text-primary fw-bold"><?= h($row['office_phone'] ?: '-') ?></td></tr>
                <tr><td class="text-muted small">อีเมล:</td><td class="small"><?= h($row['reporter_email'] ?: '-') ?></td></tr>
                <tr><td class="text-muted small">สถานที่:</td><td class="small"><?= h($row['floor_name']) ?> (<?= h($row['location_name']) ?>)</td></tr>
                <tr><td class="text-muted small">แจ้งเมื่อ:</td><td class="small"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?> น.</td></tr>
            </table>
        </div>

        <!-- ฝั่งขวา: รายละเอียดอุปกรณ์ -->
        <div class="col-md-6">
            <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">รายละเอียดทางเทคนิค</h6>
            <table class="table table-sm table-borderless align-middle">
                <tr><td width="35%" class="text-muted small">ประเภท:</td><td><span class="small"><?= h($row['category_name']) ?></span></td></tr>
                <tr><td class="text-muted small">อุปกรณ์:</td><td><span class="fw-bold"><?= h($row['device_name']) ?></span></td></tr>
                <tr><td class="text-muted small">อาการเสีย:</td><td class="text-danger fw-bold small"><?= h($row['fault_name']) ?></td></tr>
                <tr><td class="text-muted small">S/N:</td><td><span class="badge bg-light text-dark border"><?= h($row['serial_number'] ?: '-') ?></span></td></tr>
                <tr><td class="text-muted small">สถานะล่าสุด:</td><td><?= getStatusBadge($row['status']) ?></td></tr>
                <tr><td class="text-muted small">วันที่ซ่อมเสร็จ:</td><td class="text-success"><?= date('d/m/Y H:i', strtotime($row['finished_at'])) ?> น.</td></tr>
                <tr><td class="text-muted small">ผู้ดำเนินการ:</td><td class="small fw-bold text-main"><?= h($row['tech_name']) ?></td></tr>
            </table>
        </div>

        <!-- ส่วนล่าง 1: รายละเอียดบันทึกงาน -->
        <div class="col-12 mt-2">
            <h6 class="text-primary fw-bold mb-2 small"><i class="bi bi-chat-square-text-fill me-1"></i> รายละเอียดบันทึกงาน:</h6>
            <div class="p-3 bg-light rounded-4 border border-white shadow-sm d-flex align-items-center" style="min-height: 100px;">
                <div class="w-100 text-dark" style="font-size: 0.9rem; line-height: 1.5;">
                    <?php 
                        $desc = trim($row['description'] ?? '');
                        echo $desc ? nl2br(h($desc)) : '<span class="text-muted italic">ไม่มีบันทึกเพิ่มเติม</span>';
                    ?>
                </div>
            </div>
        </div>

        <!-- ส่วนล่าง 2: รูปภาพแกลเลอรี่ -->
        <div class="col-12">
            <h6 class="text-primary fw-bold mb-2 small"><i class="bi bi-images me-1"></i> รูปภาพประกอบ:</h6>
            <div class="row g-2">
                <?php
                $imgs = $conn->query("SELECT image_path FROM repair_images WHERE repair_request_id = $id");
                while($img = $imgs->fetch_assoc()):
                ?>
                <div class="col-3 col-md-2">
                    <a href="../<?= $img['image_path'] ?>" data-fancybox="history-gallery" data-caption="รูปถ่ายประวัติงานซ่อม <?= $row['request_code'] ?>">
                        <img src="../<?= $img['image_path'] ?>" class="img-history-detail">
                    </a>
                </div>
                <?php endwhile; if($imgs->num_rows == 0) echo "<div class='ps-2 text-muted x-small'>ไม่มีรูปภาพประกอบในระบบ</div>"; ?>
            </div>
        </div>
    </div>
</div>