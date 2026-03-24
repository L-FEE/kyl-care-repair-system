<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// ตรวจสอบสิทธิ์ (ต้องเป็น admin เท่านั้น)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ดึงรูปโปรไฟล์ล่าสุดจากฐานข้อมูล
$stmt_profile = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt_profile->bind_param("i", $user_id);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result()->fetch_assoc();

// กำหนด path ของรูปภาพ ถ้าไม่มีให้ใช้รูปมาตรฐาน
$image_file = $res_profile['profile_image'] ?: 'default_profile.png';
$profile_img = "../uploads/profiles/" . $image_file;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../uploads/logo/logo.png">
    <title>ผู้ดูแลระบบ - KYL CARE</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f7fa; }
        .bg-main { background-color: #003366 !important; }
        
        /* ปรับปรุง Sidebar ให้ล็อกอยู่กับที่ */
        .sidebar { 
            position: sticky; 
            top: 56px; 
            height: calc(100vh - 56px); 
            background: #ffffff; 
            border-right: 1px solid #dee2e6; 
            padding-top: 1rem;
            z-index: 1000;
            overflow-y: auto;
        }

        .nav-link { 
            color: #495057; 
            padding: 12px 20px; 
            border-radius: 8px; 
            margin: 4px 10px; 
            transition: 0.3s; 
            font-weight: 500;
        }
        .nav-link:hover, .nav-link.active { 
            background: #e7f1ff; 
            color: #003366; 
            font-weight: 600; 
        }

        /* ปรับปรุงส่วนเนื้อหาให้เลื่อนแยกต่างหาก */
        main {
            height: calc(100vh - 56px);
            overflow-y: auto;
        }

        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        /* ตกแต่งรูปโปรไฟล์บน Navbar */
        .nav-profile-img {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.2);
        }
    </style>
    
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-main sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/admin/dashboard.php">
            <i class="bi bi-tools me-1"></i> KYL CARE | Admin Panel
        </a>
        
        <div class="dropdown">
            <span class="text-white me-2 d-none d-md-inline small">ผู้ดูแล: <?= h($_SESSION['full_name']) ?></span>
            <button class="btn btn-link text-white p-0 text-decoration-none dropdown-toggle-nocaret" data-bs-toggle="dropdown" aria-expanded="false">
                <!-- เปลี่ยนจากไอคอนวงกลม เป็นรูปโปรไฟล์จริง -->
                <img src="<?= $profile_img ?>?v=<?= time() ?>" class="nav-profile-img shadow-sm">
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3">
                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/admin/profile.php"><i class="bi bi-person-circle me-2"></i> ข้อมูลส่วนตัว</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="confirmLogout()"><i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 sidebar d-none d-md-block pt-2">
            <div class="nav flex-column">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php">
                    <i class="bi bi-grid-1x2-fill me-2"></i> Dashboard & Report
                </a>

                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'user_monitoring.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/user_monitoring.php">
                    <i class="bi bi-person-vcard-fill me-2"></i> ติดตามงาน
                </a>

                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'technicians.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/technicians.php">
                    <i class="bi bi-person-badge-fill me-2"></i> ข้อมูลผู้ใช้งาน
                </a>

                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_assets.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_assets.php">
                    <i class="bi bi-diagram-3-fill me-2"></i> ข้อมูลห้องและอุปกรณ์
                </a>

                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'repeat_repairs.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/repeat_repairs.php">
                    <i class="bi bi-arrow-repeat me-2"></i> รายการแจ้งซ่อมซ้ำ
                </a>

                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'all_repairs.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/all_repairs.php">
                    <i class="bi bi-clipboard-data-fill me-2"></i> รายการแจ้งซ่อมทั้งหมด
                </a>

                <!-- <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_history.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/manage_history.php">
                    </i> ลบประวัติ
                </a> -->
            </div>
        </nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">