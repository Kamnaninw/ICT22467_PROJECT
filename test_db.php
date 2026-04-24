<?php
require_once __DIR__ . '/config/db.php';

echo "<h2>เชื่อมฐานข้อมูลสำเร็จ</h2>";

$stmt = $pdo->query("SELECT NOW() AS current_time");
$row = $stmt->fetch();

echo "<p>เวลาในฐานข้อมูล: " . htmlspecialchars($row['current_time']) . "</p>";
?>
