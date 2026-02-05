<?php
// --- ตั้งค่าเชื่อมต่อ DB ---
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'tax_db'; // เปลี่ยนตามชื่อ DB จริงของคุณ

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Connection failed']);
    exit;
}