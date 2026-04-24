<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/db.php';

$pageTitle = "Dashboard";
$activePage = "dashboard";

$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalRFID = (int)$pdo->query("SELECT COUNT(*) FROM rfid_tags")->fetchColumn();
$totalZones = (int)$pdo->query("SELECT COUNT(*) FROM zones")->fetchColumn();
$totalEmployees = (int)$pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();

$statusRows = $pdo->query("SELECT status, COUNT(*) AS total FROM rfid_tags GROUP BY status")->fetchAll();
$statusMap = ['In-Stock' => 0, 'Moving' => 0, 'Shipped' => 0];
foreach ($statusRows as $row) {
    $statusMap[$row['status']] = (int)$row['total'];
}

$transactionRows = $pdo->query("
    SELECT transaction_type, COUNT(*) AS total
    FROM stock_transactions
    GROUP BY transaction_type
")->fetchAll();

$buyCount = 0;
$sellCount = 0;
foreach ($transactionRows as $row) {
    if ($row['transaction_type'] === 'IN') {
        $buyCount = (int)$row['total'];
    }
    if ($row['transaction_type'] === 'OUT') {
        $sellCount = (int)$row['total'];
    }
}

$activities = $pdo->query("
    SELECT
        st.transaction_type,
        st.document_no,
        st.quantity,
        st.transaction_datetime,
        p.product_name,
        rt.rfid_code,
        z.zone_code,
        s.shelf_code,
        e.full_name AS employee_name
    FROM stock_transactions st
    JOIN products p ON st.product_id = p.product_id
    JOIN rfid_tags rt ON st.rfid_id = rt.rfid_id
    JOIN shelves s ON st.shelf_id = s.shelf_id
    JOIN zones z ON s.zone_id = z.zone_id
    JOIN employees e ON st.employee_id = e.employee_id
    ORDER BY st.transaction_datetime DESC, st.transaction_id DESC
    LIMIT 5
")->fetchAll();

$lowStockProducts = $pdo->query("
    SELECT
        p.product_name,
        p.sku,
        p.reorder_point,
        COALESCE(ps.current_qty, 0) AS current_qty
    FROM products p
    LEFT JOIN product_stock ps ON p.product_id = ps.product_id
    WHERE p.reorder_point > 0
      AND COALESCE(ps.current_qty, 0) <= p.reorder_point
    ORDER BY current_qty ASC, p.product_name ASC
    LIMIT 4
")->fetchAll();

$overCapacityShelves = $pdo->query("
    SELECT
        z.zone_code,
        s.shelf_code,
        s.shelf_capacity,
        COALESCE(SUM(ps.current_qty), 0) AS used_qty
    FROM shelves s
    JOIN zones z ON s.zone_id = z.zone_id
    LEFT JOIN rfid_tags rt ON rt.shelf_id = s.shelf_id
    LEFT JOIN product_stock ps ON ps.product_id = rt.product_id
    GROUP BY z.zone_code, s.shelf_code, s.shelf_capacity
    HAVING COALESCE(SUM(ps.current_qty), 0) > s.shelf_capacity
    ORDER BY used_qty DESC, z.zone_code, s.shelf_code
    LIMIT 4
")->fetchAll();

$unassignedTags = $pdo->query("
    SELECT
        rt.rfid_code,
        rt.status,
        p.product_name,
        p.sku
    FROM rfid_tags rt
    JOIN products p ON rt.product_id = p.product_id
    LEFT JOIN shelves s ON rt.shelf_id = s.shelf_id
    WHERE rt.shelf_id IS NULL OR s.shelf_id IS NULL
    ORDER BY rt.rfid_code ASC
    LIMIT 4
")->fetchAll();

$totalAlerts = count($lowStockProducts) + count($overCapacityShelves) + count($unassignedTags);

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<style>
.dashboard-chart-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 22px;
  margin-bottom: 24px;
}
.chart-box {
  position: relative;
  width: 100%;
  height: 260px;
}
.quick-alert-strip {
  display: grid;
  grid-template-columns: 1.2fr 1fr 1fr 1fr;
  gap: 14px;
  margin-bottom: 24px;
}
.quick-alert-card {
  padding: 18px;
  border-radius: 20px;
  border: 1px solid #e5edf7;
  background: #fff;
}
.quick-alert-card strong {
  display: block;
  font-size: 30px;
  margin-top: 8px;
}
.content-grid {
  display: grid;
  grid-template-columns: 1.1fr .9fr;
  gap: 18px;
  margin-bottom: 24px;
}
.stack-grid {
  display: grid;
  gap: 18px;
}
.section-card {
  padding: 20px;
}
.section-head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 12px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}
.section-title {
  margin: 0;
  color: #17324f;
  font-size: 22px;
}
.section-sub {
  color: #7b8ea3;
  font-size: 13px;
}
.simple-list {
  display: grid;
  gap: 10px;
}
.simple-item {
  padding: 12px 14px;
  border: 1px solid #e8eef5;
  border-radius: 14px;
  background: #fcfdff;
}
.simple-title {
  font-weight: 700;
  color: #17324f;
}
.simple-meta {
  margin-top: 4px;
  color: #6f839b;
  font-size: 13px;
  line-height: 1.7;
}
.alert-chip-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}
.alert-chip {
  display: inline-flex;
  align-items: center;
  padding: 8px 12px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
}
.chip-danger {
  background: #fff1f1;
  color: #c0392b;
}
.chip-warn {
  background: #fff8e8;
  color: #b7791f;
}
.chip-info {
  background: #eef4ff;
  color: #1f4e98;
}
.activity-item {
  padding: 12px 0;
  border-bottom: 1px solid #e8eef5;
}
.activity-item:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}
.empty-state {
  color: #7b8ea3;
  font-size: 14px;
  line-height: 1.7;
}
@media (max-width: 1100px) {
  .dashboard-chart-grid,
  .quick-alert-strip,
  .content-grid {
    grid-template-columns: 1fr;
  }
  .chart-box {
    height: 220px;
  }
}
</style>

<h1 class="page-title">Dashboard</h1>
<div class="page-sub">ภาพรวมคลังและรายการที่ต้องเช็ก</div>

<div class="summary-grid">
  <div class="card summary-card">
    <div class="label">Products</div>
    <div class="value"><?php echo $totalProducts; ?></div>
    <div class="note">สินค้าทั้งหมดในระบบ</div>
  </div>
  <div class="card summary-card">
    <div class="label">RFID Tags</div>
    <div class="value"><?php echo $totalRFID; ?></div>
    <div class="note">แท็ก RFID ที่ผูกกับสินค้า</div>
  </div>
  <div class="card summary-card" style="background:#fff8ef;">
    <div class="label" style="color:#b07a1f;">Zones</div>
    <div class="value" style="color:#d78b1a;"><?php echo $totalZones; ?></div>
    <div class="note" style="color:#b07a1f;">โซนที่ใช้งานอยู่</div>
  </div>
  <div class="card summary-card" style="background:#eef4ff;">
    <div class="label" style="color:#356fd0;">Employees</div>
    <div class="value" style="color:#1f4e98;"><?php echo $totalEmployees; ?></div>
    <div class="note" style="color:#356fd0;">พนักงานในระบบ</div>
  </div>
</div>

<div class="quick-alert-strip">
  <div class="quick-alert-card" style="background:#fff7ef;">
    <div class="label" style="color:#b06f19;">Alert Overview</div>
    <strong style="color:#cf7f00;"><?php echo $totalAlerts; ?></strong>
    <div class="note" style="color:#9a6a1f;">รายการที่ควรตรวจสอบ</div>
  </div>
  <div class="quick-alert-card">
    <div class="label">Low Stock</div>
    <strong style="color:#d94f4f;"><?php echo count($lowStockProducts); ?></strong>
    <div class="note">สินค้าใกล้หมด</div>
  </div>
  <div class="quick-alert-card">
    <div class="label">Over Capacity</div>
    <strong style="color:#b7791f;"><?php echo count($overCapacityShelves); ?></strong>
    <div class="note">Shelf เกินความจุ</div>
  </div>
  <div class="quick-alert-card">
    <div class="label">RFID Location</div>
    <strong style="color:#1f4e98;"><?php echo count($unassignedTags); ?></strong>
    <div class="note">RFID ยังไม่ผูกตำแหน่ง</div>
  </div>
</div>

<div class="content-grid">
  <div class="stack-grid">
    <div class="card section-card">
      <div class="section-head">
        <h3 class="section-title">Alert สำคัญ</h3>
        <div class="section-sub">ดูเฉพาะรายการที่ควรเช็กตอนนี้</div>
      </div>

      <div class="alert-chip-row">
        <span class="alert-chip chip-danger">Low Stock <?php echo count($lowStockProducts); ?></span>
        <span class="alert-chip chip-warn">Over Capacity <?php echo count($overCapacityShelves); ?></span>
        <span class="alert-chip chip-info">RFID Location <?php echo count($unassignedTags); ?></span>
      </div>

      <div class="simple-list">
        <?php if ($lowStockProducts): ?>
          <?php foreach ($lowStockProducts as $item): ?>
            <div class="simple-item">
              <div class="simple-title"><?php echo htmlspecialchars($item['product_name']); ?></div>
              <div class="simple-meta">
                SKU: <?php echo htmlspecialchars($item['sku']); ?><br>
                คงเหลือ <?php echo (int)$item['current_qty']; ?> / จุดสั่งซื้อซ้ำ <?php echo (int)$item['reorder_point']; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php elseif ($overCapacityShelves): ?>
          <?php foreach ($overCapacityShelves as $shelf): ?>
            <div class="simple-item">
              <div class="simple-title"><?php echo htmlspecialchars($shelf['zone_code'] . ' / ' . $shelf['shelf_code']); ?></div>
              <div class="simple-meta">
                ใช้งาน <?php echo (int)$shelf['used_qty']; ?> / ความจุ <?php echo (int)$shelf['shelf_capacity']; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php elseif ($unassignedTags): ?>
          <?php foreach ($unassignedTags as $tag): ?>
            <div class="simple-item">
              <div class="simple-title"><?php echo htmlspecialchars($tag['rfid_code']); ?></div>
              <div class="simple-meta">
                <?php echo htmlspecialchars($tag['product_name']); ?> (<?php echo htmlspecialchars($tag['sku']); ?>)
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">ตอนนี้ยังไม่พบรายการผิดปกติที่ต้องรีบตรวจสอบ</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="dashboard-chart-grid">
      <div class="card section-card">
        <div class="section-head">
          <h3 class="section-title">สถานะ RFID</h3>
          <div class="section-sub">ภาพรวมของแท็กในระบบ</div>
        </div>
        <div class="chart-box">
          <canvas id="statusChart"></canvas>
        </div>
      </div>

      <div class="card section-card">
        <div class="section-head">
          <h3 class="section-title">สรุปธุรกรรม</h3>
          <div class="section-sub">รับเข้าและจ่ายออก</div>
        </div>
        <div class="chart-box">
          <canvas id="transactionChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="stack-grid">
    <div class="card section-card">
      <div class="section-head">
        <h3 class="section-title">แยกตามประเภท Alert</h3>
        <div class="section-sub">อ่านเร็ว ไม่แน่นเกินไป</div>
      </div>

      <div class="simple-list">
        <div class="simple-item">
          <div class="simple-title">สินค้าใกล้หมด</div>
          <div class="simple-meta"><?php echo count($lowStockProducts); ?> รายการ</div>
        </div>
        <div class="simple-item">
          <div class="simple-title">Shelf เกินความจุ</div>
          <div class="simple-meta"><?php echo count($overCapacityShelves); ?> รายการ</div>
        </div>
        <div class="simple-item">
          <div class="simple-title">RFID ยังไม่ผูกตำแหน่ง</div>
          <div class="simple-meta"><?php echo count($unassignedTags); ?> รายการ</div>
        </div>
      </div>
    </div>

    <div class="card section-card">
      <div class="section-head">
        <h3 class="section-title">กิจกรรมล่าสุด</h3>
        <div class="section-sub">5 รายการล่าสุด</div>
      </div>

      <?php if ($activities): ?>
        <?php foreach ($activities as $row): ?>
          <div class="activity-item">
            <div class="simple-title">
              <?php echo $row['transaction_type'] === 'IN' ? 'รับสินค้าเข้า' : 'จ่ายสินค้าออก'; ?>
            </div>
            <div class="simple-meta">
              <?php echo htmlspecialchars($row['product_name']); ?> •
              <?php echo htmlspecialchars($row['rfid_code']); ?><br>
              <?php echo htmlspecialchars($row['zone_code']); ?>/<?php echo htmlspecialchars($row['shelf_code']); ?> •
              จำนวน <?php echo (int)$row['quantity']; ?> •
              <?php echo htmlspecialchars($row['employee_name']); ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">ยังไม่มีประวัติการเคลื่อนไหวล่าสุด</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const statusCtx = document.getElementById('statusChart');
const transactionCtx = document.getElementById('transactionChart');

new Chart(statusCtx, {
  type: 'doughnut',
  data: {
    labels: ['In-Stock', 'Moving', 'Shipped'],
    datasets: [{
      data: [<?php echo $statusMap['In-Stock']; ?>, <?php echo $statusMap['Moving']; ?>, <?php echo $statusMap['Shipped']; ?>]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'bottom' }
    }
  }
});

new Chart(transactionCtx, {
  type: 'bar',
  data: {
    labels: ['Buy / IN', 'Sell / OUT'],
    datasets: [{
      label: 'Transactions',
      data: [<?php echo $buyCount; ?>, <?php echo $sellCount; ?>],
      backgroundColor: ['#4a90e2', '#f5a623'],
      borderColor: ['#4a90e2', '#f5a623'],
      borderWidth: 1,
      borderRadius: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: { precision: 0 }
      }
    }
  }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
