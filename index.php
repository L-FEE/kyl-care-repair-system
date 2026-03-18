<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="uploads\logo\logo.png">
    <title>KYL CARE | ระบบแจ้งซ่อมอุปกรณ์</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8f9fa; }
        .bg-main { background-color: #003366 !important; }
        .hero-section { background: linear-gradient(rgba(0,51,102,0.9), rgba(0,51,102,0.9)), url('assets/images/bg-repair.jpg'); background-size: cover; color: white; padding: 60px 0; }
        .search-box { background: white; border-radius: 50px; padding: 10px 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .search-box input { border: none; outline: none; width: 100%; font-size: 1.1rem; }
        .card { border: none; border-radius: 15px; transition: 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .btn-report { background-color: #ffc107; border: none; color: #003366; font-weight: bold; padding: 12px 30px; border-radius: 50px; }
        .btn-report:hover { background-color: #ffca2c; }
    </style>
</head>
<body>

<!-- Navbar -->
<!-- Navbar สำหรับหน้า index.php -->
<nav class="navbar navbar-expand-lg navbar-dark bg-main shadow-sm sticky-top">
    <div class="container">
        <!-- Logo ลิงก์กลับหน้าแรก -->
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-tools me-2"></i>KYL CARE
        </a>
        
        <!-- ปุ่ม Toggle สำหรับมือถือ -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="bi bi-house-door me-1"></i>หน้าแรก/ติดตามสถานะ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="report_create.php">
                        <i class="bi bi-plus-circle me-1"></i>แจ้งซ่อมอุปกรณ์
                    </a>
                </li>
                <li class="nav-item ms-lg-3">
                    <a href="login.php" class="btn btn-outline-light btn-sm px-3 rounded-pill">
                        <i class="bi bi-person-lock me-1"></i>สำหรับเจ้าหน้าที่
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section & Search -->
<div class="hero-section text-center">
    <div class="container">
        <h1 class="display-5 fw-bold mb-3">ติดตามสถานะการแจ้งซ่อม</h1>
        <p class="lead mb-4">พิมพ์ชื่อ-นามสกุลของคุณ เพื่อค้นหาประวัติและสถานะการซ่อม</p>
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="search-box d-flex align-items-center">
                    <i class="bi bi-search text-muted fs-4 me-3"></i>
                    <input type="text" id="search_name" placeholder="ระบุชื่อ-นามสกุล..." autocomplete="off">
                    <button onclick="searchHistory()" class="btn btn-primary px-4 rounded-pill bg-main border-0">ค้นหา</button>
                </div>
            </div>
        </div>
        <div class="mt-5">
            <a href="report_create.php" class="btn btn-report shadow"><i class="bi bi-plus-circle me-2"></i> แจ้งซ่อม </a>
        </div>
    </div>
</div>

<!-- Display Section -->
<div class="container py-5">
    <div id="display_title" class="mb-4 d-flex justify-content-between align-items-center">
        <h4 class="fw-bold m-0">รายการแจ้งซ่อมล่าสุด</h4>
    </div>

    <div id="repair_list" class="row g-4">
        <!-- ข้อมูลจะโหลดจาก AJAX หรือ PHP เบื้องต้น -->
        <?php
        $sql = "SELECT * FROM repair_requests ORDER BY created_at DESC LIMIT 6";
        $res = $conn->query($sql);
        while($row = $res->fetch_assoc()):
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-light text-dark border"><?= $row['request_code'] ?></span>
                    <?= getStatusBadge($row['status']) ?>
                </div>
                <h5 class="fw-bold mb-1"><?= h($row['device_name']) ?></h5>
                <p class="text-danger small mb-2"><i class="bi bi-exclamation-circle"></i> <?= h($row['fault_name']) ?></p>
                <div class="text-muted small mb-3">
                    <i class="bi bi-geo-alt"></i> <?= h($row['location_name']) ?>
                </div>
                <hr class="mt-auto mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted"><?= h($row['reporter_name']) ?></small>
                    <small class="text-muted"><?= date('d/m/Y', strtotime($row['created_at'])) ?></small>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
function searchHistory() {
    const name = $('#search_name').val();
    if (name.length < 2) {
        Swal.fire('แจ้งเตือน', 'กรุณาระบุชื่ออย่างน้อย 2 ตัวอักษร', 'info');
        return;
    }

    // แสดง Loading
    $('#repair_list').html('<div class="text-center py-5 w-100"><div class="spinner-border text-primary"></div><p class="mt-2">กำลังค้นหา...</p></div>');

    $.ajax({
        url: 'api/get_search_history.php',
        type: 'GET',
        data: { reporter_name: name },
        dataType: 'json',
        success: function(data) {
            $('#display_title h4').text('ผลการค้นหาสำหรับ: ' + name);
            let html = '';
            if (data.length > 0) {
                data.forEach(item => {
                    html += `
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-light text-dark border">${item.request_code}</span>
                                ${item.status_badge}
                            </div>
                            <h5 class="fw-bold mb-1">${item.device_name}</h5>
                            <p class="text-danger small mb-2"><i class="bi bi-exclamation-circle"></i> ${item.fault_name}</p>
                            <div class="text-muted small mb-3">
                                <i class="bi bi-geo-alt"></i> ${item.floor_name} | ${item.location_name}
                            </div>
                            <hr class="mt-auto mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">ผู้แจ้ง: ${item.reporter_name}</small>
                                <small class="text-muted">${item.date_formatted}</small>
                            </div>
                        </div>
                    </div>`;
                });
            } else {
                html = '<div class="text-center py-5 w-100"><i class="bi bi-search fs-1 text-muted"></i><p class="mt-3">ไม่พบประวัติการแจ้งซ่อมในชื่อนี้</p></div>';
            }
            $('#repair_list').html(html);
        }
    });
}

// ค้นหาเมื่อกด Enter
$('#search_name').on('keypress', function(e) {
    if(e.which == 13) searchHistory();
});
</script>

</body>
</html>