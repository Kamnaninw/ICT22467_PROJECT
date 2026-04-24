<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/db.php';

$pageTitle = "Employees";
$activePage = "employees";
$msg = $_GET['msg'] ?? '';

$sql = "
SELECT employee_id, full_name, position_name, email, phone
FROM employees
ORDER BY employee_id ASC
";
$employees = $pdo->query($sql)->fetchAll();

$totalEmployees = count($employees);
$positions = [];
foreach ($employees as $e) {
    if (!empty($e['position_name'])) $positions[$e['position_name']] = true;
}

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Employees</h1>
<div class="page-sub">
  หน้านี้ใช้สำหรับตรวจสอบและจัดการข้อมูลพนักงานในระบบ เช่น ชื่อ ตำแหน่ง อีเมล และเบอร์โทร
</div>

<?php if ($msg === 'add_success'): ?>
  <div style="margin-bottom:18px;padding:12px 14px;border-radius:12px;background:#eef7ff;color:#1f4e98;font-weight:700;">เพิ่มพนักงานเรียบร้อยแล้ว</div>
<?php elseif ($msg === 'edit_success'): ?>
  <div style="margin-bottom:18px;padding:12px 14px;border-radius:12px;background:#eef7ff;color:#1f4e98;font-weight:700;">แก้ไขข้อมูลพนักงานเรียบร้อยแล้ว</div>
<?php elseif ($msg === 'delete_success'): ?>
  <div style="margin-bottom:18px;padding:12px 14px;border-radius:12px;background:#eef7ff;color:#1f4e98;font-weight:700;">ลบพนักงานเรียบร้อยแล้ว</div>
<?php elseif ($msg === 'form_error'): ?>
  <div style="margin-bottom:18px;padding:12px 14px;border-radius:12px;background:#fff3f3;color:#c0392b;font-weight:700;">กรุณากรอกข้อมูลให้ครบ หรือข้อมูลอาจซ้ำในระบบ</div>
<?php endif; ?>

<div class="summary-grid" style="grid-template-columns:repeat(3,minmax(0,1fr));">
  <div class="card summary-card">
    <div class="label">All Employees</div>
    <div class="value"><?php echo $totalEmployees; ?></div>
    <div class="note">จำนวนพนักงานทั้งหมดในระบบ</div>
  </div>

  <div class="card summary-card">
    <div class="label">Positions</div>
    <div class="value"><?php echo count($positions); ?></div>
    <div class="note">จำนวนตำแหน่งงานที่แตกต่างกัน</div>
  </div>

  <div class="card summary-card">
    <div class="label">Active Data</div>
    <div class="value"><?php echo $totalEmployees; ?></div>
    <div class="note">ข้อมูลพนักงานที่พร้อมใช้งาน</div>
  </div>
</div>

<div class="card" style="overflow:hidden;">
  <div style="padding:18px 20px;border-bottom:1px solid #e8eef5;background:#fbfcff;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
    <h3 style="margin:0;font-size:20px;color:#17324f;">Employee List</h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
      <div class="search-box">
        <input id="searchInput" placeholder="Search name, position, email">
      </div>
      <a href="employees_add.php" class="btn-primary">+ Add Employee</a>
    </div>
  </div>

  <div class="table-wrap">
    <table id="employeeTable">
      <thead>
        <tr>
          <th style="width:120px;">Actions</th>
          <th>ID</th>
          <th>Name</th>
          <th>Position</th>
          <th>Email</th>
          <th>Phone</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($employees as $row): ?>
          <tr>
            <td>
              <a href="employees_edit.php?id=<?php echo (int)$row['employee_id']; ?>" style="display:inline-block;padding:8px 10px;border-radius:10px;background:#eef4ff;color:#1f4e98;font-weight:700;margin-right:6px;">Edit</a>
              <a href="employees_delete.php?id=<?php echo (int)$row['employee_id']; ?>" onclick="return confirm('ยืนยันการลบพนักงานนี้?')" style="display:inline-block;padding:8px 10px;border-radius:10px;background:#fff1f1;color:#c0392b;font-weight:700;">Delete</a>
            </td>
            <td><?php echo (int)$row['employee_id']; ?></td>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><?php echo htmlspecialchars($row['position_name'] ?: '-'); ?></td>
            <td><?php echo htmlspecialchars($row['email'] ?: '-'); ?></td>
            <td><?php echo htmlspecialchars($row['phone'] ?: '-'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const searchInput = document.getElementById('searchInput');
const rows = document.querySelectorAll('#employeeTable tbody tr');

searchInput.addEventListener('input', function () {
  const keyword = this.value.toLowerCase().trim();
  rows.forEach(row => {
    const text = row.innerText.toLowerCase();
    row.style.display = text.includes(keyword) ? '' : 'none';
  });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
