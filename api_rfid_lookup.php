<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาเข้าสู่ระบบก่อน'
    ]);
    exit;
}

require_once __DIR__ . '/config/db.php';

$code = trim($_GET['code'] ?? '');

if ($code === '') {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุรหัส RFID'
    ]);
    exit;
}

$sql = "
SELECT
    p.product_id,
    rt.rfid_code,
    rt.status,
    p.product_name,
    p.sku,
    p.price,
    p.reorder_point,
    COALESCE(ps.current_qty, 0) AS current_qty,
    z.zone_code,
    s.shelf_code,
    s.shelf_capacity
FROM rfid_tags rt
JOIN products p ON rt.product_id = p.product_id
LEFT JOIN product_stock ps ON p.product_id = ps.product_id
JOIN shelves s ON rt.shelf_id = s.shelf_id
JOIN zones z ON s.zone_id = z.zone_id
WHERE rt.rfid_code = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$code]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบข้อมูล RFID นี้ในระบบ'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'item' => [
        'product_id' => (int)$item['product_id'],
        'rfid_code' => $item['rfid_code'],
        'status' => $item['status'],
        'product_name' => $item['product_name'],
        'sku' => $item['sku'],
        'price' => $item['price'],
        'reorder_point' => (int)$item['reorder_point'],
        'current_qty' => (int)$item['current_qty'],
        'zone_code' => $item['zone_code'],
        'shelf_code' => $item['shelf_code'],
        'shelf_capacity' => (int)$item['shelf_capacity']
    ]
], JSON_UNESCAPED_UNICODE);
