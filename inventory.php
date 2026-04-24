<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/db.php';

$pageTitle = "Inventory";
$activePage = "inventory";
$msg = $_GET['msg'] ?? '';

$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalRFID = (int)$pdo->query("SELECT COUNT(*) FROM rfid_tags")->fetchColumn();
$totalZones = (int)$pdo->query("SELECT COUNT(*) FROM zones")->fetchColumn();
$totalShelves = (int)$pdo->query("SELECT COUNT(*) FROM shelves")->fetchColumn();

$sql = "
SELECT
    p.product_id,
    p.product_name,
    p.sku,
    p.price,
    p.reorder_point,
    COALESCE(ps.current_qty, 0) AS current_qty,
    rt.rfid_code,
    rt.status,
    s.shelf_code,
    s.shelf_capacity,
    z.zone_code
FROM products p
LEFT JOIN product_stock ps ON p.product_id = ps.product_id
LEFT JOIN rfid_tags rt ON p.product_id = rt.product_id
LEFT JOIN shelves s ON rt.shelf_id = s.shelf_id
LEFT JOIN zones z ON s.zone_id = z.zone_id
ORDER BY p.product_id ASC
";
$products = $pdo->query($sql)->fetchAll();

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Inventory</h1>
<div class="page-sub">
  ตรวจสอบข้อมูลสินค้า SKU ราคา Shelf RFID และสถานะสินค้าจากโครงสร้าง Zone / Shelf
</div>

<?php if ($msg === 'add_success'): ?>
  <div class="alert alert-success">เพิ่มสินค้าเรียบร้อยแล้ว</div>
<?php elseif ($msg === 'edit_success'): ?>
  <div class="alert alert-success">แก้ไขสินค้าเรียบร้อยแล้ว</div>
<?php elseif ($msg === 'delete_success'): ?>
  <div class="alert alert-success">ลบสินค้าเรียบร้อยแล้ว</div>
<?php endif; ?>

<div class="summary-grid">
  <div class="card summary-card">
    <div class="label">All Products</div>
    <div class="value"><?php echo $totalProducts; ?></div>
    <div class="note">จำนวนสินค้าทั้งหมดในระบบ</div>
  </div>

  <div class="card summary-card">
    <div class="label">RFID Tags</div>
    <div class="value"><?php echo $totalRFID; ?></div>
    <div class="note">แท็ก RFID ที่เชื่อมกับสินค้า</div>
  </div>

  <div class="card summary-card" style="background:#fff8ef;">
    <div class="label" style="color:#b07a1f;">Zones</div>
    <div class="value" style="color:#d78b1a;"><?php echo $totalZones; ?></div>
    <div class="note" style="color:#b07a1f;">จำนวนโซนหลักในคลัง</div>
  </div>

  <div class="card summary-card" style="background:#eef4ff;">
    <div class="label" style="color:#356fd0;">Shelves</div>
    <div class="value" style="color:#1f4e98;"><?php echo $totalShelves; ?></div>
    <div class="note" style="color:#356fd0;">จำนวน shelf ที่ใช้งานอยู่</div>
  </div>
</div>

<div class="card" style="overflow:hidden;">
  <div style="padding:18px 20px;border-bottom:1px solid #e8eef5;background:#fbfcff;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
    <h3 style="margin:0;font-size:20px;color:#17324f;">Product / RFID List</h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
      <div class="search-box">
        <input id="searchInput" placeholder="Search product, SKU, RFID, zone, shelf">
      </div>
      <a href="products_add.php" class="btn-primary">+ Add Product</a>
    </div>
  </div>

  <div class="table-wrap">
    <table id="inventoryTable">
      <thead>
        <tr>
          <th style="width:120px;">Actions</th>
          <th>Product</th>
          <th>SKU</th>
          <th>RFID Tag</th>
          <th>Zone</th>
          <th>Shelf</th>
          <th>Shelf Capacity</th>
          <th>Current Qty</th>
          <th>Price</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $row): ?>
          <tr>
            <td>
              <a href="products_edit.php?id=<?php echo (int)$row['product_id']; ?>" style="display:inline-block;padding:8px 10px;border-radius:10px;background:#eef4ff;color:#1f4e98;font-weight:700;margin-right:6px;">Edit</a>
              <a href="products_delete.php?id=<?php echo (int)$row['product_id']; ?>" onclick="return confirm('ยืนยันการลบสินค้านี้?')" style="display:inline-block;padding:8px 10px;border-radius:10px;background:#fff1f1;color:#c0392b;font-weight:700;">Delete</a>
            </td>
            <td>
              <div style="font-weight:700;color:#17324f;"><?php echo htmlspecialchars($row['product_name']); ?></div>
              <div style="font-size:13px;color:#7b8ea3;">Reorder Point: <?php echo (int)$row['reorder_point']; ?></div>
            </td>
            <td><?php echo htmlspecialchars($row['sku']); ?></td>
            <td><?php echo htmlspecialchars($row['rfid_code'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($row['zone_code'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($row['shelf_code'] ?? '-'); ?></td>
            <td><?php echo isset($row['shelf_capacity']) ? (int)$row['shelf_capacity'] : '-'; ?></td>
            <td><?php echo (int)$row['current_qty']; ?></td>
            <td>฿<?php echo number_format((float)$row['price'], 2); ?></td>
            <td>
              <?php
                $badgeClass = 'ok';
                if (($row['status'] ?? '') === 'Moving') $badgeClass = 'warn';
                if (($row['status'] ?? '') === 'Shipped') $badgeClass = 'danger';
              ?>
              <span class="badge <?php echo $badgeClass; ?>">
                <?php echo htmlspecialchars($row['status'] ?? '-'); ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
tableSearch('searchInput', 'inventoryTable');
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>