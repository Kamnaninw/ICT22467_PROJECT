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

    $stmt = $pdo->prepare("
        SELECT
            p.product_id,
            rt.rfid_id,
            rt.shelf_id,
            s.shelf_capacity,
            COALESCE(ps.current_qty, 0) AS current_qty
        FROM products p
        JOIN rfid_tags rt ON p.product_id = rt.product_id
        JOIN shelves s ON rt.shelf_id = s.shelf_id
        LEFT JOIN product_stock ps ON p.product_id = ps.product_id
        WHERE p.product_id = ?
        LIMIT 1
    ");
    $stmt->execute([$product_id]);
    $item = $stmt->fetch();

    if (!$item) {
        throw new Exception("Product / RFID / Shelf not found");
    }

    $qty_before = (int)$item['current_qty'];
    $qty_after = $qty_before + $quantity;
    $shelf_capacity = (int)$item['shelf_capacity'];

    if ($qty_after > $shelf_capacity) {
        $pdo->rollBack();
        header("Location: transactions.php?msg=capacity_error");
        exit;
    }

    $stmt = $pdo->prepare("SELECT current_qty FROM product_stock WHERE product_id = ? FOR UPDATE");
    $stmt->execute([$product_id]);
    $stock = $stmt->fetch();

    if ($stock) {
        $stmt = $pdo->prepare("UPDATE product_stock SET current_qty = ? WHERE product_id = ?");
        $stmt->execute([$qty_after, $product_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO product_stock (product_id, current_qty) VALUES (?, ?)");
        $stmt->execute([$product_id, $qty_after]);
    }

    $stmt = $pdo->prepare("UPDATE rfid_tags SET status = 'In-Stock' WHERE rfid_id = ?");
    $stmt->execute([$item['rfid_id']]);

    $stmt = $pdo->prepare("
        INSERT INTO stock_transactions
        (transaction_type, document_no, product_id, rfid_id, shelf_id, quantity, employee_id, note)
        VALUES ('IN', ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $document_no,
        $product_id,
        $item['rfid_id'],
        $item['shelf_id'],
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
        $quantity,
        $qty_after
    ]);

    $pdo->commit();
    header("Location: transactions.php?msg=buy_success");
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die($e->getMessage());
}
