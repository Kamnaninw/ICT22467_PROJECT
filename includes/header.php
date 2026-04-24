<aside class="sidebar">
  <div class="brand">
    <div class="logo">TK</div>
    <div class="brand-text">
      <strong>Smart Warehouse</strong>
      <span>RFID System</span>
    </div>
  </div>

  <nav class="nav">
    <a href="dashboard.php" class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">🏠 Dashboard</a>
    <a href="inventory.php" class="<?php echo $activePage === 'inventory' ? 'active' : ''; ?>">📦 Inventory</a>
    <a href="employees.php" class="<?php echo $activePage === 'employees' ? 'active' : ''; ?>">👥 Employees</a>
    <a href="transactions.php" class="<?php echo $activePage === 'transactions' ? 'active' : ''; ?>">💳 Transactions</a>
    <a href="rfid_lookup.php" class="<?php echo $activePage === 'rfid_lookup' ? 'active' : ''; ?>">📡 RFID Lookup</a>
    <a href="zones.php" class="<?php echo $activePage === 'zones' ? 'active' : ''; ?>">🗂 Zones</a>
  </nav>
</aside>

<main class="main">
  <div class="topbar">
    <div class="topbar-left">
      <div class="mini-logo">TK</div>
      <div>
        <div style="font-weight:800;font-size:16px;">Smart Warehouse RFID System</div>
        <div style="font-size:13px;color:rgba(255,255,255,.88);margin-top:3px;">
          <?php echo htmlspecialchars($pageTitle); ?>
        </div>
      </div>
    </div>

    <div class="topbar-right">
      <a href="dashboard.php">Home</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>

  <div class="content">
