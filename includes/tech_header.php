<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// ตรวจสอบสิทธิ์ (ต้องเป็น technician เท่านั้น)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header('Location: ../login.php');
    exit;
}

// ตรวจสอบว่าต้องเปลี่ยนรหัสผ่านหรือไม่
$user_id = $_SESSION['user_id'];
$check_user = $conn->query("SELECT is_first_login, profile_image FROM users WHERE id = $user_id")->fetch_assoc();
if ($check_user['is_first_login'] == 1) {
    header('Location: ../change_password.php');
    exit;
}

$profile_img = "../uploads/profiles/" . ($check_user['profile_image'] ?: 'default_profile.png');

// ... หลังจากดึงข้อมูล user ...
// 1. นับจำนวนงานที่ยังไม่มีผู้รับ (สถานะ pending)
$count_pending = $conn->query("SELECT id FROM repair_requests WHERE status = 'pending'")->num_rows;
// 2. นับจำนวนงานที่ช่างคนนี้กำลังดูแล (accepted, in_progress, waiting_parts)
$count_my_jobs = $conn->query("SELECT id FROM repair_requests WHERE technician_id = $user_id AND status IN ('accepted', 'in_progress', 'waiting_parts')")->num_rows;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="..\uploads\logo\logo.png">
    <title>ช่างซ่อม - kyl_care</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- jQuery และ SweetAlert2 มาไว้ที่นี่ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8f9fa; }
        .bg-main { background-color: #003366 !important; }

        /* ปรับปรุง Sidebar ของช่างให้ล็อกอยู่กับที่ */
        .sidebar { 
            position: sticky; 
            top: 56px; /* ระยะห่างจากด้านบนเท่ากับความสูง Navbar */
            height: calc(100vh - 56px); /* ความสูงหน้าจอ ลบความสูง Navbar */
            background: white; 
            border-right: 1px solid #eee; 
            padding-top: 1rem;
            z-index: 1000;
            overflow-y: auto; /* เลื่อนเฉพาะในเมนูได้ถ้าเมนูยาว */
        }

        /* ปรับปรุงส่วนเนื้อหาของช่างให้เลื่อนแยกต่างหาก */
        main {
            height: calc(100vh - 56px);
            overflow-y: auto;
        }

        .nav-link { 
            color: #555; 
            padding: 12px 20px; 
            border-radius: 8px; 
            margin: 4px 10px; 
        }
        .nav-link:hover, .nav-link.active { 
            background: #e7f1ff; 
            color: #003366; 
            font-weight: 600; 
        }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-main sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">KYL CARE | Technician</a>
        <div class="d-flex align-items-center">
            <span class="text-white me-3 d-none d-md-block">ช่าง: <?= h($_SESSION['full_name']) ?></span>
            <div class="dropdown">
                <img src="<?= $profile_img ?>" width="35" height="35" class="rounded-circle border" data-bs-toggle="dropdown" role="button">
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> ข้อมูลส่วนตัว</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmLogout()"><i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 sidebar d-none d-md-block pt-3">
            <div class="nav flex-column">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="bi bi-grid-1x2-fill me-2"></i> แผงควบคุม
                </a>

                <!-- งานที่ยังไม่รับ (แสดงถ้ามีงานเข้า) -->
                <a class="nav-link d-flex justify-content-between align-items-center <?= basename($_SERVER['PHP_SELF']) == 'available_jobs.php' ? 'active' : '' ?>" href="available_jobs.php">
                    <span><i class="bi bi-tools me-2"></i> งานที่ยังไม่รับ</span>
                    <?php if ($count_pending > 0): ?>
                        <span class="badge rounded-pill bg-primary" style="font-size: 0.7rem; padding: 0.4em 0.8em; background-color: #003366 !important;">
                            <?= $count_pending ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- งานของฉัน (แสดงสีน้ำเงินเพื่อแจ้งยอดสะสม) -->
                <a class="nav-link d-flex justify-content-between align-items-center <?= basename($_SERVER['PHP_SELF']) == 'my_jobs.php' ? 'active' : '' ?>" href="my_jobs.php">
                    <span><i class="bi bi-clipboard-check me-2"></i> งานของฉัน</span>
                    <?php if ($count_my_jobs > 0): ?>
                        <span class="badge rounded-pill bg-primary" style="font-size: 0.7rem; padding: 0.4em 0.8em; background-color: #003366 !important;">
                            <?= $count_my_jobs ?>
                        </span>
                    <?php endif; ?>
                </a>

                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active' : '' ?>" href="history.php">
                    <i class="bi bi-clock-history me-2"></i> ประวัติการซ่อม
                </a>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">