<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$name = trim($_GET['reporter_name'] ?? '');

if (!$name) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM repair_requests WHERE reporter_name LIKE ? ORDER BY created_at DESC");
$search = "%$name%";
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $row['status_badge'] = getStatusBadge($row['status']);
    $row['date_formatted'] = date('d/m/Y H:i', strtotime($row['created_at']));
    // ป้องกัน XSS
    $row['reporter_name'] = h($row['reporter_name']);
    $row['device_name'] = h($row['device_name']);
    $row['fault_name'] = h($row['fault_name']);
    $data[] = $row;
}

echo json_encode($data);