<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/db.php';

$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) {
    header("Location: inventory.php");
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM stock_logs WHERE product_id = ?");
    $stmt->execute([$product_id]);

    $stmt = $pdo->prepare("DELETE FROM stock_transactions WHERE product_id = ?");
    $stmt->execute([$product_id]);

    $stmt = $pdo->prepare("DELETE FROM product_stock WHERE product_id = ?");
    $stmt->execute([$product_id]);

    $stmt = $pdo->prepare("DELETE FROM rfid_tags WHERE product_id = ?");
    $stmt->execute([$product_id]);

    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);

    $pdo->commit();
    header("Location: inventory.php?msg=delete_success");
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header("Location: inventory.php?msg=form_error");
    exit;
}
