<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/db.php';

$pageTitle = "Transaction Detail";
$activePage = "transactions";

$transaction_id = (int)($_GET['id'] ?? 0);
if ($transaction_id <= 0) {
    header("Location: transactions.php");
    exit;
}

$sql = "
SELECT
    st.transaction_id,
    st.transaction_type,
    st.document_no,
    st.quantity,
    st.transaction_datetime,
    st.note,
    p.product_name,
    p.sku,
    p.price,
    COALESCE(ps.current_qty, 0) AS current_qty,
    rt.rfid_code,
    rt.status AS rfid_status,
    sl.zone_code,
    sl.shelf_id,
    sl.capacity,
    e.full_name AS employee_name,
    e.position_name,
    e.email AS employee_email,
    e.phone AS employee_phone
FROM stock_transactions st
JOIN products p ON st.product_id = p.product_id
LEFT JOIN product_stock ps ON p.product_id = ps.product_id
JOIN rfid_tags rt ON st.rfid_id = rt.rfid_id
JOIN storage_locations sl ON st.location_id = sl.location_id
JOIN employees e ON st.employee_id = e.employee_id
WHERE st.transaction_id = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$transaction_id]);
$item = $stmt->fetch();

if (!$item) {
    header("Location: transactions.php");
    exit;
}

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Transaction Detail</h1>
<div class="page-sub">
  รายละเอียดธุรกรรมแต่ละรายการจากฐานข้อมูลจริง ครอบคลุมข้อมูลสินค้า RFID ตำแหน่งจัดเก็บ จำนวน และพนักงานผู้รับผิดชอบ
</div>

<div class="card" style="padding:22px;margin-bottom:22px;">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
    <div>
      <div class="label">Transaction ID</div>
      <div class="value" style="font-size:34px;"><?php echo (int)$item['transaction_id']; ?></div>
    </div>
    <div>
      <?php if ($item['transaction_type'] === 'IN'): ?>
        <span class="badge ok" style="font-size:15px;">BUY / IN</span>
      <?php else: ?>
        <span class="badge warn" style="font-size:15px;">SELL / OUT</span>
      <?php endif; ?>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
  <div class="card" style="padding:20px;">
    <h3 style="margin:0 0 16px;font-size:24px;color:#17324f;">Document Info</h3>
    <div style="display:grid;gap:12px;">
      <div><strong>Document No:</strong> <?php echo htmlspecialchars($item['document_no']); ?></div>
      <div><strong>Date / Time:</strong> <?php echo htmlspecialchars($item['transaction_datetime']); ?></div>
      <div><strong>Quantity:</strong> <?php echo (int)$item['quantity']; ?></div>
      <div><strong>Note:</strong> <?php echo htmlspecialchars($item['note'] ?: '-'); ?></div>
    </div>
  </div>

  <div class="card" style="padding:20px;">
    <h3 style="margin:0 0 16px;font-size:24px;color:#17324f;">Product Info</h3>
    <div style="display:grid;gap:12px;">
      <div><strong>Product Name:</strong> <?php echo htmlspecialchars($item['product_name']); ?></div>
      <div><strong>SKU:</strong> <?php echo htmlspecialchars($item['sku']); ?></div>
      <div><strong>Price:</strong> ฿<?php echo number_format((float)$item['price'], 2); ?></div>
      <div><strong>Current Stock:</strong> <?php echo (int)$item['current_qty']; ?></div>
    </div>
  </div>

  <div class="card" style="padding:20px;">
    <h3 style="margin:0 0 16px;font-size:24px;color:#17324f;">RFID / Location</h3>
    <div style="display:grid;gap:12px;">
      <div><strong>RFID Tag:</strong> <?php echo htmlspecialchars($item['rfid_code']); ?></div>
      <div>
        <strong>Status:</strong>
        <?php
          $badgeClass = 'ok';
          if ($item['rfid_status'] === 'Moving') $badgeClass = 'warn';
          if ($item['rfid_status'] === 'Shipped') $badgeClass = 'danger';
        ?>
        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($item['rfid_status']); ?></span>
      </div>
      <div><strong>Zone:</strong> <?php echo htmlspecialchars($item['zone_code']); ?></div>
      <div><strong>Shelf ID:</strong> <?php echo htmlspecialchars($item['shelf_id']); ?></div>
      <div><strong>Capacity:</strong> <?php echo (int)$item['capacity']; ?></div>
    </div>
  </div>

  <div class="card" style="padding:20px;">
    <h3 style="margin:0 0 16px;font-size:24px;color:#17324f;">Employee Info</h3>
    <div style="display:grid;gap:12px;">
      <div><strong>Name:</strong> <?php echo htmlspecialchars($item['employee_name']); ?></div>
      <div><strong>Position:</strong> <?php echo htmlspecialchars($item['position_name'] ?: '-'); ?></div>
      <div><strong>Email:</strong> <?php echo htmlspecialchars($item['employee_email'] ?: '-'); ?></div>
      <div><strong>Phone:</strong> <?php echo htmlspecialchars($item['employee_phone'] ?: '-'); ?></div>
    </div>
  </div>
</div>

<div style="margin-top:22px;display:flex;gap:12px;flex-wrap:wrap;">
  <a href="transactions.php" class="btn-light">กลับไป Transactions</a>
  <a href="inventory.php" class="btn-light">ไปหน้า Inventory</a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
