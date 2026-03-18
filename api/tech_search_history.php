<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') exit;

$tech_id = $_SESSION['user_id'];
$month  = $_GET['month']  ?? date('n');
$year   = $_GET['year']   ?? date('Y');
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where_clauses = [
    "r.technician_id = $tech_id",
    "r.status IN ('completed', 'cannot_repair')",
    "YEAR(r.created_at) = $year"
];

if ($month !== 'all') {
    $where_clauses[] = "MONTH(r.created_at) = $month";
}

if ($status !== 'all') {
    $where_clauses[] = "r.status = '$status'";
}

if ($search !== "") {
    $safe_search = $conn->real_escape_string($search);
    $where_clauses[] = "(r.request_code LIKE '%$safe_search%' OR r.device_name LIKE '%$safe_search%' OR r.serial_number LIKE '%$safe_search%')";
}

$where_sql = "WHERE " . implode(' AND ', $where_clauses);

// ดึงข้อมูลและ Repeat Count
$sql = "SELECT r.*,
        (SELECT COUNT(*) FROM repair_requests WHERE serial_number = r.serial_number AND serial_number IS NOT NULL AND serial_number != 'ไม่มีหมายเลขซีเรียล' AND serial_number != '') as repeat_count
        FROM repair_requests r
        $where_sql
        ORDER BY r.finished_at DESC";

$res = $conn->query($sql);

if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        ?>
        <tr>
            <td class="ps-4">
                <strong><?= $row['request_code'] ?></strong>
                <?php if ($row['repeat_count'] > 1): ?>
                    <br><span class="badge bg-danger-subtle text-danger" style="font-size: 0.65rem;">
                        <i class="bi bi-exclamation-triangle"></i> ซ่อมซ้ำ <?= $row['repeat_count'] ?> ครั้ง
                    </span>
                <?php endif; ?>
            </td>
            <td>
                <div class="small fw-bold"><?= h($row['reporter_name']) ?></div>
                <div class="small text-muted"><?= h($row['location_name']) ?></div>
            </td>
            <td>
                <div class="text-primary fw-bold small text-truncate" style="max-width: 150px;"><?= h($row['device_name']) ?></div>
                <div class="text-muted" style="font-size: 0.75rem;">S/N: <?= h($row['serial_number'] ?: '-') ?></div>
            </td>
            <td class="text-center"><?= getStatusBadge($row['status']) ?></td>
            <td class="small"><?= date('d/m/Y', strtotime($row['finished_at'])) ?></td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary" style="border-radius: 8px;" onclick="viewJobHistory(<?= $row['id'] ?>)">
                    <i class="bi bi-search">รายอะเอียด</i>
                </button>
            </td>
        </tr>
        <?php
    }
} else {
    echo "<tr><td colspan='6' class='text-center py-5 text-muted'>ไม่พบข้อมูลการค้นหาในเงื่อนไขที่กำหนด</td></tr>";
}
?>