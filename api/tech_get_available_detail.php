<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') exit;

$id = $_GET['id'] ?? 0;
// ดึงข้อมูล รวมถึง office_phone
$stmt = $conn->prepare("SELECT * FROM repair_requests WHERE id = ? AND status = 'pending'");
$stmt->bind_param("i", $id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    echo "<div class='p-5 text-center text-muted'>ขออภัย ไม่พบข้อมูลงานนี้ หรือมีผู้อื่นรับงานไปแล้ว</div>";
    exit;
}
?>

<div class="p-4">
    <div class="row g-4">
        <!-- ข้อมูลผู้แจ้งและสถานที่ -->
        <div class="col-md-6 border-end">
            <h6 class="text-primary fw-bold mb-3"><i class="bi bi-person-circle me-1"></i> ข้อมูลผู้แจ้งซ่อม</h6>
            <div class="mb-3">
                <div class="text-muted x-small">ชื่อผู้แจ้ง:</div>
                <div class="fw-bold fs-5"><?= h($job['reporter_name']) ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <div class="text-muted x-small">เบอร์โทรส่วนตัว:</div>
                    <div class="fw-bold text-success"><i class="bi bi-telephone-fill"></i> <?= h($job['reporter_phone']) ?></div>
                </div>
                <div class="col-6">
                    <div class="text-muted x-small">เบอร์ที่ทำงาน:</div>
                    <div class="fw-bold text-primary"><i class="bi bi-telephone-plus-fill"></i> <?= h($job['office_phone']) ?></div>
                </div>
            </div>
            <div class="mb-3">
                <div class="text-muted x-small">สถานที่ / ห้อง:</div>
                <div class="fw-bold"><i class="bi bi-geo-alt-fill text-danger"></i> <?= h($job['location_name'] ?: 'ไม่ระบุสถานที่') ?></div>
            </div>
        </div>

        <!-- ข้อมูลอุปกรณ์ -->
        <div class="col-md-6">
            <h6 class="text-primary fw-bold mb-3"><i class="bi bi-tools me-1"></i> รายละเอียดอุปกรณ์</h6>
            <div class="mb-2"><span class="badge bg-secondary">รหัสงาน: <?= $job['request_code'] ?></span></div>
            <div class="mb-2"><strong>อุปกรณ์:</strong> <?= h($job['device_name'] ?: 'ยังไม่ได้ระบุ') ?></div>
            <div class="mb-2 text-danger"><strong>อาการที่:</strong> <?= h($job['fault_name'] ?: 'ยังไม่ได้ระบุ') ?></div>
            <!-- <div class="mb-2"><strong>ประเภท:</strong> <?= h($job['category_name'] ?: 'ยังไม่ได้ระบุ') ?></div> -->
            <div class="mb-2"><strong>S/N:</strong> <?= h($job['serial_number'] ?: 'ยังไม่ได้ระบุ') ?></div>
        </div>

        <!-- รายละเอียดปัญหาเพิ่มเติม -->
        <!-- <div class="col-12">
            <h6 class="text-primary fw-bold mb-2">คำอธิบายเพิ่มเติมจากผู้แจ้ง:</h6>
            <div class="p-3 bg-light rounded border small" style="min-height: 80px;">
                <?= nl2br(h($job['description'] ?: 'ไม่มีรายละเอียดเพิ่มเติม')) ?>
            </div>
        </div> -->

        <!-- รูปภาพประกอบ -->
        <div class="col-12">
            <h6 class="text-primary fw-bold mb-2">รูปภาพประกอบ:</h6>
            <div class="row g-2">
                <?php
                $imgs = $conn->query("SELECT image_path FROM repair_images WHERE repair_request_id = $id");
                while($img = $imgs->fetch_assoc()):
                ?>
                <div class="col-3 col-md-2">
                    <a href="../<?= $img['image_path'] ?>" data-fancybox="gallery-modal" data-caption="รูปประกอบงาน <?= $job['request_code'] ?>">
                        <img src="../<?= $img['image_path'] ?>" class="img-fluid rounded shadow-sm border" style="height:70px; width:100%; object-fit:cover;">
                    </a>
                </div>
                <?php endwhile; if($imgs->num_rows == 0) echo "<div class='ps-2 text-muted x-small'>ไม่มีรูปภาพประกอบ</div>"; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer bg-light border-0 px-4 py-3">
    <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">ปิด</button>
    <button type="button" onclick="acceptJob(<?= $job['id'] ?>, '<?= $job['request_code'] ?>')" class="btn btn-primary px-5 fw-bold shadow">
        <i class="bi bi-check2-square me-1"></i> รับงาน
    </button>
</div>