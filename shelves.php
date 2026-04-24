<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/db.php';

$pageTitle = "Shelves";
$activePage = "shelves";
$msg = $_GET['msg'] ?? '';

$shelves = $pdo->query("
    SELECT
        s.shelf_id,
        s.shelf_code,
        s.shelf_capacity,
        z.zone_code,
        z.zone_name,
        COALESCE(SUM(ps.current_qty), 0) AS used_qty,
        s.shelf_capacity - COALESCE(SUM(ps.current_qty), 0) AS remaining_qty
    FROM shelves s
    JOIN zones z ON s.zone_id = z.zone_id
    LEFT JOIN rfid_tags rt ON rt.shelf_id = s.shelf_id
    LEFT JOIN product_stock ps ON ps.product_id = rt.product_id
    GROUP BY s.shelf_id, s.shelf_code, s.shelf_capacity, z.zone_code, z.zone_name
    ORDER BY z.zone_code, s.shelf_code
")->fetchAll();

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Shelves</h1>
<div class="page-sub">จัดการ Shelf ภายในแต่ละ Zone และดูความจุที่ใช้จริง</div>

<div class="card" style="overflow:hidden;">
  <div style="padding:18px 20px;border-bottom:1px solid #e8eef5;background:#fbfcff;display:flex;justify-content:space-between;align-items:center;">
    <h3 style="margin:0;font-size:20px;color:#17324f;">Shelf List</h3>
    <a href="shelves_add.php" class="btn-primary">+ Add Shelf</a>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Actions</th>
          <th>Zone</th>
          <th>Zone Name</th>
          <th>Shelf Code</th>
          <th>Shelf Capacity</th>
          <th>Used Qty</th>
          <th>Remaining Qty</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($shelves as $row): ?>
          <?php
            $isOver = (int)$row['used_qty'] > (int)$row['shelf_capacity'];
          ?>
          <tr>
            <td>
              <a href="shelves_edit.php?id=<?php echo (int)$row['shelf_id']; ?>" style="display:inline-block;padding:8px 10px;border-radius:10px;background:#eef4ff;color:#1f4e98;font-weight:700;margin-right:6px;">Edit</a>
              <a href="shelves_delete.php?id=<?php echo (int)$row['shelf_id']; ?>" style="display:inline-block;padding:8px 10px;border-radius:10px;background:#fff1f1;color:#c0392b;font-weight:700;">Delete</a>
            </td>
            <td><?php echo htmlspecialchars($row['zone_code']); ?></td>
            <td><?php echo htmlspecialchars($row['zone_name']); ?></td>
            <td><?php echo htmlspecialchars($row['shelf_code']); ?></td>
            <td><?php echo (int)$row['shelf_capacity']; ?></td>
            <td><?php echo (int)$row['used_qty']; ?></td>
            <td><?php echo (int)$row['remaining_qty']; ?></td>
            <td>
              <?php if ($isOver): ?>
                <span class="badge danger">Over Capacity</span>
              <?php else: ?>
                <span class="badge ok">Normal</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>