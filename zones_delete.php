<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/db.php';

$zone_id = (int)($_GET['id'] ?? 0);
if ($zone_id <= 0) {
    header("Location: zones.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM shelves WHERE zone_id = ?");
    $stmt->execute([$zone_id]);
    $shelfCount = (int)$stmt->fetchColumn();

    if ($shelfCount > 0) {
        header("Location: zones.php?msg=form_error");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM zones WHERE zone_id = ?");
    $stmt->execute([$zone_id]);

    header("Location: zones.php?msg=delete_success");
    exit;
} catch (Throwable $e) {
    header("Location: zones.php?msg=form_error");
    exit;
}