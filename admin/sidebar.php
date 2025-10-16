<?php
// Shared sidebar navigation for admin pages
$page = basename($_SERVER['PHP_SELF'], '.php');
// Ensure sidebar counters are available on every page that includes this file
// Also load counts if blockedUsersCount not set so its badge is always available
if (!isset($pendingProcessingCount) || !isset($unpaidPayments) || !isset($blockedUsersCount)) {
    /** @noinspection PhpIncludeInspection */
    @require_once __DIR__ . '/sidebar_counts.php';
}
?>
<div class="pt-3">
        <style>
            /* Ensure offcanvas sidebar matches desktop sidebar visuals across all pages */
            .offcanvas.offcanvas-start .offcanvas-body { 
                background: linear-gradient(180deg, var(--primary-color,#4361ee), var(--secondary-color,#3f37c9));
                color: white;
                padding: 0.75rem 0.5rem;
                min-height: 100%;
            }
            .offcanvas.offcanvas-start { --bs-offcanvas-width: 260px; }
            .offcanvas.offcanvas-start .nav { padding-left: 0.5rem; }
            .offcanvas.offcanvas-start .nav-link { color: rgba(255,255,255,0.95); }
            .offcanvas.offcanvas-start .nav-link i { color: rgba(255,255,255,0.95); }
            .offcanvas.offcanvas-start .nav-link .badge { background: rgba(255,255,255,0.12); color: white; margin-left:auto; }
            /* Keep desktop/sidebar consistent */
            .sidebar { background: linear-gradient(180deg, var(--primary-color,#4361ee), var(--secondary-color,#3f37c9)); }
        </style>
    <div class="d-flex align-items-center mb-4 px-3">
        <div class="bg-white p-2 rounded me-2">
            <i class="bi bi-shield-lock text-primary fs-4"></i>
        </div>
        <div class="logo-text fw-bold fs-5">AdminPanel</div>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link<?php if($page=='index')echo' active'; ?>" href="index.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php if($page=='admins')echo' active'; ?>" href="admins.php">
                <i class="bi bi-people-fill"></i>
                <span>Admins</span>
            </a>
        </li>
        <li class="nav-item">
            <?php $isCombined = ($page==='orders_payments'); ?>
            <a class="nav-link<?= $isCombined ? ' active' : '' ?>" href="orders_payments.php">
                <i class="bi bi-stack"></i>
                <span>Payments & Orders</span>
                <?php if (!empty($pendingProcessingCount)) : ?>
                    <span class="badge rounded-pill bg-warning text-dark float-end ms-1"><?= (int)$pendingProcessingCount ?></span>
                <?php endif; ?>
                <?php if (!empty($unpaidPayments)) : ?>
                    <span class="badge rounded-pill bg-danger float-end"><?= (int)$unpaidPayments ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php if($page=='products')echo' active'; ?>" href="products.php">
                <i class="bi bi-box-seam"></i>
                <span>Products</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php if($page=='categories')echo' active'; ?>" href="categories.php">
                <i class="bi bi-tags"></i>
                <span>Categories</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php if($page=='addons')echo' active'; ?>" href="addons.php">
                <i class="bi bi-plus-circle"></i>
                <span>Add-ons</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php if($page=='drivers')echo' active'; ?>" href="drivers.php">
                <i class="bi bi-truck"></i>
                <span>Drivers</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link<?php if($page=='blocked_users')echo' active'; ?>" href="blocked_users.php">
                <i class="bi bi-shield-exclamation"></i>
                                <span>Blocked Users</span>
                                <?php if (!empty($blockedUsersCount)): ?>
                                    <span class="badge rounded-pill bg-danger float-end ms-1"><?= (int)$blockedUsersCount ?></span>
                                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>
