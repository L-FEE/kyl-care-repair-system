<?php
/*
|------------------------------------------------------
| File: config/db.php
| Description: Database connection and global settings
|------------------------------------------------------
*/

// ตั้งค่า Timezone ให้เป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "data_kyl_care";

// เชื่อมต่อฐานข้อมูลด้วย MySQLi
$conn = new mysqli($host, $user, $pass, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่าให้รองรับภาษาไทย (UTF-8)
$conn->set_charset("utf8mb4");

// กำหนดค่าคงที่สำหรับเรียกใช้ในโปรเจกต์ (Base URL)
define('BASE_URL', 'http://localhost/kyl_care/');
?>