<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/db.php';

$product_id  = (int)($_POST['product_id'] ?? 0);
$rfid_code   = trim($_POST['rfid_code'] ?? '');
$employee_id = (int)($_POST['employee_id'] ?? 0);
$document_no = trim($_POST['document_no'] ?? '');
$quantity    = (int)($_POST['quantity'] ?? 0);
$note        = trim($_POST['note'] ?? '');

if (($product_id <= 0 && $rfid_code === '') || $employee_id <= 0 || $document_no === '' || $quantity <= 0) {
    header("Location: transactions.php?msg=form_error");
    exit;
}

try {
    $pdo->beginTransaction();

    if ($product_id <= 0 && $rfid_code !== '') {
        $stmt = $pdo->prepare("
            SELECT product_id
            FROM rfid_tags
            WHERE rfid_code = ?
            LIMIT 1
        ");
        $stmt->execute([$rfid_code]);
        $product_id = (int)$stmt->fetchColumn();
    }

    if ($product_id <= 0) {
        $pdo->rollBack();
        header("Location: transactions.php?msg=rfid_error");
        exit;
    }

    $stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Product not found");
    }

    $stmt = $pdo->prepare("SELECT rfid_id, shelf_id FROM rfid_tags WHERE product_id = ? LIMIT 1");
    $stmt->execute([$product_id]);
    $rfid = $stmt->fetch();

    if (!$rfid || empty($rfid['shelf_id'])) {
        throw new Exception("RFID or shelf not found for this product");
    }

    $stmt = $pdo->prepare("SELECT current_qty FROM product_stock WHERE product_id = ? FOR UPDATE");
    $stmt->execute([$product_id]);
    $stock = $stmt->fetch();

    if (!$stock) {
        throw new Exception("Stock not found");
    }

    $qty_before = (int)$stock['current_qty'];

    if ($qty_before < $quantity) {
        $pdo->rollBack();
        header("Location: transactions.php?msg=stock_error");
        exit;
    }

    $qty_after = $qty_before - $quantity;

    $stmt = $pdo->prepare("UPDATE product_stock SET current_qty = ? WHERE product_id = ?");
    $stmt->execute([$qty_after, $product_id]);

    $new_status = $qty_after > 0 ? 'Moving' : 'Shipped';

    $stmt = $pdo->prepare("UPDATE rfid_tags SET status = ? WHERE rfid_id = ?");
    $stmt->execute([$new_status, $rfid['rfid_id']]);

    $stmt = $pdo->prepare("
        INSERT INTO stock_transactions
        (transaction_type, document_no, product_id, rfid_id, shelf_id, quantity, employee_id, note)
        VALUES ('OUT', ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $document_no,
        $product_id,
        $rfid['rfid_id'],
        $rfid['shelf_id'],
        $quantity,
        $employee_id,
        $note
    ]);

    $transaction_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO stock_logs (product_id, transaction_id, qty_before, qty_change, qty_after)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $product_id,
        $transaction_id,
        $qty_before,
        -$quantity,
        $qty_after
    ]);

    $pdo->commit();
    header("Location: transactions.php?msg=sell_success");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die($e->getMessage());
}
