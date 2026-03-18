<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit;

$action = $_POST['action'] ?? '';
$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? 0;
$name = trim($_POST['name'] ?? '');
$parent_id = $_POST['parent_id'] ?? 0;

$table = [
    'floor' => ['t' => 'floors', 'c' => 'floor_name'],
    'location' => ['t' => 'locations', 'c' => 'location_name', 'p' => 'floor_id'],
    'category' => ['t' => 'device_categories', 'c' => 'category_name'],
    'device' => ['t' => 'devices', 'c' => 'device_name', 'p' => 'category_id']
];

$t = $table[$type]['t'];
$c = $table[$type]['c'];

if ($action === 'add') {
    if (isset($table[$type]['p'])) {
        $p_col = $table[$type]['p'];
        $stmt = $conn->prepare("INSERT INTO $t ($c, $p_col) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $parent_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO $t ($c) VALUES (?)");
        $stmt->bind_param("s", $name);
    }
    if($stmt->execute()) echo json_encode(['success' => true]);
} elseif ($action === 'edit') {
    $stmt = $conn->prepare("UPDATE $t SET $c = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $id);
    if($stmt->execute()) echo json_encode(['success' => true]);
} elseif ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM $t WHERE id = ?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false, 'error' => 'ไม่สามารถลบได้ เนื่องจากมีการใช้งานข้อมูลนี้อยู่']);
}