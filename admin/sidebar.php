<?php
// Shared sidebar navigation for admin pages
$page = basename($_SERVER['PHP_SELF'], '.php');
// Ensure sidebar counters are available on every page that includes this file
if (!isset($pendingProcessingCount) || !isset($unpaidPayments)) {
    /** @noinspection PhpIncludeInspection */
    @require_once __DIR__ . '/sidebar_counts.php';
}
?>
<div class="pt-3">
    <div class="d-flex align-items-center mb-4 px-3">
        <div class="bg-white p-2 rounded me-2">
            <i class="bi bi-shield-lock text-primary fs-4"></i>
        </div>
        <div class="logo-text fw-bold fs-5">AdminPanel</div>
    </div>
    <ul class="nav flex-column">
        <?php
        // Helper to render a nav link uniformly
        function navItem($key,$href,$icon,$label,$page,$badgesHtml=''){ $active = ($page===$key)?' active':''; echo "<li class='nav-item'>\n<a class='nav-link d-flex align-items-center gap-2$active' href='$href'>\n<i class='bi $icon'></i><span class='flex-grow-1'>$label</span>$badgesHtml</a>\n</li>"; }

        navItem('index','index.php','bi-speedometer2','Dashboard',$page);
        navItem('admins','admins.php','bi-people-fill','Admins',$page);

        // Orders & Payments badges group
        $opBadges = '';
        if (!empty($pendingProcessingCount)) {
            $opBadges .= "<span class='badge rounded-pill bg-warning text-dark ms-1'>$pendingProcessingCount</span>";
        }
        if (!empty($unpaidPayments)) {
            $opBadges .= "<span class='badge rounded-pill bg-danger ms-1'>$unpaidPayments</span>";
        }
        if ($opBadges) $opBadges = "<span class='d-inline-flex ms-auto'>$opBadges</span>"; else $opBadges = "<span class='ms-auto'></span>"; // keep spacing
        navItem('orders_payments','orders_payments.php','bi-stack','Orders & Payments',$page,$opBadges);

        navItem('products','products.php','bi-box-seam','Products',$page);
        navItem('categories','categories.php','bi-tags','Categories',$page);
        navItem('addons','addons.php','bi-plus-circle','Add-ons',$page);
        navItem('drivers','drivers.php','bi-truck','Drivers',$page);

        $blockedBadge = !empty($blockedUsersCount) ? "<span class='badge rounded-pill bg-danger ms-auto'>$blockedUsersCount</span>" : "<span class='ms-auto'></span>";
        // For blocked users we want the badge on the far right without extra wrapper since only one badge
        $active = ($page==='blocked_users')?' active':'';
        echo "<li class='nav-item'>\n<a class='nav-link d-flex align-items-center gap-2$active' href='blocked_users.php'>\n<i class='bi bi-shield-exclamation'></i><span class='flex-grow-1'>Blocked Users</span>" . (!empty($blockedUsersCount)?"<span class='badge rounded-pill bg-danger'>$blockedUsersCount</span>":"") . "</a>\n</li>";

        navItem('logout','logout.php','bi-box-arrow-right','Logout',$page);
        ?>
    </ul>
</div>
