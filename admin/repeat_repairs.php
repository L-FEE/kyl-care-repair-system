<?php require_once '../includes/admin_header.php'; 

// 1. รับค่าตัวกรอง
$device_filter = $_GET['device'] ?? 'all';
$range_filter = $_GET['range'] ?? '2'; // เปลี่ยนจาก min_count เป็น range
$current_tab = $_GET['tab'] ?? 'active';
?>

<style>
    .nav-pills .nav-link { border-radius: 10px; padding: 10px 25px; color: #4a5568; font-weight: 600; }
    .nav-pills .nav-link.active { background-color: #003366 !important; color: #fff; }
</style>

<div class="mb-4">
    <h3 class="fw-bold mb-1"><i class="bi bi-arrow-repeat text-primary me-2"></i>รายการอุปกรณ์แจ้งซ่อมซ้ำ</h3>
</div>

<!-- Tabs -->
<ul class="nav nav-pills mb-4 bg-white p-2 shadow-sm rounded-4" id="repairTabs">
    <li class="nav-item">
        <a class="nav-link <?= $current_tab == 'active' ? 'active' : '' ?>" href="?tab=active&device=<?= $device_filter ?>&range=<?= $range_filter ?>">
            <i class="bi bi-tools me-1"></i> ยังอยู่ในระบบ (ซ่อมได้)
        </a>
    </li>
    <li class="nav-item ms-2">
        <a class="nav-link <?= $current_tab == 'failed' ? 'active' : '' ?>" href="?tab=failed&device=<?= $device_filter ?>&range=<?= $range_filter ?>">
            <i class="bi bi-trash3 me-1"></i> เสีย / ซ่อมไม่ได้
        </a>
    </li>
</ul>

<!-- ตัวกรองแถวเดียว -->
<div class="card p-3 mb-4 shadow-sm border-0 rounded-4">
    <form method="GET" class="row gx-2 gy-2 align-items-end" id="filterForm">
        <input type="hidden" name="tab" value="<?= h($current_tab) ?>">
        
        <div class="col-md-3">
            <label class="small fw-bold text-muted">ระบุชื่ออุปกรณ์</label>
            <select name="device" class="form-select form-select-sm shadow-none" onchange="this.form.submit()">
                <option value="all">-- ทุกรายการ --</option>
                <?php $d_res = $conn->query("SELECT DISTINCT device_name FROM devices ORDER BY device_name ASC");
                while($d = $d_res->fetch_assoc()) echo "<option value='{$d['device_name']}' ".($device_filter==$d['device_name']?'selected':'').">{$d['device_name']}</option>"; ?>
            </select>
        </div>

        <!-- แก้ไขส่วนช่วงการกรองจำนวนครั้ง -->
        <div class="col-md-3">
            <label class="small fw-bold text-muted">จำนวนครั้งที่แจ้งซ่อมซ้ำ</label>
            <select name="range" class="form-select form-select-sm shadow-none" onchange="this.form.submit()">
                <option value="all" <?= $range_filter == 'all' ? 'selected' : '' ?>>รายการทั้งหมด</option>
                <option value="2" <?= $range_filter == '2' ? 'selected' : '' ?>>แจ้งซ้ำ 2 ครั้ง</option>
                <option value="3-4" <?= $range_filter == '3-4' ? 'selected' : '' ?>>แจ้งซ้ำ 3 - 4 ครั้ง</option>
                <option value="5+" <?= $range_filter == '5+' ? 'selected' : '' ?>>แจ้งซ้ำตั้งแต่ 5 ครั้งขึ้นไป</option>
            </select>
        </div>

        <div class="col-md-3"></div> 
        
        <div class="col-md-auto ms-auto d-flex gap-2">
            <!-- ปุ่มรีเซ็ตไอคอน -->
            <a href="<?= BASE_URL ?>/repeat_repairs.php?tab=<?= $current_tab ?>" class="btn btn-sm btn-outline-secondary px-3 rounded-3" title="ล้างค่า" style="height: 31px; display: flex; align-items: center;">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
            <!-- ปุ่ม EXCEL ปรับขนาดให้เป็นมาตรฐาน -->
            <button type="button" onclick="exportExcelRepeat()" class="btn btn-sm btn-success fw-bold rounded-3 shadow-sm px-4" style="min-width: 140px; height: 31px;">
                <i class="bi bi-file-earmark-excel me-1"></i> EXCEL
            </button>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-main text-white" style="background-color: #003366 !important;">
                <tr><th class="ps-4">SERIAL NUMBER</th><th>ชื่ออุปกรณ์</th><th class="text-center">ประวัติซ่อม</th><th class="text-center">สถานะ</th><th class="text-center">รายละเอียด</th></tr>
            </thead>
            <tbody>
                <?php 
                $dev_where = ($device_filter !== 'all') ? "AND r.device_name = '$device_filter'" : "";
                
                // แปลง range เป็น HAVING SQL
                if($range_filter === 'all') {
                    $having = "HAVING total >= 2";
                } elseif($range_filter === '2') {
                    $having = "HAVING total = 2";
                } elseif($range_filter === '3-4') {
                    $having = "HAVING total BETWEEN 3 AND 4";
                } else {
                    $having = "HAVING total >= 5";
                }

                $sql = "SELECT serial_number, device_name, category_name, COUNT(*) as total, 
                        (SELECT status FROM repair_requests WHERE serial_number = r.serial_number ORDER BY created_at DESC LIMIT 1) as cur_status 
                        FROM repair_requests r 
                        WHERE serial_number != '' AND serial_number != 'ไม่มีหมายเลขซีเรียล' $dev_where 
                        GROUP BY serial_number $having ORDER BY total DESC";
                $res = $conn->query($sql);
                
                $count = 0;
                while($row = $res->fetch_assoc()): 
                    $is_f = ($row['cur_status'] == 'cannot_repair');
                    if(($current_tab == 'active' && !$is_f) || ($current_tab == 'failed' && $is_f)):
                        $count++;
                ?>
                <tr>
                    <td class="ps-4"><span class="badge bg-light text-dark border p-2">S/N: <?= h($row['serial_number']) ?></span></td>
                    <td><div class="fw-bold"><?= h($row['device_name']) ?></div><small class="text-muted"><?= h($row['category_name']) ?></small></td>
                    <td class="text-center"><span class="badge bg-danger rounded-pill px-3 shadow-sm"><?= $row['total'] ?> ครั้ง</span></td>
                    <td class="text-center"><?= getStatusBadge($row['cur_status']) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary px-3 rounded-pill shadow-sm" onclick="showTimeline('<?= h($row['serial_number']) ?>')">ดูไทม์ไลน์</button>
                    </td>
                </tr>
                <?php endif; endwhile; 
                if($count == 0) echo "<tr><td colspan='5' class='text-center py-5 text-muted'>ไม่มีรายการในหมวดนี้ที่ตรงตามจำนวนที่ระบุ</td></tr>";
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal [Timeline คงเดิมตามโค้ดแอดมินปกติ] -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-main text-white py-3">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-clock-history me-2"></i>ประวัติการแจ้งซ่อมของ S/N</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div id="historyContent" class="modal-body p-0"></div>
                <!-- <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div> -->
            </div>
        </div>
    </div>

<script>
function showTimeline(sn) {
    new bootstrap.Modal(document.getElementById('historyModal')).show();
    $('#historyContent').html('<div class="p-5 text-center"><div class="spinner-border text-primary"></div></div>');
    $.get('../api/admin_get_sn_history.php?sn=' + encodeURIComponent(sn), h => $('#historyContent').html(h));
}

function exportExcelRepeat() {
    const params = new URLSearchParams(window.location.search).toString();
    window.location.href = '../api/export_excel_repeat.php?' + params;
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>