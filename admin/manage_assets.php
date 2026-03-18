<?php require_once '../includes/admin_header.php'; ?>

<style>
    /* Explorer Layout */
    .explorer-container {
        display: flex;
        gap: 15px;
        height: calc(100vh - 250px);
        min-height: 480px;
    }
    .explorer-column {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid #edf2f7;
        overflow: hidden;
    }
    .column-header {
        padding: 15px;
        background: #f8fafc;
        border-bottom: 1px solid #edf2f7;
        font-weight: 700;
        color: #003366;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
    }
    .column-body {
        flex: 1;
        overflow-y: auto;
        padding: 8px;
    }
    /* List Items */
    .list-group-item {
        border: none;
        border-radius: 10px !important;
        margin-bottom: 4px;
        padding: 10px 15px;
        cursor: pointer;
        transition: 0.2s;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
        color: #4a5568;
    }
    .list-group-item:hover { background-color: #f1f5f9; color: #0033664b; }
    .list-group-item.active {
        background-color: #003366 !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(0,51,102,0.2);
    }
    .item-actions { display: none; gap: 4px; }
    .list-group-item:hover .item-actions, .list-group-item.active .item-actions { display: flex; }
    .btn-action {
        width: 26px; height: 26px; padding: 0;
        display: flex; align-items: center; justify-content: center;
        border-radius: 6px; background: rgba(0,0,0,0.05); border: none; color: inherit;
    }
    .btn-action:hover { background: #fff; color: #003366; }
    .empty-state { text-align: center; padding: 50px 20px; color: #a0aec0; font-size: 0.85rem; }
    .btn-add-sm { padding: 2px 8px; font-size: 0.75rem; border-radius: 6px; }
    

    /* ปรับแต่งสีปุ่มแท็บที่ถูกเลือกเป็นสีน้ำเงินเข้ม */
    .nav-pills .nav-link.active {
        background-color: #003366 !important; /* พื้นหลังสีน้ำเงินเข้ม */
        color: #ffffff !important;           /* ตัวหนังสือสีขาว */
    }

    /* ปรับแต่งสีตัวหนังสือของแท็บที่ยังไม่ได้เลือก */
    .nav-pills .nav-link {
        color: #4a5568; 
    }

    /* เมื่อเอาเมาส์ชี้ปุ่มที่ไม่ได้เลือก ให้เปลี่ยนสีพื้นเบาๆ */
    .nav-pills .nav-link:hover:not(.active) {
        background-color: #f1f5f9;
        color: #003366;
    }
</style>

<div class="mb-4">
    <h3 class="fw-bold"><i class="bi bi-box-seam-fill text-primary me-2"></i>สถานที่และอุปกรณ์</h3>
</div>

<!-- Tabs -->
<ul class="nav nav-pills mb-4 shadow-sm bg-white p-2 rounded-3" id="assetTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-bold" id="location-tab" data-bs-toggle="tab" data-bs-target="#tab-location" type="button" role="tab">
            <i class="bi bi-geo-alt-fill me-1"></i> สถานที่
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-bold ms-2" id="device-tab" data-bs-toggle="tab" data-bs-target="#tab-device" type="button" role="tab">
            <i class="bi bi-pc-display me-1"></i> อุปกรณ์และอาการเสีย
        </button>
    </li>
</ul>

<div class="tab-content" id="assetTabContent">
    
    <!-- แท็บ 1: สถานที่ -->
    <div class="tab-pane fade show active" id="tab-location" role="tabpanel">
        <div class="explorer-container">
            <div class="explorer-column">
                <div class="column-header">
                    <span>1. ชั้น</span>
                    <button class="btn btn-sm btn-outline-primary btn-add-sm" onclick="openModal('add', 'floor')"><i class="bi bi-plus"></i> เพิ่มชั้น</button>
                </div>
                <div class="column-body" id="list-floor"></div>
            </div>
            <div class="explorer-column" style="flex: 1.5;">
                <div class="column-header">
                    <span>2. ห้อง / ตำแหน่ง</span>
                    <button class="btn btn-sm btn-outline-primary btn-add-sm" id="btn-add-loc" disabled onclick="openModal('add', 'location')"><i class="bi bi-plus"></i> เพิ่มห้อง</button>
                </div>
                <div class="column-body" id="list-location">
                    <div class="empty-state"><i class="bi bi-arrow-left-circle fs-2 d-block mb-2"></i>เลือกชั้นด้านซ้าย</div>
                </div>
            </div>
        </div>
    </div>

    <!-- แท็บ 2: อุปกรณ์ -->
    <div class="tab-pane fade" id="tab-device" role="tabpanel">
        <div class="explorer-container">
            <div class="explorer-column">
                <div class="column-header">
                    <span>1. ประเภท</span>
                    <button class="btn btn-sm btn-outline-primary btn-add-sm" onclick="openModal('add', 'category')"><i class="bi bi-plus"></i> เพิ่ม</button>
                </div>
                <div class="column-body" id="list-category"></div>
            </div>
            <div class="explorer-column">
                <div class="column-header">
                    <span>2. รายการอุปกรณ์</span>
                    <button class="btn btn-sm btn-outline-primary btn-add-sm" id="btn-add-dev" disabled onclick="openModal('add', 'device')"><i class="bi bi-plus"></i> เพิ่ม</button>
                </div>
                <div class="column-body" id="list-device-items">
                    <div class="empty-state">เลือกประเภทด้านซ้าย</div>
                </div>
            </div>
            <div class="explorer-column">
                <div class="column-header">
                    <span>3. อาการเสีย</span>
                    <button class="btn btn-sm btn-outline-primary btn-add-sm" id="btn-add-fault" disabled onclick="openModal('add', 'fault')"><i class="bi bi-plus"></i> เพิ่ม</button>
                </div>
                <div class="column-body" id="list-fault">
                    <div class="empty-state">เลือกอุปกรณ์ช่องกลาง</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Shared -->
<div class="modal fade" id="assetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-main text-white">
                <h5 class="modal-title" id="modal-title">จัดการข้อมูล</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="assetForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" id="form_action">
                    <input type="hidden" name="type" id="form_type">
                    <input type="hidden" name="id" id="form_id">
                    <input type="hidden" name="parent_id" id="form_parent_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ระบุชื่อรายการ</label>
                        <input type="text" name="name" id="form_name" class="form-control form-control-lg" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4">ยืนยัน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let selectedFloorId = null;
let selectedCatId = null;
let selectedDevId = null;

$(document).ready(function() {
    // 1. ฟื้นฟูแท็บหลัก (Location vs Device)
    let activeTab = localStorage.getItem('activeAssetTab');
    if (activeTab) {
        const tabEl = document.querySelector('#' + activeTab);
        if (tabEl) new bootstrap.Tab(tabEl).show();
    }
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', e => localStorage.setItem('activeAssetTab', e.target.id));

    // 2. ดึงค่า ID คอลัมน์ที่เปิดค้างไว้ล่าสุด (ถ้ามี)
    selectedFloorId = localStorage.getItem('lastFloorId');
    selectedCatId   = localStorage.getItem('lastCatId');
    selectedDevId   = localStorage.getItem('lastDevId');

    // 3. เริ่มโหลดข้อมูลเริ่มต้น (กู้คืนสถานะ)
    loadBaseData();
});

// ฟังก์ชันดึงข้อมูลรอบแรกพร้อมการจำค่า (Auto Click)
function loadBaseData() {
    // โหลดแท็บชั้น
    $.getJSON('../api/admin_get_assets.php?type=floor', data => {
        let html = '';
        data.forEach(item => {
            let activeCls = (selectedFloorId == item.id) ? 'active' : '';
            html += `<div class="list-group-item floor-item ${activeCls}" onclick="selectFloor(this, ${item.id})">
                <span class="text-truncate">${item.floor_name}</span>
                <div class="item-actions">
                    <button class="btn-action" onclick="openModal('edit', 'floor', ${item.id}, '${item.floor_name}', event)"><i class="bi bi-pencil-fill"></i></button>
                    <button class="btn-action text-danger" onclick="deleteItem('floor', ${item.id}, event)"><i class="bi bi-trash-fill"></i></button>
                </div>
            </div>`;
        });
        $('#list-floor').html(html);
        if (selectedFloorId) fetchLocations(selectedFloorId); // เรียกห้องมาแสดงถ้ามีการล็อคชั้นไว้
    });

    // โหลดแท็บหมวดหมู่
    $.getJSON('../api/admin_get_assets.php?type=category', data => {
        let html = '';
        data.forEach(item => {
            let activeCls = (selectedCatId == item.id) ? 'active' : '';
            html += `<div class="list-group-item cat-item ${activeCls}" onclick="selectCategory(this, ${item.id})">
                <span class="text-truncate fw-bold">${item.category_name}</span>
                <div class="item-actions">
                    <button class="btn-action" onclick="openModal('edit', 'category', ${item.id}, '${item.category_name}', event)"><i class="bi bi-pencil-fill"></i></button>
                    <button class="btn-action text-danger" onclick="deleteItem('category', ${item.id}, event)"><i class="bi bi-trash-fill"></i></button>
                </div>
            </div>`;
        });
        $('#list-category').html(html);
        if (selectedCatId) fetchDevices(selectedCatId); // เรียกอุปกรณ์ถ้ามีการล็อคหมวดไว้
    });
}

// ---------------- สถานที่ ----------------
function selectFloor(el, id) {
    $('.floor-item').removeClass('active'); $(el).addClass('active');
    selectedFloorId = id; 
    localStorage.setItem('lastFloorId', id); // บันทึกชั้น
    fetchLocations(id);
}
function fetchLocations(floor_id) {
    $('#btn-add-loc').prop('disabled', false);
    $.getJSON('../api/admin_get_assets.php?type=location&parent_id=' + floor_id, data => {
        let html = '';
        data.forEach(item => {
            html += `<div class="list-group-item">
                <span>${item.location_name}</span>
                <div class="item-actions">
                    <button class="btn-action" onclick="openModal('edit', 'location', ${item.id}, '${item.location_name}', event)"><i class="bi bi-pencil-fill"></i></button>
                    <button class="btn-action text-danger" onclick="deleteItem('location', ${item.id}, event)"><i class="bi bi-trash-fill"></i></button>
                </div>
            </div>`;
        });
        $('#list-location').html(html || '<div class="empty-state">ไม่มีข้อมูลห้อง</div>');
    });
}

// ---------------- อุปกรณ์ ----------------
function selectCategory(el, id) {
    $('.cat-item').removeClass('active'); $(el).addClass('active');
    selectedCatId = id; 
    localStorage.setItem('lastCatId', id); // บันทึกหมวด
    
    // เคลียร์อุปกรณ์ที่เคยเลือกไว้ เพราะเราเปลี่ยนหมวด
    selectedDevId = null;
    localStorage.removeItem('lastDevId');
    $('#list-fault').html('<div class="empty-state">เลือกอุปกรณ์</div>');
    $('#btn-add-fault').prop('disabled', true);

    fetchDevices(id);
}

function fetchDevices(cat_id) {
    $('#btn-add-dev').prop('disabled', false);
    $.getJSON('../api/admin_get_assets.php?type=device&parent_id=' + cat_id, data => {
        let html = '';
        data.forEach(item => {
            let activeCls = (selectedDevId == item.id) ? 'active' : '';
            html += `<div class="list-group-item dev-item ${activeCls}" onclick="selectDevice(this, ${item.id})">
                <span>${item.device_name}</span>
                <div class="item-actions">
                    <button class="btn-action" onclick="openModal('edit', 'device', ${item.id}, '${item.device_name}', event)"><i class="bi bi-pencil-fill"></i></button>
                    <button class="btn-action text-danger" onclick="deleteItem('device', ${item.id}, event)"><i class="bi bi-trash-fill"></i></button>
                </div>
            </div>`;
        });
        $('#list-device-items').html(html || '<div class="empty-state">ไม่มีอุปกรณ์</div>');
        if (selectedDevId) fetchFaults(selectedDevId); // โหลด Fault ต่อ ถ้าล็อคอุปกรณ์ไว้
    });
}

function selectDevice(el, id) {
    $('.dev-item').removeClass('active'); $(el).addClass('active');
    selectedDevId = id;
    localStorage.setItem('lastDevId', id); // บันทึกอุปกรณ์
    fetchFaults(id);
}

function fetchFaults(dev_id) {
    $('#btn-add-fault').prop('disabled', false);
    $.getJSON('../api/admin_get_assets.php?type=fault&parent_id=' + dev_id, data => {
        let html = '';
        data.forEach(item => {
            html += `<div class="list-group-item">
                <span class="text-danger small fw-bold"><i class="bi bi-dash"></i> ${item.fault_name}</span>
                <div class="item-actions">
                    <button class="btn-action" onclick="openModal('edit', 'fault', ${item.id}, '${item.fault_name}', event)"><i class="bi bi-pencil-fill"></i></button>
                    <button class="btn-action text-danger" onclick="deleteItem('fault', ${item.id}, event)"><i class="bi bi-trash-fill"></i></button>
                </div>
            </div>`;
        });
        $('#list-fault').html(html || '<div class="empty-state">ไม่มีอาการเสีย</div>');
    });
}

// ---------------- ควบคุม Action ----------------
function openModal(action, type, id = '', name = '', event = null) {
    if(event) event.stopPropagation();
    $('#form_action').val(action); $('#form_type').val(type);
    $('#form_id').val(id); $('#form_name').val(name);
    $('#modal-title').text((action === 'add' ? 'เพิ่ม' : 'แก้ไข') + 'ข้อมูล');
    
    if(type === 'location') $('#form_parent_id').val(selectedFloorId);
    else if(type === 'device') $('#form_parent_id').val(selectedCatId);
    else if(type === 'fault') $('#form_parent_id').val(selectedDevId);
    
    new bootstrap.Modal(document.getElementById('assetModal')).show();
}

$('#assetForm').on('submit', function(e) {
    e.preventDefault();
    $.post('../api/admin_asset_actions.php', $(this).serialize(), function(res) {
        if(res.success) {
            location.reload(); // รีโหลดหน้า ทุกอย่างยังคงที่ด้วย localStorage
        } else { 
            Swal.fire('ผิดพลาด', res.error, 'error'); 
        }
    }, 'json');
});

function deleteItem(type, id, event) {
    event.stopPropagation();
    Swal.fire({
        title: 'ยืนยันการลบ?', 
        text: "ข้อมูลย่อยภายในจะถูกลบด้วย",
        icon: 'warning', 
        showCancelButton: true, 
        confirmButtonColor: '#d33',
        confirmButtonText: 'ยืนยัน'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../api/admin_asset_actions.php', {action:'delete', type:type, id:id}, res => {
                if(res.success) {
                    // หากเราลบไอเทมที่เลือกค้างอยู่ ต้องสั่งให้ลืมค่านั้น เพื่อกัน Error หาไม่เจอ
                    if(type === 'floor' && selectedFloorId == id) localStorage.removeItem('lastFloorId');
                    if(type === 'category' && selectedCatId == id) localStorage.removeItem('lastCatId');
                    if(type === 'device' && selectedDevId == id) localStorage.removeItem('lastDevId');
                    location.reload(); 
                }
                else Swal.fire('ผิดพลาด', res.error, 'error');
            }, 'json');
        }
    });
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>