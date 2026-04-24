<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/db.php';

$pageTitle = "Zones";
$activePage = "zones";
$msg = $_GET['msg'] ?? '';

$zones = $pdo->query("
    SELECT
        z.zone_id,
        z.zone_code,
        z.zone_name,
        z.total_capacity,
        COALESCE(SUM(ps.current_qty), 0) AS used_qty,
        z.total_capacity - COALESCE(SUM(ps.current_qty), 0) AS remaining_qty
    FROM zones z
    LEFT JOIN shelves s ON s.zone_id = z.zone_id
    LEFT JOIN rfid_tags rt ON rt.shelf_id = s.shelf_id
    LEFT JOIN product_stock ps ON ps.product_id = rt.product_id
    GROUP BY z.zone_id, z.zone_code, z.zone_name, z.total_capacity
    ORDER BY z.zone_code
")->fetchAll();

$shelvesRaw = $pdo->query("
    SELECT
        s.shelf_id,
        s.zone_id,
        s.shelf_code,
        s.shelf_capacity,
        COALESCE(SUM(ps.current_qty), 0) AS used_qty,
        s.shelf_capacity - COALESCE(SUM(ps.current_qty), 0) AS remaining_qty
    FROM shelves s
    LEFT JOIN rfid_tags rt ON rt.shelf_id = s.shelf_id
    LEFT JOIN product_stock ps ON ps.product_id = rt.product_id
    GROUP BY s.shelf_id, s.zone_id, s.shelf_code, s.shelf_capacity
    ORDER BY s.zone_id, s.shelf_code
")->fetchAll();

$shelvesByZone = [];
foreach ($shelvesRaw as $shelf) {
    $shelvesByZone[$shelf['zone_id']][] = $shelf;
}

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Zones</h1>
<div class="page-sub">จัดการโซนหลักของคลัง พร้อม Shelf ภายในแต่ละ Zone และดูความจุที่ใช้จริง</div>

<?php if ($msg === 'add_success'): ?>
  <div class="alert alert-success">เพิ่ม Zone เรียบร้อยแล้ว</div>
<?php elseif ($msg === 'edit_success'): ?>
  <div class="alert alert-success">แก้ไข Zone เรียบร้อยแล้ว</div>
<?php elseif ($msg === 'delete_success'): ?>
  <div class="alert alert-success">ลบ Zone เรียบร้อยแล้ว</div>
<?php elseif ($msg === 'shelf_add_success'): ?>
  <div class="alert alert-success">เพิ่ม Shelf เรียบร้อยแล้ว</div>
<?php elseif ($msg === 'shelf_edit_success'): ?>
  <div class="alert alert-success">แก้ไข Shelf เรียบร้อยแล้ว</div>
<?php elseif ($msg === 'shelf_delete_success'): ?>
  <div class="alert alert-success">ลบ Shelf เรียบร้อยแล้ว</div>
<?php elseif ($msg === 'form_error'): ?>
  <div class="alert alert-error">กรุณาตรวจสอบข้อมูลอีกครั้ง</div>
<?php endif; ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:18px;">
  <a href="zones_add.php" class="btn-primary">+ Add Zone</a>
</div>

<div style="display:grid;gap:22px;">
  <?php foreach ($zones as $zone): ?>
    <div class="card" style="overflow:hidden;">
      <div style="padding:18px 20px;border-bottom:1px solid #e8eef5;background:#fbfcff;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;">
        <div>
          <div style="font-size:24px;font-weight:800;color:#17324f;">
            <?php echo htmlspecialchars($zone['zone_code']); ?> - <?php echo htmlspecialchars($zone['zone_name']); ?>
          </div>
          <div style="font-size:14px;color:#7b8ea3;margin-top:6px;">
            Total: <?php echo (int)$zone['total_capacity']; ?> |
            Used: <?php echo (int)$zone['used_qty']; ?> |
            Remaining: <?php echo (int)$zone['remaining_qty']; ?>
          </div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <a href="shelves_add.php?zone_id=<?php echo (int)$zone['zone_id']; ?>" class="btn-primary">+ Add Shelf</a>
          <a href="zones_edit.php?id=<?php echo (int)$zone['zone_id']; ?>" class="btn-light">Edit Zone</a>
          <a href="zones_delete.php?id=<?php echo (int)$zone['zone_id']; ?>" class="btn-light" onclick="return confirm('ยืนยันการลบ Zone นี้?')">Delete Zone</a>
        </div>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Actions</th>
              <th>Shelf Code</th>
              <th>Shelf Capacity</th>
              <th>Used Qty</th>
              <th>Remaining Qty</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($shelvesByZone[$zone['zone_id']])): ?>
              <?php foreach ($shelvesByZone[$zone['zone_id']] as $shelf): ?>
                <?php $isOver = (int)$shelf['used_qty'] > (int)$shelf['shelf_capacity']; ?>
                <tr>
                  <td>
                    <a href="shelves_edit.php?id=<?php echo (int)$shelf['shelf_id']; ?>" style="display:inline-block;padding:8px 10px;border-radius:10px;background:#eef4ff;color:#1f4e98;font-weight:700;margin-right:6px;">Edit</a>
                    <a href="shelves_delete.php?id=<?php echo (int)$shelf['shelf_id']; ?>" style="display:inline-block;padding:8px 10px;border-radius:10px;background:#fff1f1;color:#c0392b;font-weight:700;" onclick="return confirm('ยืนยันการลบ Shelf นี้?')">Delete</a>
                  </td>
                  <td><?php echo htmlspecialchars($shelf['shelf_code']); ?></td>
                  <td><?php echo (int)$shelf['shelf_capacity']; ?></td>
                  <td><?php echo (int)$shelf['used_qty']; ?></td>
                  <td><?php echo (int)$shelf['remaining_qty']; ?></td>
                  <td>
                    <?php if ($isOver): ?>
                      <span class="badge danger">Over Capacity</span>
                    <?php else: ?>
                      <span class="badge ok">Normal</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" style="text-align:center;color:#7b8ea3;">ยังไม่มี Shelf ใน Zone นี้</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>