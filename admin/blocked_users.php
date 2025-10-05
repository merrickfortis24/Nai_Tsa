<?php
session_start();
if (!isset($_SESSION['Admin_ID']) && !isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/classes/database.php';
require_once __DIR__ . '/classes/fraud_blocker.php';
$db = new database();
$con = $db->opencon();

// Ensure structures without triggering full detection pass
try { $fb = new FraudBlocker(); if (method_exists($fb,'ensureStructures')) { $fb->ensureStructures(); } } catch(Throwable $e) { }

// Fetch blocked list with metrics
$blocked = [];
try {
  $chk = $con->query("SHOW TABLES LIKE 'blocked_users'");
  if ($chk && $chk->rowCount()>0) {
    $sql = "SELECT b.customer_id, b.blocked_at, b.reason, b.auto_block, c.Customer_Name, c.Customer_Email,
      (SELECT COUNT(*) FROM orders o1 WHERE o1.Customer_ID=b.customer_id) AS total_orders,
      (SELECT COUNT(*) FROM orders o2 WHERE o2.Customer_ID=b.customer_id AND o2.order_status='Cancelled') AS cancelled_orders,
      (SELECT COUNT(*) FROM orders o3 WHERE o3.Customer_ID=b.customer_id AND o3.Order_Date>=NOW()-INTERVAL 1 DAY) AS orders_24h
      FROM blocked_users b LEFT JOIN customer c ON c.Customer_ID=b.customer_id ORDER BY b.blocked_at DESC";
    $stmt = $con->query($sql);
    $blocked = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($blocked as &$row) {
      $tot = (int)($row['total_orders'] ?? 0); $can = (int)($row['cancelled_orders'] ?? 0);
      $row['cancel_ratio'] = $tot>0 ? ($can / max($tot,1)) : 0.0;
    }
    unset($row);
  }
} catch(Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blocked Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { background:#f8f9fc; }
    .table td, .table th { vertical-align: middle; }
    .badge-auto { background:#0d6efd; }
    .badge-manual { background:#6f42c1; }
    .ratio-high { color:#dc3545; font-weight:600; }
    .ratio-med { color:#fd7e14; }
    .page-header-card { border-radius:12px; }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 col-lg-2 d-md-block sidebar collapse" id="sidebarCollapse">
        <?php include 'sidebar.php'; ?>
    </div>
  <div class="col-md-10 col-lg-10 main-content">
      <div class="card shadow-sm page-header-card mb-4 p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between w-100 gap-3">
          <div>
            <h1 class="h5 mb-1"><i class="bi bi-shield-exclamation me-2"></i>Blocked Users</h1>
            <p class="text-muted small mb-0">Manage suspicious users and view fraud detection results.</p>
          </div>
          <div class="d-flex gap-2 align-items-start">
            <button id="runScanBtn" class="btn btn-sm btn-outline-primary"><i class="bi bi-play-circle me-1"></i>Run Scan</button>
            <button id="dryScanBtn" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>Dry Run</button>
            <button id="exportCsvBtn" class="btn btn-sm btn-outline-success"><i class="bi bi-download me-1"></i>CSV</button>
          </div>
        </div>
      </div>
      <div class="row g-4">
        <div class="col-lg-5 col-xl-4">
          <div class="card shadow-sm h-100">
            <div class="card-header fw-semibold">Manual Block / Unblock</div>
            <div class="card-body d-flex flex-column">
              <form id="manualBlockForm" class="mb-3" autocomplete="off">
                <div class="mb-3">
                  <label class="form-label">Customer ID</label>
                  <input type="number" class="form-control" id="mbCustomerId" placeholder="e.g. 42" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Reason</label>
                  <input type="text" class="form-control" id="mbReason" placeholder="Reason (e.g. spam orders)" required>
                </div>
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary" id="btnManualBlock">Save</button>
                  <button type="button" class="btn btn-secondary" id="btnResetForm">Reset</button>
                  <button type="button" class="btn btn-outline-success" id="btnManualUnblock">Unblock</button>
                </div>
              </form>
              <div class="border-top pt-3 small flex-grow-1 d-flex flex-column">
                <div class="d-flex justify-content-between mb-2"><span class="text-uppercase text-muted fw-semibold" style="font-size:11px;">Scan Output</span><span class="text-muted" style="font-size:11px;">JSON</span></div>
                <pre id="scanOutput" class="bg-light border rounded p-2 mb-0 flex-grow-1" style="overflow:auto;font-size:12px;line-height:1.3;">Click Run Scan or Dry Run to evaluate.</pre>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-7 col-xl-8">
          <div class="card shadow-sm h-100 d-flex flex-column">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
              <span class="fw-semibold">All Blocked Users</span>
              <div class="d-flex gap-2">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search..." style="min-width:150px;">
                <select id="typeFilter" class="form-select form-select-sm">
                  <option value="">All</option>
                  <option value="auto">Auto</option>
                  <option value="manual">Manual</option>
                </select>
                <select id="sortSelect" class="form-select form-select-sm">
                  <option value="blocked_at_desc">Newest</option>
                  <option value="blocked_at_asc">Oldest</option>
                  <option value="cancel_ratio_desc">Cancel %</option>
                  <option value="orders_24h_desc">24h Orders</option>
                </select>
              </div>
            </div>
            <div class="card-body p-0 d-flex flex-column">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="blockedTable">
                  <thead class="table-light">
                    <tr>
                      <th style="width:70px;">ID</th>
                      <th>Name</th>
                      <th>Email</th>
                      <th class="text-center">Tot</th>
                      <th class="text-center">Canc</th>
                      <th class="text-center">Ratio</th>
                      <th class="text-center">24h</th>
                      <th>Reason</th>
                      <th>Type</th>
                      <th>Blocked At</th>
                      <th style="width:50px;"></th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php if (!$blocked): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No blocked users.</td></tr>
                  <?php else: foreach($blocked as $b): ?>
                    <?php $ratioClass = ($b['cancel_ratio']>=0.5? 'ratio-high' : ($b['cancel_ratio']>=0.25? 'ratio-med':'')); ?>
                    <tr data-id="<?= (int)$b['customer_id'] ?>" data-auto="<?= (int)$b['auto_block']===1?'1':'0' ?>" data-total="<?= (int)$b['total_orders'] ?>" data-cancel="<?= (int)$b['cancelled_orders'] ?>" data-ratio="<?= number_format($b['cancel_ratio'],2) ?>" data-24h="<?= (int)$b['orders_24h'] ?>" data-blocked="<?= htmlspecialchars($b['blocked_at']) ?>">
                      <td><?= (int)$b['customer_id'] ?></td>
                      <td><?= htmlspecialchars($b['Customer_Name'] ?? 'Unknown') ?></td>
                      <td class="text-truncate" style="max-width:160px;" title="<?= htmlspecialchars($b['Customer_Email'] ?? '') ?>"><?= htmlspecialchars($b['Customer_Email'] ?? '') ?></td>
                      <td class="text-center small"><?= (int)$b['total_orders'] ?></td>
                      <td class="text-center small"><?= (int)$b['cancelled_orders'] ?></td>
                      <td class="text-center small <?= $ratioClass ?>"><?= number_format($b['cancel_ratio']*100,1) ?>%</td>
                      <td class="text-center small"><?= (int)$b['orders_24h'] ?></td>
                      <td class="small text-truncate" style="max-width:160px;" title="<?= htmlspecialchars($b['reason']) ?>"><?= htmlspecialchars($b['reason']) ?></td>
                      <td><?php if ((int)$b['auto_block']===1): ?><span class="badge badge-auto">Auto</span><?php else: ?><span class="badge badge-manual">Manual</span><?php endif; ?></td>
                      <td class="small text-nowrap"><?= htmlspecialchars($b['blocked_at']) ?></td>
                      <td><button class="btn btn-sm btn-outline-success unblock-btn" title="Unblock"><i class="bi bi-unlock"></i></button></td>
                    </tr>
                  <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>
              <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                <div class="small text-muted" id="statsSummary"></div>
                <ul class="pagination pagination-sm mb-0" id="pager"></ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function runScan(dry=false){
  const out = document.getElementById('scanOutput');
  if(out) out.textContent = 'Running scan...';
  try {
    const res = await fetch('ajax/fraud_scan.php?'+(dry?'dry=1&detail=1':'detail=1'));
    const data = await res.json();
    if(out) out.textContent = JSON.stringify(data,null,2);
    if(!dry && data.blocked_now && data.blocked_now.length){
      // reload page to reflect new blocks
      setTimeout(()=>location.reload(), 800);
    }
  } catch(e){ if(out) out.textContent = 'Error: '+e; }
}
document.getElementById('runScanBtn').addEventListener('click', ()=>runScan(false));
document.getElementById('dryScanBtn').addEventListener('click', ()=>runScan(true));

// Client-side filtering / sorting / pagination
const rows = Array.from(document.querySelectorAll('#blockedTable tbody tr'));
const searchInput = document.getElementById('searchInput');
const typeFilter = document.getElementById('typeFilter');
const sortSelect = document.getElementById('sortSelect');
const pager = document.getElementById('pager');
const statsSummary = document.getElementById('statsSummary');
const PAGE_SIZE = 15;
let currentPage = 1;

function applyFilters(){
  const term = (searchInput.value||'').toLowerCase();
  const type = typeFilter.value;
  let filtered = rows.filter(r=>{
    if(!r.getAttribute('data-id')) return false;
    const name = r.children[1]?.textContent.toLowerCase()||'';
    const email = r.children[2]?.textContent.toLowerCase()||'';
    const id = r.children[0]?.textContent.toLowerCase()||'';
    if(term && !name.includes(term) && !email.includes(term) && !id.includes(term)) return false;
    if(type==='auto' && r.getAttribute('data-auto')!=='1') return false;
    if(type==='manual' && r.getAttribute('data-auto')!=='0') return false;
    return true;
  });
  // Sort
  const mode = sortSelect.value;
  filtered.sort((a,b)=>{
    const aNum = parseFloat(a.getAttribute('data-ratio'))||0; const bNum = parseFloat(b.getAttribute('data-ratio'))||0;
    const a24 = parseInt(a.getAttribute('data-24h'))||0; const b24 = parseInt(b.getAttribute('data-24h'))||0;
    const at = a.getAttribute('data-blocked')||''; const bt = b.getAttribute('data-blocked')||'';
    switch(mode){
      case 'cancel_ratio_desc': return bNum - aNum;
      case 'orders_24h_desc': return b24 - a24;
      case 'blocked_at_asc': return at.localeCompare(bt);
      case 'blocked_at_desc': default: return bt.localeCompare(at);
    }
  });
  // Pagination
  const total = filtered.length;
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if(currentPage > totalPages) currentPage = totalPages;
  rows.forEach(r=>r.style.display='none');
  filtered.slice((currentPage-1)*PAGE_SIZE, currentPage*PAGE_SIZE).forEach(r=>r.style.display='');
  // Pager UI
  pager.innerHTML = '';
  for(let p=1;p<=totalPages;p++){
    const li=document.createElement('li'); li.className='page-item'+(p===currentPage?' active':'');
    const a=document.createElement('a'); a.className='page-link'; a.href='#'; a.textContent=p;
    a.addEventListener('click',e=>{e.preventDefault(); currentPage=p; applyFilters();});
    li.appendChild(a); pager.appendChild(li);
  }
  statsSummary.textContent = `${total} blocked users (page ${currentPage}/${totalPages})`;
}
['input','change'].forEach(ev=>searchInput.addEventListener(ev, ()=>{currentPage=1; applyFilters();}));
typeFilter.addEventListener('change', ()=>{currentPage=1; applyFilters();});
sortSelect.addEventListener('change', ()=>{currentPage=1; applyFilters();});
applyFilters();

// CSV Export
document.getElementById('exportCsvBtn').addEventListener('click', ()=>{
  const visible = rows.filter(r=>r.style.display!== 'none' && r.getAttribute('data-id'));
  const header = ['Customer_ID','Name','Email','Total_Orders','Cancelled','Cancel_Ratio','Orders_24h','Reason','Type','Blocked_At'];
  const lines=[header.join(',')];
  visible.forEach(r=>{
    const cells = r.querySelectorAll('td');
    const cid = cells[0].textContent.trim();
    const name = cells[1].textContent.trim().replace(/"/g,'""');
    const email = cells[2].textContent.trim();
    const tot = r.getAttribute('data-total');
    const can = r.getAttribute('data-cancel');
    const ratio = r.getAttribute('data-ratio');
    const h24 = r.getAttribute('data-24h');
    const reason = (cells[7]?.textContent || '').trim().replace(/"/g,'""');
    const type = r.getAttribute('data-auto')==='1'?'Auto':'Manual';
    const blockedAt = r.getAttribute('data-blocked');
    lines.push([cid,`"${name}"`,email,tot,can,ratio,h24,`"${reason}"`,type,blockedAt].join(','));
  });
  const blob = new Blob([lines.join('\n')], {type:'text/csv'});
  const url = URL.createObjectURL(blob);
  const a=document.createElement('a'); a.href=url; a.download='blocked_users.csv'; a.click();
  setTimeout(()=>URL.revokeObjectURL(url),500);
});

document.querySelectorAll('.unblock-btn').forEach(btn=>{
  btn.addEventListener('click', async function(){
    const tr = this.closest('tr');
    const id = tr.getAttribute('data-id');
    if(!confirm('Unblock customer #'+id+'?')) return;
    try {
      const res = await fetch('ajax/fraud_unblock.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({customer_id:id})});
      const data = await res.json();
      if(data.success){ tr.remove(); }
      else alert(data.message||'Failed');
    }catch(e){ alert('Error: '+e); }
    applyFilters();
  });
});

// Manual block/unblock logic
const manualForm = document.getElementById('manualBlockForm');
if(manualForm){
  const cidInput = document.getElementById('mbCustomerId');
  const reasonInput = document.getElementById('mbReason');
  document.getElementById('btnResetForm').addEventListener('click', ()=>manualForm.reset());
  manualForm.addEventListener('submit', async e => {
    e.preventDefault();
    const cid = parseInt(cidInput.value,10); if(!cid){ alert('Enter valid Customer ID'); return; }
    const reason = reasonInput.value.trim()||'Manual block';
    const btn = document.getElementById('btnManualBlock'); btn.disabled=true; btn.textContent='Saving...';
    try {
      const res = await fetch('ajax/fraud_manual_block.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({customer_id:cid,reason})});
      const data = await res.json();
      if(!data.success){ alert(data.message||'Failed'); }
      else { location.reload(); }
    }catch(err){ alert('Error: '+err); }
    btn.disabled=false; btn.textContent='Save';
  });
  document.getElementById('btnManualUnblock').addEventListener('click', async ()=>{
    const cid = parseInt(cidInput.value,10); if(!cid){ alert('Enter Customer ID'); return; }
    if(!confirm('Unblock customer #'+cid+'?')) return;
    try {
      const res = await fetch('ajax/fraud_unblock.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({customer_id:cid})});
      const data = await res.json();
      if(!data.success){ alert(data.message||'Failed'); } else { location.reload(); }
    }catch(e){ alert('Error: '+e); }
  });
}
</script>
</body>
</html>
