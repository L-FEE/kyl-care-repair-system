<?php require_once '../includes/admin_header.php'; ?>

<style>
    .nav-pills .nav-link { border-radius: 12px; padding: 12px 25px; color: #4a5568; font-weight: 600; transition: 0.3s; }
    .nav-pills .nav-link.active { background-color: #003366 !important; box-shadow: 0 4px 15px rgba(0,51,102,0.25); }
    
    .table-mon { border-radius: 15px; overflow: hidden; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .table-mon thead { background-color: #f8fafc; border-bottom: 2px solid #edf2f7; }
    .table-mon thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; padding: 15px; }
    
    .avatar-box { width: 42px; height: 42px; border-radius: 12px; object-fit: cover; border: 1px solid #e2e8f0; }
    .status-indicator { font-size: 0.85rem; font-weight: 700; padding: 5px 12px; border-radius: 8px; }
</style>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="fw-bold mb-1" style="color: #003366;"><i class="bi bi-person-vcard-fill text-primary"></i> ติดตามงาน</h3>
    </div>
</div>

<!-- ส่วน Tabs -->
<ul class="nav nav-pills mb-4 bg-white p-2 shadow-sm rounded-4" id="pills-tab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" id="pills-tech-tab" data-bs-toggle="pill" data-bs-target="#pills-tech" type="button" role="tab">
            <i class="bi bi-tools me-2"></i> เจ้าหน้าที่ (ช่าง)
        </button>
    </li>
    <li class="nav-item ms-2" role="presentation">
        <button class="nav-link fw-bold" id="pills-reporter-tab" data-bs-toggle="pill" data-bs-target="#pills-reporter" type="button" role="tab">
            <i class="bi bi-person-circle me-2"></i> พนักงาน (ผู้แจ้ง)
        </button>
    </li>
</ul>

<div class="tab-content" id="pills-tabContent">
    
    <!-- แท็บ 1: ช่างเทคนิค (Active Only) -->
    <div class="tab-pane fade show active" id="pills-tech" role="tabpanel">
        <div class="card table-mon border-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">รายชื่อช่าง</th>
                        <th class="text-center">งานที่รับผิดชอบ (Active)</th>
                        <th class="text-center">สถานะการดำเนินงาน</th>
                        <th class="text-end pe-4">รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT u.id, u.full_name, u.profile_image, 
                            COUNT(r.id) as total_active,
                            SUM(CASE WHEN r.status = 'accepted' THEN 1 ELSE 0 END) as accep,
                            SUM(CASE WHEN r.status = 'in_progress' THEN 1 ELSE 0 END) as in_prog,
                            SUM(CASE WHEN r.status = 'waiting_parts' THEN 1 ELSE 0 END) as w_parts
                            FROM users u 
                            LEFT JOIN repair_requests r ON u.id = r.technician_id AND r.status IN ('accepted', 'in_progress', 'waiting_parts')
                            WHERE u.role = 'technician' AND u.status = 'active'
                            GROUP BY u.id ORDER BY total_active DESC";
                    $res = $conn->query($sql);
                    while($row = $res->fetch_assoc()):
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center py-1">
                                <img src="../uploads/profiles/<?= $row['profile_image'] ?>" class="avatar-box me-3 shadow-sm">
                                <div><strong class="text-dark"><?= h($row['full_name']) ?></strong></div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill bg-primary px-3 shadow-sm"><?= $row['total_active'] ?> รายการ</span>
                        </td>
                        <td class="text-center">
                            <span style="color: #03630b;" class="fw-bold small"><i class="bi bi-check-circle"></i> รับงานแล้ว: <?= $row['accep'] ?></span> &nbsp;
                            <span style="color: #003366;" class="fw-bold small"><i class="bi bi-play-circle"></i> กำลังซ่อม: <?= $row['in_prog'] ?></span> &nbsp;
                            <span style="color: #ff7300;" class="fw-bold small"><i class="bi bi-hourglass-split"></i> รออะไหล่: <?= $row['w_parts'] ?></span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary shadow-sm rounded-pill px-4" onclick="viewMonitorDetail(<?= $row['id'] ?>, '<?= h($row['full_name']) ?>', 'tech')">
                                ตรวจสอบงาน
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- แท็บ 2: พนักงานผู้แจ้ง (Active Only - Updated Layout) -->
    <div class="tab-pane fade" id="pills-reporter" role="tabpanel">
        <div class="card table-mon border-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ผู้แจ้ง</th>
                        <th class="text-center">รายการที่กำลังดำเนินการ</th>
                        <th class="text-center">สถานะการดำเนินการงาน</th>
                        <th class="text-end pe-4"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // กรองเฉพาะพนักงานที่เป็น Active และคำนวณแยกตามรายพนักงาน
                    $sql = "SELECT p.id, p.full_name,
                            SUM(CASE WHEN r.status IN ('pending', 'accepted', 'in_progress', 'waiting_parts') THEN 1 ELSE 0 END) as total_pending,
                            SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as raw_pending,
                            SUM(CASE WHEN r.status = 'accepted' THEN 1 ELSE 0 END) as raw_accepted,
                            SUM(CASE WHEN r.status = 'in_progress' THEN 1 ELSE 0 END) as raw_prog,
                            SUM(CASE WHEN r.status = 'waiting_parts' THEN 1 ELSE 0 END) as raw_wait
                            FROM reporters p 
                            LEFT JOIN repair_requests r ON p.full_name = r.reporter_name
                            WHERE p.status = 'active'
                            GROUP BY p.id
                            ORDER BY total_pending DESC, p.full_name ASC";
                    $res = $conn->query($sql);
                    while($row = $res->fetch_assoc()):
                        // ปรับจำนวนครั้งรวมเพื่อไม่ให้นับรวม status งานที่เสร็จแล้วหรือยกเลิกแล้ว
                    ?>
                    <tr>
                        <td class="ps-4 py-3">
                            <div><strong class="text-dark"><?= h($row['full_name']) ?></strong></div>
                        </td>
                        <td class="text-center">
                            <?php if($row['total_pending'] > 0): ?>
                                <span class="badge rounded-pill bg-primary px-3 shadow-sm"><?= $row['total_pending'] ?> รายการ</span>
                            <?php else: ?>
                                <span class="badge rounded-pill bg-light text-muted border px-3">0 รายการ</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if($row['total_pending'] > 0): ?>
                                <span style="color: #6c757d;" class="fw-bold small" title="พนักงานแจ้งไว้แล้ว รอช่างเข้ายืนยัน"><i class="bi bi-clock-history"></i> รอรับงาน: <?= $row['raw_pending'] ?></span> &nbsp;
                                <span style="color: #03630b;" class="fw-bold small"><i class="bi bi-person-check"></i> รับงานแล้ว: <?= $row['raw_accepted'] + $row['raw_prog'] ?></span> &nbsp;
                                <span style="color: #ff7300;" class="fw-bold small"><i class="bi bi-hourglass-split"></i> รออะไหล่: <?= $row['raw_wait'] ?></span>
                            <?php else: ?>
                                <span class="small text-muted italic">ไม่มีรายการแจ้งซ่อม</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary shadow-sm rounded-pill px-4" onclick="viewMonitorDetail('<?= h($row['full_name']) ?>', '<?= h($row['full_name']) ?>', 'reporter')">
                                ประวัติการแจ้ง
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal รายละเอียด สากล [คงเดิม] -->
<div class="modal fade" id="monitorDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-main text-white py-3">
                <h5 class="modal-title fw-bold" id="monitorTitle">รายละเอียดงาน</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div id="monitorContentArea" class="modal-body p-0">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewMonitorDetail(id, name, mode) {
    const modal = new bootstrap.Modal(document.getElementById('monitorDetailModal'));
    modal.show();
    
    $('#monitorTitle').text(((mode === 'tech') ? 'งานที่รับผิดชอบ: ' : 'รายการซ่อมของ: ') + name);
    $('#monitorContentArea').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted small">กำลังเตรียมข้อมูล...</p></div>');
    
    $.get('../api/admin_get_user_monitoring_list.php', { id: id, mode: mode }, function(html) {
        $('#monitorContentArea').html(html);
    });
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>