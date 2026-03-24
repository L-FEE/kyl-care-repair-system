<?php 
/*
|------------------------------------------------------
| File: technician/dashboard.php
| Description: สรุปผลงานและสถิติส่วนบุคคลของช่างเทคนิค
|------------------------------------------------------
*/
require_once '../includes/tech_header.php'; 

// 1. จัดการตัวกรองเวลา (เหมือนแอดมิน)
$selected_month = $_GET['month'] ?? date('n'); 
$selected_year = $_GET['year'] ?? date('Y');

$month_names = [
    'all' => "ทุกเดือน (รวมทั้งปี)",
    1 => "มกราคม", 2 => "กุมภาพันธ์", 3 => "มีนาคม", 4 => "เมษายน", 
    5 => "พฤษภาคม", 6 => "มิถุนายน", 7 => "กรกฎาคม", 8 => "สิงหาคม", 
    9 => "กันยายน", 10 => "ตุลาคม", 11 => "พฤศจิกายน", 12 => "ธันวาคม"
];

// 2. เงื่อนไข SQL (กรองตามเวลา และกรองเฉพาะงานของช่างคนนี้)
$time_where = "YEAR(created_at) = $selected_year";
if ($selected_month !== 'all') {
    $time_where .= " AND MONTH(created_at) = $selected_month";
}

// 3. ดึงสถิติตัวเลขเฉพาะของตัวเอง
$stats = [
    'available' => $conn->query("SELECT id FROM repair_requests WHERE status='pending' AND $time_where")->num_rows, // งานที่ระบบรออยู่
    'working'   => $conn->query("SELECT id FROM repair_requests WHERE technician_id = $user_id AND status IN ('accepted','in_progress') AND $time_where")->num_rows,
    'waiting'   => $conn->query("SELECT id FROM repair_requests WHERE technician_id = $user_id AND status='waiting_parts' AND $time_where")->num_rows,
    'completed' => $conn->query("SELECT id FROM repair_requests WHERE technician_id = $user_id AND status='completed' AND $time_where")->num_rows,
    'cannot'    => $conn->query("SELECT id FROM repair_requests WHERE technician_id = $user_id AND status='cannot_repair' AND $time_where")->num_rows,
    'my_total'  => $conn->query("SELECT id FROM repair_requests WHERE technician_id = $user_id AND $time_where")->num_rows
];
?>

<div class="mb-4 d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h3 class="fw-bold mb-0">แผงควบคุมและสถิติ</h3>
        <p class="text-muted small mb-0">ติดตามผลการดำเนินงานส่วนบุคคลของคุณ</p>
    </div>

    <form method="GET" class="row g-2 mt-2 mt-md-0">
        <div class="col-auto">
            <select name="month" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                <?php foreach($month_names as $m_val => $m_name): ?>
                    <option value="<?= $m_val ?>" <?= ($selected_month == $m_val) ? 'selected' : '' ?>><?= $m_name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <select name="year" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                <?php 
                $year_res = $conn->query("SELECT YEAR(MIN(created_at)) as min_y FROM repair_requests");
                $min_y = $year_res->fetch_assoc()['min_y'] ?: date('Y');
                if($min_y > 2024) $min_y = 2024;
                for($y = date('Y'); $y >= $min_y; $y--): ?>
                    <option value="<?= $y ?>" <?= ($selected_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-auto">
            <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-sm btn-outline-secondary">วันนี้</a>
        </div>
    </form>
</div>

<!-- 6 สรุปตัวเลข (เหมือนแอดมินแต่ความหมายเปลี่ยนเล็กน้อยเพื่อให้เข้ากับช่าง) -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-6 g-3 mb-4">
    <div class="col">
        <div class="card p-3 border-0 shadow-sm border-start border-5 border-secondary h-100 bg-white">
            <div class="text-muted small fw-bold">งานระบบ (ว่าง)</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0 text-secondary"><?= $stats['available'] ?></h3>
                <i class="bi bi- megaphone-fill text-secondary opacity-25 fs-3"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card p-3 border-0 shadow-sm border-start border-5 border-primary h-100 bg-white">
            <div class="text-primary small fw-bold">งานของคุณ</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0 text-primary"><?= $stats['working'] ?></h3>
                <i class="bi bi-tools text-primary opacity-25 fs-3"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card p-3 border-0 shadow-sm border-start border-5 h-100 bg-white" style="border-color:#ef6c00!important">
            <div class="small fw-bold" style="color:#ef6c00">รออะไหล่</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0" style="color:#ef6c00"><?= $stats['waiting'] ?></h3>
                <i class="bi bi-hourglass-split opacity-25 fs-3" style="color:#ef6c00"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card p-3 border-0 shadow-sm border-start border-5 border-success h-100 bg-white">
            <div class="text-success small fw-bold">สำเร็จ (คุณ)</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0 text-success"><?= $stats['completed'] ?></h3>
                <i class="bi bi-check-circle-fill text-success opacity-25 fs-3"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card p-3 border-0 shadow-sm border-start border-5 border-danger h-100 bg-white">
            <div class="text-danger small fw-bold">ยกเลิก/ทำไม่ได้</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0 text-danger"><?= $stats['cannot'] ?></h3>
                <i class="bi bi-x-circle-fill text-danger opacity-25 fs-3"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card p-3 border-0 shadow-sm h-100 text-white bg-main">
            <div class="small fw-bold opacity-75">รวมงานที่คุณปิด</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0"><?= $stats['completed'] + $stats['cannot'] ?></h3>
                <i class="bi bi-person-check fs-3 opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- กราฟแท่ง -->
    <div class="col-lg-7 mb-4">
        <div class="card p-4 border-0 shadow-sm h-100">
            <h6 class="fw-bold text-secondary mb-4"><i class="bi bi-bar-chart-fill me-2"></i>ประเภทอุปกรณ์ที่คุณดำเนินการล่าสุด</h6>
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="techCategoryChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- กราฟวงกลม -->
    <div class="col-lg-5 mb-4">
        <div class="card p-4 border-0 shadow-sm h-100 text-center">
            <h6 class="fw-bold text-secondary mb-4 text-start"><i class="bi bi-pie-chart-fill me-2"></i>สัดส่วนสถานะงานของคุณ</h6>
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="techStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // 1. ข้อมูลสำหรับกราฟแท่ง - ผลงานแยกตามประเภทอุกปรณ์ (ช่างคนนี้)
    <?php
    $cat_res = $conn->query("SELECT category_name, COUNT(*) as count 
                             FROM repair_requests 
                             WHERE technician_id = $user_id 
                             AND $time_where 
                             AND category_name != '' 
                             AND category_name IS NOT NULL
                             GROUP BY category_name 
                             ORDER BY count DESC");
    $l_bar = []; $c_bar = [];
    while($r = $cat_res->fetch_assoc()){ 
        $l_bar[] = $r['category_name']; 
        $c_bar[] = $r['count']; 
    }
    ?>

    // Chart.js - Bar Chart (ปรับขนาดแท่งให้เท่า Admin)
    const ctxBar = document.getElementById('techCategoryChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?= json_encode($l_bar) ?>,
            datasets: [{
                label: 'จำนวนงาน',
                data: <?= json_encode($c_bar) ?>,
                backgroundColor: '#003366',
                borderRadius: 8,
                barThickness: 25 // ขนาดแท่งเท่ากับหน้า Admin
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Chart.js - Doughnut Chart (สัดส่วนสถานะงานของคุณ)
    const ctxStatus = document.getElementById('techStatusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['กำลังทำ', 'รออะไหล่', 'สำเร็จ', 'ไม่ได้'],
            datasets: [{
                data: [
                    <?= $stats['working'] ?>, 
                    <?= $stats['waiting'] ?>, 
                    <?= $stats['completed'] ?>, 
                    <?= $stats['cannot'] ?>
                ],
                backgroundColor: [
                    '#007bff', // กำลังดำเนินการ
                    '#ef6c00', // รออะไหล่ (ส้มเข้ม)
                    '#198754', // สำเร็จ
                    '#dc3545'  // ไม่ได้
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { 
                        padding: 20, 
                        usePointStyle: true,
                        font: { family: 'Sarabun' } 
                    } 
                }
            },
            cutout: '65%' // ทำเป็นวงแหวนเท่ากับหน้า Admin
        }
    });
});
</script>

<?php require_once '../includes/tech_footer.php'; ?>