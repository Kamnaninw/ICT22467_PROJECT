<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/db.php';

$pageTitle = "Edit Product";
$activePage = "inventory";

$shelves = $pdo->query("
    SELECT
        s.shelf_id,
        s.shelf_code,
        s.shelf_capacity,
        z.zone_code,
        z.zone_name
    FROM shelves s
    JOIN zones z ON s.zone_id = z.zone_id
    ORDER BY z.zone_code, s.shelf_code
")->fetchAll();

$product_id = (int)($_GET['id'] ?? $_POST['product_id'] ?? 0);
if ($product_id <= 0) {
    header("Location: inventory.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name  = trim($_POST['product_name'] ?? '');
    $sku           = trim($_POST['sku'] ?? '');
    $price         = trim($_POST['price'] ?? '');
    $current_qty   = (int)($_POST['current_qty'] ?? 0);
    $reorder_point = (int)($_POST['reorder_point'] ?? 0);
    $rfid_code     = trim($_POST['rfid_code'] ?? '');
    $shelf_id      = (int)($_POST['shelf_id'] ?? 0);
    $status        = trim($_POST['status'] ?? 'In-Stock');

    if ($product_name === '' || $sku === '' || $price === '' || $rfid_code === '' || $shelf_id <= 0) {
        header("Location: products_edit.php?id={$product_id}&msg=form_error");
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE products
            SET sku = ?, product_name = ?, reorder_point = ?, price = ?
            WHERE product_id = ?
        ");
        $stmt->execute([$sku, $product_name, $reorder_point, $price, $product_id]);

        $stmt = $pdo->prepare("
            UPDATE product_stock
            SET current_qty = ?
            WHERE product_id = ?
        ");
        $stmt->execute([$current_qty, $product_id]);

        $stmt = $pdo->prepare("
            UPDATE rfid_tags
            SET rfid_code = ?, shelf_id = ?, status = ?
            WHERE product_id = ?
        ");
        $stmt->execute([$rfid_code, $shelf_id, $status, $product_id]);

        $pdo->commit();
        header("Location: inventory.php?msg=edit_success");
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: products_edit.php?id={$product_id}&msg=form_error");
        exit;
    }
}

$sql = "
SELECT
    p.product_id,
    p.sku,
    p.product_name,
    p.reorder_point,
    p.price,
    COALESCE(ps.current_qty, 0) AS current_qty,
    rt.rfid_code,
    rt.status,
    rt.shelf_id
FROM products p
LEFT JOIN product_stock ps ON p.product_id = ps.product_id
LEFT JOIN rfid_tags rt ON p.product_id = rt.product_id
WHERE p.product_id = ?
LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: inventory.php");
    exit;
}

$msg = $_GET['msg'] ?? '';

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Edit Product</h1>
<div class="page-sub">แก้ไขข้อมูลสินค้า SKU, RFID, จำนวนคงเหลือ และเลือกตำแหน่งจัดเก็บจาก Shelf ที่มีอยู่ในระบบ</div>

<?php if ($msg === 'form_error'): ?>
  <div class="alert alert-error">กรุณากรอกข้อมูลให้ครบ หรือข้อมูลอาจซ้ำในระบบ</div>
<?php endif; ?>

<div class="card" style="padding:22px;max-width:800px;">
  <form method="post">
    <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">

    <div class="form-grid">
      <div>
        <label class="form-label">ชื่อสินค้า</label>
        <input type="text" name="product_name" class="form-control" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
      </div>

      <div>
        <label class="form-label">SKU</label>
        <input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($product['sku']); ?>" required>
      </div>

      <div>
        <label class="form-label">ราคา</label>
        <input type="number" step="0.01" name="price" class="form-control" value="<?php echo htmlspecialchars($product['price']); ?>" required>
      </div>

      <div>
        <label class="form-label">Current Qty</label>
        <input type="number" name="current_qty" class="form-control" value="<?php echo (int)$product['current_qty']; ?>" required>
      </div>

      <div>
        <label class="form-label">Reorder Point</label>
        <input type="number" name="reorder_point" class="form-control" value="<?php echo (int)$product['reorder_point']; ?>" required>
      </div>

      <div>
        <label class="form-label">RFID Code</label>
        <input type="text" name="rfid_code" class="form-control" value="<?php echo htmlspecialchars($product['rfid_code'] ?? ''); ?>" required>
      </div>

      <div>
        <label class="form-label">Shelf / Location</label>
        <select name="shelf_id" class="form-control" required>
          <option value="">-- เลือก Shelf --</option>
          <?php foreach ($shelves as $shelf): ?>
            <option value="<?php echo (int)$shelf['shelf_id']; ?>" <?php echo ((int)$product['shelf_id'] === (int)$shelf['shelf_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($shelf['zone_code'] . ' / ' . $shelf['shelf_code'] . ' (Capacity: ' . $shelf['shelf_capacity'] . ')'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="In-Stock" <?php echo (($product['status'] ?? '') === 'In-Stock') ? 'selected' : ''; ?>>In-Stock</option>
          <option value="Moving" <?php echo (($product['status'] ?? '') === 'Moving') ? 'selected' : ''; ?>>Moving</option>
          <option value="Shipped" <?php echo (($product['status'] ?? '') === 'Shipped') ? 'selected' : ''; ?>>Shipped</option>
        </select>
      </div>
    </div>

    <div class="mt-20" style="display:flex;gap:12px;flex-wrap:wrap;">
      <button type="submit" class="btn-primary">บันทึกการแก้ไข</button>
      <a href="inventory.php" class="btn-light">กลับไป Inventory</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>