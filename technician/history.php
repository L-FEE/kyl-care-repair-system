<?php 
/*
|------------------------------------------------------
| File: technician/history.php
| Description: ประวัติการซ่อมแบบ Instant Search (สไตล์แอดมิน)
|------------------------------------------------------
*/
require_once '../includes/tech_header.php'; 

$selected_month = $_GET['month'] ?? date('n'); 
$selected_year = $_GET['year'] ?? date('Y');
$status_filter = $_GET['status'] ?? 'all';

$month_names = [
    'all' => "-- ทุกเดือน --",
    1 => "มกราคม", 2 => "กุมภาพันธ์", 3 => "มีนาคม", 4 => "เมษายน", 5 => "พฤษภาคม", 6 => "มิถุนายน",
    7 => "กรกฎาคม", 8 => "สิงหาคม", 9 => "กันยายน", 10 => "ตุลาคม", 11 => "พฤศจิกายน", 12 => "ธันวาคม"
];
?>

<div class="mb-4">
    <h3 class="fw-bold mb-0">ประวัติการดำเนินการของฉัน</h3>
    <p class="text-muted small">รวมข้อมูลงานซ่อมที่คุณปิดงานแล้วทั้งหมด</p>
</div>

<!-- ส่วนตัวกรอง (เลียนแบบ All Repairs Admin) -->
<div class="card p-3 mb-4 shadow-sm border-0 rounded-4">
    <form id="filterForm" class="row g-3">
        <div class="col-md-2 col-6">
            <label class="small text-muted fw-bold">ประจำปี</label>
            <select name="year" id="filter_year" class="form-select form-select-sm shadow-sm">
                <?php 
                $y_res = $conn->query("SELECT YEAR(MIN(created_at)) as min_y FROM repair_requests");
                $min_y = $y_res->fetch_assoc()['min_y'] ?: 2025;
                for($y = date('Y'); $y >= 2025; $y--): ?>
                    <option value="<?= $y ?>" <?= ($selected_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="col-md-3 col-6">
            <label class="small text-muted fw-bold">ประจำเดือน</label>
            <select name="month" id="filter_month" class="form-select form-select-sm shadow-sm">
                <?php foreach($month_names as $val => $name): ?>
                    <option value="<?= $val ?>" <?= ($selected_month == $val) ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 col-6">
            <label class="small text-muted fw-bold">สถานะ</label>
            <select name="status" id="filter_status" class="form-select form-select-sm shadow-sm">
                <option value="all">-- ทุกสถานะ --</option>
                <option value="completed">ซ่อมเสร็จสิ้น</option>
                <option value="cannot_repair">ซ่อมไม่ได้</option>
            </select>
        </div>

        <div class="col-md-5 col-6">
            <label class="small text-muted fw-bold">ค้นหา</label>
            <div class="input-group input-group-sm shadow-sm">
                <input type="text" id="instant_search" name="search" class="form-control" placeholder="พิมพ์เพื่อค้นหา..." autocomplete="off">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            </div>
        </div>
    </form>
</div>

<!-- ตารางประวัติ (สไตล์ All Repairs Admin) -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">รหัสงาน</th>
                    <th>ผู้แจ้ง</th>
                    <th>อุปกรณ์</th>
                    <th class="text-center">สถานะ</th>
                    <th>วันที่ซ่อมเสร็จ</th>
                    <th class="text-center">รายละเอียด</th>
                </tr>
            </thead>
            <tbody id="history_table_body">
                <!-- ข้อมูลจาก AJAX จะมาโหลดลงที่นี่ -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal แสดงรายละเอียดประวัติ (ช่าง) -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-main text-white py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i>รายละเอียดงานซ่อม</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div id="historyContent" class="modal-body p-0">
                <!-- ข้อมูลจะถูกโหลดผ่าน AJAX -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <!-- <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary px-4 fw-bold shadow-sm" data-bs-dismiss="modal">ปิด</button>
            </div> -->
        </div>
    </div>
</div>

<script>
function viewJobHistory(id) {
    const modal = new bootstrap.Modal(document.getElementById('historyModal'));
    modal.show();
    
    // ดึงเนื้อหาจาก API
    $.get('../api/tech_get_history_detail.php?id=' + id, function(html) {
        $('#historyContent').html(html);
        
        // ผูกการทำงาน Fancybox (สำหรับรูปใน Modal)
        if (typeof Fancybox !== 'undefined') {
            Fancybox.bind("[data-fancybox='history-gallery']", {
                parentEl: document.body, // สำคัญ: เพื่อให้รูปขยายอยู่บนสุด
                dragToClose: false
            });
        }
    });
}
</script>

<style>
    .bg-main { background-color: #003366 !important; }
    /* ดันเลเยอร์รูปขยายให้ทับซ้อน Modal */
    .fancybox__container { z-index: 100000 !important; }
    
    .img-history-detail {
        height: 80px; width: 100%;
        object-fit: cover; border-radius: 12px;
        border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        cursor: pointer; transition: 0.2s;
    }
    .img-history-detail:hover { transform: scale(1.05); }
</style>

<!-- เพิ่ม Fancybox CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script>
$(document).ready(function() {
    // ฟังก์ชันหลักในการโหลดข้อมูลแบบ AJAX
    function fetchHistory() {
        const formData = $('#filterForm').serialize();
        // แสดง loading เล็กๆ ที่ตาราง
        $('#history_table_body').css('opacity', '0.5');

        $.get('../api/tech_search_history.php', formData, function(html) {
            $('#history_table_body').html(html).css('opacity', '1');
            Fancybox.bind("[data-fancybox]", {}); // ผูก Fancybox ใหม่
        });
    }

    // 1. ตรวจสอบเมื่อมีการพิมพ์ (Instant Search)
    $('#instant_search').on('input', function() {
        fetchHistory();
    });

    // 2. ตรวจสอบเมื่อมีการเปลี่ยน Dropdown
    $('#filter_year, #filter_month, #filter_status').on('change', function() {
        fetchHistory();
    });

    // โหลดข้อมูลครั้งแรกที่เข้าหน้า
    fetchHistory();
});

// ฟังก์ชันเปิดดูรายละเอียดผ่านป๊อปอัป
function viewJobHistory(id) {
    const modalEl = document.getElementById('historyModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    $('#historyContent').html('<div class="p-5 text-center"><div class="spinner-border text-primary"></div></div>');
    
    $.get('../api/tech_get_history_detail.php?id=' + id, function(html) {
        $('#historyContent').html(html);
        
        // ผูกการทำงาน Fancybox (สำหรับ Gallery ในป๊อปอัปประวัติ)
        Fancybox.bind("[data-fancybox='history-detail-gallery']", {
            parentEl: document.body,
            dragToClose: false
        });
    });
}
</script>

<style>
    .bg-main { background-color: #003366 !important; }
    .badge { font-weight: 500; }

    /* บังคับให้หน้าต่างดูรูปขยายอยู่บนสุด (ทับหน้า Modal) */
    .fancybox__container {
        z-index: 99999 !important;
    }

    /* ปรับ Thumbnail ในป๊อปอัปให้ดูทันสมัย */
    .history-img-thumb {
        width: 100%;
        height: 80px;
        object-fit: cover;
        border-radius: 10px;
        cursor: pointer;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: 0.2s;
    }
    .history-img-thumb:hover {
        transform: scale(1.05);
        border-color: #003366;
    }
        .badge-tech {
        background: #e7f1ff;
        color: #003366;
        border: 1px solid #cfe2ff;
        padding: 5px 12px;
        border-radius: 8px;
        font-weight: 600;
    }
    /* สไตล์ช่อง Modal Details */
    #detailContent p, #historyContent p {
        margin-bottom: 0.5rem;
    }
</style>

<?php require_once '../includes/tech_footer.php'; ?>