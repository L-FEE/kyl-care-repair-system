<?php 
/*
|------------------------------------------------------
| File: technician/profile.php
| Description: จัดการโปรไฟล์เจ้าหน้าที่ (ดีไซน์ใหม่)
|------------------------------------------------------
*/
require_once '../includes/tech_header.php'; 

// ดึงข้อมูลล่าสุด
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

$profile_img = "../uploads/profiles/" . ($u['profile_image'] ?: 'default_profile.png');
?>

<style>
    /* ส่วนครอบโปรไฟล์ด้านบน */
    .profile-hero {
        background: linear-gradient(135deg, #003366 0%, #0056b3 100%);
        border-radius: 20px;
        padding: 40px 20px;
        color: white;
        margin-bottom: -60px; /* ให้ Card ด้านล่างเกยขึ้นมา */
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .profile-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    /* รูปภาพโปรไฟล์และวงแหวนตกแต่ง */
    .avatar-wrapper {
        position: relative;
        width: 140px;
        height: 140px;
        margin: 0 auto;
        border-radius: 50%;
        padding: 5px;
        background: #fff;
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }
    .avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #f8f9fa;
    }
    .btn-camera {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 4px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    /* ตกแต่งฟอร์ม */
    .info-label { font-size: 0.8rem; font-weight: 700; color: #6c757d; text-transform: uppercase; }
    .form-control-static { font-weight: 600; color: #333; padding: 8px 0; border-bottom: 1px solid #eee; }
    .input-group-custom { border-radius: 12px; border-color: #e2e8f0; }
</style>

<div class="container pb-5">
    <!-- Hero Section -->
    <div class="profile-hero text-center">
        <h2 class="fw-bold mb-0">ข้อมูลส่วนตัว</h2>
        <p class="opacity-75">จัดการข้อมูลบัญชีและรหัสผ่านเข้าสู่ระบบ</p>
        <br>
    </div>

    <div class="row justify-content-center">
        <!-- ฝั่งซ้าย: ข้อมูลบัญชี -->
        <div class="col-lg-4 mb-4">
            <div class="card profile-card">
                <div class="card-body p-4 text-center mt-3">
                    <div class="avatar-wrapper mb-3">
                        <img src="<?= $profile_img ?>?v=<?= time() ?>" id="current_img" class="avatar-img">
                        <button type="button" class="btn btn-primary btn-camera" id="btn_change_photo" title="เปลี่ยนรูปโปรไฟล์">
                            <i class="bi bi-camera-fill"></i>
                        </button>
                    </div>
                    
                    <h4 class="fw-bold mb-1"><?= h($u['full_name']) ?></h4>
                    <span class="badge bg-primary-subtle text-primary px-3 rounded-pill mb-4">
                        <i class="bi bi-patch-check-fill"></i> เจ้าหน้าที่ช่างเทคนิค
                    </span>

                    <hr class="my-4">
                    
                    <div class="text-start">
                        <div class="mb-3">
                            <label class="info-label"><i class="bi bi-envelope me-1"></i> อีเมล</label>
                            <div class="form-control-static"><?= h($u['email']) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="info-label"><i class="bi bi-phone me-1"></i> เบอร์โทรศัพท์</label>
                            <div class="form-control-static"><?= h($u['phone']) ?></div>
                        </div>
                    </div>
                    
                    <form id="uploadPhotoForm" style="display:none;">
                        <input type="file" name="profile_img" id="profile_img_input" accept="image/*">
                        <input type="hidden" name="action" value="update_photo">
                    </form>
                </div>
            </div>
        </div>

        <!-- ฝั่งขวา: เปลี่ยนรหัสผ่าน -->
        <div class="col-lg-6 mb-4">
            <div class="card profile-card h-100">
                <div class="card-header bg-white p-4 border-0">
                    <h5 class="fw-bold mb-0 text-main"><i class="bi bi-key-fill me-2"></i>เปลี่ยนรหัสผ่าน</h5>
                    <p class="text-muted small mb-0 mt-1">เพื่อความปลอดภัย โปรดหมั่นเปลี่ยนรหัสผ่านเป็นประจำ</p>
                </div>
                <div class="card-body p-4 pt-0">
                    <form id="updatePassForm">
                        <input type="hidden" name="action" value="update_password">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">รหัสผ่านปัจจุบัน</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" name="old_password" class="form-control bg-light border-start-0 shadow-none" placeholder="ป้อนรหัสผ่านที่ใช้งานอยู่ในตอนนี้" required>
                            </div>
                        </div>
                        
                        <hr class="my-4 border-dashed">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">รหัสผ่านใหม่</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-shield-plus"></i></span>
                                <input type="password" name="new_password" id="new_password" class="form-control border-start-0 shadow-none" placeholder="รหัสผ่านใหม่ (6 ตัวขึ้นไป)" required minlength="6">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold">ยืนยันรหัสผ่านใหม่</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-shield-check"></i></span>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control border-start-0 shadow-none" placeholder="กรอกรหัสผ่านใหม่ซ้ำอีกครั้ง" required minlength="6">
                            </div>
                        </div>
                         <div class="text-center mt-4">
                            <button type="submit" class="btn px-5 py-2 shadow-sm rounded-pill" 
                                    style="background-color: #003366; color: white; font-weight: 600; min-width: 200px;">
                                <i class="bi bi-check2-circle me-1"></i> อัปเดตรหัสผ่าน
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // เลือกไฟล์รูป
    $('#btn_change_photo').on('click', function() {
        $('#profile_img_input').click();
    });

    // อัปโหลดรูปอัตโนมัติเมื่อเลือกไฟล์
    $('#profile_img_input').on('change', function() {
        const formData = new FormData($('#uploadPhotoForm')[0]);
        Swal.fire({ title: 'กำลังบันทึกรูปภาพ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        $.ajax({
            url: '../api/user_actions.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({ icon: 'success', title: 'เปลี่ยนรูปสำเร็จ', timer: 1200, showConfirmButton: false }).then(() => location.reload());
                } else {
                    Swal.fire('ผิดพลาด', res.error, 'error');
                }
            }
        });
    });

    // เปลี่ยนรหัสผ่าน
    $('#updatePassForm').on('submit', function(e) {
        e.preventDefault();
        
        if($('#new_password').val() !== $('#confirm_password').val()) {
            Swal.fire('ข้อมูลไม่ถูกต้อง', 'รหัสผ่านใหม่ทั้ง 2 ช่องไม่ตรงกัน', 'error');
            return;
        }

        Swal.fire({
            title: 'ยืนยันการเปลี่ยนรหัสผ่าน?',
            text: "ระบบจะแจ้งให้คุณเข้าสู่ระบบใหม่อีกครั้ง",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#003366',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../api/user_actions.php',
                    type: 'POST',
                    data: $('#updatePassForm').serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            Swal.fire('สำเร็จ', 'อัปเดตรหัสผ่านแล้ว กรุณาเข้าสู่ระบบใหม่', 'success').then(() => location.href = '../logout.php');
                        } else {
                            Swal.fire('ล้มเหลว', res.error, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>

<?php require_once '../includes/tech_footer.php'; ?>