<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit;

$sn = $_GET['sn'] ?? '';
if (!$sn) exit;

$sql = "SELECT r.*, u.full_name as tech_name 
        FROM repair_requests r 
        LEFT JOIN users u ON r.technician_id = u.id 
        WHERE r.serial_number = ? 
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $sn);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="p-4 bg-light">
    <div class="mb-3">
        <h5 class="m-0 text-dark">Serial Number: <span class="text-primary"><?= h($sn) ?></span></h5>
    </div>

    <div class="timeline-wrapper">
        <?php 
        $count = $result->num_rows;
        while($row = $result->fetch_assoc()): 
        ?>
        <div class="timeline-item d-flex mb-4">
            <div class="timeline-badge-wrapper text-center me-3">
                <div class="badge-circle bg-main text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <?= $count-- ?>
                </div>
                <div class="timeline-line h-100 border-start border-2 ms-auto me-auto mt-2"></div>
            </div>
            <div class="card flex-grow-1 border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between mb-2">
                    <span class="small fw-bold text-muted"><i class="bi bi-calendar-check"></i> วันที่แจ้ง: <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></span>
                    <?= getStatusBadge($row['status']) ?>
                </div>
                <div class="mb-2"><strong>อาการที่:</strong> <span class="text-danger"><?= h($row['fault_name']) ?></span></div>
                <div class="mb-2 small"><strong>รายละเอียด:</strong> <?= nl2br(h($row['description'] ?: '-')) ?></div>
                <div class="mt-2 pt-2 border-top x-small text-muted">
                    <div class="row">
                        <div class="col-6"><i class="bi bi-person"></i> ช่าง: <?= h($row['tech_name'] ?: 'ยังไม่มีคนรับงาน') ?></div>
                        <div class="col-6 text-end"><i class="bi bi-geo-alt"></i> <?= h($row['floor_name']) ?> (<?= h($row['location_name']) ?>)</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<style>
    .bg-main { background-color: #003366; }
    .x-small { font-size: 0.75rem; }
    .timeline-item:last-child .timeline-line { display: none; }
</style>