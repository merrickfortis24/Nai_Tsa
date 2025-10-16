<?php
session_start();
if (!isset($_SESSION['Admin_ID']) && !isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/classes/database.php';
require_once __DIR__ . '/classes/fraud_blocker.php';
$db = new database();
$con = $db->opencon();

// Ensure tables exist (idempotent) without triggering auto-block logic on page load
$fb = new FraudBlocker();
// IMPORTANT: Do not auto-run detection here; it can immediately re-block recently unblocked users
// Run detection explicitly via the "Run Scan" button (ajax/fraud_scan.php) or a scheduled job instead.
// $fb->runDetection();

// Fetch blocked list with extra metrics (cancel ratio & recent order counts)
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
      $row['cancel_ratio'] = $tot>0 ? round($can / $tot, 2) : 0.0;
    }
    unset($row);
  }
} catch(Throwable $e) {}

// Recent blocks will be loaded via AJAX with filters & pagination
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blocked Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    body { background:#f8f9fc; }
    .table td, .table th { vertical-align: middle; }
    .badge-auto { background:#0d6efd; }
    .badge-manual { background:#6f42c1; }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
  <!-- Desktop sidebar (visible on md+) -->
  <div class="col-md-2 col-lg-2 d-none d-md-block sidebar" id="sidebarCollapse">
    <?php include 'sidebar.php'; ?>
  </div>
  <!-- Offcanvas sidebar for small screens -->
  <div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" style="--bs-offcanvas-width:260px;">
    <div class="offcanvas-body p-0">
      <?php include 'sidebar.php'; ?>
    </div>
  </div>
    <div class="col-md-10 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 mb-3 border-bottom">
        <h1 class="h4 mb-0"><i class="bi bi-shield-exclamation me-2"></i>Blocked Users</h1>
        <div>
          <button id="runScanBtn" class="btn btn-sm btn-outline-primary"><i class="bi bi-play-circle me-1"></i>Run Scan</button>
          <button id="dryScanBtn" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>Dry Run</button>
        </div>
        <!-- Sidebar toggle button for small screens (opens offcanvas) -->
  <button class="btn btn-outline-primary d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle navigation">
          <i class="bi bi-list" style="font-size:1.7rem;"></i>
        </button>
      </div>
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <div class="alert alert-warning py-2 small">
            Manual unblocks are no longer protected by a grace period. Users manually unblocked by an admin may be auto-blocked immediately if they violate the rules again.
          </div>
          <div class="mb-3">
            <h6 class="mb-2"><i class="bi bi-clock-history me-1"></i>Recent Block Events</h6>
            <div class="row g-2 align-items-end mb-2">
              <div class="col-12 col-md-3">
                <label class="form-label small mb-1">Search</label>
                <input type="text" id="rbSearch" class="form-control form-control-sm" placeholder="ID / name / email / reason">
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label small mb-1">From</label>
                <input type="date" id="rbStart" class="form-control form-control-sm">
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label small mb-1">To</label>
                <input type="date" id="rbEnd" class="form-control form-control-sm">
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Source</label>
                <select id="rbSource" class="form-select form-select-sm">
                  <option value="">All</option>
                  <option value="auto">Auto</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div class="col-6 col-md-1">
                <label class="form-label small mb-1">Page</label>
                <input type="number" id="rbPage" class="form-control form-control-sm" min="1" value="1">
              </div>
              <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Per Page</label>
                <select id="rbPageSize" class="form-select form-select-sm">
                  <option>10</option>
                  <option selected>20</option>
                  <option>50</option>
                  <option>100</option>
                </select>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-striped align-middle mb-0" id="rbTable">
                <thead class="table-light">
                  <tr>
                    <th style="width:140px">Time</th>
                    <th style="width:80px">Customer</th>
                    <th>Email</th>
                    <th>Reason</th>
                    <th style="width:80px">Source</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <div class="small text-muted" id="rbSummary"></div>
              <nav>
                <ul class="pagination pagination-sm mb-0" id="rbPager"></ul>
              </nav>
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-4 col-lg-3">
              <input type="text" id="searchInput" class="form-control" placeholder="Search name/email/ID">
            </div>
            <div class="col-md-3 col-lg-2">
              <select id="typeFilter" class="form-select">
                <option value="">All Types</option>
                <option value="auto">Auto</option>
                <option value="manual">Manual</option>
              </select>
            </div>
            <div class="col-md-3 col-lg-2">
              <select id="sortSelect" class="form-select">
                <option value="blocked_at_desc">Newest Blocked</option>
                <option value="blocked_at_asc">Oldest Blocked</option>
                <option value="cancel_ratio_desc">Cancel Ratio ↓</option>
                <option value="orders_24h_desc">24h Orders ↓</option>
              </select>
            </div>
            <div class="col-md-2 col-lg-2">
              <button id="exportCsvBtn" class="btn btn-outline-success w-100"><i class="bi bi-download me-1"></i>CSV</button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="blockedTable">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th class="text-center">Tot</th>
                  <th class="text-center">Canc</th>
                  <th class="text-center">Ratio</th>
                  <th class="text-center">24h</th>
                  <th>Reason</th>
                  <th>Type</th>
                  <th>Blocked At</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php if (!$blocked): ?>
                <tr><td colspan="11" class="text-center text-muted py-4">No blocked users.</td></tr>
              <?php else: foreach($blocked as $b): ?>
                <tr data-id="<?= (int)$b['customer_id'] ?>" data-auto="<?= (int)$b['auto_block']===1?'1':'0' ?>" data-total="<?= (int)$b['total_orders'] ?>" data-cancel="<?= (int)$b['cancelled_orders'] ?>" data-ratio="<?= number_format($b['cancel_ratio'],2) ?>" data-24h="<?= (int)$b['orders_24h'] ?>" data-blocked="<?= htmlspecialchars($b['blocked_at']) ?>">
                  <td><?= (int)$b['customer_id'] ?></td>
                  <td><?= htmlspecialchars($b['Customer_Name'] ?? 'Unknown') ?></td>
                  <td><?= htmlspecialchars($b['Customer_Email'] ?? '') ?></td>
                  <td class="text-center small"><?= (int)$b['total_orders'] ?></td>
                  <td class="text-center small"><?= (int)$b['cancelled_orders'] ?></td>
                  <td class="text-center small"><?= number_format($b['cancel_ratio'],2) ?></td>
                  <td class="text-center small"><?= (int)$b['orders_24h'] ?></td>
                  <td class="small"><?= htmlspecialchars($b['reason']) ?></td>
                  <td>
                    <?php if ((int)$b['auto_block']===1): ?>
                      <span class="badge badge-auto">Auto</span>
                    <?php else: ?>
                      <span class="badge badge-manual">Manual</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($b['blocked_at']) ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline-success unblock-btn"><i class="bi bi-unlock"></i></button>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="small text-muted" id="statsSummary"></div>
            <nav>
              <ul class="pagination pagination-sm mb-0" id="pager"></ul>
            </nav>
          </div>
        </div>
      </div>
      <div id="scanOutput" class="mt-3"></div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function runScan(dry=false){
  const out = document.getElementById('scanOutput');
  out.innerHTML = '<div class="alert alert-info">Running scan...</div>';
  try {
    const res = await fetch('ajax/fraud_scan.php?'+(dry?'dry=1&detail=1':'detail=1'));
    const data = await res.json();
    // Build pretty summary
    const blockedNow = Array.isArray(data.blocked_now) ? data.blocked_now : [];
    const skippedGrace = Array.isArray(data.skipped_due_to_grace) ? data.skipped_due_to_grace : [];
    const already = Number(data.already_blocked || 0);
    const evalCount = Number(data.evaluated_count || 0);
    const evaluated = Array.isArray(data.evaluated) ? data.evaluated : [];

    const pills = `
      <div class="mb-2">
        <span class="badge bg-success me-2">Blocked now: ${blockedNow.length}</span>
        <span class="badge bg-warning text-dark me-2">Skipped (grace): ${skippedGrace.length}</span>
        <span class="badge bg-secondary me-2">Already blocked: ${already}</span>
        <span class="badge bg-info text-dark">Evaluated: ${evalCount}</span>
      </div>`;

    const blockedList = blockedNow.length ? `<div class="small">Newly blocked IDs: ${blockedNow.join(', ')}</div>` : '';
    const skippedList = skippedGrace.length ? `<div class="small text-muted">Skipped due to grace: ${skippedGrace.join(', ')}</div>` : '';

    // Compact evaluated table (top 15)
    const rows = evaluated.slice(0, 15).map(m=>{
      const cls = m.decision==='block' ? 'table-danger' : 'table-light';
      return `<tr class="${cls}">
        <td class="small">${m.customer_id}</td>
        <td class="small text-center">${m.total_orders}</td>
        <td class="small text-center">${(m.cancel_ratio||0).toFixed(2)}</td>
        <td class="small text-center">${m.orders_last_24h}</td>
        <td class="small text-center">${m.unpaid_recent}</td>
        <td class="small">${m.decision==='block' ? '<span class="badge bg-danger">BLOCK</span>' : '<span class="badge bg-success">CLEAN</span>'}</td>
        <td class="small">${m.reason||''}</td>
      </tr>`;
    }).join('');

    const table = `
      <div class="card">
        <div class="card-header py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div><i class="bi bi-activity me-1"></i>${dry ? 'Dry Run' : 'Run Scan'} Results</div>
            <button class="btn btn-sm btn-outline-secondary" id="toggleRawBtn">Raw JSON</button>
          </div>
        </div>
        <div class="card-body">
          ${pills}
          ${blockedList}
          ${skippedList}
          <div class="table-responsive mt-2">
            <table class="table table-sm table-bordered align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:80px">Customer</th>
                  <th class="text-center" style="width:80px">Total</th>
                  <th class="text-center" style="width:90px">Cancel%</th>
                  <th class="text-center" style="width:90px">24h</th>
                  <th class="text-center" style="width:90px">Unpaid</th>
                  <th style="width:90px">Decision</th>
                  <th>Reason</th>
                </tr>
              </thead>
              <tbody>${rows || '<tr><td colspan="7" class="text-center text-muted">No candidates.</td></tr>'}</tbody>
            </table>
            <div class="small text-muted mt-1">Showing ${Math.min(15, evaluated.length)} of ${evaluated.length} evaluated.</div>
          </div>
          <pre id="rawJson" class="d-none small bg-light p-2 border rounded mt-3">${(JSON.stringify(data,null,2))}</pre>
        </div>
      </div>`;

    out.innerHTML = table;
    const toggle = document.getElementById('toggleRawBtn');
    const raw = document.getElementById('rawJson');
    if (toggle && raw) toggle.addEventListener('click', ()=>{
      raw.classList.toggle('d-none');
    });

    if(!dry && blockedNow.length){
      // reload page to reflect new blocks
      setTimeout(()=>location.reload(), 800);
    }
  } catch(e){ out.innerHTML = '<div class="alert alert-danger">Error: '+e+'</div>'; }
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

// Recent Block Events dynamic loader
const rbSearch = document.getElementById('rbSearch');
const rbStart = document.getElementById('rbStart');
const rbEnd = document.getElementById('rbEnd');
const rbSource = document.getElementById('rbSource');
const rbPage = document.getElementById('rbPage');
const rbPageSize = document.getElementById('rbPageSize');
const rbTableBody = document.querySelector('#rbTable tbody');
const rbPager = document.getElementById('rbPager');
const rbSummary = document.getElementById('rbSummary');

async function loadRecentBlocks(pageOverride){
  const page = pageOverride || parseInt(rbPage.value || '1');
  const pageSize = parseInt(rbPageSize.value || '20');
  const params = new URLSearchParams();
  params.set('page', page.toString());
  params.set('pageSize', pageSize.toString());
  const q = (rbSearch.value||'').trim(); if(q) params.set('q', q);
  const s = (rbStart.value||'').trim(); if(s) params.set('start', s);
  const e = (rbEnd.value||'').trim(); if(e) params.set('end', e);
  const src = rbSource.value; if(src) params.set('source', src);
  rbTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Loading...</td></tr>';
  try{
    const res = await fetch('ajax/fraud_recent_blocks.php?'+params.toString());
    const data = await res.json();
    if(!data.success){ throw new Error(data.message||'Failed'); }
    const items = Array.isArray(data.items)?data.items:[];
    rbTableBody.innerHTML = items.length ? items.map(it=>{
      const sourceBadge = it.admin_id ? '<span class="badge bg-secondary">Admin</span>' : '<span class="badge bg-primary">Auto</span>';
      const email = (it.Customer_Email||'').replace(/&/g,'&amp;').replace(/</g,'&lt;');
      const name = (it.Customer_Name||'Unknown').replace(/&/g,'&amp;').replace(/</g,'&lt;');
      const reason = (it.reason||'').replace(/&/g,'&amp;').replace(/</g,'&lt;');
      return `<tr>
        <td class="small text-nowrap">${it.created_at}</td>
        <td><span class="d-block fw-semibold small">#${it.customer_id}</span><span class="d-block small text-muted">${name}</span></td>
        <td class="small text-muted text-truncate" style="max-width:220px;">${email}</td>
        <td class="small">${reason}</td>
        <td>${sourceBadge}</td>
      </tr>`;
    }).join('') : '<tr><td colspan="5" class="text-center text-muted py-3">No records.</td></tr>';

    // Summary
    rbSummary.textContent = `${data.total} records (page ${data.page}/${data.totalPages})`;

    // Pager
    rbPager.innerHTML = '';
    const totalPages = Math.max(1, parseInt(data.totalPages||'1'));
    const cur = parseInt(data.page||'1');
    function addPage(p, label, active=false){
      const li=document.createElement('li'); li.className='page-item'+(active?' active':'');
      const a=document.createElement('a'); a.className='page-link'; a.href='#'; a.textContent=label||p;
      a.addEventListener('click', e=>{e.preventDefault(); rbPage.value=p; loadRecentBlocks(p);});
      li.appendChild(a); rbPager.appendChild(li);
    }
    if(totalPages>1){
      addPage(Math.max(1,cur-1), '«');
      for(let p=1;p<=totalPages;p++){
        if(p===1 || p===totalPages || Math.abs(p-cur)<=2){ addPage(p, String(p), p===cur); }
        else if(Math.abs(p-cur)===3){ const li=document.createElement('li'); li.className='page-item disabled'; li.innerHTML='<span class="page-link">…</span>'; rbPager.appendChild(li); }
      }
      addPage(Math.min(totalPages,cur+1), '»');
    }
  }catch(err){
    rbTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Error loading records</td></tr>';
  }
}

// Bind filters
['input','change'].forEach(ev=>rbSearch.addEventListener(ev, ()=>{ rbPage.value=1; loadRecentBlocks(1); }));
rbStart.addEventListener('change', ()=>{ rbPage.value=1; loadRecentBlocks(1); });
rbEnd.addEventListener('change', ()=>{ rbPage.value=1; loadRecentBlocks(1); });
rbSource.addEventListener('change', ()=>{ rbPage.value=1; loadRecentBlocks(1); });
rbPageSize.addEventListener('change', ()=>{ rbPage.value=1; loadRecentBlocks(1); });

// Initial load
loadRecentBlocks(1);
</script>
</body>
</html>
