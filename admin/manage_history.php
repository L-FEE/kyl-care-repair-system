<?php require_once '../includes/admin_header.php'; 

// กรองข้อมูลเหมือนหน้าอื่นเพื่อให้ค้นหารายการที่ต้องการลบได้แม่นยำ
$selected_year = $_GET['year'] ?? date('Y');
$selected_month = $_GET['month'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$month_names = ['all' => "-- ทุกเดือน --", 1 => "มกราคม", 2 => "กุมภาพันธ์", 3 => "มีนาคม", 4 => "เมษายน", 5 => "พฤษภาคม", 6 => "มิถุนายน", 7 => "กรกฎาคม", 8 => "สิงหาคม", 9 => "กันยายน", 10 => "ตุลาคม", 11 => "พฤศจิกายน", 12 => "ธันวาคม"];

$where = ["YEAR(created_at) = $selected_year"];
if($selected_month !== 'all') $where[] = "MONTH(created_at) = $selected_month";
if($search !== "") {
    $s = $conn->real_escape_string($search);
    $where[] = "(request_code LIKE '%$s%' OR reporter_name LIKE '%$s%')";
}
$where_sql = "WHERE " . implode(' AND ', $where);

$sql = "SELECT id, request_code, reporter_name, device_name, status, created_at FROM repair_requests $where_sql ORDER BY created_at DESC";
$res = $conn->query($sql);
?>

<style>
    .delete-panel { background: #fff; border-radius: 15px; border-left: 5px solid #dc3545; }
    .table-delete thead { background: #f8f9fa; }
    .btn-delete-selected { background-color: #dc3545; color: #fff; border: none; padding: 8px 15px; border-radius: 8px; font-weight: 600; display: none; }
</style>

<div class="mb-4">
    <h3 class="fw-bold mb-1 text-danger"><i class="bi bi-trash3-fill"></i> จัดการลบประวัติงานแจ้งซ่อม</h3>
    <p class="text-muted small">โปรดระมัดระวัง ข้อมูลที่ลบแล้วจะไม่สามารถกู้คืนกลับมาได้</p>
</div>

<!-- 1. เครื่องมือล้างข้อมูลตามช่วงเวลา -->
<div class="card delete-panel p-3 mb-4 shadow-sm">
    <div class="d-flex align-items-center justify-content-between">
        <h6 class="m-0 fw-bold"><i class="bi bi-exclamation-octagon-fill text-danger me-1"></i> ล้างข้อมูลด่วนตามช่วงเวลา</h6>
        <div class="d-flex gap-2">
            <select id="clean_month" class="form-select form-select-sm" style="width: 150px;">
                <?php foreach($month_names as $v => $n) echo "<option value='$v' ".($selected_month == $v ? 'selected' : '').">$n</option>"; ?>
            </select>
            <button class="btn btn-danger btn-sm" onclick="deleteByTimeRange()">ลบข้อมูลเดือนนี้ทั้งหมด</button>
        </div>
    </div>
</div>

<!-- 2. ตัวกรองสำหรับค้นหารายการเพื่อลบทีละรายการ -->
<div class="card p-3 mb-4 shadow-sm border-0 rounded-4 bg-white">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="small fw-bold text-muted">ประจำปี</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php for($y=date('Y'); $y>=2024; $y--) echo "<option value='$y' ".($selected_year==$y?'selected':'').">$y</option>"; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="small fw-bold text-muted">ประจำเดือน</label>
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach($month_names as $v => $n) echo "<option value='$v' ".($selected_month == $v ? 'selected' : '').">$n</option>"; ?>
            </select>
        </div>
        <div class="col-md-4">
            <!-- <label class="small fw-bold text-muted">ค้นหาตามรหัสงาน/ชื่อพนักงาน</label>
            <input type="text" name="search" class="form-control form-control-sm" value="<?= h($search) ?>" placeholder="พิมพ์ข้อมูลค้นหา..."> -->
        </div>
        <div class="col-md-2"></div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">ค้นหา</button>
            <a href="<?= BASE_URL ?>/manage_history.php" class="btn btn-sm btn-light border w-50" title="ล้างการกรอง"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<!-- 3. รายการที่รอการจัดการ -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="m-0 fw-bold">เลือกรายการที่จะลบ (<?= $res->num_rows ?> รายการ)</h6>
        <!-- ปุ่มลบหลายรายการที่เลือก -->
        <button id="btnBulkDelete" class="btn-delete-selected" onclick="deleteSelected()">
            <i class="bi bi-trash3 me-1"></i> ลบรายการที่เลือก (<span id="select-count">0</span>)
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-delete">
            <thead>
                <tr>
                    <th class="ps-4" width="40"><input type="checkbox" id="checkAll" class="form-check-input"></th>
                    <th>รหัสงาน</th>
                    <th>ชื่อผู้แจ้ง</th>
                    <th>อุปกรณ์</th>
                    <th class="text-center">สถานะ</th>
                    <th class="text-end pe-4">ลบทิ้ง</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetch_assoc()): ?>
                <tr>
                    <td class="ps-4"><input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="form-check-input check-item"></td>
                    <td class="fw-bold"><?= $row['request_code'] ?></td>
                    <td><small><?= h($row['reporter_name']) ?></small></td>
                    <td><small><?= h($row['device_name']) ?></small></td>
                    <td class="text-center"><?= getStatusBadge($row['status']) ?></td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSingle(<?= $row['id'] ?>, '<?= $row['request_code'] ?>')" title="ลบงานชิ้นนี้"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; if($res->num_rows == 0) echo "<tr><td colspan='6' class='text-center py-5 text-muted small'>ไม่มีข้อมูลที่จะลบในช่วงเวลาที่เลือก</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// --- ส่วนของระบบ Checkbox ---
$('#checkAll').on('change', function() {
    $('.check-item').prop('checked', this.checked);
    toggleBulkButton();
});

$(document).on('change', '.check-item', function() {
    if (!this.checked) $('#checkAll').prop('checked', false);
    toggleBulkButton();
});

function toggleBulkButton() {
    let checkedCount = $('.check-item:checked').length;
    $('#select-count').text(checkedCount);
    if(checkedCount > 0) $('#btnBulkDelete').fadeIn(); else $('#btnBulkDelete').fadeOut();
}

// 1. ลบรายการเดียว
function deleteSingle(id, code) {
    confirmAndAction('ลบรายการแจ้งซ่อม ' + code + '?', { action: 'delete_single', id: id });
}

// 2. ลบเฉพาะรายการที่เลือก (Batch)
function deleteSelected() {
    let ids = [];
    $('.check-item:checked').each(function() { ids.push($(this).val()); });
    confirmAndAction('ลบรายการที่เลือกทั้งหมด ' + ids.length + ' รายการ?', { action: 'delete_selected', ids: ids });
}

// 3. ลบล้างประวัติเดือนนั้นๆ (Month cleanup)
function deleteByTimeRange() {
    const month = $('#clean_month').val();
    const year = <?= $selected_year ?>;
    const monthName = $('#clean_month option:selected').text();
    
    if(month === 'all') {
        confirmAndAction('ล้างข้อมูลงานแจ้งซ่อมทั้งหมดของปี ' + year + '?', { action: 'delete_range', month: 'all', year: year }, 'warning');
    } else {
        confirmAndAction('ล้างข้อมูลงานแจ้งซ่อมของ ' + monthName + ' ' + year + ' ทั้งหมด?', { action: 'delete_range', month: month, year: year }, 'warning');
    }
}

// ฟังก์ชันกลางสำหรับการยืนยันและเรียก API
function confirmAndAction(msg, postData, icon = 'error') {
    Swal.fire({
        title: 'ยืนยันดำเนินการ?',
        text: msg,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'กำลังประมวลผล...', didOpen: () => Swal.showLoading() });
            $.post('../api/admin_delete_actions.php', postData, function(res) {
                if(res.success) {
                    Swal.fire('ลบแล้ว!', 'ข้อมูลที่เลือกถูกนำออกจากระบบแล้ว ' + (res.count ? res.count + ' รายการ' : ''), 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', res.error, 'error');
                }
            }, 'json');
        }
    });
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>