<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/db.php';

$pageTitle = "Edit Employee";
$activePage = "employees";

$employee_id = (int)($_GET['id'] ?? $_POST['employee_id'] ?? 0);
if ($employee_id <= 0) {
    header("Location: employees.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $position_name = trim($_POST['position_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($full_name === '' || $position_name === '' || $email === '' || $phone === '') {
        header("Location: employees_edit.php?id={$employee_id}&msg=form_error");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE employees
            SET full_name = ?, position_name = ?, email = ?, phone = ?
            WHERE employee_id = ?
        ");
        $stmt->execute([$full_name, $position_name, $email, $phone, $employee_id]);

        header("Location: employees.php?msg=edit_success");
        exit;
    } catch (Throwable $e) {
        header("Location: employees_edit.php?id={$employee_id}&msg=form_error");
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT employee_id, full_name, position_name, email, phone
    FROM employees
    WHERE employee_id = ?
    LIMIT 1
");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

if (!$employee) {
    header("Location: employees.php");
    exit;
}

$msg = $_GET['msg'] ?? '';

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Edit Employee</h1>
<div class="page-sub">แก้ไขข้อมูลพนักงาน</div>

<?php if ($msg === 'form_error'): ?>
  <div style="margin-bottom:18px;padding:12px 14px;border-radius:12px;background:#fff3f3;color:#c0392b;font-weight:700;">กรอกข้อมูลให้ครบ</div>
<?php endif; ?>

<div class="card" style="padding:22px;max-width:800px;">
  <form method="post">
    <input type="hidden" name="employee_id" value="<?php echo (int)$employee['employee_id']; ?>">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div>
        <label style="display:block;margin-bottom:8px;font-weight:700;color:#1f4e98;">ชื่อ - นามสกุล</label>
        <input name="full_name" value="<?php echo htmlspecialchars($employee['full_name']); ?>" style="width:100%;padding:12px;border:1px solid #cfd8e3;border-radius:12px;" required>
      </div>

      <div>
        <label style="display:block;margin-bottom:8px;font-weight:700;color:#1f4e98;">ตำแหน่ง</label>
        <input name="position_name" value="<?php echo htmlspecialchars($employee['position_name']); ?>" style="width:100%;padding:12px;border:1px solid #cfd8e3;border-radius:12px;" required>
      </div>

      <div>
        <label style="display:block;margin-bottom:8px;font-weight:700;color:#1f4e98;">อีเมล</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" style="width:100%;padding:12px;border:1px solid #cfd8e3;border-radius:12px;" required>
      </div>

      <div>
        <label style="display:block;margin-bottom:8px;font-weight:700;color:#1f4e98;">เบอร์โทร</label>
        <input name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>" style="width:100%;padding:12px;border:1px solid #cfd8e3;border-radius:12px;" required>
      </div>
    </div>

    <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
      <button type="submit" class="btn-primary">บันทึกการแก้ไข</button>
      <a href="employees.php" class="btn-light">กลับไป Employees</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
