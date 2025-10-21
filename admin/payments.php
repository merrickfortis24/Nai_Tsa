<?php

session_start();
// Attempt to restore session from remember-me cookie if present
require_once __DIR__ . '/../includes/remember.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once('classes/database.php');

$db = new database();
$error = '';

// --- Backend: Filtering, Search, Pagination ---
$search = trim($_GET['search'] ?? ''); // customer name
$statusFilter = $_GET['status'] ?? ''; // Paid / Unpaid
$methodFilter = $_GET['method'] ?? ''; // Payment method
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Handle POST updates (payment status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['payment_status'], $_POST['payment_id'])) {
            $db->updatePaymentStatus($_POST['payment_id'], $_POST['payment_status']);
        }
    } catch (Throwable $e) {
        $error = 'Update failed: ' . $e->getMessage();
    }
    // Redirect back preserving current filters
    $qs = $_GET;
    header('Location: payments.php' . ($qs ? ('?' . http_build_query($qs)) : ''));
    exit();
}

// Fetch + in-PHP filter (can be optimized later with SQL WHERE + LIMIT)
try {
    $allPayments = $db->getAllPayments();
    if ($search) {
        $allPayments = array_filter($allPayments, function($p) use ($db, $search) {
            $name = strtolower($db->getCustomerNameByOrderId($p['Order_ID']));
            return strpos($name, strtolower($search)) !== false;
        });
    }
    if ($statusFilter) {
        $allPayments = array_filter($allPayments, function($p) use ($statusFilter) {
            return strtolower($p['payment_status'] ?? 'Unpaid') === strtolower($statusFilter);
        });
    }
    if ($methodFilter) {
        $allPayments = array_filter($allPayments, function($p) use ($methodFilter) {
            return strtolower($p['Payment_Method']) === strtolower($methodFilter);
        });
    }
    if ($from || $to) {
        $fromTs = $from ? strtotime($from . ' 00:00:00') : null;
        $toTs = $to ? strtotime($to . ' 23:59:59') : null;
        $allPayments = array_filter($allPayments, function($p) use ($fromTs, $toTs) {
            $ts = strtotime($p['Payment_Date']);
            if ($fromTs && $ts < $fromTs) return false;
            if ($toTs && $ts > $toTs) return false;
            return true;
        });
    }
    $totalPayments = count($allPayments);
    $payments = array_slice(array_values($allPayments), $offset, $perPage);
    $totalPages = max(1, ceil($totalPayments / $perPage));
} catch (PDOException $e) {
    $payments = [];
    $totalPayments = 0;
    $totalPages = 1;
    $error = "Database Error: " . $e->getMessage();
}

// Stats (reuse existing helpers)
$unpaidPayments = $db->countUnpaidPayments();
$pendingOrders = $db->countPendingOrProcessingOrders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
                <!-- Desktop sidebar (visible on md+) -->
                <div class="col-md-2 col-lg-2 d-none d-md-block sidebar" id="sidebarCollapse">
                <?php include 'sidebar.php'; ?>
                </div>
                <!-- Offcanvas sidebar for small screens (moved to end of page) -->
            </div>
            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 main-content">
                <div class="header d-flex justify-content-between align-items-center">
                        <div class="mt-3">
                        <h4 class="mb-0 fw-bold">Payments</h4>
                        <p class="mb-0 text-muted">List of all payments</p>
                    </div>
                    <!-- Sidebar toggle button for small screens (opens offcanvas) -->
                    <button class="btn btn-outline-primary d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle navigation">
                        <i class="bi bi-list" style="font-size:1.7rem;"></i>
                    </button>
                </div>
                </div>
                <div class="card mt-3 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="fw-semibold"><i class="bi bi-credit-card me-1"></i> Payments List</span>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <form method="get" action="payments.php" class="d-flex align-items-center gap-2 mb-0">
                                <input type="text" class="form-control form-control-sm" name="search" placeholder="Search customer..." value="<?= htmlspecialchars($search) ?>" style="max-width:170px;" />
                                <select class="form-select form-select-sm" name="status" onchange="this.form.page && (this.form.page.value=1); this.form.submit();">
                                    <option value="">All Status</option>
                                    <option value="Paid" <?= $statusFilter==='Paid'?'selected':'' ?>>Paid</option>
                                    <option value="Unpaid" <?= $statusFilter==='Unpaid'?'selected':'' ?>>Unpaid</option>
                                </select>
                                <?php
                                    // Collect unique payment methods for filter
                                    $methodsSet = [];
                                    if (!empty($allPayments)) {
                                        foreach ($allPayments as $p) { $methodsSet[strtolower($p['Payment_Method'])] = $p['Payment_Method']; }
                                    } elseif (!empty($payments)) { // fallback when filtered earlier
                                        foreach ($payments as $p) { $methodsSet[strtolower($p['Payment_Method'])] = $p['Payment_Method']; }
                                    }
                                    ksort($methodsSet);
                                ?>
                                <select class="form-select form-select-sm" name="method" onchange="this.form.page && (this.form.page.value=1); this.form.submit();" style="min-width:130px;">
                                    <option value="">All Methods</option>
                                    <?php foreach ($methodsSet as $raw): ?>
                                        <option value="<?= htmlspecialchars($raw) ?>" <?= strtolower($methodFilter)===strtolower($raw)?'selected':'' ?>><?= htmlspecialchars($raw) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="d-flex align-items-center gap-1">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">From</span>
                                        <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>" aria-label="From date (start of range)" />
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">To</span>
                                        <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>" aria-label="To date (end of range)" />
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Filter payments whose payment date is between From (inclusive) and To (inclusive). Leave either blank for an open-ended range.">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                </div>
                                <?php if (isset($_GET['page'])): ?>
                                    <input type="hidden" name="page" value="<?= (int)$_GET['page'] ?>">
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary" type="submit" title="Search / Apply"><i class="bi bi-search"></i></button>
                                <a href="payments.php" class="btn btn-sm btn-outline-secondary" title="Reset filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if($search || $statusFilter || $methodFilter || $from || $to): ?>
                            <div class="mb-3 small">
                                <span class="text-muted me-1">Active filters:</span>
                                <?php if($search): ?><span class="badge text-bg-primary">Name: <?= htmlspecialchars($search) ?></span> <?php endif; ?>
                                <?php if($statusFilter): ?><span class="badge text-bg-success">Status: <?= htmlspecialchars($statusFilter) ?></span> <?php endif; ?>
                                <?php if($methodFilter): ?><span class="badge text-bg-secondary">Method: <?= htmlspecialchars($methodFilter) ?></span> <?php endif; ?>
                                <?php if($from): ?><span class="badge text-bg-info">From: <?= htmlspecialchars($from) ?></span> <?php endif; ?>
                                <?php if($to): ?><span class="badge text-bg-info">To: <?= htmlspecialchars($to) ?></span> <?php endif; ?>
                                <a href="payments.php" class="badge text-bg-light border text-decoration-none">Clear</a>
                            </div>
                        <?php endif; ?>
                        <div class="row g-3 mb-3">
                            <div class="col-6 col-md-3">
                                <div class="p-2 rounded bg-light border small text-center">
                                    <div class="text-muted">Total Records</div>
                                    <div class="fw-semibold"><?= number_format($totalPayments) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 rounded bg-light border small text-center">
                                    <div class="text-muted">Unpaid</div>
                                    <div class="fw-semibold text-danger"><?= number_format($unpaidPayments) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="p-2 rounded bg-light border small text-center">
                                    <div class="text-muted">Pending/Processing Orders</div>
                                    <div class="fw-semibold text-warning"><?= number_format($pendingOrders) ?></div>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <?php $customerName = $db->getCustomerNameByOrderId($payment['Order_ID']); ?>
                                        <tr<?= (isset($payment['order_status']) && $payment['order_status'] === 'Cancelled') ? ' class="table-danger"' : '' ?>>
                                            <td class="fw-semibold"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($customerName) ?></td>
                                            <td class="fw-bold text-primary">₱<?= number_format($payment['Payment_Amount'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-secondary-subtle border text-dark">
                                                    <i class="bi bi-wallet2 me-1"></i><?= htmlspecialchars($payment['Payment_Method']) ?>
                                                </span>
                                            </td>
                                            <td><span class="text-muted small"><i class="bi bi-calendar-event me-1"></i><?= date('M j, Y g:i A', strtotime($payment['Payment_Date'])) ?></span></td>
                                            <td>
                                                <?php if (isset($payment['order_status']) && $payment['order_status'] === 'Cancelled'): ?>
                                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Cancelled</span>
                                                <?php else: ?>
                                                    <form method="post" action="payments.php?<?= http_build_query($_GET) ?>" style="display:inline;">
                                                        <input type="hidden" name="payment_id" value="<?= $payment['Payment_ID'] ?>">
                                                        <select
                                                            name="payment_status"
                                                            class="form-select form-select-sm payment-status-select <?php
                                                                if (($payment['payment_status'] ?? '') === 'Unpaid') echo ' bg-warning text-dark';
                                                                elseif (($payment['payment_status'] ?? '') === 'Paid') echo ' bg-success text-white';
                                                            ?>"
                                                            onchange="this.form.submit()"
                                                            style="min-width:110px;"
                                                        >
                                                            <?php foreach (['Unpaid','Paid'] as $status): $sel = (($payment['payment_status'] ?? '') === $status)?'selected':''; ?>
                                                                <option value="<?= $status ?>" <?= $sel ?>><?= $status ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <nav aria-label="Payments pagination">
                            <ul class="pagination justify-content-end">
                                <li class="page-item <?= $page==1?'disabled':'' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET,["page"=>$page-1])) ?>">Previous</a>
                                </li>
                                <?php for($i=1;$i<=$totalPages;$i++): ?>
                                    <li class="page-item <?= $page==$i?'active':'' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET,["page"=>$i])) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page==$totalPages?'disabled':'' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET,["page"=>$page+1])) ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
        <div id="notifToastContainer"></div>
    </div>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Close alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

document.querySelectorAll('.payment-status-select').forEach(function(select) {
    select.addEventListener('change', function() {
        select.classList.remove('bg-warning', 'bg-success', 'text-dark', 'text-white');
        if (select.value === 'Unpaid') {
            select.classList.add('bg-warning', 'text-dark');
        } else if (select.value === 'Paid') {
            select.classList.add('bg-success', 'text-white');
        }
    });
});

// Notification polling and toast display
(function(){
    const container = document.getElementById('notifToastContainer');
    if (!container) return;

    function showToast(title, message) {
        const wrap = document.createElement('div');
        wrap.innerHTML = `
        <div class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="7000">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br/>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>`;
        const toastEl = wrap.firstElementChild;
        container.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        return toastEl;
    }

    async function poll() {
        try {
            const res = await fetch('ajax/notifications_list.php', { cache: 'no-store' });
            if (!res.ok) throw new Error('net');
            const data = await res.json();
            if (data && data.success && Array.isArray(data.data) && data.data.length) {
                const ids = [];
                data.data.forEach(n => {
                    ids.push(n.Notification_ID);
                    showToast(n.Title || 'Notification', n.Message || '');
                });
                // Mark read
                fetch('ajax/notifications_mark_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids })
                }).catch(()=>{});
            }
        } catch (e) {
            // ignore
        } finally {
            setTimeout(poll, 6000);
        }
    }
    poll();
})();
    </script>
</body>
</html>
<?php include 'offcanvas_sidebar.php'; ?>