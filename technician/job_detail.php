<?php require_once '../includes/tech_header.php'; 

/*
|------------------------------------------------------
| File: technician/job_detail.php
| Description: บันทึกข้อมูลเทคนิค และแก้ไขปัญหารีโหลดหน้า (Fix Syntax & Base URL)
|------------------------------------------------------
*/

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM repair_requests WHERE id = ? AND technician_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) { 
    // แก้ไขจุดที่เขียนผิด: ส่ง BASE_URL ไปยังหน้าอื่นแบบปลอดภัย
    echo "<script>location.href='" . BASE_URL . "/technician/my_jobs.php';</script>"; 
    exit; 
}

// เช็คจำนวนรูป (ใส่ default เป็น 0 กันค่าว่าง)
$check_imgs = $conn->query("SELECT id FROM repair_images WHERE repair_request_id = $id");
$image_count_in_db = (int)$check_imgs->num_rows;
?>

<!-- Fancybox 5 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
<style>
    body, html { height: 100vh; overflow: hidden; background-color: #f1f5f9; }
    .main-wrapper { display: flex; height: calc(100vh - 70px); gap: 15px; padding: 15px; overflow: hidden; }
    .content-area { flex: 8; overflow-y: auto; padding-right: 10px; }
    .sidebar-area { flex: 4; display: flex; flex-direction: column; height: 100%; }

    .card-modern { border-radius: 20px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.04); background: #fff; padding: 22px; margin-bottom: 15px; }
    .tag-title { font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 3px; display: block; }
    .val-text { font-weight: 600; color: #1e293b; margin: 0; }
    
    .btn-action-sm { border-radius: 14px; padding: 12px 18px; font-weight: 700; border: none; transition: 0.3s; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
    .btn-action-sm:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
    
    .img-grid-thumb { height: 75px; width: 100%; object-fit: cover; border-radius: 12px; cursor: pointer; border: 2px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    .fancybox__container { z-index: 100000 !important; }
</style>

<div class="main-wrapper">
    <div class="content-area">
        <div class="d-flex align-items-center mb-3">
            <a href="<?= BASE_URL ?>/technician/my_jobs.php" class="btn btn-white bg-white text-dark me-3 rounded-circle shadow-sm border-0"><i class="bi bi-chevron-left"></i></a>
            <div>
                <h4 class="fw-bold m-0" style="color:#003366;"><?= $job['request_code'] ?></h4>
                <div class="mt-1"><?= getStatusBadge($job['status']) ?></div>
            </div>
        </div>

        <div class="card-modern">
            <div class="row g-4">
                <div class="col-md-6 border-end">
                    <label class="tag-title">ผู้แจ้งซ่อม</label><p class="val-text fs-6"><?= h($job['reporter_name']) ?></p>
                    <div class="row mt-2">
                        <div class="col-6"><label class="tag-title text-success">มือถือ</label><p class="val-text small"><?= h($job['reporter_phone']) ?></p></div>
                        <div class="col-6"><label class="tag-title text-primary">เบอร์ที่ทำงาน</label><p class="val-text small"><?= h($job['office_phone'] ?: '-') ?></p></div>
                    </div>
                </div>
                <div class="col-md-6 ps-md-4">
                    <label class="tag-title">สถานที่</label><p class="val-text small"><?= h($job['floor_name']) ?> | <?= h($job['location_name']) ?></p>
                    <label class="tag-title mt-2">เวลาแจ้ง</label><p class="val-text small"><?= date('d/m/Y H:i', strtotime($job['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <div class="card-modern border-start border-4 border-primary">
            <h6 class="fw-bold text-main border-bottom pb-2 mb-3">สรุปผลหน้างาน</h6>
            <div class="row g-4">
                <div class="col-md-4"><label class="tag-title">หมวดหมู่</label><div class="val-text small"><?= h($job['category_name'] ?: 'รอยืนยัน') ?></div></div>
                <div class="col-md-4"><label class="tag-title">อุปกรณ์</label><div class="val-text small text-primary"><?= h($job['device_name'] ?: 'รอยืนยัน') ?></div></div>
                <div class="col-md-4"><label class="tag-title">อาการเสีย</label><div class="val-text small text-danger"><?= h($job['fault_name'] ?: 'รอยืนยัน') ?></div></div>
                <div class="col-md-12"><label class="tag-title">SERIAL NUMBER</label><p class="val-text badge bg-light text-dark border"><?= h($job['serial_number'] ?: 'รอยืนยัน') ?></p></div>
            </div>
        </div>

        <div class="row g-2 px-1 mb-5">
            <?php $imgs = $conn->query("SELECT image_path FROM repair_images WHERE repair_request_id = $id");
            while($im = $imgs->fetch_assoc()): ?>
            <div class="col-3 col-md-2"><a href="<?= BASE_URL ?>/<?= $im['image_path'] ?>" data-fancybox="job-gallery"><img src="<?= BASE_URL ?>/<?= $im['image_path'] ?>" class="img-grid-thumb border"></a></div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- ส่วน Sidebar จัดการสถานะ -->
    <div class="sidebar-area">
        <div class="card-modern h-100 d-flex flex-column shadow-sm">
            <h6 class="fw-bold mb-4" style="color: #003366;"><i class="bi bi-patch-check-fill me-1"></i>ดำเนินการ</h6>
            <div class="flex-grow-1">
                <?php $s = $job['status']; if($s === 'accepted'): ?>
                    <button onclick="updateStep('in_progress')" class="btn btn-primary btn-action-sm shadow w-100 py-3"><i class="bi bi-tools"></i>เริ่มบันทึกหน้างาน</button>
                <?php elseif($s === 'in_progress' || $s === 'waiting_parts'): ?>
                    <?php if($s === 'in_progress'): ?>
                        <button onclick="updateStep('completed')" class="btn btn-success btn-action-sm w-100 mb-2 shadow-sm">✅ ซ่อมสำเร็จ / ปิดงาน</button>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><button onclick="updateStep('waiting_parts')" class="btn btn-outline-warning text-dark btn-action-sm py-2 shadow-none" style="font-size:0.8rem;"><i class="bi bi-pause-fill"></i>รออะไหล่</button></div>
                            <div class="col-6"><button onclick="updateStep('cannot_repair')" class="btn btn-outline-danger btn-action-sm py-2 shadow-none" style="font-size:0.8rem;"><i class="bi bi-x-circle"></i>ซ่อมไม่ได้</button></div>
                        </div>
                    <?php else: ?>
                        <button onclick="updateStep('in_progress')" class="btn btn-primary btn-action-sm shadow w-100 py-3 mb-3">กลับมาดำเนินการต่อ</button>
                    <?php endif; ?>
                    <label class="tag-title mt-4">สรุปผลงาน (บังคับปิดงาน)</label>
                    <textarea id="tech_note" class="form-control border-0 bg-light p-3" rows="7" placeholder="ระบุสิ่งที่แก้ไขไป..." style="border-radius:15px; font-size:0.85rem;"></textarea>
                <?php else: ?>
                    <div class="text-center py-5 bg-light rounded-4 opacity-75 border-dotted"><i class="bi bi-check-all display-6 text-muted"></i><p class="mt-2 fw-bold text-muted m-0 small uppercase">ปิดงานแล้ว</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script>
// ประกาศ BASE_URL สำหรับใช้ใน JS
const BASE_URL = '<?= BASE_URL ?>';

Fancybox.bind("[data-fancybox]", {});

const jobConf = {
    img_in_db: <?= (int)$image_count_in_db ?>, // ป้องกันค่าว่าง
    sn: '<?= addslashes(h($job['serial_number'])) ?>',
    st: '<?= $job['status'] ?>',
    f: '<?= addslashes(h($job['floor_name'])) ?>',
    l: '<?= addslashes(h($job['location_name'])) ?>',
    c: '<?= addslashes(h($job['category_name'])) ?>',
    d: '<?= addslashes(h($job['device_name'])) ?>',
    ft: '<?= addslashes(h($job['fault_name'])) ?>'
};

function updateStep(status) {
    const note = ($('#tech_note').length > 0) ? $('#tech_note').val().trim() : "";

    if(jobConf.st === 'accepted' && status === 'in_progress') {
        openSmartSetup();
        return;
    }

    if((status==='completed' || status==='cannot_repair') && note === "") {
        Swal.fire({ icon:'warning', title:'โปรดกรอกสรุปผล', text:'ช่างจำเป็นต้องบันทึกรายละเอียดก่อนส่งปิดงาน' });
        return;
    }

    Swal.fire({
        title:'ยืนยันปรับสถานะ?', icon:'question', showCancelButton:true, confirmButtonColor:'#003366', 
        confirmButtonText:'ยืนยัน', cancelButtonText:'ยกเลิก'
    }).then(r => { 
        if(r.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/api/tech_actions.php',
                type: 'POST',
                data: { action: 'update_status', job_id: <?= $id ?>, new_status: status, tech_note: note },
                success: function(res) { location.reload(); },
                error: function() { location.reload(); }
            });
        }
    });
}

function openSmartSetup(hist = null, cache = null) {
    Swal.fire({
        title: '<h6 class="fw-bold text-primary mb-0">กรอกข้อมูลทางเทคนิค</h6>',
        width: '660px',
        html: `
            <div class="text-start p-1" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">
                <label class="tag-title">1. สถานที่แจ้ง (ชั้น - ห้อง) <span class="text-danger">*</span></label>
                <select id="sw-l" class="form-select mb-3 border-primary-subtle rounded-3"></select>

                <div class="row g-2 mb-3">
                    <div class="col-6"><label class="tag-title">2. ประเภทอุปกรณ์ <span class="text-danger">*</span></label><select id="sw-c" class="form-select border-primary-subtle rounded-3"></select></div>
                    <div class="col-6"><label class="tag-title">3. ชื่ออุปกรณ์ <span class="text-danger">*</span></label><select id="sw-d" class="form-select border-primary-subtle rounded-3" disabled></select></div>
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-6"><label class="tag-title">4. สรุปอาการเสีย <span class="text-danger">*</span></label><select id="sw-f" class="form-select border-primary-subtle rounded-3" disabled></select></div>
                    <div class="col-6">
                        <label class="tag-title">5. หมายเลข SERIAL NUMBER <span class="text-danger">*</span></label>
                        <div class="d-flex gap-1">
                            <input id="sw-sn" class="form-control rounded-3 border-primary-subtle shadow-none" value="${cache ? cache.sn : jobConf.sn}">
                            <button type="button" class="btn btn-outline-secondary rounded-circle" style="width:38px;height:38px;flex-shrink:0" onclick="document.getElementById('sw-sn').value='ไม่มีหมายเลขซีเรียล'"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                </div>

                <label class="tag-title text-primary"><i class="bi bi-images me-1"></i> รูปถ่ายเพิ่มเติม (จำเป็นต้องมี 1 รูปถ้าในระบบไม่มี)</label>
                <input type="file" id="sw-img" class="form-control border-primary-subtle rounded-3 mb-2" multiple accept="image/*">
                <div id="sw-pv" class="row g-2"></div>
            </div>
        `,
        didOpen: () => {
            initSmartDrops(hist, cache);
            $('#sw-img').on('change', function(e) {
                $('#sw-pv').empty();
                Array.from(e.target.files).slice(0,3).forEach(f => {
                    let reader = new FileReader();
                    reader.onload = ev => $('#sw-pv').append('<div class="col-4"><img src="'+ev.target.result+'" style="height:65px;width:100%;object-fit:cover;border-radius:10px;"></div>');
                    reader.readAsDataURL(f);
                });
            });
        },
        showCancelButton: true, confirmButtonText: 'ยืนยัน', confirmButtonColor: '#003366', cancelButtonText: 'ยกเลิก',
        preConfirm: () => {
            const vals = { 
                l:$('#sw-l').val(), 
                cid:$('#sw-c').val(), cn:$('#sw-c option:selected').text(), 
                did:$('#sw-d').val(), dn:$('#sw-d option:selected').text(), 
                fid:$('#sw-f').val(), fn:$('#sw-f option:selected').text(), 
                sn:$('#sw-sn').val(), imgs:$('#sw-img')[0].files 
            };
            if(!vals.l || !vals.cid || !vals.did || !vals.fid || !vals.sn) return Swal.showValidationMessage('ระบุข้อมูลที่มี * ให้ครบถ้วน');
            if(jobConf.img_in_db === 0 && vals.imgs.length === 0) return Swal.showValidationMessage('ต้องแนบรูปหลักฐานอย่างน้อย 1 รูป');
            return vals;
        }
    }).then(res => { 
        if(res.isConfirmed) {
            Swal.fire({ title:'ยืนยันความถูกต้อง?', text:'กดยืนยันบันทึกข้อมูลสถิตหน้างานจริง', icon:'question', showCancelButton:true, confirmButtonText:'ตกลง', confirmButtonColor:'#003366', cancelButtonText:'แก้ไข' })
            .then(c => { if(c.isConfirmed) commitBigSave(res.value); else openSmartSetup(null, res.value); });
        }
    });
}

function initSmartDrops(hist, cache) {
    $.getJSON(BASE_URL + '/api/tech_get_dropdown_data.php?type=locations', d => {
        $('#sw-l').append('<option value="">-- โปรดระบุห้อง --</option>');
        d.forEach(i => $('#sw-l').append(new Option(i.display_name, i.id, false, cache ? cache.l == i.id : (jobConf.f+' - '+jobConf.l) === i.display_name)));
    });
    $.getJSON(BASE_URL + '/api/tech_get_dropdown_data.php?type=categories', d => {
        $('#sw-c').append('<option value="">-- เลือกประเภท --</option>');
        d.forEach(i => {
            let active = (hist && hist.type==='category' && hist.id == i.id) || (cache ? cache.cid == i.id : i.category_name === jobConf.c);
            $('#sw-c').append(new Option(i.category_name, i.id, false, active));
        });
        $('#sw-c').append(new Option('+ เพิ่มหมวดใหม่...', 'ADD')).trigger('change');
    });

    $('#sw-c').on('change', function() {
        const cid = $(this).val(); if(cid === 'ADD') return addNewEntry('category', 0);
        $('#sw-d').empty().append('<option value="">-- เลือกรายการ --</option>').prop('disabled', !cid);
        if(cid && $.isNumeric(cid)) $.getJSON(BASE_URL + '/api/tech_get_dropdown_data.php?type=devices&parent_id='+cid, data => {
            data.forEach(i => {
                let active = (hist && hist.type==='device' && hist.id == i.id) || (cache ? cache.did == i.id : i.device_name === jobConf.d);
                $('#sw-d').append(new Option(i.device_name, i.id, false, active));
            });
            $('#sw-d').append(new Option('+ เพิ่มอุปกรณ์...', 'ADD')).trigger('change');
        });
    });

    $('#sw-d').on('change', function() {
        const did = $(this).val(); if(did === 'ADD') return addNewEntry('device', $('#sw-c').val());
        $('#sw-f').empty().append('<option value="">-- เลือกอาการเสีย --</option>').prop('disabled', !did);
        if(did && $.isNumeric(did)) $.getJSON(BASE_URL + '/api/tech_get_dropdown_data.php?type=faults&parent_id='+did, data => {
            data.forEach(i => {
                let active = (hist && hist.type==='fault' && hist.id == i.id) || (cache ? cache.fid == i.id : i.fault_name === jobConf.ft);
                $('#sw-f').append(new Option(i.fault_name, i.id, false, active));
            });
            $('#sw-f').append(new Option('+ เพิ่มอาการเสีย...', 'ADD'));
        });
    });
    $('#sw-f').on('change', function() { if($(this).val() === 'ADD') addNewEntry('fault', $('#sw-d').val()); });
}

function addNewEntry(type, pid) {
    const cache = { sn:$('#sw-sn').val(), l:$('#sw-l').val(), cid:$('#sw-c').val(), did:$('#sw-d').val(), fid:$('#sw-f').val() };
    Swal.fire({
        title: 'เพิ่มเข้าฐานข้อมูลหลัก', input: 'text', showCancelButton: true, confirmButtonColor: '#003366', 
        cancelButtonText: 'ยกเลิก',
        preConfirm: n => { if(!n) return Swal.showValidationMessage('กรอกชื่อที่ต้องการ'); return $.post(BASE_URL + '/api/tech_add_master_data.php', {type, name:n, parent_id:pid}); }
    }).then(r => { if(r.isConfirmed) openSmartSetup({type, id: r.value.new_id}, cache); else openSmartSetup(null, cache); });
}

function commitBigSave(v) {
    const fd = new FormData();
    fd.append('action','update_status'); 
    fd.append('job_id', <?= $id ?>); 
    fd.append('new_status','in_progress');
    fd.append('tech_note', $('#tech_note').val()); 
    fd.append('location_input', v.l);
    fd.append('category_name', v.cn); 
    fd.append('device_name', v.dn); 
    fd.append('fault_name', v.fn); 
    fd.append('serial_number', v.sn);
    for(let i=0; i<v.imgs.length; i++) fd.append('repair_images[]', v.imgs[i]);

    $.ajax({ 
        url: BASE_URL + '/api/tech_actions.php', 
        type:'POST', 
        data:fd, 
        processData:false, 
        contentType:false, 
        success: function() { location.reload(); },
        error: function() { location.reload(); }
    });
}
</script>

<?php require_once '../includes/tech_footer.php'; ?>