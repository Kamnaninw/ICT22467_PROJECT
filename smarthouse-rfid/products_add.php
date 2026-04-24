<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/db.php';

$pageTitle = "Add Product";
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
        header("Location: products_add.php?msg=form_error");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM rfid_tags
            WHERE rfid_code = ?
        ");
        $stmt->execute([$rfid_code]);
        $rfidExists = (int)$stmt->fetchColumn();

        if ($rfidExists > 0) {
            header("Location: products_add.php?msg=rfid_duplicate");
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO products (sku, product_name, reorder_point, price)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$sku, $product_name, $reorder_point, $price]);
        $product_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO product_stock (product_id, current_qty)
            VALUES (?, ?)
        ");
        $stmt->execute([$product_id, $current_qty]);

        $stmt = $pdo->prepare("
            INSERT INTO rfid_tags (rfid_code, product_id, shelf_id, status)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$rfid_code, $product_id, $shelf_id, $status]);

        $pdo->commit();
        header("Location: inventory.php?msg=add_success");
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: products_add.php?msg=form_error");
        exit;
    }
}

$msg = $_GET['msg'] ?? '';
$totalShelves = count($shelves);

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<style>
.product-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(280px, .85fr);
  gap: 20px;
  align-items: start;
}
.product-form-card {
  padding: 24px;
}
.rfid-main {
  border: 1px solid #d9e7fb;
  border-radius: 22px;
  padding: 22px;
  background: linear-gradient(180deg, #f7fbff, #eef4ff);
}
.rfid-main-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) 220px;
  gap: 18px;
  align-items: start;
}
.rfid-input {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: .02em;
  padding: 16px 18px;
  border-radius: 16px;
  background: #fff;
}
.rfid-side {
  display: grid;
  gap: 10px;
}
.mini-box {
  border: 1px solid #dce8f8;
  border-radius: 16px;
  background: rgba(255,255,255,.82);
  padding: 14px 16px;
}
.mini-box strong {
  display: block;
  font-size: 22px;
  color: #1f4e98;
  margin-top: 6px;
}
.short-note {
  margin-top: 10px;
  color: #5e738f;
  font-size: 14px;
  line-height: 1.7;
}
.chip-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 12px;
}
.chip {
  display: inline-flex;
  align-items: center;
  padding: 8px 12px;
  border-radius: 999px;
  background: #fff;
  border: 1px solid #d7e4f7;
  color: #1f4e98;
  font-size: 13px;
  font-weight: 700;
}
.group-box {
  border: 1px solid #e6edf6;
  border-radius: 18px;
  padding: 18px;
  background: linear-gradient(180deg, #ffffff, #fbfdff);
}
.group-box + .group-box {
  margin-top: 16px;
}
.group-tag {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .05em;
  text-transform: uppercase;
  color: #6f839b;
  margin-bottom: 6px;
}
.group-title {
  margin: 0 0 14px;
  color: #17324f;
  font-size: 22px;
}
.side-grid {
  display: grid;
  gap: 16px;
}
.side-card {
  padding: 20px;
}
.info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}
.info-box {
  border: 1px solid #e4ecf6;
  border-radius: 16px;
  padding: 14px;
  background: #fff;
}
.info-box strong {
  display: block;
  font-size: 24px;
  color: #1f4e98;
  margin-top: 6px;
}
.hint {
  margin-top: 8px;
  color: #6f839b;
  font-size: 13px;
  line-height: 1.6;
}
.status-pill {
  display: inline-flex;
  align-items: center;
  padding: 8px 12px;
  border-radius: 999px;
  background: #eef4ff;
  color: #1f4e98;
  font-weight: 700;
  font-size: 13px;
}
.tip-list {
  margin: 0;
  padding-left: 18px;
  color: #5f738b;
  line-height: 1.8;
}
@media (max-width: 1100px) {
  .product-layout,
  .rfid-main-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<h1 class="page-title">Add Product</h1>
<div class="page-sub">เพิ่มสินค้าใหม่และผูกแท็ก</div>

<?php if ($msg === 'form_error'): ?>
  <div class="alert alert-error">กรอกข้อมูลให้ครบ</div>
<?php elseif ($msg === 'rfid_duplicate'): ?>
  <div class="alert alert-error">รหัสแท็กนี้มีอยู่แล้ว</div>
<?php endif; ?>

<div class="product-layout">
  <div class="card product-form-card">
    <form method="post">
      <div class="rfid-main">
        <div class="group-tag">Tag First</div>
        <h3 class="group-title">กำหนดรหัส Tag</h3>

        <div class="rfid-main-grid">
          <div>
            <label class="form-label">รหัสแท็ก RFID</label>
            <input type="text" name="rfid_code" class="form-control rfid-input" placeholder="เช่น RFID-BX-001" required autofocus>
            <div class="short-note">ใช้เป็นรหัสหลักของสินค้าในระบบ</div>
            <div class="chip-row">
              <span class="chip">1 แท็ก ต่อ 1 สินค้า</span>
              <span class="chip">ตรวจรหัสซ้ำก่อนบันทึก</span>
            </div>
          </div>

          <div class="rfid-side">
            <div class="mini-box">
              <div class="label">ชั้นที่พร้อมใช้งาน</div>
              <strong><?php echo $totalShelves; ?></strong>
            </div>
            <div class="mini-box">
              <div class="label">สถานะเริ่มต้น</div>
              <strong style="font-size:18px;">พร้อมเก็บ</strong>
            </div>
          </div>
        </div>
      </div>

      <div class="group-box">
        <div class="group-tag">Product</div>
        <h3 class="group-title">ข้อมูลสินค้า</h3>

        <div class="form-grid">
          <div>
            <label class="form-label">ชื่อสินค้า</label>
            <input type="text" name="product_name" class="form-control"  required>
          </div>

          <div>
            <label class="form-label">รหัสสินค้า</label>
            <input type="text" name="sku" class="form-control" required>
          </div>

          <div>
            <label class="form-label">สถานะเริ่มต้น</label>
            <select name="status" class="form-control">
              <option value="In-Stock">In-Stock</option>
              <option value="Moving">Moving</option>
              <option value="Shipped">Shipped</option>
            </select>
            <div class="hint">แนะนำ: <span class="status-pill">พร้อมเก็บ</span></div>
          </div>
        </div>
      </div>

      <div class="group-box">
        <h3 class="group-title">Stock</h3>

        <div class="form-grid">
          <div>
            <label class="form-label">ราคา</label>
            <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
          </div>

          <div>
            <label class="form-label">จำนวนเริ่มต้น</label>
            <input type="number" name="current_qty" value="0" class="form-control" required>
          </div>

          <div>
            <label class="form-label">Reorder Point</label>
            <input type="number" name="reorder_point" value="0" class="form-control" required>
          </div>
        </div>
      </div>

      <div class="group-box">
        <div class="group-tag">Location</div>
        <h3 class="group-title">ตำแหน่งจัดเก็บ</h3>

        <div>
          <label class="form-label">ชั้นจัดเก็บ</label>
          <select name="shelf_id" class="form-control" required>
            <option value="">-- เลือกชั้นจัดเก็บ --</option>
            <?php foreach ($shelves as $shelf): ?>
              <option value="<?php echo (int)$shelf['shelf_id']; ?>">
                <?php echo htmlspecialchars($shelf['zone_code'] . ' / ' . $shelf['shelf_code'] . ' (Capacity: ' . $shelf['shelf_capacity'] . ')'); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="hint">เลือกตำแหน่งเก็บของสินค้า</div>
        </div>
      </div>

      <div class="mt-20" style="display:flex;gap:12px;flex-wrap:wrap;">
        <button type="submit" class="btn-primary">บันทึกสินค้าใหม่</button>
        <a href="inventory.php" class="btn-light">กลับไป Inventory</a>
      </div>
    </form>
  </div>

  <div class="side-grid">
    <div class="card side-card">
      <h3 class="group-title" style="font-size:20px;">Overview</h3>
      <div class="info-grid">
        <div class="info-box">
          <div class="label">ชั้นพร้อมใช้</div>
          <strong><?php echo $totalShelves; ?></strong>
        </div>
        <div class="info-box">
          <div class="label">รหัสหลัก</div>
          <strong style="font-size:20px;">แท็ก RFID</strong>
        </div>
      </div>
      <div class="hint">ระบบจะใช้แท็กเป็นตัวอ้างอิงหลักของสินค้า</div>
    </div>

    <div class="card side-card">
      <div class="group-tag">Tips</div>
      <h3 class="group-title" style="font-size:20px;">ข้อควรเช็ก</h3>
      <ul class="tip-list">
        <li>ตรวจรหัสแท็กก่อนบันทึก</li>
        <li>รหัสสินค้าและรหัสแท็กไม่ควรซ้ำ</li>
        <li>เลือกชั้นให้ตรงกับจุดเก็บจริง</li>
      </ul>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
