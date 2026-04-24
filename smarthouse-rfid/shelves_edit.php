<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/db.php';

$pageTitle = "Edit Shelf";
$activePage = "shelves";

$shelf_id = (int)($_GET['id'] ?? $_POST['shelf_id'] ?? 0);
if ($shelf_id <= 0) {
    header("Location: shelves.php");
    exit;
}

$zones = $pdo->query("
    SELECT
        z.zone_id,
        z.zone_code,
        z.zone_name,
        z.total_capacity,
        COALESCE(SUM(s.shelf_capacity), 0) AS used_capacity,
        z.total_capacity - COALESCE(SUM(s.shelf_capacity), 0) AS remaining_capacity
    FROM zones z
    LEFT JOIN shelves s ON z.zone_id = s.zone_id
    GROUP BY z.zone_id, z.zone_code, z.zone_name, z.total_capacity
    ORDER BY z.zone_code
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $zone_id = (int)($_POST['zone_id'] ?? 0);
    $shelf_code = trim($_POST['shelf_code'] ?? '');
    $shelf_capacity = (int)($_POST['shelf_capacity'] ?? 0);

    if ($zone_id <= 0 || $shelf_code === '' || $shelf_capacity <= 0) {
        header("Location: shelves_edit.php?id={$shelf_id}&msg=form_error");
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT
            z.total_capacity,
            COALESCE(SUM(s.shelf_capacity), 0) AS used_capacity
        FROM zones z
        LEFT JOIN shelves s ON z.zone_id = s.zone_id AND s.shelf_id <> ?
        WHERE z.zone_id = ?
        GROUP BY z.zone_id, z.total_capacity
    ");
    $stmt->execute([$shelf_id, $zone_id]);
    $zone = $stmt->fetch();

    if (!$zone) {
        header("Location: shelves_edit.php?id={$shelf_id}&msg=form_error");
        exit;
    }

    $remaining = (int)$zone['total_capacity'] - (int)$zone['used_capacity'];

    if ($shelf_capacity > $remaining) {
        header("Location: shelves_edit.php?id={$shelf_id}&msg=capacity_error");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE shelves
            SET zone_id = ?, shelf_code = ?, shelf_capacity = ?
            WHERE shelf_id = ?
        ");
        $stmt->execute([$zone_id, $shelf_code, $shelf_capacity, $shelf_id]);

        header("Location: shelves.php?msg=edit_success");
        exit;
    } catch (Throwable $e) {
        header("Location: shelves_edit.php?id={$shelf_id}&msg=form_error");
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT shelf_id, zone_id, shelf_code, shelf_capacity
    FROM shelves
    WHERE shelf_id = ?
    LIMIT 1
");
$stmt->execute([$shelf_id]);
$shelf = $stmt->fetch();

if (!$shelf) {
    header("Location: shelves.php");
    exit;
}

$msg = $_GET['msg'] ?? '';

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Edit Shelf</h1>
<div class="page-sub">แก้ไข shelf</div>

<?php if ($msg === 'form_error'): ?>
  <div class="alert alert-error">กรอกข้อมูลให้ครบ หรือ Shelf Code อาจซ้ำ</div>
<?php elseif ($msg === 'capacity_error'): ?>
  <div class="alert alert-error">Shelf Capacity เกินพื้นที่คงเหลือของ Zone</div>
<?php endif; ?>

<div class="card" style="padding:22px;max-width:800px;">
  <form method="post">
    <input type="hidden" name="shelf_id" value="<?php echo (int)$shelf['shelf_id']; ?>">

    <div class="form-grid">
      <div>
        <label class="form-label">Zone</label>
        <select name="zone_id" class="form-control" required>
          <option value="">-- เลือก Zone --</option>
          <?php foreach ($zones as $z): ?>
            <option value="<?php echo (int)$z['zone_id']; ?>" <?php echo ((int)$shelf['zone_id'] === (int)$z['zone_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($z['zone_code'] . ' / ' . $z['zone_name'] . ' (Remaining: ' . $z['remaining_capacity'] . ')'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="form-label">Shelf Code</label>
        <input name="shelf_code" class="form-control" value="<?php echo htmlspecialchars($shelf['shelf_code']); ?>" required>
      </div>

      <div>
        <label class="form-label">Shelf Capacity</label>
        <input type="number" name="shelf_capacity" class="form-control" value="<?php echo (int)$shelf['shelf_capacity']; ?>" required>
      </div>
    </div>

    <div class="mt-20" style="display:flex;gap:12px;flex-wrap:wrap;">
      <button type="submit" class="btn-primary">บันทึกการแก้ไข</button>
      <a href="shelves.php" class="btn-light">กลับไป Shelves</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
