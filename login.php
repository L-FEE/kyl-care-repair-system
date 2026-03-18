<?php
session_start();
require_once 'config/db.php';

// ถ้า Login อยู่แล้วให้ไปที่ Dashboard ของแต่ละ Role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') header('Location: admin/dashboard.php');
    else header('Location: technician/my_jobs.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="uploads\logo\logo.png">
    <title>เข้าสู่ระบบ - kyl_care</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #003366; display: flex; align-items: center; min-height: 100vh; }
        .login-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; max-width: 400px; width: 100%; margin: auto; background: white; }
        .login-header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 1px solid #eee; }
        .login-body { padding: 40px; }
        .btn-main { background-color: #003366; border: none; color: white; padding: 12px; border-radius: 8px; width: 100%; font-weight: 600; }
        .btn-main:hover { background-color: #002244; color: white; }
        .form-control { padding: 12px; border-radius: 8px; border: 1px solid #ddd; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <h3 style="color: #003366; font-weight: 600;">KYL CARE</h3>
        <p class="text-muted mb-0">เข้าสู่ระบบสำหรับเจ้าหน้าที่</p>
    </div>
    <div class="login-body">
        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">อีเมล</label>
                <input type="email" name="email" class="form-control" placeholder="abc@gmail.com" required>
            </div>
            <div class="mb-4">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" name="password" class="form-control" placeholder="••••••" required>
            </div>
            <button type="submit" class="btn btn-main">เข้าสู่ระบบ</button>
            <div class="text-center mt-4">
                <a href="index.php" class="text-decoration-none text-muted" style="font-size: 0.9rem;"><i class="bi bi-arrow-left"></i> ย้อนกลับ</a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$('#loginForm').on('submit', function(e) {
    e.preventDefault();
    const btn = $(this).find('button');
    btn.prop('disabled', true).text('กำลังตรวจสอบ...');

    $.ajax({
        url: 'api/auth_login.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'สำเร็จ', text: 'กำลังเข้าสู่ระบบ', timer: 1500, showConfirmButton: false })
                .then(() => {
                    location.href = res.redirect;
                });
            } else {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: res.error });
                btn.prop('disabled', false).text('เข้าสู่ระบบ');
            }
        }
    });
});
</script>
</body>
</html>