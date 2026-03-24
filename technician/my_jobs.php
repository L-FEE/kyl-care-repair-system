<?php 
/*
|------------------------------------------------------
| File: technician/my_jobs.php
| Description: รายการงานที่กำลังดำเนินการ (งานของฉัน)
|------------------------------------------------------
*/
require_once '../includes/tech_header.php'; 

// สถิติเฉพาะงานในมือของช่างคนนี้
$stats = [
    'accepted' => $conn->query("SELECT id FROM repair_requests WHERE technician_id = $user_id AND status = 'accepted'")->num_rows,
    'working' => $conn->query("SELECT id FROM repair_requests WHERE technician_id = $user_id AND status = 'in_progress'")->num_rows,
    'waiting' => $conn->query("SELECT id FROM repair_requests WHERE technician_id = $user_id AND status = 'waiting_parts'")->num_rows,
    'total' => 0
];
$stats['total'] = $stats['accepted'] + $stats['working'] + $stats['waiting'];
?>

<style>
    .card-summary {
        border: none;
        border-radius: 15px;
        transition: 0.3s;
    }
    .card-summary:hover {
        transform: translateY(-5px);
    }
    .job-table thead th {
        background-color: #f8fafc;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        border: none;
    }
    .request-code-badge {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    .btn-manage {
        background-color: #003366;
        color: white;
        border-radius: 10px;
        padding: 8px 16px;
        font-weight: 600;
        transition: 0.2s;
    }
    .btn-manage:hover {
        background-color: #002244;
        color: white;
        box-shadow: 0 4px 10px rgba(0,51,102,0.3);
    }
</style>

<div class="mb-4">
    <h3 class="fw-bold mb-1">รายการงานของฉัน</h3>
    <p class="text-muted small">รวมงานที่คุณรับผิดชอบและยังดำเนินการไม่เสร็จสิ้น</p>
</div>

<!-- ส่วนสรุปยอดงาน (Cards) -->
<div class="row g-3 mb-4">
    <!-- <div class="col-6 col-md-3">
        <div class="card card-summary bg-white p-3 shadow-sm border-start border-4 border-primary">
            <small class="text-muted d-block fw-bold">รับงานแล้ว</small>
            <h3 class="mb-0 fw-bold"><?= $stats['accepted'] ?></h3>
        </div>
    </div> -->
    <div class="col-6 col-md-4">
        <div class="card card-summary bg-white p-3 shadow-sm border-start border-4 border-info">
            <small class="text-muted d-block fw-bold">กำลังซ่อม</small>
            <h3 class="mb-0 fw-bold text-info"><?= $stats['working'] ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card card-summary bg-white p-3 shadow-sm border-start border-4 border-warning">
            <small class="text-muted d-block fw-bold">รออะไหล่</small>
            <h3 class="mb-0 fw-bold text-warning"><?= $stats['waiting'] ?></h3>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card card-summary bg-main p-3 shadow-sm text-white">
            <small class="fw-bold opacity-75 d-block">งานที่รับผิดชอบ</small>
            <h3 class="mb-0 fw-bold"><?= $stats['total'] ?></h3>
        </div>
    </div>
</div>

<!-- ตารางรายการงาน -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-bottom border-light">
        <h5 class="mb-0 fw-bold" style="color: #003366;"><i class="bi bi-list-task me-2"></i>รายการงานดำเนินการ</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 job-table">
            <thead>
                <tr>
                    <th class="ps-4">รหัสงาน / เวลาที่รับ</th>
                    <th>ผู้แจ้ง</th>
                    <th>อุปกรณ์และอาการ</th>
                    <th>สถานที่</th>
                    <th class="text-center">สถานะ</th>
                    <th class="text-end pe-4">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM repair_requests 
                        WHERE technician_id = $user_id 
                        AND status IN ('accepted', 'in_progress', 'waiting_parts')
                        ORDER BY FIELD(status, 'in_progress', 'waiting_parts', 'accepted'), accepted_at DESC";
                $res = $conn->query($sql);
                while($row = $res->fetch_assoc()):
                ?>
                <tr>
                    <td class="ps-4">
                        <div class="request-code-badge d-inline-block mb-1"><?= $row['request_code'] ?></div>
                        <div class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-calendar3"></i> <?= date('d/m/Y H:i', strtotime($row['accepted_at'])) ?></div>
                    </td>
                    <td>
                        <div class="fw-bold text-dark"><?= h($row['reporter_name']) ?></div>
                        <small class="text-muted"><i class="bi bi-telephone"></i> <?= h($row['reporter_phone']) ?></small>
                    </td>
                    <td>
                        <div class="text-primary fw-bold small"><?= h($row['device_name'] ?: 'ยังไม่ได้ระบุ') ?></div>
                        <div class="text-danger small" style="font-size: 0.75rem;"><?= h($row['fault_name'] ?: 'ยังไม่ได้ระบุ') ?></div>
                    </td>
                    <td>
                        <div class="small fw-bold"><?= h($row['floor_name']) ?></div>
                        <div class="small text-muted text-truncate" style="max-width: 150px;"><?= h($row['location_name'] ?: '-') ?></div>
                    </td>
                    <td class="text-center">
                        <?= getStatusBadge($row['status']) ?>
                    </td>
                    <td class="text-end pe-4">
                        <a href="<?= BASE_URL ?>/technician/job_detail.php?id=<?= $row['id'] ?>" class="btn btn-manage btn-sm shadow-sm">
                            <i class="bi bi-pencil-square me-1"></i> จัดการงาน
                        </a>
                    </td>
                </tr>
                <?php endwhile; if($res->num_rows == 0) echo "<tr><td colspan='6' class='text-center py-5 text-muted'>ยังไม่มีงานที่รับผิดชอบในขณะนี้</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/tech_footer.php'; ?>