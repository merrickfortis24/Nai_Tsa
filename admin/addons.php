<?php
session_start();
require_once __DIR__ . '/../includes/remember.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Add-ons</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard-page">
<div class="container-fluid">
  <div class="row">
    <!-- Desktop sidebar (visible on md+) -->
    <div class="col-md-2 col-lg-2 d-none d-md-block sidebar" id="sidebarCollapse">
      <?php include 'sidebar.php'; ?>
    </div>
  <!-- Offcanvas sidebar for small screens (moved to end of page) -->
    <!-- Main Content -->
    <div class="col-md-10 col-lg-10 main-content">
      <div class="header d-flex justify-content-between align-items-center mt-3">
        <div>
          <h4 class="mb-0 fw-bold">Add-ons</h4>
          <p class="mb-0 text-muted">Manage add-ons and product mappings</p>
        </div>
  <!-- Sidebar toggle button for small screens (opens offcanvas) -->
  <button class="btn btn-outline-primary d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle navigation">
          <i class="bi bi-list" style="font-size:1.7rem;"></i>
        </button>
      </div>

  <div class="row g-4">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-header">Create / Edit Add-on</div>
        <div class="card-body">
          <form id="addonForm" class="vstack gap-3">
            <input type="hidden" id="addon_id">
            <div>
              <label class="form-label">Name</label>
              <input class="form-control" id="addon_name" required>
            </div>
            <div>
              <label class="form-label">Price</label>
              <input class="form-control" id="addon_price" type="number" min="0" step="0.01" value="0.00" required>
            </div>
            <div>
              <label class="form-label">Status</label>
              <select class="form-select" id="addon_status">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-primary" type="submit">Save</button>
              <button class="btn btn-secondary" type="button" id="resetBtn">Reset</button>
              <button class="btn btn-danger ms-auto" type="button" id="deleteBtn" style="display:none;">Delete</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-md-7">
      <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span>All Add-ons</span>
          <input id="addonSearch" class="form-control form-control-sm" placeholder="Search..." style="width:220px;">
        </div>
        <div class="table-responsive" style="max-height: 420px;">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:60px;">ID</th>
                <th>Name</th>
                <th style="width:120px;">Price</th>
                <th style="width:110px;">Status</th>
                <th style="width:80px;">Action</th>
              </tr>
            </thead>
            <tbody id="addonsTbody"></tbody>
          </table>
        </div>
      </div>

  <!-- Product assignment section removed per request -->
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let ADDONS = [];

async function fetchAll(){
  const res = await fetch('ajax/fetch_addons.php?ts=' + Date.now());
  const data = await res.json();
  if(!data.success) throw new Error(data.message||'Failed to load');
  ADDONS = data.addons || [];
  renderAddons();
}

function money(v){ return Number(v).toFixed(2); }

function renderAddons(filter=''){
  const tbody = document.getElementById('addonsTbody');
  const q = filter.toLowerCase();
  tbody.innerHTML = '';
  (ADDONS||[]).filter(a=>!q || a.Addon_Name.toLowerCase().includes(q)).forEach(a=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${a.Addon_ID}</td>
      <td>${a.Addon_Name}</td>
      <td>₱ ${money(a.Addon_Price)}</td>
      <td><span class="badge ${a.Status==='Active'?'text-bg-success':'text-bg-secondary'}">${a.Status}</span></td>
      <td><button class="btn btn-sm btn-outline-primary" data-edit="${a.Addon_ID}">Edit</button></td>
    `;
    tbody.appendChild(tr);
  });
}

// Product mapping UI removed.

// Mapping logic removed.

// Search
document.getElementById('addonSearch').addEventListener('input', (e)=>{
  renderAddons(e.target.value);
});

// Edit from table
document.getElementById('addonsTbody').addEventListener('click',(e)=>{
  const btn = e.target.closest('[data-edit]');
  if(!btn) return;
  const id = Number(btn.getAttribute('data-edit'));
  const a = (ADDONS||[]).find(x=>Number(x.Addon_ID)===id);
  if(!a) return;
  document.getElementById('addon_id').value = a.Addon_ID;
  document.getElementById('addon_name').value = a.Addon_Name;
  document.getElementById('addon_price').value = money(a.Addon_Price);
  document.getElementById('addon_status').value = a.Status;
  document.getElementById('deleteBtn').style.display = '';
});

// Reset form
document.getElementById('resetBtn').addEventListener('click', ()=>{
  document.getElementById('addonForm').reset();
  document.getElementById('addon_id').value = '';
  document.getElementById('addon_price').value = '0.00';
  document.getElementById('deleteBtn').style.display = 'none';
});

// Save (create/update)
document.getElementById('addonForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const id = document.getElementById('addon_id').value;
  const name = document.getElementById('addon_name').value.trim();
  const price = document.getElementById('addon_price').value;
  const status = document.getElementById('addon_status').value;
  const form = new FormData();
  if(id) form.append('id', id);
  form.append('name', name);
  form.append('price', price);
  form.append('status', status);
  const url = id ? 'ajax/update_addon.php' : 'ajax/add_addon.php';
  const res = await fetch(url, { method:'POST', body: form });
  const data = await res.json();
  if(!data.success){ alert(data.message||'Failed'); return; }
  await fetchAll();
  document.getElementById('resetBtn').click();
});

// Delete
document.getElementById('deleteBtn').addEventListener('click', async ()=>{
  const id = document.getElementById('addon_id').value;
  if(!id) return;
  if(!confirm('Delete this add-on?')) return;
  const form = new FormData();
  form.append('id', id);
  const res = await fetch('ajax/delete_addon.php', { method:'POST', body: form });
  const data = await res.json();
  if(!data.success){ alert(data.message||'Failed'); return; }
  await fetchAll();
  document.getElementById('resetBtn').click();
});

fetchAll().catch(err=>alert(err.message||String(err)));
</script>
</body>
</html>
<?php include 'offcanvas_sidebar.php'; ?>
