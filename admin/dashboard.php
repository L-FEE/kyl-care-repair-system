<?php 
/*
|------------------------------------------------------
| File: admin/dashboard.php
| Description: สรุปรายงานภาพรวม พร้อมระบบกรอง เดือน/ปี
|------------------------------------------------------
*/
require_once '../includes/admin_header.php'; 

// 1. จัดการตัวกรองเวลา (เดือน/ปี)
$selected_month = $_GET['month'] ?? date('n'); // ค่าเริ่มต้นคือเดือนปัจจุบัน
$selected_year = $_GET['year'] ?? date('Y');  // ค่าเริ่มต้นคือปีปัจจุบัน

$month_names = [
    'all' => "ทุกเดือน (รวมทั้งปี)",
    1 => "มกราคม", 2 => "กุมภาพันธ์", 3 => "มีนาคม", 4 => "เมษายน", 
    5 => "พฤษภาคม", 6 => "มิถุนายน", 7 => "กรกฎาคม", 8 => "สิงหาคม", 
    9 => "กันยายน", 10 => "ตุลาคม", 11 => "พฤศจิกายน", 12 => "ธันวาคม"
];

// 2. สร้างเงื่อนไข SQL ตามเวลาที่เลือก
$where_clauses = ["YEAR(created_at) = $selected_year"];
if ($selected_month !== 'all') {
    $where_clauses[] = "MONTH(created_at) = $selected_month";
}
$where_sql = "WHERE " . implode(' AND ', $where_clauses);

// 3. ดึงสถิติตัวเลข (แยกครบทุกสถานะ)
$stats = [
    'pending'   => $conn->query("SELECT id FROM repair_requests $where_sql AND status='pending'")->num_rows,
    'working'   => $conn->query("SELECT id FROM repair_requests $where_sql AND status IN ('accepted','in_progress')")->num_rows,
    'waiting'   => $conn->query("SELECT id FROM repair_requests $where_sql AND status='waiting_parts'")->num_rows,
    'completed' => $conn->query("SELECT id FROM repair_requests $where_sql AND status='completed'")->num_rows,
    'cannot'    => $conn->query("SELECT id FROM repair_requests $where_sql AND status='cannot_repair'")->num_rows,
    'total'     => $conn->query("SELECT id FROM repair_requests $where_sql")->num_rows
];
?>

<!-- หัวข้อและตัวกรอง -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard & Report</h3>
        <p class="text-muted small mb-0">ข้อมูลประจำช่วงเวลา: 
            <strong><?= ($selected_month === 'all') ? 'ปี '.$selected_year : $month_names[$selected_month].' '.$selected_year ?></strong>
        </p>
    </div>

    <!-- ฟอร์มตัวกรอง -->
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
                // 1. หาปีที่เก่าที่สุดที่มีการแจ้งซ่อมในฐานข้อมูล
                $year_query = $conn->query("SELECT YEAR(MIN(created_at)) as min_y FROM repair_requests");
                $year_row = $year_query->fetch_assoc();
                
                // ถ้ายังไม่มีข้อมูลเลย ให้เริ่มที่ปีปัจจุบัน แต่ถ้ามีข้อมูล ให้เริ่มที่ปีที่เก่าที่สุด
                $start_year = $year_row['min_y'] ? $year_row['min_y'] : date('Y');
                $current_year = date('Y');

                // 2. วนลูปสร้างตัวเลือกตั้งแต่ปีปัจจุบัน ถอยหลังไปจนถึงปีที่เริ่มมีข้อมูล (หรือปีที่ระบบเริ่มใช้งาน)
                if($start_year > 2025) $start_year = 2025; 

                for($y = $current_year; $y >= $start_year; $y--): ?>
                    <option value="<?= $y ?>" <?= ($selected_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-auto">
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary shadow-sm">คืนค่า</a>
        </div>
    </form>
</div>

<!-- ส่วนตัวเลขสรุปยอด (Cards) -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-6 g-3 mb-4">
    <!-- 1. รอดำเนินการ -->
    <div class="col">
        <div class="card p-3 border-0 shadow-sm border-start border-5 border-secondary h-100 bg-white">
            <div class="text-muted small fw-bold">รอดำเนินการ</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0"><?= number_format($stats['pending']) ?></h3>
                <i class="bi bi-clock-history text-secondary opacity-50 fs-3"></i>
            </div>
        </div>
    </div>

    <!-- 2. กำลังดำเนินการ -->
    <div class="col">
        <div class="card p-3 border-0 shadow-sm border-start border-5 border-primary h-100 bg-white">
            <div class="text-muted small fw-bold text-primary">กำลังดำเนินการ</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0 text-primary"><?= number_format($stats['working']) ?></h3>
                <i class="bi bi-gear-wide-connected text-primary opacity-50 fs-3"></i>
            </div>
        </div>
    </div>

    <!-- 3. รออะไหล่ -->
    <div class="col">
        <div class="card p-3 border-0 shadow-sm border-start border-5 h-100 bg-white" style="border-color: #ef6c00 !important;">
            <div class="small fw-bold" style="color: #ef6c00;">รออะไหล่</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0" style="color: #ef6c00;"><?= number_format($stats['waiting']) ?></h3>
                <i class="bi bi-hourglass-split opacity-50 fs-3" style="color: #ef6c00;"></i>
            </div>
        </div>
    </div>

    <!-- 4. ซ่อมสำเร็จ -->
    <div class="col">
        <div class="card p-3 border-0 shadow-sm border-start border-5 border-success h-100 bg-white">
            <div class="text-muted small fw-bold text-success">ซ่อมสำเร็จ</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0 text-success"><?= number_format($stats['completed']) ?></h3>
                <i class="bi bi-check-circle text-success opacity-50 fs-3"></i>
            </div>
        </div>
    </div>

    <!-- 5. ซ่อมไม่ได้ -->
    <div class="col">
        <div class="card p-3 border-0 shadow-sm border-start border-5 border-danger h-100 bg-white">
            <div class="text-muted small fw-bold text-danger">ซ่อมไม่ได้</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0 text-danger"><?= number_format($stats['cannot']) ?></h3>
                <i class="bi bi-x-circle text-danger opacity-50 fs-3"></i>
            </div>
        </div>
    </div>

    <!-- 6. ยอดรวมทั้งหมด -->
    <div class="col">
        <div class="card p-3 border-0 shadow-sm h-100 text-white" style="background-color: #003366;">
            <div class="small fw-bold opacity-75">รวมทั้งหมด</div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <h3 class="fw-bold mb-0"><?= number_format($stats['total']) ?></h3>
                <i class="bi bi-layers-fill opacity-50 fs-3"></i>
            </div>
        </div>
    </div>
</div>

<!-- ส่วนกราฟ -->
<div class="row">
    <!-- กราฟแท่งแยกตามประเภท -->
    <div class="col-lg-7 mb-4">
        <div class="card p-4 border-0 shadow-sm h-100">
            <h6 class="fw-bold mb-4 text-secondary">
                <i class="bi bi-bar-chart-fill me-2"></i>สถิติแยกตามประเภทอุปกรณ์
            </h6>
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- กราฟวงกลมสถานะงาน -->
    <div class="col-lg-5 mb-4">
        <div class="card p-4 border-0 shadow-sm h-100 text-center">
            <h6 class="fw-bold mb-4 text-secondary text-start">
                <i class="bi bi-pie-chart-fill me-2"></i>สัดส่วนสถานะงานซ่อม
            </h6>
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // ข้อมูลสำหรับกราฟประเภทอุปกรณ์
    <?php
        $cat_data = $conn->query("SELECT category_name, COUNT(*) as count 
                                FROM repair_requests 
                                $where_sql 
                                AND category_name != '' 
                                AND category_name IS NOT NULL 
                                GROUP BY category_name 
                                ORDER BY count DESC");
        $labels = []; $counts = [];
    while($row = $cat_data->fetch_assoc()){ 
        $labels[] = $row['category_name']; 
        $counts[] = $row['count']; 
    }
    ?>

    // Chart.js - Bar Chart
    const ctxBar = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'จำนวนงาน',
                data: <?= json_encode($counts) ?>,
                backgroundColor: '#003366',
                borderRadius: 8,
                barThickness: 25
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

    // Chart.js - Doughnut Chart
    // --- แก้ไขส่วนกราฟวงกลมแสดงสถานะงาน ---
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            // เพิ่มหัวข้อให้ครบตาม Card ด้านบน
            labels: ['รอดำเนินการ', 'กำลังดำเนินการ', 'รออะไหล่', 'ซ่อมสำเร็จ', 'ซ่อมไม่ได้'],
            datasets: [{
                // ดึงค่าจาก PHP $stats ที่เราปรับปรุงใหม่ให้ตรงคีย์
                data: [
                    <?= $stats['pending'] ?>, 
                    <?= $stats['working'] ?>, 
                    <?= $stats['waiting'] ?>, 
                    <?= $stats['completed'] ?>, 
                    <?= $stats['cannot'] ?>
                ],
                backgroundColor: [
                    '#6c757d', // รอดำเนินการ (เทา)
                    '#007bff', // กำลังดำเนินการ (น้ำเงิน)
                    '#ef6c00', // รออะไหล่ (ส้มเข้ม)
                    '#198754', // ซ่อมสำเร็จ (เขียว)
                    '#dc3545'  // ซ่อมไม่ได้ (แดง)
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
            cutout: '65%' // ทำเป็นวงแหวนให้ดูทันสมัย
        }
    });
});
</script>

<style>
    .bg-main { background-color: #003366 !important; }
    .card { transition: transform 0.2s; }
</style>

<?php require_once '../includes/admin_footer.php'; ?>