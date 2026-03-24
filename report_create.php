<?php require_once 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/png" href="uploads\logo\logo.png">
    <title>แจ้งซ่อมด่วน - KYL CARE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f8f9fa; font-family: 'Sarabun', sans-serif; }
        .bg-main { background-color: #003366 !important; }
        .container { max-width: 500px; }
        .card { border: none; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .form-label { font-weight: 600; color: #003366; font-size: 0.9rem; margin-bottom: 8px; }
        .select2-container--bootstrap-5 .select2-selection { border-radius: 12px; min-height: 48px; display: flex; align-items: center; border: 1px solid #dee2e6; }
        .btn-submit { background-color: #003366; border: none; border-radius: 15px; padding: 15px; font-weight: bold; font-size: 1.1rem; }
        .img-preview img { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; border: 1px solid #eee; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-main mb-3 shadow-sm">
    <div class="container justify-content-center">
        <span class="navbar-brand fw-bold">KYL CARE แจ้งซ่อม</span>
    </div>
</nav><br>

<div class="container mb-5">
    <div class="card p-4">
        <form id="repairForm" enctype="multipart/form-data">
            
            <!-- 1. พิมพ์ค้นหาชื่อผู้แจ้ง -->
            <div class="mb-3">
                <label class="form-label">ชื่อผู้แจ้งซ่อม <span class="text-danger">*</span></label>
                <select name="reporter_id" id="reporter_id" class="form-select select2-basic" required>
                    <option value="">-- พิมพ์ค้นหาชื่อของคุณ --</option>
                </select>
            </div>

            <!-- 2. พิมพ์ค้นหาสถานที่ (ห้ามเพิ่มใหม่) -->
            <div class="mb-3">
                <label class="form-label">สถานที่ / ห้อง</label>
                <select name="location_id" id="location_id" class="form-select select2-basic">
                    <option value="">-- พิมพ์ค้นหาสถานที่ --</option>
                </select>
            </div>

            <hr class="my-4 opacity-50">

            <!-- 3. พิมพ์ค้นหาอุปกรณ์ (เพิ่มใหม่ได้) -->
            <div class="mb-3">
                <label class="form-label">อุปกรณ์ที่เสีย</label>
                <select name="device_input" id="device_input" class="form-select select2-tags">
                    <option value="">-- ระบุอุปกรณ์ --</option>
                </select>
            </div>

            <!-- 4. พิมพ์อาการเสีย (Textarea เหมือนเดิม) -->
            <!-- <div class="mb-3">
                <label class="form-label">อาการเสีย</label>
                <textarea name="fault_text" class="form-control" rows="3" placeholder="ระบุอาการสั้นๆ เช่น เปิดไม่ติด, จอแตก" style="border-radius: 12px;"></textarea>
            </div> -->

            <!-- 5. แนบรูปภาพ (จำกัด 3 รูป) -->
            <div class="mb-4">
                <label class="form-label">แนบรูปภาพ (ไม่เกิน 3 รูป)</label>
                <input type="file" name="repair_images[]" id="repair_images" class="form-control" accept="image/*" capture="environment" multiple style="border-radius: 12px;">
                <div id="image-preview" class="img-preview mt-2 d-flex flex-wrap gap-2"></div>
            </div>

            <button type="button" onclick="confirmSubmit()" class="btn btn-primary btn-submit w-100 shadow">
                <i class="bi bi-send-fill me-2"></i> แจ้งซ่อม
            </button>

            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Select2 สำหรับเลือกที่มีอยู่ (Reporter & Location)
    $('.select2-basic').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'พิมพ์ค้นหา...'
    });

    // 2. Select2 สำหรับอุปกรณ์ (พิมพ์ใหม่ได้ - Taggable)
    $('.select2-tags').select2({
        theme: 'bootstrap-5',
        width: '100%',
        tags: true,
        placeholder: 'พิมพ์ค้นหา...'
    });

    // โหลดข้อมูล
    $.getJSON('api/get_master_data.php?type=reporters', data => {
        data.forEach(r => $('#reporter_id').append(new Option(r.full_name, r.id)));
    });

    $.getJSON('api/get_master_data.php?type=all_locations', data => {
        data.forEach(l => $('#location_id').append(new Option(l.loc_display, l.id)));
    });

    $.getJSON('api/get_master_data.php?type=all_devices', data => {
        data.forEach(d => $('#device_input').append(new Option(d.device_name, d.id)));
    });

    // ตรวจสอบและพรีวิวรูปภาพ
    $('#repair_images').on('change', function() {
        const preview = $('#image-preview');
        preview.html('');
        if (this.files.length > 3) {
            Swal.fire('แจ้งเตือน', 'แนบรูปได้ไม่เกิน 3 รูป', 'warning');
            this.value = ''; return;
        }
        Array.from(this.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => preview.append(`<img src="${e.target.result}">`);
            reader.readAsDataURL(file);
        });
    });
});

function confirmSubmit() {
    if (!$('#reporter_id').val()) {
        Swal.fire('กรุณาระบุชื่อ', 'โปรดระบุรายชื่อผู้แจ้งซ่อมก่อนครับ', 'warning');
        return;
    }
    Swal.fire({
        title: 'ยืนยันข้อมูล?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#003366',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'แก้ไข'
    }).then((result) => { if (result.isConfirmed) submitData(); });
}

function submitData() {
    const formData = new FormData($('#repairForm')[0]);
    Swal.fire({ title: 'กำลังส่งข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    $.ajax({
        url: 'api/save_repair_report.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire('แจ้งซ่อมสำเร็จ!', 'รหัสงาน: ' + res.code, 'success').then(() => location.href = 'index.php');
            } else {
                Swal.fire('ผิดพลาด', res.error, 'error');
            }
        }
    });
}
</script>
</body>
</html>