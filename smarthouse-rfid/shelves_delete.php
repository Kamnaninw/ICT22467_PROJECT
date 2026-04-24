<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/db.php';

$shelf_id = (int)($_GET['id'] ?? 0);
if ($shelf_id <= 0) {
    header("Location: shelves.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rfid_tags WHERE shelf_id = ?");
    $stmt->execute([$shelf_id]);
    $usedCount = (int)$stmt->fetchColumn();

    if ($usedCount > 0) {
        header("Location: shelves.php?msg=form_error");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM shelves WHERE shelf_id = ?");
    $stmt->execute([$shelf_id]);

    header("Location: shelves.php?msg=delete_success");
    exit;
} catch (Throwable $e) {
    header("Location: shelves.php?msg=form_error");
    exit;
}