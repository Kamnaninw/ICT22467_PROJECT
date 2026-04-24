<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$pageTitle = "RFID Lookup";
$activePage = "rfid_lookup";

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">RFID Lookup</h1>
<div class="page-sub">ค้นหาสินค้าจาก RFID</div>

<div class="card" style="padding:22px;max-width:980px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:18px;">
    <div class="search-box" style="min-width:360px;flex:1;">
      <input id="rfidInput" placeholder="กรอกรหัส RFID เช่น RFID-SK-030">
    </div>
    <button class="btn-primary" onclick="lookupRFID()">ค้นหา RFID</button>
  </div>

  <div style="margin-bottom:16px;padding:14px 16px;border:1px solid #dfe8f4;border-radius:16px;background:linear-gradient(180deg,#f8fbff,#eef4ff);font-size:14px;color:#19344d;">
    ตัวอย่าง: <strong>RFID-GL-001</strong>, <strong>RFID-SK-030</strong>, <strong>RFID-BG-014</strong>
  </div>

  <div id="lookupMessage"></div>

  <div id="lookupResult" style="display:none;margin-top:18px;">
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
      <div class="card" style="padding:18px;">
        <div class="label">RFID Tag</div>
        <div id="resultRfid" class="value" style="font-size:30px;">-</div>
      </div>

      <div class="card" style="padding:18px;">
        <div class="label">Status</div>
        <div id="resultStatusWrap" style="margin-top:12px;">-</div>
      </div>

      <div class="card" style="padding:18px;">
        <div class="label">Product Name</div>
        <div id="resultProduct" class="value" style="font-size:30px;">-</div>
      </div>

      <div class="card" style="padding:18px;">
        <div class="label">SKU</div>
        <div id="resultSku" class="value" style="font-size:30px;">-</div>
      </div>

      <div class="card" style="padding:18px;">
        <div class="label">Current Quantity</div>
        <div id="resultQty" class="value" style="font-size:30px;">-</div>
      </div>

      <div class="card" style="padding:18px;">
        <div class="label">Reorder Point</div>
        <div id="resultReorder" class="value" style="font-size:30px;">-</div>
      </div>

      <div class="card" style="padding:18px;">
        <div class="label">Zone</div>
        <div id="resultZone" class="value" style="font-size:30px;">-</div>
      </div>

      <div class="card" style="padding:18px;">
        <div class="label">Shelf</div>
        <div id="resultShelf" class="value" style="font-size:30px;">-</div>
      </div>

      <div class="card" style="padding:18px;">
        <div class="label">Shelf Capacity</div>
        <div id="resultCapacity" class="value" style="font-size:30px;">-</div>
      </div>

      <div class="card" style="padding:18px;">
        <div class="label">Price</div>
        <div id="resultPrice" class="value" style="font-size:30px;">-</div>
      </div>
    </div>
  </div>
</div>

<script>
async function lookupRFID() {
  const input = document.getElementById('rfidInput');
  const code = input.value.trim();
  const msg = document.getElementById('lookupMessage');
  const result = document.getElementById('lookupResult');

  msg.innerHTML = '';
  result.style.display = 'none';

  if (!code) {
    msg.innerHTML = '<div class="alert alert-error">กรุณากรอกรหัส RFID</div>';
    return;
  }

  try {
    const response = await fetch('api_rfid_lookup.php?code=' + encodeURIComponent(code));
    const data = await response.json();

    if (!data.success) {
      msg.innerHTML = '<div class="alert alert-error">' + data.message + '</div>';
      return;
    }

    document.getElementById('resultRfid').textContent = data.item.rfid_code;
    document.getElementById('resultProduct').textContent = data.item.product_name;
    document.getElementById('resultSku').textContent = data.item.sku;
    document.getElementById('resultQty').textContent = data.item.current_qty;
    document.getElementById('resultReorder').textContent = data.item.reorder_point;
    document.getElementById('resultZone').textContent = data.item.zone_code;
    document.getElementById('resultShelf').textContent = data.item.shelf_code;
    document.getElementById('resultCapacity').textContent = data.item.shelf_capacity;
    document.getElementById('resultPrice').textContent = '฿' + Number(data.item.price).toFixed(2);

    let badgeClass = 'ok';
    if (data.item.status === 'Moving') badgeClass = 'warn';
    if (data.item.status === 'Shipped') badgeClass = 'danger';

    document.getElementById('resultStatusWrap').innerHTML =
      '<span class="badge ' + badgeClass + '">' + data.item.status + '</span>';

    msg.innerHTML = '<div class="alert alert-success">ค้นหาสำเร็จ</div>';
    result.style.display = 'block';

  } catch (error) {
    msg.innerHTML = '<div class="alert alert-error">เชื่อมต่อระบบไม่สำเร็จ</div>';
  }
}

document.getElementById('rfidInput').addEventListener('keypress', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    lookupRFID();
  }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
