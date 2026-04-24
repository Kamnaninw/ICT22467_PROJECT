<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/db.php';

$employee_id = (int)($_GET['id'] ?? 0);
if ($employee_id <= 0) {
    header("Location: employees.php");
    exit;
}

try {
    // กันลบพนักงานที่ถูกใช้งานใน stock_transactions
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_transactions WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $usedCount = (int)$stmt->fetchColumn();

    if ($usedCount > 0) {
        header("Location: employees.php?msg=form_error");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM employees WHERE employee_id = ?");
    $stmt->execute([$employee_id]);

    header("Location: employees.php?msg=delete_success");
    exit;
} catch (Throwable $e) {
    header("Location: employees.php?msg=form_error");
    exit;
}
