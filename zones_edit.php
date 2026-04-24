<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/db.php';

$pageTitle = "Edit Zone";
$activePage = "zones";

$zone_id = (int)($_GET['id'] ?? $_POST['zone_id'] ?? 0);
if ($zone_id <= 0) {
    header("Location: zones.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $zone_code = trim($_POST['zone_code'] ?? '');
    $zone_name = trim($_POST['zone_name'] ?? '');
    $total_capacity = (int)($_POST['total_capacity'] ?? 0);

    if ($zone_code === '' || $zone_name === '' || $total_capacity <= 0) {
        header("Location: zones_edit.php?id={$zone_id}&msg=form_error");
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(shelf_capacity), 0) AS used_capacity
        FROM shelves
        WHERE zone_id = ?
    ");
    $stmt->execute([$zone_id]);
    $used_capacity = (int)$stmt->fetchColumn();

    if ($total_capacity < $used_capacity) {
        header("Location: zones_edit.php?id={$zone_id}&msg=capacity_error");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE zones
            SET zone_code = ?, zone_name = ?, total_capacity = ?
            WHERE zone_id = ?
        ");
        $stmt->execute([$zone_code, $zone_name, $total_capacity, $zone_id]);

        header("Location: zones.php?msg=edit_success");
        exit;
    } catch (Throwable $e) {
        header("Location: zones_edit.php?id={$zone_id}&msg=form_error");
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT zone_id, zone_code, zone_name, total_capacity
    FROM zones
    WHERE zone_id = ?
    LIMIT 1
");
$stmt->execute([$zone_id]);
$zone = $stmt->fetch();

if (!$zone) {
    header("Location: zones.php");
    exit;
}

$msg = $_GET['msg'] ?? '';

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Edit Zone</h1>
<div class="page-sub">แก้ไขโซน</div>

<?php if ($msg === 'form_error'): ?>
  <div class="alert alert-error">กรอกข้อมูลให้ครบ หรืออาจมี Zone Code ซ้ำ</div>
<?php elseif ($msg === 'capacity_error'): ?>
  <div class="alert alert-error">Total Capacity ต้องไม่น้อยกว่าความจุ shelf ที่มีอยู่</div>
<?php endif; ?>

<div class="card" style="padding:22px;max-width:800px;">
  <form method="post">
    <input type="hidden" name="zone_id" value="<?php echo (int)$zone['zone_id']; ?>">

    <div class="form-grid">
      <div>
        <label class="form-label">Zone Code</label>
        <input name="zone_code" class="form-control" value="<?php echo htmlspecialchars($zone['zone_code']); ?>" required>
      </div>

      <div>
        <label class="form-label">Zone Name</label>
        <input name="zone_name" class="form-control" value="<?php echo htmlspecialchars($zone['zone_name']); ?>" required>
      </div>

      <div>
        <label class="form-label">Total Capacity</label>
        <input type="number" name="total_capacity" class="form-control" value="<?php echo (int)$zone['total_capacity']; ?>" required>
      </div>
    </div>

    <div class="mt-20" style="display:flex;gap:12px;flex-wrap:wrap;">
      <button type="submit" class="btn-primary">บันทึกการแก้ไข</button>
      <a href="zones.php" class="btn-light">กลับไป Zones</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
