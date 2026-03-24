<?php require_once '../includes/admin_header.php'; 

/*
|------------------------------------------------------
| File: admin/all_repairs.php
| Description: รายการแจ้งซ่อมทั้งหมด (ปรับขนาด Modal ให้เล็กลงเท่าของเดิม)
|------------------------------------------------------
*/

// 1. รับค่าตัวกรอง
$device_filter = $_GET['device'] ?? 'all'; 
$selected_year = $_GET['year'] ?? date('Y');
$selected_month = $_GET['month'] ?? date('n');
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$month_names = ['all' => "-- ทุกเดือน --", 1 => "มกราคม", 2 => "กุมภาพันธ์", 3 => "มีนาคม", 4 => "เมษายน", 5 => "พฤษภาคม", 6 => "มิถุนายน", 7 => "กรกฎาคม", 8 => "สิงหาคม", 9 => "กันยายน", 10 => "ตุลาคม", 11 => "พฤศจิกายน", 12 => "ธันวาคม"];

// 2. เงื่อนไข Query
$where = ["YEAR(r.created_at) = $selected_year"];
if($device_filter !== 'all') $where[] = "r.device_name = '" . $conn->real_escape_string($device_filter) . "'";
if($selected_month !== 'all') $where[] = "MONTH(r.created_at) = $selected_month";
if($status !== 'all') $where[] = "r.status = '$status'";
if($search !== "") {
    $s = $conn->real_escape_string($search);
    $where[] = "(r.request_code LIKE '%$s%' OR r.reporter_name LIKE '%$s%' OR r.serial_number LIKE '%$s%')";
}
$where_sql = "WHERE " . implode(' AND ', $where);

// 3. Query ข้อมูล
$sql = "SELECT r.*, u.full_name as tech_name,
        (SELECT COUNT(*) FROM repair_requests WHERE serial_number = r.serial_number AND serial_number IS NOT NULL AND serial_number != 'ไม่มีหมายเลขซีเรียล' AND serial_number != '') as repeat_count
        FROM repair_requests r LEFT JOIN users u ON r.technician_id = u.id $where_sql ORDER BY r.created_at DESC";
$res = $conn->query($sql);
?>

<style>
    .x-small { font-size: 0.7rem; }
    .bg-main { background-color: #003366 !important; }
    .fancybox__container { z-index: 100000 !important; }
</style>

<div class="mb-4">
    <h3 class="fw-bold mb-1"><i class="bi bi-clipboard-data-fill text-primary me-2"></i>รายการแจ้งซ่อมทั้งหมด</h3>
</div>

<!-- ตัวกรอง -->
<div class="card p-3 mb-4 shadow-sm border-0 rounded-4">
    <form method="GET" class="row gx-2 gy-2 align-items-end">
        <div class="col-md-2">
            <label class="small fw-bold text-muted">เลือกอุปกรณ์</label>
            <select name="device" class="form-select form-select-sm shadow-none" onchange="this.form.submit()">
                <option value="all">-- ทั้งหมด --</option>
                <?php 
                $d_res = $conn->query("SELECT DISTINCT device_name FROM devices ORDER BY device_name ASC");
                while($d = $d_res->fetch_assoc()) echo "<option value='{$d['device_name']}' ".($device_filter == $d['device_name'] ? 'selected' : '').">{$d['device_name']}</option>";
                ?>
            </select>
        </div>
        <div class="col-md-1">
            <label class="small fw-bold text-muted">ปี</label>
            <select name="year" class="form-select form-select-sm shadow-none" onchange="this.form.submit()">
                <?php for($y=date('Y'); $y>=2025; $y--) echo "<option value='$y' ".($selected_year==$y?'selected':'').">$y</option>"; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="small fw-bold text-muted">เดือน</label>
            <select name="month" class="form-select form-select-sm shadow-none" onchange="this.form.submit()">
                <?php foreach($month_names as $v => $n) echo "<option value='$v' ".($selected_month==$v?'selected':'').">$n</option>"; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="small fw-bold text-muted">สถานะ</label>
            <select name="status" class="form-select form-select-sm shadow-none" onchange="this.form.submit()">
                <option value="all">-- ทุกสถานะ --</option>
                <option value="pending" <?= $status=='pending'?'selected':'' ?>>รอดำเนินการ</option>
                <option value="accepted" <?= $status=='accepted'?'selected':'' ?>>รับงานแล้ว</option>
                <option value="in_progress" <?= $status=='in_progress'?'selected':'' ?>>กำลังซ่อม</option>
                <option value="waiting_parts" <?= $status=='waiting_parts'?'selected':'' ?>>รออะไหล่</option>
                <option value="completed" <?= $status=='completed'?'selected':'' ?>>ซ่อมเสร็จสิ้น</option>
                <option value="cannot_repair" <?= $status=='cannot_repair'?'selected':'' ?>>ซ่อมไม่ได้</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="small fw-bold text-muted">ค้นหา (รหัสงานหรือชื่อผู้แจ้ง)</label>
            <input type="text" name="search" class="form-control form-control-sm shadow-none" placeholder="ค้นหา..." value="<?= h($search) ?>">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold rounded-3 shadow-sm"><i class="bi bi-search"></i> ค้นหา</button>
            <a href="<?= BASE_URL ?>/all_repairs.php" class="btn btn-sm btn-outline-secondary px-3 rounded-3" title="ล้างค่า"><i class="bi bi-arrow-counterclockwise"></i></a>
            <button type="button" onclick="exportToExcel()" class="btn btn-sm btn-success w-100 fw-bold rounded-3 shadow-sm"><i class="bi bi-file-earmark-excel"></i> EXCEL</button>
        </div>
    </form>
</div>

<!-- ตารางแสดงรายการ -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">รหัสงาน</th>
                    <th>ผู้แจ้ง</th>
                    <th>อุปกรณ์</th>
                    <th class="text-center">สถานะ</th>
                    <th>ช่างผู้รับผิดชอบ</th>
                    <th class="text-center">รายะเอียด</th></tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetch_assoc()): ?>
                <tr>
                    <td class="ps-4"><strong><?= $row['request_code'] ?></strong><?php if($row['repeat_count']>1) echo "<br><span class='badge bg-danger-subtle text-danger x-small' style='font-size:0.6rem;'>ซ่อมซ้ำ {$row['repeat_count']} ครั้ง</span>"; ?></td>
                    <td><small><?= h($row['reporter_name']) ?></small></td>
                    <td><div class="text-primary fw-bold small text-truncate" style="max-width: 140px;"><?= h($row['device_name']) ?></div><small class="text-muted"><?= h($row['serial_number'] ?: 'ไม่มี S/N') ?></small></td>
                    <td class="text-center"><?= getStatusBadge($row['status']) ?></td>
                    <td><small><?= h($row['tech_name'] ?: '-') ?></small></td>
                    <td class="text-center"><button class="btn btn-sm btn-outline-primary rounded-3 px-3 shadow-sm" onclick="viewDetails(<?= $row['id'] ?>)"><i class="bi bi-search">รายละเอียด</i></button></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal รายละเอียด (ปรับขนาดเอา modal-lg ออก เพื่อให้เล็กลง) -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-main text-white py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-info-circle me-2"></i>รายละเอียดงานซ่อม</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div id="detailContent" class="modal-body p-0">
                <!-- ข้อมูลจะโหลดผ่าน AJAX -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>

<script>
function viewDetails(id) {
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
    $('#detailContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">กำลังดึงข้อมูล...</p></div>');

    $.get('../api/admin_get_detail.php?id=' + id, function(html) {
        $('#detailContent').html(html);
        if (typeof Fancybox !== 'undefined') {
            Fancybox.bind("[data-fancybox='admin-gallery']", {
                parentEl: document.body,
                autoFocus: false,
                placeFocusBack: false
            });
        }
    });
}

function exportToExcel() {
    const params = new URLSearchParams(window.location.search).toString();
    window.location.href = '../api/export_excel_all.php?' + params;
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>