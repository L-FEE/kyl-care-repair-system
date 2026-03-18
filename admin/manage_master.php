<?php 
/*
|------------------------------------------------------
| File: admin/manage_master.php
| Description: จัดการข้อมูลพื้นฐาน (ประเภท > อุปกรณ์ > อาการเสีย) แบบ 3 คอลัมน์
|------------------------------------------------------
*/
require_once '../includes/admin_header.php'; 
?>

<style>
    /* Explorer Layout */
    .explorer-container {
        display: flex;
        gap: 20px;
        height: calc(100vh - 200px);
        min-height: 550px;
    }
    
    .explorer-column {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    /* Column Header */
    .column-header {
        padding: 18px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .column-title {
        font-weight: 700;
        color: #003366;
        font-size: 1rem;
    }

    /* Column Body */
    .column-body {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
    }

    /* List Items */
    .list-group-item {
        border: none;
        border-radius: 12px !important;
        margin-bottom: 6px;
        padding: 14px 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #475569;
        background: transparent;
    }

    .list-group-item:hover {
        background-color: #f1f5f9;
        transform: translateX(4px);
    }

    .list-group-item.active {
        background-color: #003366 !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(0,51,102,0.2);
    }

    /* Actions */
    .item-actions {
        display: none;
        gap: 6px;
    }

    .list-group-item:hover .item-actions, 
    .list-group-item.active .item-actions {
        display: flex;
    }

    .btn-action {
        width: 32px;
        height: 32px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(0,0,0,0.05);
        color: inherit;
        transition: 0.2s;
    }

    .btn-action:hover {
        background: #fff;
        color: #003366;
        transform: scale(1.1);
    }

    .btn-add-new {
        padding: 4px 12px;
        font-size: 0.85rem;
        border-radius: 8px;
        font-weight: 600;
        transition: 0.2s;
    }

    /* Empty States */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 3rem;
        display: block;
        margin-bottom: 15px;
        opacity: 0.5;
    }
</style>

<div class="mb-4">
    <h3 class="fw-bold"><i class="bi bi-diagram-3 text-primary me-2"></i>จัดการโครงสร้างอุปกรณ์</h3>
    <p class="text-muted">บริหารจัดการประเภท, รายการอุปกรณ์ และอาการเสีย เพื่อระเบียบของข้อมูล</p>
</div>

<div class="explorer-container">
    <!-- 1. Categories -->
    <div class="explorer-column">
        <div class="column-header">
            <span class="column-title">1. ประเภทอุปกรณ์</span>
            <button class="btn btn-outline-primary btn-add-new" onclick="openAdd('category')">
                <i class="bi bi-plus-lg"></i> เพิ่ม
            </button>
        </div>
        <div class="column-body" id="list-category">
            <div class="text-center py-5"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
    </div>

    <!-- 2. Devices -->
    <div class="explorer-column">
        <div class="column-header">
            <span class="column-title">2. รายการอุปกรณ์</span>
            <button class="btn btn-outline-primary btn-add-new" id="btn-add-device" onclick="openAdd('device')" disabled title="โปรดเลือกประเภทก่อน">
                <i class="bi bi-plus-lg"></i> เพิ่ม
            </button>
        </div>
        <div class="column-body" id="list-device">
            <div class="empty-state">
                <i class="bi bi-arrow-left-circle"></i>
                <p>เลือกประเภทจากด้านซ้าย</p>
            </div>
        </div>
    </div>

    <!-- 3. Faults -->
    <div class="explorer-column">
        <div class="column-header">
            <span class="column-title">3. อาการเสีย</span>
            <button class="btn btn-outline-primary btn-add-new" id="btn-add-fault" onclick="openAdd('fault')" disabled title="โปรดเลือกอุปกรณ์ก่อน">
                <i class="bi bi-plus-lg"></i> เพิ่ม
            </button>
        </div>
        <div class="column-body" id="list-fault">
            <div class="empty-state">
                <i class="bi bi-arrow-left-circle"></i>
                <p>เลือกอุปกรณ์จากช่องกลาง</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับ เพิ่มและแก้ไข -->
<div class="modal fade" id="masterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-main text-white">
                <h5 class="modal-title" id="modal-title">จัดการข้อมูล</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="masterForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" id="form_action">
                    <input type="hidden" name="type" id="form_type">
                    <input type="hidden" name="id" id="form_id">
                    <input type="hidden" name="parent_id" id="form_parent_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold" id="label-name">ระบุชื่อรายการ</label>
                        <input type="text" name="name" id="form_name" class="form-control form-control-lg" placeholder="กรอกข้อมูลที่นี่..." required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4">ยืนยัน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let selectedCatId = null;
let selectedDevId = null;

$(document).ready(function() {
    loadCategories();
});

// --- ฟังก์ชันหลักในการโหลดข้อมูล ---

function loadCategories() {
    $.getJSON('../api/admin_get_master_drilldown.php?type=category', function(data) {
        let html = '';
        data.forEach(item => {
            html += `
                <div class="list-group-item cat-item" onclick="selectCategory(this, ${item.id}, '${item.category_name}')">
                    <span class="text-truncate fw-bold">${item.category_name}</span>
                    <div class="item-actions">
                        <button class="btn-action" onclick="openEdit('category', ${item.id}, '${item.category_name}', event)"><i class="bi bi-pencil"></i></button>
                        <button class="btn-action text-danger" onclick="deleteItem('category', ${item.id}, event)"><i class="bi bi-trash"></i></button>
                    </div>
                </div>`;
        });
        $('#list-category').html(html || '<div class="empty-state">ไม่มีข้อมูลประเภท</div>');
    });
}

function selectCategory(el, id, name) {
    $('.cat-item').removeClass('active');
    $(el).addClass('active');
    selectedCatId = id;
    selectedDevId = null; // รีเซ็ตตัวเลือกระดับ 3
    
    // ปลดล็อคปุ่มเพิ่มอุปกรณ์
    $('#btn-add-device').prop('disabled', false).attr('title', 'เพิ่มอุปกรณ์ในหมวด ' + name);
    $('#btn-add-fault').prop('disabled', true);
    
    // รีเซ็ตช่องอาการเสีย
    $('#list-fault').html('<div class="empty-state"><i class="bi bi-arrow-left-circle"></i><p>เลือกอุปกรณ์จากช่องกลาง</p></div>');

    $.getJSON(`../api/admin_get_master_drilldown.php?type=device&parent_id=${id}`, function(data) {
        let html = '';
        data.forEach(item => {
            html += `
                <div class="list-group-item dev-item" onclick="selectDevice(this, ${item.id}, '${item.device_name}')">
                    <span class="text-truncate">${item.device_name}</span>
                    <div class="item-actions">
                        <button class="btn-action" onclick="openEdit('device', ${item.id}, '${item.device_name}', event)"><i class="bi bi-pencil"></i></button>
                        <button class="btn-action text-danger" onclick="deleteItem('device', ${item.id}, event)"><i class="bi bi-trash"></i></button>
                    </div>
                </div>`;
        });
        $('#list-device').html(html || '<div class="empty-state">ยังไม่มีรายการอุปกรณ์</div>');
    });
}

function selectDevice(el, id, name) {
    $('.dev-item').removeClass('active');
    $(el).addClass('active');
    selectedDevId = id;

    // ปลดล็อคปุ่มเพิ่มอาการเสีย
    $('#btn-add-fault').prop('disabled', false).attr('title', 'เพิ่มอาการเสียของ ' + name);

    $.getJSON(`../api/admin_get_master_drilldown.php?type=fault&parent_id=${id}`, function(data) {
        let html = '';
        data.forEach(item => {
            html += `
                <div class="list-group-item">
                    <span class="text-truncate text-danger small"><i class="bi bi-dot"></i> ${item.fault_name}</span>
                    <div class="item-actions">
                        <button class="btn-action" onclick="openEdit('fault', ${item.id}, '${item.fault_name}', event)"><i class="bi bi-pencil"></i></button>
                        <button class="btn-action text-danger" onclick="deleteItem('fault', ${item.id}, event)"><i class="bi bi-trash"></i></button>
                    </div>
                </div>`;
        });
        $('#list-fault').html(html || '<div class="empty-state">ยังไม่มีรายการอาการเสีย</div>');
    });
}

// --- ฟังก์ชันจัดการ Modal (Add / Edit) ---

function openAdd(type) {
    $('#form_action').val('add');
    $('#form_type').val(type);
    $('#form_id').val('');
    $('#form_name').val('');
    
    if (type === 'category') {
        $('#modal-title').text('เพิ่มประเภทอุปกรณ์ใหม่');
        $('#form_parent_id').val('');
    } else if (type === 'device') {
        $('#modal-title').text('เพิ่มอุปกรณ์ใหม่');
        $('#form_parent_id').val(selectedCatId);
    } else if (type === 'fault') {
        $('#modal-title').text('เพิ่มอาการเสียใหม่');
        $('#form_parent_id').val(selectedDevId);
    }
    
    new bootstrap.Modal(document.getElementById('masterModal')).show();
}

function openEdit(type, id, name, event) {
    event.stopPropagation();
    $('#form_action').val('edit');
    $('#form_type').val(type);
    $('#form_id').val(id);
    $('#form_name').val(name);
    $('#modal-title').text('แก้ไขข้อมูล');
    new bootstrap.Modal(document.getElementById('masterModal')).show();
}

$('#masterForm').on('submit', function(e) {
    e.preventDefault();
    const type = $('#form_type').val();
    
    $.post('../api/admin_master_actions.php', $(this).serialize(), function(res) {
        if(res.success) {
            Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 800, showConfirmButton: false });
            bootstrap.Modal.getInstance(document.getElementById('masterModal')).hide();
            
            // รีโหลดเฉพาะส่วนที่แก้ไข
            if(type === 'category') loadCategories();
            else if(type === 'device') $('.cat-item.active').click();
            else if(type === 'fault') $('.dev-item.active').click();
        } else {
            Swal.fire('ผิดพลาด', res.error, 'error');
        }
    }, 'json');
});

// --- ฟังก์ชันลบ ---

function deleteItem(type, id, event) {
    event.stopPropagation();
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลย่อยภายในรายการนี้จะหายไปทั้งหมด!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../api/admin_master_actions.php', { action: 'delete', type: type, id: id }, function(res) {
                if(res.success) {
                    if(type === 'category') {
                        loadCategories();
                        $('#list-device, #list-fault').html('<div class="empty-state"><i class="bi bi-arrow-left-circle"></i><p>เลือกรายการด้านซ้าย</p></div>');
                    }
                    else if(type === 'device') {
                        $('.cat-item.active').click();
                        $('#list-fault').html('<div class="empty-state"><i class="bi bi-arrow-left-circle"></i><p>เลือกอุปกรณ์จากช่องกลาง</p></div>');
                    }
                    else if(type === 'fault') $('.dev-item.active').click();
                } else {
                    Swal.fire('Error', res.error, 'error');
                }
            }, 'json');
        }
    });
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>