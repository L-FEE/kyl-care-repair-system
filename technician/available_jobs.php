<?php 
/*
|------------------------------------------------------
| File: technician/available_jobs.php
| Description: รายการงานแจ้งซ่อมใหม่ (ฉบับแสดงรูปภาพเริ่มต้นตามหมวดหมู่)
|------------------------------------------------------
*/
require_once '../includes/tech_header.php'; 
?>

<!-- Fancybox 5 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />

<style>
    /* ✅ เลเยอร์ Fancybox อยู่บนสุดเสมอ */
    .fancybox__container {
        z-index: 100000 !important; 
    }

    /* ตกแต่งการ์ดงาน */
    .job-card {
        border: none;
        border-radius: 18px;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        background: #fff;
        border: 1px solid #f1f5f9;
        overflow: hidden;
    }
    .job-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0,51,102,0.12) !important;
    }

    .card-img-top, .placeholder-box {
        height: 180px;
        object-fit: cover;
    }

    /* สไตล์สำหรับกล่องไอคอนกรณีไม่มีรูป */
    .placeholder-box {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
    }

    .req-code-label {
        background-color: rgba(0, 0, 0, 0.6);
        color: #fff;
        font-size: 0.7rem;
        padding: 4px 10px;
        border-radius: 50px;
        backdrop-filter: blur(4px);
    }
    .contact-box {
        background-color: #f8fafc;
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #edf2f7;
    }
    .btn-accept-main {
        background-color: #003366;
        color: #fff;
        border-radius: 12px;
        padding: 10px;
        font-weight: 700;
        transition: 0.2s;
        border: none;
    }
    .btn-accept-main:hover {
        background-color: #002244;
        box-shadow: 0 4px 10px rgba(0,51,102,0.25);
    }
    .btn-view-circle {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        border: 1px solid #e2e8f0; color: #64748b; transition: 0.2s;
    }
    .btn-view-circle:hover { background-color: #f1f5f9; color: #003366; }
</style>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="fw-bold mb-1">งานรอดำเนินการ</h3>
        <p class="text-muted small">กรุณาเลือกรับงานซ่อมที่ได้รับแจ้งเข้ามาใหม่</p>
    </div>
    <span class="badge bg-main px-3 py-2 rounded-pill shadow-sm">
        <i class="bi bi- megaphone"></i> งานรอรับ: 
        <?php echo $conn->query("SELECT id FROM repair_requests WHERE status = 'pending'")->num_rows; ?> รายการ
    </span>
</div>

<div class="row g-4">
    <?php
    $sql = "SELECT r.*, 
            (SELECT image_path FROM repair_images WHERE repair_request_id = r.id LIMIT 1) as first_img 
            FROM repair_requests r 
            WHERE r.status = 'pending' 
            ORDER BY r.created_at DESC";
    $res = $conn->query($sql);

    while($row = $res->fetch_assoc()):
        // --- ส่วนเลือกรูปภาพเริ่มต้นตามประเภทอุปกรณ์ ---
        $cat = $row['category_name'];
        $icon = 'bi-tools'; // ไอคอนเริ่มต้น
        $bg_class = 'text-secondary';
        
        if($cat == 'คอมพิวเตอร์') { $icon = 'bi-pc-display'; $bg_class = 'text-primary'; }
        elseif($cat == 'อุปกรณ์ไฟฟ้า') { $icon = 'bi-lightning-charge-fill'; $bg_class = 'text-warning'; }
        elseif($cat == 'เครื่องเสียง') { $icon = 'bi-speaker-fill'; $bg_class = 'text-info'; }
        elseif($cat == 'โปรเจคเตอร์') { $icon = 'bi-projector-fill'; $bg_class = 'text-success'; }
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm job-card">
            <div class="position-relative">
                <?php if (!empty($row['first_img'])): ?>
                    <!-- ถ้ามีรูปที่พนักงานแนบมา -->
                    <img src="../<?= $row['first_img'] ?>" class="card-img-top">
                <?php else: ?>
                    <!-- ถ้าไม่มีรูป ให้ใช้ Placeholder กราฟิกประจำหมวด -->
                    <div class="placeholder-box">
                        <i class="bi <?= $icon ?> <?= $bg_class ?> display-1" style="opacity: 0.3;"></i>
                    </div>
                <?php endif; ?>

                <div class="position-absolute top-0 start-0 m-3"><span class="req-code-label">#<?= $row['request_code'] ?></span></div>
                <div class="position-absolute bottom-0 end-0 m-2">
                    <span class="badge bg-white text-dark shadow-sm x-small" style="border-radius: 8px;">
                        <i class="bi bi-clock"></i> <?= date('d/m H:i', strtotime($row['created_at'])) ?>
                    </span>
                </div>
            </div>

            <div class="card-body">
                <h5 class="fw-bold text-dark text-truncate mb-1"><?= h($row['device_name'] ?: 'พนักงานไม่ได้ระบุอุปกรณ์') ?></h5>
                <p class="text-danger small fw-bold mb-3">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= h($row['fault_name'] ?: 'รอยืนยันอาการเสีย') ?>
                </p>

                <div class="contact-box">
                    <div class="small fw-bold mb-1 text-primary">
                        <i class="bi bi-person-circle"></i> <?= h($row['reporter_name']) ?>
                    </div>
                    <div class="d-flex justify-content-between x-small text-muted">
                        <span><i class="bi bi-telephone-fill"></i> <?= h($row['reporter_phone']) ?></span>
                        <span class="text-dark fw-bold"><i class="bi bi-telephone-plus"></i> <?= h($row['office_phone']) ?></span>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button onclick="acceptJob(<?= $row['id'] ?>, '<?= $row['request_code'] ?>')" class="btn btn-accept-main flex-grow-1 shadow-sm">รับงานนี้</button>
                    <button onclick="viewJobDetails(<?= $row['id'] ?>)" class="btn btn-view-circle shadow-none" title="ดูรายละเอียดเพิ่มเติม"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; if($res->num_rows == 0) echo '<div class="col-12 text-center py-5 text-muted"><h4>ยังไม่มีรายการแจ้งซ่อมใหม่ในขณะนี้</h4></div>'; ?>
</div>

<!-- Modal รายละเอียดงาน -->
<div class="modal fade" id="jobDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-main text-white py-3 border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i>รายละเอียดงานแจ้งซ่อม</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="jobDetailContent" class="modal-body p-0">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script>
function viewJobDetails(id) {
    const modal = new bootstrap.Modal(document.getElementById('jobDetailModal'));
    modal.show();
    $.get('../api/tech_get_available_detail.php?id=' + id, function(html) {
        $('#jobDetailContent').html(html);
        if (typeof Fancybox !== 'undefined') {
            Fancybox.bind("[data-fancybox='gallery-modal']", {
                zIndex: 100000,
                autoFocus: false
            });
        }
    });
}

function acceptJob(id, code) {
    Swal.fire({
        title: 'ยืนยันรับงานซ่อม?',
        text: `รหัสรายการ: ${code}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#003366',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../api/tech_actions.php', { action: 'accept', job_id: id }, function(res) {
                if(res.success) {
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'รับงานสำเร็จ!', 
                        text: 'กรุณาเข้าจัดการต่อในเมนู "งานของฉัน"',
                        timer: 1500, 
                        showConfirmButton: false 
                    }).then(() => location.href = 'my_jobs.php');
                } else {
                    Swal.fire('ผิดพลาด', res.error, 'error');
                }
            }, 'json');
        }
    });
}
</script>

<?php require_once '../includes/tech_footer.php'; ?>