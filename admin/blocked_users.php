<?php
session_start();
if (!isset($_SESSION['Admin_ID']) && !isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/classes/database.php';
require_once __DIR__ . '/classes/fraud_blocker.php';
$db = new database();
$con = $db->opencon();

// Ensure tables exist (idempotent)
$fb = new FraudBlocker();
$fb->runDetection(); // passive run to ensure structures; actual blocking only if heuristics trigger

// Fetch blocked list
$blocked = [];
try {
    $chk = $con->query("SHOW TABLES LIKE 'blocked_users'");
    if ($chk && $chk->rowCount()>0) {
        $stmt = $con->query("SELECT b.customer_id, b.blocked_at, b.reason, b.auto_block, c.Customer_Name, c.Customer_Email FROM blocked_users b LEFT JOIN customer c ON c.Customer_ID=b.customer_id ORDER BY b.blocked_at DESC");
        $blocked = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-2 col-lg-2 d-md-block sidebar collapse" id="sidebarCollapse">
        <?php include 'sidebar.php'; ?>
    </div>
    <div class="col-md-10 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 mb-3 border-bottom">
        <h1 class="h4 mb-0"><i class="bi bi-shield-exclamation me-2"></i>Blocked Users</h1>
        <div>
          <button id="runScanBtn" class="btn btn-sm btn-outline-primary"><i class="bi bi-play-circle me-1"></i>Run Scan</button>
          <button id="dryScanBtn" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye me-1"></i>Dry Run</button>
        </div>
      </div>
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="blockedTable">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Reason</th>
                  <th>Type</th>
                  <th>Blocked At</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
              <?php if (!$blocked): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No blocked users.</td></tr>
              <?php else: foreach($blocked as $b): ?>
                <tr data-id="<?= (int)$b['customer_id'] ?>">
                  <td><?= (int)$b['customer_id'] ?></td>
                  <td><?= htmlspecialchars($b['Customer_Name'] ?? 'Unknown') ?></td>
                  <td><?= htmlspecialchars($b['Customer_Email'] ?? '') ?></td>
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
        </div>
      </div>
      <div id="scanOutput" class="mt-3"></div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function runScan(dry=false){
  const out = document.getElementById('scanOutput');
  out.innerHTML = '<div class="alert alert-info">Running scan...</div>';
  try {
    const res = await fetch('ajax/fraud_scan.php?'+(dry?'dry=1&detail=1':'detail=1'));
    const data = await res.json();
    out.innerHTML = '<pre class="small bg-light p-3 border rounded mb-0">'+JSON.stringify(data,null,2)+'</pre>';
    if(!dry && data.blocked_now && data.blocked_now.length){
      // reload page to reflect new blocks
      setTimeout(()=>location.reload(), 800);
    }
  } catch(e){ out.innerHTML = '<div class="alert alert-danger">Error: '+e+'</div>'; }
}
document.getElementById('runScanBtn').addEventListener('click', ()=>runScan(false));
document.getElementById('dryScanBtn').addEventListener('click', ()=>runScan(true));

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
  });
});
</script>
</body>
</html>
