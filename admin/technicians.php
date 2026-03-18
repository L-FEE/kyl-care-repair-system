<?php 
/*
|------------------------------------------------------
| File: admin/technicians.php
| Description: จัดการผู้ใช้แบบจดจำตำแหน่ง (ไม่มีรหัสผ่านแอดมิน)
|------------------------------------------------------
*/
require_once '../includes/admin_header.php'; 
?>

<style>
    /* สไตล์ปุ่มแอดมินแบบสลับสีตามสถานะ */
    .btn-admin-custom { 
        background-color: #fff !important; 
        color: #dc3545 !important; 
        border: 2px solid #dc3545 !important; 
        padding: 10px 20px !important;
        transition: 0.3s;
    }
    /* เมื่อแท็บแอดมินถูกเลือก */
    .btn-admin-custom.active { 
        background-color: #dc3545 !important; 
        color: #fcfcfc !important; 
        border: 2px solid #6800009c !important; 
    }
    
    /* ไอคอนปุ่มจัดการ */
    .btn-action-lg {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.2rem; transition: 0.2s; border: none; margin: 0 2px;
    }
    .btn-edit { background-color: #e7f1ff; color: #007bff; }
    .btn-reset { background-color: #fff3cd; color: #856404; }
    .btn-lock { background-color: #f8d7da; color: #dc3545; }
    .btn-unlock { background-color: #d1e7dd; color: #198754; }
    .btn-action-lg:hover { filter: brightness(0.9); transform: scale(1.05); }

    .suspended { opacity: 0.4; filter: grayscale(1); background-color: #f8f9fa; }
    .nav-pills .nav-link:not(.btn-admin-custom) { border-radius: 10px; padding: 12px 25px; color: #4a5568; font-weight: 600; }
    .nav-pills .nav-link.active:not(.btn-admin-custom) { background-color: #003366; color: #fff; }
</style>

<div class="mb-4">
    <h3 class="fw-bold mb-0"><i class="bi bi-people-fill text-primary me-2"></i>จัดการข้อมูลผู้ใช้งาน</h3>
</div>

<!-- ส่วนแถบเมนู (Tabs) -->
<ul class="nav nav-pills mb-4 bg-white p-2 shadow-sm rounded-4" id="userTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tech-tab" data-bs-toggle="tab" data-bs-target="#tab-tech" type="button" role="tab">
            <i class="bi bi-person-badge me-1"></i> เจ้าหน้าที่ช่าง
        </button>
    </li>
    <li class="nav-item ms-2" role="presentation">
        <button class="nav-link" id="reporter-tab" data-bs-toggle="tab" data-bs-target="#tab-reporter" type="button" role="tab">
            <i class="bi bi-person-lines-fill me-1"></i> ผู้แจ้งซ่อม
        </button>
    </li>
    <li class="nav-item ms-auto" role="presentation">
        <button class="nav-link btn-admin-custom" id="admin-tab" data-bs-toggle="tab" data-bs-target="#tab-admin" type="button" role="tab">
            <i class="bi bi-shield-lock-fill me-1"></i> ผู้ดูแลระบบ
        </button>
    </li>
</ul>

<div class="tab-content" id="userTabContent">
    
    <!-- แท็บ 1: ช่างซ่อม -->
    <div class="tab-pane fade show active" id="tab-tech" role="tabpanel">
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-primary px-4 shadow-sm" onclick="openTechModal()"><i class="bi bi-plus-lg"></i> เพิ่ม</button>
        </div>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th class="ps-4">ชื่อ-สกุล</th><th>อีเมล/โทรศัพท์</th><th class="text-center">สถานะ</th><th class="text-end pe-4">จัดการ</th></tr>
                </thead>
                <tbody>
                    <?php
                    $res = $conn->query("SELECT * FROM users WHERE role='technician' ORDER BY full_name ASC");
                    while($row = $res->fetch_assoc()):
                        $is_active = ($row['status'] == 'active');
                    ?>
                    <tr class="<?= !$is_active ? 'suspended' : '' ?>">
                        <td class="ps-4"><strong><?= h($row['full_name']) ?></strong></td>
                        <td><?= h($row['email']) ?><br><small class="text-muted"><?= h($row['phone']) ?></small></td>
                        <td class="text-center"><?= $is_active ? '<span class="badge bg-success">ปกติ</span>' : '<span class="badge bg-danger">ถูกระงับ</span>' ?></td>
                        <td class="text-end pe-4">
                            <?php if($is_active): ?>
                                <button class="btn-action-lg btn-edit" onclick="openTechEditModal(<?= htmlspecialchars(json_encode($row)) ?>)" title="แก้ไขข้อมูล"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn-action-lg btn-reset" onclick="handleResetPass(<?= $row['id'] ?>, '<?= h($row['full_name']) ?>')" title="รีเซ็ตรหัสผ่าน"><i class="bi bi-key"></i></button>
                                <button class="btn-action-lg btn-lock" onclick="handleToggleStatus(<?= $row['id'] ?>, 'active', '<?= h($row['full_name']) ?>', 'user')" title="ระงับใช้งาน"><i class="bi bi-unlock-fill"></i></button>
                            <?php else: ?>
                                <button class="btn-action-lg btn-unlock" onclick="handleToggleStatus(<?= $row['id'] ?>, 'inactive', '<?= h($row['full_name']) ?>', 'user')" title="ปลดล็อค"><i class="bi bi-lock-fill"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- แท็บ 2: ผู้แจ้งซ่อม -->
    <div class="tab-pane fade" id="tab-reporter" role="tabpanel">
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-primary px-4 shadow-sm" onclick="openReporterModal('add')"><i class="bi bi-plus-lg"></i> เพิ่ม</button>
        </div>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th class="ps-4">ชื่อ-สกุล</th><th>ข้อมูลติดต่อ</th><th class="text-center">สถานะ</th><th class="text-end pe-4">จัดการ</th></tr>
                </thead>
                <tbody>
                    <?php
                    $res = $conn->query("SELECT * FROM reporters ORDER BY full_name ASC");
                    while($row = $res->fetch_assoc()):
                        $is_active = ($row['status'] == 'active');
                    ?>
                    <tr class="<?= !$is_active ? 'suspended' : '' ?>">
                        <td class="ps-4"><strong><?= h($row['full_name']) ?></strong></td>
                        <td><?= h($row['phone']) ?> <span class="badge bg-light text-dark border ms-1"><?= h($row['office_phone']) ?></span></td>
                        <td class="text-center"><?= $is_active ? '<span class="badge bg-success">ปกติ</span>' : '<span class="badge bg-danger">ถูกระงับ</span>' ?></td>
                        <td class="text-end pe-4">
                            <?php if($is_active): ?>
                                <button class="btn-action-lg btn-edit" onclick="openReporterModal('edit', <?= htmlspecialchars(json_encode($row)) ?>)" title="แก้ไข"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn-action-lg btn-lock" onclick="handleToggleStatus(<?= $row['id'] ?>, 'active', '<?= h($row['full_name']) ?>', 'reporter')" title="ระงับรายชื่อ"><i class="bi bi-unlock-fill"></i></button>
                            <?php else: ?>
                                <button class="btn-action-lg btn-unlock" onclick="handleToggleStatus(<?= $row['id'] ?>, 'inactive', '<?= h($row['full_name']) ?>', 'reporter')" title="ปลดล็อค"><i class="bi bi-lock-fill"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- แท็บ 3: ผู้ดูแลระบบ -->
    <div class="tab-pane fade" id="tab-admin" role="tabpanel">
        <div class="d-flex justify-content-between mb-3 align-items-center">
            <h5 class="text-danger fw-bold mb-0"><i class="bi bi-shield-check"></i> ข้อมูลผู้ดูแลระบบ</h5>
            <button class="btn btn-danger rounded-3 px-4 shadow-sm" onclick="openAdminModal()"><i class="bi bi-plus-lg"></i> เพิ่ม</button>
        </div>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-top border-4 border-danger">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-danger"><tr><th class="ps-4">ชื่อ-สกุล</th><th>อีเมล</th><th class="text-center">สถานะ</th><th class="text-end pe-4">จัดการ</th></tr></thead>
                <tbody>
                    <?php
                    $my_id = $_SESSION['user_id'];
                    $res = $conn->query("SELECT * FROM users WHERE role='admin' ORDER BY (id = $my_id) DESC, full_name ASC");
                    while($row = $res->fetch_assoc()):
                        $is_active = ($row['status'] == 'active');
                    ?>
                    <tr class="<?= !$is_active ? 'suspended' : '' ?>">
                        <td class="ps-4"><strong><?= h($row['full_name']) ?></strong> <?= ($row['id'] == $my_id) ? '<span class="badge bg-danger ms-1">บัญชีคุณ</span>' : '' ?></td>
                        <td><?= h($row['email']) ?></td>
                        <td class="text-center"><?= $is_active ? '<span class="badge bg-success">ปกติ</span>' : '<span class="badge bg-danger">ถูกระงับ</span>' ?></td>
                        <td class="text-end pe-4">
                            <?php if($row['id'] != $my_id): ?>
                                <?php if($is_active): ?>
                                    <button class="btn-action-lg btn-reset" onclick="handleResetPass(<?= $row['id'] ?>, '<?= h($row['full_name']) ?>')" title="รีเซ็ต"><i class="bi bi-key"></i></button>
                                    <button class="btn-action-lg btn-lock" onclick="handleToggleStatus(<?= $row['id'] ?>, 'active', '<?= h($row['full_name']) ?>', 'user')" title="ระงับการใช้งาน"><i class="bi bi-unlock-fill"></i></button>
                                <?php else: ?>
                                    <button class="btn-action-lg btn-unlock" onclick="handleToggleStatus(<?= $row['id'] ?>, 'inactive', '<?= h($row['full_name']) ?>', 'user')" title="ปลดล็อค"><i class="bi bi-lock-fill"></i></button>
                                <?php endif; ?>
                            <?php else: ?>
                                <small class="text-muted">จัดการที่ข้อมูลส่วนตัว</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal สำหรับพนักงาน/ผู้แจ้งซ่อม -->
<div class="modal fade" id="reporterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-main text-white py-3 rounded-top-4">
                <h5 class="modal-title fw-bold" id="rep-modal-title">
                    <i class="bi bi-person-lines-fill me-2"></i>ข้อมูลพนักงาน
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reporterForm">
                <input type="hidden" name="action" id="rep-action">
                <input type="hidden" name="id" id="rep-id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted mb-1">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="rep-name" class="form-control rounded-3 border-primary-subtle" placeholder="ชื่อ-นามสกุล" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-7 mb-3">
                            <label class="form-label small fw-bold text-muted mb-1">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="rep-phone" class="form-control rounded-3 border-primary-subtle" maxlength="10" placeholder="06, 08, 09XXXXXXXX" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label small fw-bold text-muted mb-1">เบอร์ที่ทำงาน <span class="text-danger">*</span></label>
                            <input type="text" name="office_phone" id="rep-office" class="form-control rounded-3 border-primary-subtle" maxlength="4" placeholder="เช่น 1111" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-muted mb-1">อีเมล (ไม่บังคับ)</label>
                        <input type="email" name="email" id="rep-email" class="form-control rounded-3 border-primary-subtle" placeholder="example@mail.com">
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">ยืนยัน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal สำหรับเจ้าหน้าที่ช่าง -->
<div class="modal fade" id="techModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-main text-white py-3 rounded-top-4">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-person-badge-fill me-2"></i>ลงทะเบียนช่างซ่อม
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="techForm">
                <input type="hidden" name="action" value="add_tech">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted mb-1">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control rounded-3 border-primary-subtle" placeholder="ชื่อ-นามสกุล" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted mb-1">อีเมล <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control rounded-3 border-primary-subtle" placeholder="technicians@gmail.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted mb-1">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control rounded-3 border-primary-subtle" maxlength="10" placeholder="06, 08, 09XXXXXXXX" required>
                    </div>
                    <div class="alert alert-primary py-2 px-3 mb-0 d-flex align-items-center rounded-3">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <span style="font-size: 0.85rem;">รหัสผ่านเริ่มต้นสำหรับการเข้าใช้งานคือ: <strong>password@123</strong></span>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">ยืนยัน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- [เพิ่ม Modal สำหรับแก้ไขช่าง] -->
<div class="modal fade" id="editTechModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-main text-white py-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลเจ้าหน้าที่ช่าง</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editTechForm">
                <input type="hidden" name="action" value="edit_tech">
                <input type="hidden" name="id" id="edit-tech-id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted mb-1">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="edit-tech-name" class="form-control rounded-3 border-primary-subtle" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted mb-1">อีเมลเข้าใช้งาน <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="edit-tech-email" class="form-control rounded-3 border-primary-subtle" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted mb-1">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                        <input type="text" name="phone" id="edit-tech-phone" class="form-control rounded-3 border-primary-subtle" maxlength="10" required>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">ยืนยัน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // ระบบจำตำแหน่งแท็บหลัง Reload
    let activeTab = localStorage.getItem('activeUserTab');
    if (activeTab) {
        const tabEl = document.querySelector('#' + activeTab);
        if (tabEl) {
            const tab = new bootstrap.Tab(tabEl);
            tab.show();
        }
    }

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        localStorage.setItem('activeUserTab', e.target.id);
    });
});

// ฟังก์ชันระงับ / ปลดล็อค สไตล์คำถาม
function handleToggleStatus(id, current, name, type) {
    const nextStatus = current === 'active' ? 'inactive' : 'active';
    const actionLabel = nextStatus === 'active' ? 'ปลดระงับการใช้งาน' : 'ระงับการใช้งาน';
    const apiUrl = type === 'reporter' ? '../api/admin_reporter_actions.php' : '../api/admin_actions.php';

    Swal.fire({
        title: actionLabel + '?',
        text: `ยืนยัน ${actionLabel} ของคุณ "${name}" ใช่หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#003366',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(apiUrl, { action: 'toggle_status', id: id, status: nextStatus }, function(res) {
                if(res.success) location.reload();
                else Swal.fire('Error', res.error, 'error');
            }, 'json');
        }
    });
}

function handleResetPass(id, name) {
    Swal.fire({
        title: 'รีเซ็ตรหัสผ่าน?',
        text: `รหัสผ่านของ "${name}" จะถูกเปลี่ยนเป็น password@123`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#856404',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../api/admin_actions.php', { action: 'reset_pass', id: id }, function(res) {
                if(res.success) Swal.fire('สำเร็จ', 'รีเซ็ตรหัสผ่านเรียบร้อย', 'success');
            }, 'json');
        }
    });
}

function openTechModal() { $('#techForm')[0].reset(); new bootstrap.Modal(document.getElementById('techModal')).show(); }

function openReporterModal(mode, data = null) {
    $('#reporterForm')[0].reset();
    if(mode === 'add') {
        $('#rep-modal-title').text('เพิ่มพนักงานใหม่');
        $('#rep-action').val('add_reporter'); $('#rep-id').val('');
    } else {
        $('#rep-modal-title').text('แก้ไขข้อมูลพนักงาน');
        $('#rep-action').val('edit_reporter');
        $('#rep-id').val(data.id); $('#rep-name').val(data.full_name); $('#rep-phone').val(data.phone); $('#rep-office').val(data.office_phone); $('#rep-email').val(data.email);
    }
    new bootstrap.Modal(document.getElementById('reporterModal')).show();
}

function openAdminModal() {
    Swal.fire({
        title: '<h4 class="fw-bold text-danger mb-0"><i class="bi bi-person-plus-fill me-2"></i>เพิ่มผู้ดูแลระบบ</h4>',
        html: `
            <div class="text-start p-2">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted mb-1">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                    <input id="adm-n" class="form-control border-danger-subtle" placeholder="ระบุชื่อจริง-นามสกุล" style="border-radius:10px;">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted mb-1">อีเมล (ใช้เข้าสู่ระบบ) <span class="text-danger">*</span></label>
                    <input id="adm-e" class="form-control border-danger-subtle" placeholder="เช่น admin@system.local" style="border-radius:10px;">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted mb-1">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                    <input id="adm-p" class="form-control border-danger-subtle" maxlength="10" placeholder="06, 08, 09XXXXXXXX" style="border-radius:10px;">
                </div>
                <div class="alert alert-danger py-2 mb-0" style="font-size: 0.75rem;">
                    <i class="bi bi-info-circle me-1"></i> รหัสผ่านเริ่มต้นคือ: <strong>password@123</strong>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก',
        customClass: {
            popup: 'rounded-4 shadow-lg',
            confirmButton: 'px-4 py-2 fw-bold',
            cancelButton: 'px-4 py-2'
        },
        preConfirm: () => {
            const data = {
                full_name: $('#adm-n').val().trim(),
                email: $('#adm-e').val().trim(),
                phone: $('#adm-p').val().trim(),
                action: 'add_admin'
            };

            // ตรวจสอบความถูกต้องของข้อมูล
            const phoneRegex = /^0[689]\d{8}$/;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!data.full_name) return Swal.showValidationMessage('กรุณาระบุชื่อ-นามสกุล');
            if (!emailRegex.test(data.email)) return Swal.showValidationMessage('กรุณาระบุรูปแบบอีเมลให้ถูกต้อง');
            if (!phoneRegex.test(data.phone)) return Swal.showValidationMessage('เบอร์โทรต้องเป็น 10 หลักและขึ้นต้นด้วย 06, 08, 09');
            
            return data;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            
            $.post('../api/admin_actions.php', result.value, function(res) {
                if(res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: 'เพิ่มผู้ดูแลระบบรายใหม่เรียบร้อยแล้ว',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('ข้อผิดพลาด', res.error, 'error');
                }
            }, 'json');
        }
    });
}

$('#techForm, #reporterForm').on('submit', function(e) {
    e.preventDefault();
    const url = this.id === 'techForm' ? '../api/admin_actions.php' : '../api/admin_reporter_actions.php';
    $.post(url, $(this).serialize(), function(res) {
        if(res.success) location.reload();
        else Swal.fire('Error', res.error, 'error');
    }, 'json');
});

// ฟังก์ชันสำหรับเปิดป๊อปอัปแก้ไขช่างและเติมข้อมูลเดิมเข้าไป
function openTechEditModal(data) {
    $('#edit-tech-id').val(data.id);
    $('#edit-tech-name').val(data.full_name);
    $('#edit-tech-email').val(data.email);
    $('#edit-tech-phone').val(data.phone);
    new bootstrap.Modal(document.getElementById('editTechModal')).show();
}

// เพิ่มเหตุการณ์ตอนส่งฟอร์มแก้ไขช่าง
$('#editTechForm').on('submit', function(e) {
    e.preventDefault();
    $.post('../api/admin_actions.php', $(this).serialize(), function(res) {
        if(res.success) {
            Swal.fire({ icon: 'success', title: 'สำเร็จ', text: 'แก้ไขข้อมูลช่างเรียบร้อยแล้ว', timer: 1500, showConfirmButton: false })
            .then(() => location.reload());
        } else {
            Swal.fire('ผิดพลาด', res.error, 'error');
        }
    }, 'json');
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>