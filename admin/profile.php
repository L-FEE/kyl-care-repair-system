<?php 
/*
|------------------------------------------------------
| File: admin/profile.php
| Description: จัดการโปรไฟล์ผู้ดูแลระบบ (แก้ไขได้ครบวงจร)
|------------------------------------------------------
*/
require_once '../includes/admin_header.php'; 

// ดึงข้อมูลล่าสุดของผู้ดูแล
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

$profile_img = "../uploads/profiles/" . ($u['profile_image'] ?: 'default_profile.png');
?>

<style>
    .profile-hero {
        background: linear-gradient(135deg, #003366 0%, #0d47a1 100%);
        border-radius: 20px; padding: 45px 20px; color: white;
        margin-bottom: -60px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .profile-card { border: none; border-radius: 20px; box-shadow: 0 5px 25px rgba(0,0,0,0.08); overflow: hidden; background: #fff; }
    .avatar-wrapper { position: relative; width: 140px; height: 140px; margin: 0 auto; border-radius: 50%; padding: 5px; background: #fff; box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
    .avatar-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 2px solid #f8f9fa; }
    .btn-camera { position: absolute; bottom: 5px; right: 5px; width: 38px; height: 38px; border-radius: 50%; border: 4px solid #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    .form-label { font-weight: 700; color: #003366; font-size: 0.85rem; text-transform: uppercase; }
    .btn-main-dark { background-color: #003366; color: white; font-weight: 600; border-radius: 50px; transition: 0.3s; padding: 10px 30px; }
    .btn-main-dark:hover { background-color: #002244; color: #fff; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
</style>

<div class="container pb-5">
    <!-- ส่วนหัว -->
    <div class="profile-hero text-center">
        <h2 class="fw-bold mb-0">การตั้งค่าบัญชีผู้ดูแล</h2>
        <p class="opacity-75">จัดการข้อมูลพื้นฐานและรหัสผ่านเข้าสู่ระบบของคุณ</p>
    </div>

    <div class="row justify-content-center g-4">
        <!-- ฝั่งซ้าย: รูปและข้อมูลพื้นฐาน -->
        <div class="col-lg-5 mb-4">
            <div class="card profile-card h-100 mt-5">
                <div class="card-body p-4 pt-5 text-center">
                    <div class="avatar-wrapper mb-4" style="margin-top: -30px;">
                        <img src="<?= $profile_img ?>?v=<?= time() ?>" id="current_img" class="avatar-img">
                        <button type="button" class="btn btn-primary btn-camera" id="btn_change_photo"><i class="bi bi-camera-fill"></i></button>
                    </div>

                    <form id="updateProfileForm">
                        <input type="hidden" name="action" value="update_profile_info">
                        <div class="text-start">
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-person me-1"></i> ชื่อ-นามสกุล</label>
                                <input type="text" name="full_name" class="form-control rounded-3" value="<?= h($u['full_name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-envelope me-1"></i> อีเมลใช้งาน</label>
                                <input type="email" name="email" class="form-control rounded-3" value="<?= h($u['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-telephone me-1"></i> เบอร์โทรศัพท์</label>
                                <input type="text" name="phone" class="form-control rounded-3" value="<?= h($u['phone']) ?>" maxlength="10" required>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-main-dark"><i class="bi bi-check-circle me-1"></i> ยืนยัน</button>
                            </div>
                        </div>
                    </form>
                    
                    <form id="uploadPhotoForm" style="display:none;">
                        <input type="file" name="profile_img" id="profile_img_input" accept="image/*">
                        <input type="hidden" name="action" value="update_photo">
                    </form>
                </div>
            </div>
        </div>

        <!-- ฝั่งขวา: เปลี่ยนรหัสผ่าน -->
        <div class="col-lg-5 mb-4">
            <div class="card profile-card h-100 mt-lg-5">
                <div class="card-header bg-white p-4 border-0 pb-0">
                    <h5 class="fw-bold mb-0 text-main"><i class="bi bi-shield-lock-fill me-2"></i>รักษาความปลอดภัย</h5>
                    <p class="text-muted small mt-1">ตั้งรหัสผ่านใหม่เพื่อให้บัญชีของคุณปลอดภัยยิ่งขึ้น</p>
                </div>
                <div class="card-body p-4">
                    <form id="updatePassForm">
                        <input type="hidden" name="action" value="update_password">
                        <div class="mb-4">
                            <label class="form-label">รหัสผ่านปัจจุบัน</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" name="old_password" class="form-control" placeholder="รหัสผ่านเดิม" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="6 ตัวอักษรขึ้นไป" required minlength="6">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">ยืนยันรหัสผ่านใหม่อีกครั้ง</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control"  placeholder="ยืนยันรหัสผ่าน" required minlength="6">
                        </div><br><br>
                        <div class="text-center">
                            <button type="submit" class="btn btn-main-dark"><i class="bi bi-key-fill me-1"></i> ยืนยัน </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 1. คลิกรูปเพื่อเปลี่ยนรูป
    $('#btn_change_photo').on('click', () => $('#profile_img_input').click());
    $('#profile_img_input').on('change', function() {
        const formData = new FormData($('#uploadPhotoForm')[0]);
        Swal.fire({ title: 'กำลังอัปโหลดรูป...', didOpen: () => Swal.showLoading() });
        $.ajax({
            url: '../api/user_actions.php', type: 'POST', data: formData,
            processData: false, contentType: false, dataType: 'json',
            success: (res) => { if(res.success) location.reload(); else Swal.fire('Error', res.error, 'error'); }
        });
    });

    // 2. บันทึกข้อมูลส่วนตัว (ชื่อ, เมล, เบอร์)
    $('#updateProfileForm').on('submit', function(e) {
        e.preventDefault();
        $.post('../api/user_actions.php', $(this).serialize(), function(res) {
            if(res.success) {
                Swal.fire({ icon: 'success', title: 'อัปเดตข้อมูลแล้ว', timer: 1500, showConfirmButton: false }).then(() => location.reload());
            } else { Swal.fire('ผิดพลาด', res.error, 'error'); }
        }, 'json');
    });

    // 3. เปลี่ยนรหัสผ่าน
    $('#updatePassForm').on('submit', function(e) {
        e.preventDefault();
        if($('#new_password').val() !== $('#confirm_password').val()) {
            Swal.fire('Error', 'รหัสผ่านใหม่ไม่ตรงกัน', 'error'); return;
        }
        Swal.fire({
            title: 'ยืนยันเปลี่ยนรหัส?', text: "ระบบจะพาคุณออกไปล็อกอินใหม่อีกครั้ง",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#003366', confirmButtonText: 'ยืนยัน'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../api/user_actions.php', $(this).serialize(), function(res) {
                    if(res.success) location.href = '../logout.php';
                    else Swal.fire('Error', res.error, 'error');
                }, 'json');
            }
        });
    });
});
</script>
<?php require_once '../includes/admin_footer.php'; ?>