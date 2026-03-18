<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บังคับเปลี่ยนรหัสผ่าน - kyl_care</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; display: flex; align-items: center; min-height: 100vh; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); max-width: 450px; margin: auto; }
        .btn-main { background-color: #003366; color: white; border: none; padding: 12px; border-radius: 8px; width: 100%; }
    </style>
</head>
<body>

<div class="card p-4">
    <div class="text-center mb-4">
        <h4 style="color: #003366;">🔐 เปลี่ยนรหัสผ่านใหม่</h4>
        <p class="text-muted small">เนื่องจากเป็นการเข้าสู่ระบบครั้งแรก หรือรหัสผ่านของท่านถูกรีเซ็ต โปรดตั้งรหัสผ่านใหม่เพื่อความปลอดภัย</p>
    </div>
    <form id="changePasswordForm">
        <div class="mb-3">
            <label class="form-label">รหัสผ่านใหม่</label>
            <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
        </div>
        <div class="mb-4">
            <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
        </div>
        <button type="submit" class="btn btn-main">อัปเดตรหัสผ่านและเข้าสู่ระบบ</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$('#changePasswordForm').on('submit', function(e) {
    e.preventDefault();
    if ($('#new_password').val() !== $('#confirm_password').val()) {
        Swal.fire('ผิดพลาด', 'รหัสผ่านใหม่ไม่ตรงกัน', 'error');
        return;
    }

    $.ajax({
        url: 'api/auth_change_password.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Swal.fire('สำเร็จ', 'รหัสผ่านถูกอัปเดตแล้ว ไปยังหน้าหลัก', 'success')
                .then(() => location.href = res.redirect);
            } else {
                Swal.fire('ผิดพลาด', res.error, 'error');
            }
        }
    });
});
</script>
</body>
</html>