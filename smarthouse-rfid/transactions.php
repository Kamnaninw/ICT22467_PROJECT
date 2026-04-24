<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/config/db.php';

$pageTitle = "Transactions";
$activePage = "transactions";

$message = $_GET['msg'] ?? '';

$products = $pdo->query("
    SELECT product_id, product_name, sku
    FROM products
    ORDER BY product_name
")->fetchAll();

$employees = $pdo->query("
    SELECT employee_id, full_name
    FROM employees
    ORDER BY full_name
")->fetchAll();

$sql = "
SELECT
    st.transaction_id,
    st.transaction_type,
    st.document_no,
    st.quantity,
    st.transaction_datetime,
    st.note,
    p.product_name,
    p.sku,
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
ORDER BY st.transaction_id DESC
";
$history = $pdo->query($sql)->fetchAll();

$totalIn = (int)$pdo->query("SELECT COUNT(*) FROM stock_transactions WHERE transaction_type='IN'")->fetchColumn();
$totalOut = (int)$pdo->query("SELECT COUNT(*) FROM stock_transactions WHERE transaction_type='OUT'")->fetchColumn();
$totalEmployees = (int)$pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$totalLogs = (int)$pdo->query("SELECT COUNT(*) FROM stock_transactions")->fetchColumn();

require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/header.php';
?>

<style>
.transaction-form {
  display: grid;
  gap: 14px;
}
.scan-row {
  display: grid;
  grid-template-columns: minmax(0,1fr) auto auto;
  gap: 10px;
  align-items: center;
}
.scan-input {
  font-size: 18px;
  padding: 15px 16px;
}
.helper-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  flex-wrap: wrap;
}
.helper-note {
  color: #6f839b;
  font-size: 13px;
  line-height: 1.6;
  max-width: 520px;
}
.rfid-summary {
  padding: 16px 18px;
  border-radius: 16px;
  border: 1px solid #dfe8f4;
}
.rfid-summary strong {
  font-size: 18px;
  color: #17324f;
}
.quick-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0,1fr));
  gap: 12px;
}
.quick-card {
  border: 1px solid #e5edf6;
  border-radius: 14px;
  padding: 12px 14px;
  background: #fff;
}
.quick-card .label {
  font-size: 12px;
  letter-spacing: .04em;
  text-transform: uppercase;
}
.manual-panel {
  display: none;
  padding: 16px;
  border: 1px dashed #d5dfeb;
  border-radius: 16px;
  background: #fafcff;
}
.manual-panel.open {
  display: grid;
  gap: 14px;
}
.compact-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.ghost-btn {
  display: inline-block;
  background: transparent;
  border: 1px dashed #bdd0ea;
  color: #1f4e98;
  padding: 10px 14px;
  border-radius: 12px;
  font-weight: 700;
  white-space: nowrap;
}
.scan-action-btn {
  min-width: 92px;
}
.primary-submit {
  margin-top: 4px;
}
.section-title-th {
  color: #17324f;
  margin: 0 0 6px;
  font-size: 24px;
}
.section-sub-th {
  color: #6f839b;
  font-size: 13px;
  line-height: 1.7;
  margin-bottom: 16px;
}
@media (max-width: 900px) {
  .scan-row {
    grid-template-columns: 1fr;
  }
  .compact-grid,
  .quick-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<h1 class="page-title">Transactions</h1>
<div class="page-sub">
  สแกน RFID ก่อน ระบบจะช่วยระบุสินค้าให้อัตโนมัติ จากนั้นตรวจจำนวนและบันทึกรายการได้ทันที 
</div>

<?php if ($message === 'buy_success'): ?>
  <div class="alert alert-success">Inbound transaction saved successfully.</div>
<?php elseif ($message === 'sell_success'): ?>
  <div class="alert alert-success">Outbound transaction saved successfully.</div>
<?php elseif ($message === 'stock_error'): ?>
  <div class="alert alert-error">Stock is not enough for this outbound transaction.</div>
<?php elseif ($message === 'form_error'): ?>
  <div class="alert alert-error">Please complete all required fields.</div>
<?php elseif ($message === 'capacity_error'): ?>
  <div class="alert alert-error">Inbound quantity exceeds the capacity of the assigned shelf.</div>
<?php elseif ($message === 'rfid_error'): ?>
  <div class="alert alert-error">The scanned RFID was not found or is not linked to a product.</div>
<?php endif; ?>

<div class="summary-grid">
  <div class="card summary-card">
    <div class="label">รายการรับเข้า</div>
    <div class="value"><?php echo $totalIn; ?></div>
    <div class="note">จำนวนรายการรับเข้าสินค้า</div>
  </div>

  <div class="card summary-card">
    <div class="label">รายการจ่ายออก</div>
    <div class="value"><?php echo $totalOut; ?></div>
    <div class="note">จำนวนรายการจ่ายออกสินค้า</div>
  </div>

  <div class="card summary-card">
    <div class="label">พนักงานที่เกี่ยวข้อง</div>
    <div class="value"><?php echo $totalEmployees; ?></div>
    <div class="note">พนักงานที่ใช้งานในระบบ</div>
  </div>

  <div class="card summary-card">
    <div class="label">บันทึกรายการ</div>
    <div class="value"><?php echo $totalLogs; ?></div>
    <div class="note">จำนวนรายการที่บันทึกไว้</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
  <div class="card" style="padding:22px;">
    <h3 class="section-title-th">รับสินค้าเข้า / Inbound</h3>
    <form method="post" action="buy.php" class="rfid-transaction-form transaction-form" data-mode="PO" data-form-type="inbound">
      <input type="hidden" name="product_id">

      <div>
        <label class="form-label">สแกน RFID</label>
        <div class="scan-row">
          <input type="text" name="rfid_code" class="form-control scan-input js-rfid-input" placeholder="สแกนหรือกรอกรหัส RFID">
          <button type="button" class="btn-light js-rfid-lookup scan-action-btn">ค้นหา</button>
          <button type="button" class="btn-light js-clear-scan scan-action-btn">ล้าง</button>
        </div>
        <div class="helper-row" style="margin-top:10px;">
          <button type="button" class="ghost-btn js-toggle-manual">เลือกสินค้าด้วยตนเอง</button>
        </div>
      </div>

      <div class="js-rfid-message" style="display:none;"></div>

      <div class="rfid-summary js-rfid-result" style="display:none;background:linear-gradient(180deg,#f8fbff,#eef4ff);">
        <div style="font-size:13px;font-weight:800;color:#6f839b;letter-spacing:.04em;text-transform:uppercase;">ข้อมูลสินค้าที่พบ</div>
        <div class="js-rfid-summary" style="margin-top:10px;"></div>
      </div>

      <div class="manual-panel js-manual-panel">
        <div style="font-size:13px;font-weight:800;color:#6f839b;letter-spacing:.04em;text-transform:uppercase;">เลือกสินค้าแบบกำหนดเอง</div>
        <select class="form-control js-product-select">
          <option value="">-- เลือกสินค้า --</option>
          <?php foreach ($products as $p): ?>
            <option value="<?php echo $p['product_id']; ?>">
              <?php echo htmlspecialchars($p['product_name'] . ' (' . $p['sku'] . ')'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="compact-grid">
        <div>
          <label class="form-label">พนักงานผู้รับผิดชอบ</label>
          <select name="employee_id" required class="form-control js-employee-select">
            <option value="">-- เลือกพนักงาน --</option>
            <?php foreach ($employees as $e): ?>
              <option value="<?php echo $e['employee_id']; ?>">
                <?php echo htmlspecialchars($e['full_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label">จำนวน</label>
          <input type="number" name="quantity" min="1" value="1" required class="form-control js-quantity-input">
        </div>
      </div>

      <div class="compact-grid">
        <div>
          <label class="form-label">เลขที่เอกสารรับเข้า (PO)</label>
          <input type="text" name="document_no" required class="form-control js-document-input">
        </div>

        <div>
          <label class="form-label">หมายเหตุ</label>
          <input type="text" name="note" class="form-control">
        </div>
      </div>

      <button type="submit" class="btn-primary primary-submit">บันทึกรายการรับเข้า</button>
    </form>
  </div>

  <div class="card" style="padding:22px;">
    <h3 class="section-title-th">จ่ายสินค้าออก / Outbound</h3>
    <form method="post" action="sell.php" class="rfid-transaction-form transaction-form" data-mode="ISSUE" data-form-type="outbound">
      <input type="hidden" name="product_id">

      <div>
        <label class="form-label">สแกน RFID</label>
        <div class="scan-row">
          <input type="text" name="rfid_code" class="form-control scan-input js-rfid-input" placeholder="สแกนหรือกรอกรหัส RFID">
          <button type="button" class="btn-light js-rfid-lookup scan-action-btn">ค้นหา</button>
          <button type="button" class="btn-light js-clear-scan scan-action-btn">ล้าง</button>
        </div>
        <div class="helper-row" style="margin-top:10px;">
          <button type="button" class="ghost-btn js-toggle-manual">เลือกสินค้าด้วยตนเอง</button>
        </div>
      </div>

      <div class="js-rfid-message" style="display:none;"></div>

      <div class="rfid-summary js-rfid-result" style="display:none;background:linear-gradient(180deg,#fffaf2,#fff4e5);">
        <div style="font-size:13px;font-weight:800;color:#6f839b;letter-spacing:.04em;text-transform:uppercase;">ข้อมูลสินค้าที่พบ</div>
        <div class="js-rfid-summary" style="margin-top:10px;"></div>
      </div>

      <div class="manual-panel js-manual-panel">
        <div style="font-size:13px;font-weight:800;color:#6f839b;letter-spacing:.04em;text-transform:uppercase;">เลือกสินค้าแบบกำหนดเอง</div>
        <select class="form-control js-product-select">
          <option value="">-- เลือกสินค้า --</option>
          <?php foreach ($products as $p): ?>
            <option value="<?php echo $p['product_id']; ?>">
              <?php echo htmlspecialchars($p['product_name'] . ' (' . $p['sku'] . ')'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="compact-grid">
        <div>
          <label class="form-label">พนักงานผู้รับผิดชอบ</label>
          <select name="employee_id" required class="form-control js-employee-select">
            <option value="">-- เลือกพนักงาน --</option>
            <?php foreach ($employees as $e): ?>
              <option value="<?php echo $e['employee_id']; ?>">
                <?php echo htmlspecialchars($e['full_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label">จำนวน</label>
          <input type="number" name="quantity" min="1" value="1" required class="form-control js-quantity-input">
        </div>
      </div>

      <div class="compact-grid">
        <div>
          <label class="form-label">เลขที่เอกสารจ่ายออก</label>
          <input type="text" name="document_no" required class="form-control js-document-input">
        </div>

        <div>
          <label class="form-label">หมายเหตุ</label>
          <input type="text" name="note" class="form-control">
        </div>
      </div>

      <button type="submit" class="btn-primary primary-submit">บันทึกรายการจ่ายออก</button>
    </form>
  </div>
</div>

<div class="card" style="overflow:hidden;">
  <div style="padding:18px 20px;border-bottom:1px solid #e8eef5;background:#fbfcff;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;">
    <h3 style="margin:0;font-size:20px;color:#17324f;">Transaction History</h3>
    <div class="search-box">
      <input id="searchInput" placeholder="Search document, product, RFID, employee">
    </div>
  </div>

  <div class="table-wrap">
    <table id="historyTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Type</th>
          <th>Document No</th>
          <th>Product</th>
          <th>SKU</th>
          <th>RFID</th>
          <th>Zone</th>
          <th>Shelf</th>
          <th>Qty</th>
          <th>Date</th>
          <th>Employee</th>
          <th>Note</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($history as $row): ?>
          <tr>
            <td>
              <a href="transaction_detail.php?id=<?php echo (int)$row['transaction_id']; ?>" style="color:#1f4e98;font-weight:800;">
                <?php echo $row['transaction_id']; ?>
              </a>
            </td>
            <td>
              <?php if ($row['transaction_type'] === 'IN'): ?>
                <span class="badge ok">BUY / IN</span>
              <?php else: ?>
                <span class="badge warn">SELL / OUT</span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($row['document_no']); ?></td>
            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
            <td><?php echo htmlspecialchars($row['sku']); ?></td>
            <td><?php echo htmlspecialchars($row['rfid_code']); ?></td>
            <td><?php echo htmlspecialchars($row['zone_code']); ?></td>
            <td><?php echo htmlspecialchars($row['shelf_code']); ?></td>
            <td><?php echo (int)$row['quantity']; ?></td>
            <td><?php echo htmlspecialchars($row['transaction_datetime']); ?></td>
            <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
            <td><?php echo htmlspecialchars($row['note'] ?: '-'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const searchInput = document.getElementById('searchInput');
const rows = document.querySelectorAll('#historyTable tbody tr');
const employeeStorageKey = 'smartwarehouse:lastEmployeeId';

searchInput.addEventListener('input', function () {
  const keyword = this.value.toLowerCase().trim();
  rows.forEach(row => {
    const text = row.innerText.toLowerCase();
    row.style.display = text.includes(keyword) ? '' : 'none';
  });
});

function renderRfidMessage(container, type, text) {
  if (!container) return;
  container.style.display = 'block';
  container.className = 'js-rfid-message alert ' + (type === 'error' ? 'alert-error' : 'alert-success');
  container.textContent = text;
}

function buildDocumentNumber(prefix) {
  const now = new Date();
  const yyyy = now.getFullYear();
  const mm = String(now.getMonth() + 1).padStart(2, '0');
  const dd = String(now.getDate()).padStart(2, '0');
  const hh = String(now.getHours()).padStart(2, '0');
  const min = String(now.getMinutes()).padStart(2, '0');
  const random = String(Math.floor(Math.random() * 900) + 100);
  return `${prefix}-${yyyy}${mm}${dd}-${hh}${min}-${random}`;
}

function setAutoDocumentNumber(form, force = false) {
  const input = form.querySelector('.js-document-input');
  const prefix = form.dataset.mode || 'DOC';
  if (!input) return;

  if (force || !input.value.trim() || input.dataset.autoGenerated === 'true') {
    input.value = buildDocumentNumber(prefix);
    input.dataset.autoGenerated = 'true';
  }
}

function setEmployeeFromStorage(form) {
  const employeeSelect = form.querySelector('.js-employee-select');
  const savedEmployeeId = localStorage.getItem(employeeStorageKey);
  if (employeeSelect && savedEmployeeId) {
    employeeSelect.value = savedEmployeeId;
  }
}

function toggleManualPanel(form, forceOpen = null) {
  const panel = form.querySelector('.js-manual-panel');
  const button = form.querySelector('.js-toggle-manual');
  if (!panel || !button) return;

  const shouldOpen = forceOpen === null ? !panel.classList.contains('open') : forceOpen;
  panel.classList.toggle('open', shouldOpen);
  button.textContent = shouldOpen ? 'Hide Manual Selection' : 'Select Product Manually';
}

function clearRfidState(form, keepInputValue = true) {
  const input = form.querySelector('.js-rfid-input');
  const message = form.querySelector('.js-rfid-message');
  const result = form.querySelector('.js-rfid-result');
  const summary = form.querySelector('.js-rfid-summary');
  const hiddenProduct = form.querySelector('input[name="product_id"]');
  const productSelect = form.querySelector('.js-product-select');
  const quantityInput = form.querySelector('.js-quantity-input');

  if (!keepInputValue && input) input.value = '';
  if (message) {
    message.style.display = 'none';
    message.textContent = '';
    message.className = 'js-rfid-message';
  }
  if (result) result.style.display = 'none';
  if (summary) summary.innerHTML = '';
  if (hiddenProduct) hiddenProduct.value = '';
  if (productSelect) {
    productSelect.value = '';
    productSelect.disabled = false;
  }
  if (quantityInput) {
    quantityInput.value = quantityInput.value && quantityInput.value !== '0' ? quantityInput.value : '1';
    quantityInput.removeAttribute('data-last-rfid');
  }
}

function applyMatchedItem(form, item) {
  const result = form.querySelector('.js-rfid-result');
  const summary = form.querySelector('.js-rfid-summary');
  const hiddenProduct = form.querySelector('input[name="product_id"]');
  const productSelect = form.querySelector('.js-product-select');
  const quantityInput = form.querySelector('.js-quantity-input');

  hiddenProduct.value = String(item.product_id);
  productSelect.value = String(item.product_id);
  productSelect.disabled = true;
  quantityInput.setAttribute('data-last-rfid', item.rfid_code);

  summary.innerHTML = `
    <strong>${item.product_name}</strong>
    <div class="quick-grid" style="margin-top:12px;">
      <div class="quick-card">
        <div class="label">RFID</div>
        <div style="margin-top:6px;font-weight:700;">${item.rfid_code}</div>
      </div>
      <div class="quick-card">
        <div class="label">SKU</div>
        <div style="margin-top:6px;font-weight:700;">${item.sku}</div>
      </div>
      <div class="quick-card">
        <div class="label">Current Qty</div>
        <div style="margin-top:6px;font-weight:700;">${item.current_qty}</div>
      </div>
      <div class="quick-card">
        <div class="label">Location</div>
        <div style="margin-top:6px;font-weight:700;">${item.zone_code} / ${item.shelf_code}</div>
      </div>
    </div>
  `;
  result.style.display = 'block';
  toggleManualPanel(form, false);
  quantityInput.focus();
  quantityInput.select();
}

async function lookupRfidForForm(form) {
  const input = form.querySelector('.js-rfid-input');
  const message = form.querySelector('.js-rfid-message');
  const code = input.value.trim();

  if (form.dataset.lookupBusy === 'true') return;

  clearRfidState(form, true);

    if (!code) {
    renderRfidMessage(message, 'error', 'กรุณากรอกรหัส RFID ก่อนค้นหา');
    return;
  }

  form.dataset.lookupBusy = 'true';

  try {
    const response = await fetch('api_rfid_lookup.php?code=' + encodeURIComponent(code));
    const data = await response.json();

    if (!data.success) {
      renderRfidMessage(message, 'error', data.message || 'RFID not found.');
      return;
    }

    applyMatchedItem(form, data.item);
    renderRfidMessage(message, 'success', 'โหลดข้อมูลสินค้าจาก RFID สำเร็จ');
  } catch (error) {
    renderRfidMessage(message, 'error', 'ไม่สามารถเชื่อมต่อระบบค้นหา RFID ได้');
  } finally {
    form.dataset.lookupBusy = 'false';
  }
}

document.querySelectorAll('.rfid-transaction-form').forEach(form => {
  const lookupButton = form.querySelector('.js-rfid-lookup');
  const clearButton = form.querySelector('.js-clear-scan');
  const toggleManualButton = form.querySelector('.js-toggle-manual');
  const rfidInput = form.querySelector('.js-rfid-input');
  const productSelect = form.querySelector('.js-product-select');
  const hiddenProduct = form.querySelector('input[name="product_id"]');
  const employeeSelect = form.querySelector('.js-employee-select');
  const documentInput = form.querySelector('.js-document-input');
  let lookupTimer = null;

  setAutoDocumentNumber(form, true);
  setEmployeeFromStorage(form);

  documentInput.addEventListener('input', function () {
    this.dataset.autoGenerated = 'false';
  });

  employeeSelect.addEventListener('change', function () {
    localStorage.setItem(employeeStorageKey, this.value);
  });

  toggleManualButton.addEventListener('click', function () {
    toggleManualPanel(form);
  });

  productSelect.addEventListener('change', function () {
    hiddenProduct.value = this.value;
    if (this.value) {
      renderRfidMessage(form.querySelector('.js-rfid-message'), 'success', 'เลือกสินค้าด้วยตนเองแล้ว');
      toggleManualPanel(form, true);
    } else {
      clearRfidState(form, true);
    }
  });

  lookupButton.addEventListener('click', function () {
    lookupRfidForForm(form);
  });

  rfidInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      lookupRfidForForm(form);
    }
  });

  rfidInput.addEventListener('input', function () {
    clearTimeout(lookupTimer);
    const value = this.value.trim();
    if (value.length < 4) return;

    lookupTimer = setTimeout(() => {
      lookupRfidForForm(form);
    }, 300);
  });

  clearButton.addEventListener('click', function () {
    clearTimeout(lookupTimer);
    clearRfidState(form, false);
    setAutoDocumentNumber(form, true);
    rfidInput.focus();
  });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
