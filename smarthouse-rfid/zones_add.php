<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/db.php';

$pageTitle = "Add Zone";
$activePage = "zones";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $zone_code = trim($_POST['zone_code'] ?? '');
    $zone_name = trim($_POST['zone_name'] ?? '');
    $total_capacity = (int)($_POST['total_capacity'] ?? 0);

    if ($zone_code === '' || $zone_name === '' || $total_capacity <= 0) {
        header("Location: zones_add.php?msg=form_error");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO zones (zone_code, zone_name, total_capacity)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$zone_code, $zone_name, $total_capacity]);
        header("Location: zones.php?msg=add_success");
        exit;
    } catch (Throwable $e) {
        header("Location: zones_add.php?msg=form_error");
        exit;
    }
}

$msg = $_GET['msg'] ?? '';

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Add Zone</h1>
<div class="page-sub">เพิ่มโซนใหม่</div>

<?php if ($msg === 'form_error'): ?>
  <div class="alert alert-error">กรอกข้อมูลให้ครบ หรืออาจมี Zone Code ซ้ำ</div>
<?php endif; ?>

<div class="card" style="padding:22px;max-width:800px;">
  <form method="post">
    <div class="form-grid">
      <div>
        <label class="form-label">Zone Code</label>
        <input name="zone_code" class="form-control" required>
      </div>
      <div>
        <label class="form-label">Zone Name</label>
        <input name="zone_name" class="form-control" required>
      </div>
      <div>
        <label class="form-label">Total Capacity</label>
        <input type="number" name="total_capacity" class="form-control" required>
      </div>
    </div>

    <div class="mt-20" style="display:flex;gap:12px;flex-wrap:wrap;">
      <button type="submit" class="btn-primary">บันทึก Zone</button>
      <a href="zones.php" class="btn-light">กลับไป Zones</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
