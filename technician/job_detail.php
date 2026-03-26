<?php 
/*
|------------------------------------------------------
| File: technician/job_detail.php
| Description: ฉบับสมบูรณ์ แก้ไขบั๊กการรีโหลด และ Error JavaScript ทั้งหน้า
|------------------------------------------------------
*/
require_once '../includes/tech_header.php'; 

$id = (int)($_GET['id'] ?? 0);
// ดึงข้อมูลงานล่าสุด
$stmt = $conn->prepare("SELECT * FROM repair_requests WHERE id = ? AND technician_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) { 
    echo "<script>location.href='" . BASE_URL . "/technician/my_jobs.php';</script>"; 
    exit; 
}

// เช็คจำนวนรูปที่มีในฐานข้อมูลเพื่อใช้บังคับกรณีรูปว่าง
$check_imgs = $conn->query("SELECT id FROM repair_images WHERE repair_request_id = $id");
$image_count_in_db = (int)$check_imgs->num_rows;
?>

<!-- Fancybox 5 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />

<style>
    body, html { height: 100vh; overflow: hidden; background-color: #f1f5f9; }
    .main-wrapper { display: flex; height: calc(100vh - 70px); gap: 15px; padding: 15px; overflow: hidden; }
    .content-area { flex: 8; overflow-y: auto; padding-right: 10px; scrollbar-width: thin; }
    .sidebar-area { flex: 4; height: 100%; display: flex; flex-direction: column; }

    .card-modern { border-radius: 20px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.04); background: #fff; padding: 25px; margin-bottom: 15px; }
    .tag-title { font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 3px; display: block; }
    .val-text { font-weight: 600; color: #1e293b; margin: 0; }
    
    .btn-action-sm { border-radius: 14px; padding: 12px 18px; font-weight: 700; border: none; transition: 0.3s; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .btn-action-sm:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
    
    .img-grid-thumb { height: 85px; width: 100%; object-fit: cover; border-radius: 12px; cursor: pointer; border: 2px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.08); transition: 0.2s; }
    .img-grid-thumb:hover { transform: scale(1.05); }

    /* ป้องกันจอมืด/ค้างเมื่อเปิดรูปขยาย */
    .fancybox__container { z-index: 100000 !important; }
    .modal-gallery-img { height: 75px; width: 100%; object-fit: cover; border-radius: 10px; cursor: pointer; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
</style>

<div class="main-wrapper">
    <!-- ฝั่งซ้าย: ข้อมูลแสดงผล -->
    <div class="content-area">
        <div class="d-flex align-items-center mb-3">
            <a href="<?= BASE_URL ?>/technician/my_jobs.php" class="btn btn-white bg-white text-dark me-3 rounded-circle shadow-sm border-0"><i class="bi bi-chevron-left"></i></a>
            <div>
                <h4 class="fw-bold m-0" style="color:#003366;"><?= $job['request_code'] ?></h4>
                <div class="mt-1"><?= getStatusBadge($job['status']) ?></div>
            </div>
        </div>

        <!-- รายละเอียดข้อมูลพนักงาน -->
        <div class="card-modern">
            <h6 class="fw-bold mb-4" style="color: #003366;"><i class="bi bi-person-fill"></i> ข้อมูลผู้แจ้งซ่อม</h6>
            <div class="row g-4">
                <div class="col-md-6 border-end border-light">
                    <div class="mb-3"><label class="tag-title">ชื่อผู้แจ้ง</label><p class="val-text"><?= h($job['reporter_name']) ?></p></div>
                    <div class="row">
                        <div class="col-6"><label class="tag-title text-success">โทรศัพท์</label><p class="val-text small"><?= h($job['reporter_phone']) ?></p></div>
                        <div class="col-6"><label class="tag-title text-primary">เบอร์โต๊ะ</label><p class="val-text small"><?= h($job['office_phone'] ?: '-') ?></p></div>
                    </div>
                </div>
                <div class="col-md-6 ps-md-4">
                    <div class="mb-3"><label class="tag-title">ชั้น - ห้อง / สถานที่</label><p class="val-text small"><?= h($job['floor_name']) ?> | <?= h($job['location_name']) ?></p></div>
                    <div><label class="tag-title">วันเวลาแจ้งเรื่อง</label><p class="val-text small opacity-75"><?= date('d/m/Y H:i', strtotime($job['created_at'])) ?> น.</p></div>
                </div>
            </div>
        </div>

        <!-- รายละเอียดเครื่องหน้างานจริง -->
        <div class="card-modern border-start border-4 border-primary">
            <h6 class="fw-bold text-main border-bottom pb-2 mb-3">สถานะข้อมูลหน้างานจริง (โดยช่าง)</h6>
            <div class="row g-3">
                <div class="col-md-4"><label class="tag-title">หมวดหมู่</label><p class="val-text small"><?= h($job['category_name'] ?: 'รอยืนยัน') ?></p></div>
                <div class="col-md-4"><label class="tag-title">อุปกรณ์ / รุ่น</label><p class="val-text text-primary small fw-bold"><?= h($job['device_name'] ?: 'รอยืนยัน') ?></p></div>
                <div class="col-md-4"><label class="tag-title">S/N</label><div class="val-text small badge bg-light text-dark border"><?= h($job['serial_number'] ?: 'รอยืนยัน') ?></div></div>
                <div class="col-12"><label class="tag-title text-danger">อาการที่ตรวจพบจริง</label><p class="val-text small"><?= h($job['fault_name'] ?: 'รอยืนยันอาการเสีย') ?></p></div>
            </div>
        </div>

        <!-- แกลเลอรี่ -->
        <div class="row g-2 px-1 pb-5">
            <?php $imgs = $conn->query("SELECT image_path FROM repair_images WHERE repair_request_id = $id");
            while($im = $imgs->fetch_assoc()): ?>
            <div class="col-3 col-md-2">
                <a href="<?= BASE_URL ?>/<?= $im['image_path'] ?>" data-fancybox="main-view">
                    <img src="<?= BASE_URL ?>/<?= $im['image_path'] ?>" class="img-grid-thumb">
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- ฝั่งขวา: ปรับสถานะ (UI ที่คุณต้องการ) -->
    <div class="sidebar-area">
        <div class="card-modern h-100 d-flex flex-column shadow-sm">
            <h6 class="fw-bold mb-4" style="color: #003366;"><i class="bi bi-gear-fill me-1"></i>ดำเนินการ</h6>
            <div class="flex-grow-1 overflow-auto">
                <div class="d-flex flex-column gap-3">
                    <?php $s = $job['status']; if($s === 'accepted'): ?>
                        <div class="alert alert-info py-2 small border-0 shadow-none"><i class="bi bi-exclamation-circle me-1"></i>กรุณาบันทึกข้อมูลก่อนลงมือซ่อม</div>
                        <button onclick="updateStep('in_progress')" class="btn btn-primary btn-action-sm w-100 shadow-sm"><i class="bi bi-tools"></i>เริ่มซ่อม & ระบุอุปกรณ์</button>
                    <?php elseif($s === 'in_progress' || $s === 'waiting_parts'): ?>
                        
                        <button onclick="updateStep('completed')" class="btn btn-success btn-action-sm shadow-sm w-100 mb-1 py-3"><i class="bi bi-check-circle"></i>แจ้งงานสำเร็จ: ปิดงาน</button>
                        
                        <?php if($s === 'in_progress'): ?>
                        <div class="row g-2">
                            <div class="col-6"><button onclick="updateStep('waiting_parts')" class="btn btn-outline-warning btn-action-sm py-2" style="font-size:0.85rem;"><i class="bi bi-hourglass-split"></i>รออะไหล่</button></div>
                            <div class="col-6"><button onclick="updateStep('cannot_repair')" class="btn btn-outline-danger btn-action-sm py-2" style="font-size:0.85rem;"><i class="bi bi-x-circle"></i>ซ่อมไม่ได้</button></div>
                        </div>
                        <?php else: ?>
                             <div class="alert alert-warning py-2 text-center x-small fw-bold border-0 shadow-none">รายการนี้อยู่สถานะ: รออะไหล่</div>
                             <button onclick="updateStep('in_progress')" class="btn btn-primary btn-action-sm shadow w-100 py-3"><i class="bi bi-play-fill"></i> ได้รับอะไหล่/เริ่มงานต่อ</button>
                        <?php endif; ?>

                        <div class="mt-4 border-top pt-3">
                            <label class="tag-title text-main">สรุปผลที่ช่างดำเนินการ (จำเป็นกรณีปิดงาน)</label>
                            <textarea id="tech_note" class="form-control border-0 bg-light p-3" rows="8" placeholder="เขียนสรุป..." style="border-radius:15px; font-size:0.9rem;"></textarea>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 bg-light rounded-4"><i class="bi bi-lock-fill display-5 text-muted"></i><p class="mt-3 fw-bold text-muted small">ปิดรายการแล้ว</p></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-auto text-center opacity-50"><small style="font-size: 0.6rem;">TIMESTAMP: <?= date('d/m/y H:i:s') ?></small></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script>
// --- กำหนดค่าสำคัญจากระบบ ป้องกัน Error บน Server ---
const API_URL = '<?= BASE_URL ?>/api/tech_actions.php';
const ADD_MASTER_URL = '<?= BASE_URL ?>/api/tech_add_master_data.php';
const GET_DATA_URL = '<?= BASE_URL ?>/api/tech_get_dropdown_data.php';

const jobValues = {
    st: '<?= $job['status'] ?>',
    floor: '<?= addslashes(h($job['floor_name'])) ?>',
    loc: '<?= addslashes(h($job['location_name'])) ?>',
    cat: '<?= addslashes(h($job['category_name'])) ?>',
    dev: '<?= addslashes(h($job['device_name'])) ?>',
    flt: '<?= addslashes(h($job['fault_name'])) ?>',
    sn: '<?= addslashes(h($job['serial_number'])) ?>',
    img_db_count: <?= (int)$image_count_in_db ?> 
};

// 1. จัดการ Fancybox
Fancybox.bind("[data-fancybox]", { zIndex: 100000 });

// 2. ฟังก์ชันตรวจสอบสิทธิ์ และดำเนินการ
function updateStep(newStatus) {
    const noteElement = document.getElementById('tech_note');
    const noteValue = (noteElement) ? noteElement.value.trim() : "";

    // เงื่อนไขบังคับตรวจสอบอุปกรณ์ (หากเริ่มงานใหม่)
    if(jobValues.st === 'accepted' && newStatus === 'in_progress') return openModernAssetSetup();

    // บังคับพิมพ์ผลดำเนินการหากจบงาน
    if((newStatus==='completed' || newStatus==='cannot_repair') && noteValue === "") {
        return Swal.fire({ icon:'warning', title:'ยังขาดบันทึกข้อมูลหลังการซ่อม', text:'ช่างต้องสรุปรายละเอียดงานซ่อมลงในช่องทางขวาก่อนกดปิดงานครับ' });
    }

    let labels = {'completed':'สำเร็จเรียบร้อย', 'cannot_repair':'ทำไม่ได้/แจ้งพัสดุ', 'waiting_parts':'รออะไหล่', 'in_progress':'เริ่มงานต่อ'};
    Swal.fire({
        title:'ยืนยันความถูกต้อง?', text:`เปลี่ยนเป็น [${labels[newStatus]}]`,
        icon:'question', showCancelButton:true, confirmButtonColor:'#003366', confirmButtonText:'ยืนยัน', cancelButtonText:'ยกเลิก'
    }).then(r => { if(r.isConfirmed) executeUpdateOnly(newStatus, noteValue); });
}

// 3. ฟังก์ชันเปิดหน้าตั้งค่า Master Data + รักษาสถานะข้อมูล (Persistence)
function openModernAssetSetup(newDataObj = null, savedSnapshot = null) {
    Swal.fire({
        title: '<h5 class="fw-bold text-primary mb-0">ข้อมูลเครื่องและสถิติหน้างาน</h5>',
        width: '660px',
        showCancelButton: true, confirmButtonText: 'ยืนยัน', confirmButtonColor: '#003366', cancelButtonText: 'ยกเลิก',
        html: `
            <div class="text-start p-1" style="max-height: 70vh; overflow-x: hidden;">
                <label class="tag-title">1. สถานที่แจ้ง (ชั้น - ห้อง) <span class="text-danger">*</span></label>
                <select id="s-l" class="form-select border-primary-subtle rounded-3 mb-3"></select>

                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="tag-title">2. ประเภทอุปกรณ์ <span class="text-danger">*</span></label><select id="s-c" class="form-select border-primary-subtle rounded-3"></select></div>
                    <div class="col-6"><label class="tag-title">3. รายการที่ซ่อม <span class="text-danger">*</span></label><select id="s-d" class="form-select border-primary-subtle rounded-3" disabled></select></div>
                </div>

                <div class="row g-2 mb-4 align-items-end">
                    <div class="col-6"><label class="tag-title">4. อาการเสียจริงหน้างาน <span class="text-danger">*</span></label><select id="s-f" class="form-select border-primary-subtle rounded-3" disabled></select></div>
                    <div class="col-6">
                        <label class="tag-title">5. หมายเลข SERIAL NUMBER <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <input id="s-sn" class="form-control rounded-3 border-primary-subtle" value="${savedSnapshot ? savedSnapshot.sn : jobValues.sn}" placeholder="ระบุเลขซีเรียล">
                            <button class="btn btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;flex-shrink:0" onclick="document.getElementById('s-sn').value='ไม่มีหมายเลขซีเรียล'"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                </div>

                <label class="tag-title text-primary"><i class="bi bi-images me-1"></i> อัปโหลดรูปเพิ่ม ${jobValues.img_db_count === 0 ? '<b class="text-danger">(ต้องมีรูปหลักฐานอย่างน้อย 1 รูป)</b>' : '(ไม่บังคับ)'}</label>
                <input type="file" id="s-img-up" class="form-control mb-1 border-primary-subtle rounded-3" multiple accept="image/*">
                <div id="pv-container" class="row g-2 mt-2"></div>
            </div>
        `,
        didOpen: () => {
            loadAsyncDropdowns(newDataObj, savedSnapshot);
            $('#s-img-up').on('change', function(e) {
                const zone = $('#pv-container').empty();
                Array.from(e.target.files).slice(0,3).forEach(f => {
                    let r = new FileReader(); 
                    r.onload = ev => zone.append('<div class="col-4"><img src="'+ev.target.result+'" style="height:70px; width:100%; object-fit:cover; border-radius:10px;"></div>');
                    r.readAsDataURL(f);
                });
            });
        },
        preConfirm: () => {
            const out = { l:$('#s-l').val(), cid:$('#s-c').val(), cat:$('#s-c option:selected').text(), did:$('#s-d').val(), dev:$('#s-d option:selected').text(), fid:$('#s-f').val(), flt:$('#s-f option:selected').text(), sn:$('#s-sn').val().trim(), images:$('#s-img-up')[0].files };
            if(!out.l || !out.cid || !out.did || !out.fid || !out.sn) return Swal.showValidationMessage('กรอกข้อมูลดอกจัน * ให้ครบ');
            if(jobValues.img_db_count === 0 && out.images.length === 0) return Swal.showValidationMessage('ผู้แจ้งไม่ได้แนบรูปมา ช่างต้องแนบหลักฐานอย่างน้อย 1 รูปครับ');
            return out;
        }
        }).then(res => { 
            if (res.isConfirmed) {
        Swal.fire({
            title: 'ยืนยันความถูกต้อง?', 
            text: 'คุณตรวจสอบข้อมูลเทคนิคเรียบร้อยแล้วและเริ่มดำเนินการซ่อม',
            icon: 'question', 
            showCancelButton: true, 
            confirmButtonColor: '#003366', 
            confirmButtonText: 'ยืนยัน',    // ปรับชื่อปุ่มยืนยัน
            cancelButtonText: 'ยกเลิก',    // ปรับชื่อปุ่มยกเลิก
            cancelButtonColor: '#6c757d'    // (ทางเลือก) เพิ่มสีเทาให้ปุ่มยกเลิกเพื่อให้ตัดกับสีน้ำเงิน
        }).then(final => { 
            if (final.isConfirmed) {
                // หากกด ยืนยัน ให้ส่งข้อมูลชุดใหญ่
                sendFullUpdatePackage(res.value); 
            } else {
                // หากกด ยกเลิก ให้พากลับไปหน้าป๊อปอัปกรอกข้อมูล (เพื่อแก้ไขต่อ)
                openModernAssetSetup(null, res.value); 
            }
        });
    }
    });
}

// 4. ฟังชันดึง Dropdown พร้อมห้ามเลือกอันแรกให้อัตโนมัติ (Strict Mode)
function loadAsyncDropdowns(newItem = null, restored = null) {
    $.getJSON(GET_DATA_URL + '?type=locations', d => {
        const s = $('#s-l').append('<option value="">-- โปรดระบุตำแหน่งจริง --</option>');
        d.forEach(i => s.append(new Option(i.display_name, i.id, false, restored ? restored.l == i.id : (jobValues.floor+' - '+jobValues.loc) === i.display_name)));
    });
    $.getJSON(GET_DATA_URL + '?type=categories', d => {
        const c = $('#s-c').append('<option value="">-- ระบุประเภท --</option>');
        d.forEach(i => {
            let active = (newItem && newItem.type === 'category' && newItem.id == i.id) || (restored ? restored.cid == i.id : i.category_name === jobValues.cat);
            c.append(new Option(i.category_name, i.id, false, active));
        });
        c.append(new Option('+ เพิ่มหมวดหมู่ใหม่...', 'ADD')).trigger('change');
    });

    $('#s-c').on('change', function() {
        const pid = $(this).val(); if(pid === 'ADD') return addNewEntryHelper('category', 0);
        const d_box = $('#s-d').empty().append('<option value="">-- ระบุเครื่อง/รุ่น --</option>').prop('disabled', !pid);
        if(pid && $.isNumeric(pid)) $.getJSON(GET_DATA_URL + '?type=devices&parent_id=' + pid, data => {
            data.forEach(i => {
                let active = (newItem && newItem.type === 'device' && newItem.id == i.id) || (restored ? restored.did == i.id : i.device_name === jobValues.dev);
                d_box.append(new Option(i.device_name, i.id, false, active));
            });
            d_box.append(new Option('+ เพิ่มชื่อรุ่นใหม่...', 'ADD')).trigger('change');
        });
    });

    $('#s-d').on('change', function() {
        const did = $(this).val(); if(did === 'ADD') return addNewEntryHelper('device', $('#s-c').val());
        const f_box = $('#s-f').empty().append('<option value="">-- ระบุอาการจริง --</option>').prop('disabled', !did);
        if(did && $.isNumeric(did)) $.getJSON(GET_DATA_URL + '?type=faults&parent_id=' + did, data => {
            data.forEach(i => {
                let active = (newItem && newItem.type === 'fault' && newItem.id == i.id) || (restored ? restored.fid == i.id : i.fault_name === jobValues.flt);
                f_box.append(new Option(i.fault_name, i.id, false, active));
            });
            f_box.append(new Option('+ เพิ่มอาการอื่นใหม่...', 'ADD'));
        });
    });
    $('#s-f').on('change', function() { if($(this).val() === 'ADD') addNewEntryHelper('fault', $('#s-d').val()); });
}

// 5. ตัวช่วยดักเพิ่ม Master Data ใหม่และรักษาตำแหน่งเดิม
function addNewEntryHelper(type, pid) {
    const curState = { l:$('#s-l').val(), cid:$('#s-c').val(), did:$('#s-d').val(), fid:$('#s-f').val(), sn:$('#s-sn').val() };
    Swal.fire({
        title: 'เพิ่มข้อมูลมาตรฐานใหม่', input: 'text', showCancelButton: true, confirmButtonColor: '#003366', cancelButtonText: 'ยกเลิก',
        preConfirm: n => { if(!n) return Swal.showValidationMessage('กรุณาระบุชื่อข้อมูลครับ'); return $.post(ADD_MASTER_URL, {type, name:n, parent_id:pid}); }
    }).then(r => { 
        if(r.isConfirmed) openModernAssetSetup({type, id: r.value.new_id}, curState); 
        else openModernAssetSetup(null, curState); 
    });
}

// 6. ส่งแพ็กเกจข้อมูลอัปเดตแบบ AJAX ตัวหนา (ฉบับแก้ไข: สมูท + รีเฟรชชัวร์ 100%)
function sendFullUpdatePackage(v) {
    const fd = new FormData();
    fd.append('action', 'update_status'); 
    fd.append('job_id', <?= $id ?>);
    fd.append('new_status', 'in_progress'); 
    fd.append('tech_note', ($('#tech_note').val() || ""));
    fd.append('location_input', v.l); 
    fd.append('category_name', v.cat);
    fd.append('device_name', v.dev); 
    fd.append('fault_name', v.flt); 
    fd.append('serial_number', v.sn);
    
    for(let i=0; i<v.images.length; i++) {
        fd.append('repair_images[]', v.images[i]);
    }

    // แสดงสถานะกำลังบันทึก
    Swal.fire({ 
        title: 'กำลังจัดบันทึกประวัติซ่อม...', 
        allowOutsideClick: false, 
        didOpen: () => Swal.showLoading() 
    });

    $.ajax({ 
        url: API_URL, 
        type: 'POST', 
        data: fd, 
        processData: false, 
        contentType: false, 
        dataType: 'json', // บังคับให้อ่านเป็น JSON
        success: function(res) {
            if(res.success) {
                // ขึ้นป๊อปอัปสำเร็จสั้นๆ เพื่อให้ดู Smooth
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    showConfirmButton: false,
                    timer: 800
                }).then(() => {
                    location.reload(); 
                });
            } else {
                Swal.fire('Error', res.error, 'error');
            }
        },
        error: function() {
            // กรณี Error จากเซิร์ฟเวอร์ ให้ลองรีเฟรช 1 รอบ
            location.reload();
        }
    });
}

function executeUpdateOnly(st, n) {
    // แสดงสถานะกำลังบันทึก (กันการสะดุด)
    Swal.fire({ 
        title: 'กำลังดำเนินการ...', 
        allowOutsideClick: false, 
        didOpen: () => Swal.showLoading() 
    });

    $.ajax({ 
        url: API_URL, 
        type: 'POST', 
        data: {
            action: 'update_status', 
            job_id: <?= $id ?>, 
            new_status: st, 
            tech_note: n
        }, 
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'ปรับสถานะแล้ว',
                    showConfirmButton: false,
                    timer: 800
                }).then(() => {
                    location.reload(); 
                });
            } else {
                Swal.fire('Error', res.error, 'error');
            }
        },
        error: function() {
            location.reload();
        }
    });
}
</script>

<?php require_once '../includes/tech_footer.php'; ?>