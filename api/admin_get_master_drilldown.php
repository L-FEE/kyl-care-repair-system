<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit;

$type = $_GET['type'] ?? 'category';
$parent_id = $_GET['parent_id'] ?? 0;

if ($type == 'category') {
    $sql = "SELECT * FROM device_categories ORDER BY category_name ASC";
    $res = $conn->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
} 

elseif ($type == 'device') {
    $stmt = $conn->prepare("SELECT * FROM devices WHERE category_id = ? ORDER BY device_name ASC");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

elseif ($type == 'fault') {
    $stmt = $conn->prepare("SELECT * FROM faults WHERE device_id = ? ORDER BY fault_name ASC");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}