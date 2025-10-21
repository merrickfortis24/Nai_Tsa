<?php
/**
 * FraudBlocker
 * Lightweight heuristic-based fake / abusive booking detector & blocker.
 *
 * Usage (admin context):
 *   require_once 'fraud_blocker.php';
 *   $fb = new FraudBlocker();
 *   $result = $fb->runDetection();
 *   echo json_encode($result);
 *
 * Heuristics (tunable):
 *  - cancel_ratio >= 0.60 with >=3 cancels & total_orders >= 5
 *  - orders_last_24h >= 8 AND distinct_addresses_last_24h >= 5
 *  - unpaid_streak_recent >= 3 (recent unpaid payments)
 *  - burst_orders_20m >= 4 (spam burst)
 */
class FraudBlocker extends database {
    private array $config = [
        'min_orders_for_ratio'        => 5,
        'cancel_ratio_block'          => 0.60,
        'min_cancels_block'           => 3,
        'orders_24h_block'            => 8,
        'distinct_addresses_24h'      => 5,
        'unpaid_streak_block'         => 3,
        'burst_minutes'               => 20,
        'burst_count_block'           => 4,
        'simulate'                    => false, // set true for dry-run
    // Respect manual admin decisions: do not auto-reblock within this grace window (hours)
    // Set to 0 to disable grace; admin manual unblocks will not prevent immediate re-blocking.
    'manual_unblock_grace_hours'  => 0,
    ];

    public function __construct(array $overrides = []) {
        foreach ($overrides as $k=>$v) {
            if (array_key_exists($k,$this->config)) $this->config[$k] = $v;
        }
    }

    /** Quick check used by user-side checkout (mirrors users/classes/database.php) */
    public function isCustomerBlocked(int $customerId): bool {
        try {
            $con = $this->opencon();
            // Fast path: customer.is_blocked column (if present)
            $hasCol = false;
            try { $c = $con->query("SHOW COLUMNS FROM customer LIKE 'is_blocked'"); $hasCol = $c && $c->rowCount()>0; } catch(Throwable $e){}
            if ($hasCol) {
                $s = $con->prepare("SELECT is_blocked FROM customer WHERE Customer_ID=? LIMIT 1");
                $s->execute([$customerId]);
                $v = $s->fetchColumn();
                if ($v !== false) return (int)$v === 1;
            }
            // Fallback to blocked_users table
            $s2 = $con->prepare("SELECT 1 FROM blocked_users WHERE customer_id=? LIMIT 1");
            $s2->execute([$customerId]);
            return (bool)$s2->fetchColumn();
        } catch(Throwable $e) { return false; }
    }

    /** Gather candidate customer IDs (recent order activity) */
    private function candidateCustomerIds(): array {
        $con = $this->opencon();
        $ids = [];
        $sql = "SELECT DISTINCT Customer_ID FROM orders WHERE Order_Date >= NOW() - INTERVAL 7 DAY";
        foreach ($con->query($sql) as $row) {
            $cid = (int)$row['Customer_ID'];
            if ($cid > 0) $ids[] = $cid;
        }
        return $ids;
    }

    /** Compute metrics & decision for one customer */
    public function evaluateCustomer(int $customerId): array {
        $con = $this->opencon();
        $m = [
            'customer_id' => $customerId,
            'total_orders' => 0,
            'cancelled_orders' => 0,
            'cancel_ratio' => 0.0,
            'orders_last_24h' => 0,
            'distinct_addresses_last_24h' => 0,
            'unpaid_recent' => 0,
            'burst_orders_20m' => 0,
            'decision' => 'clean',
            'reason' => ''
        ];

        // Aggregate totals / cancels
        $stmt = $con->prepare("SELECT COUNT(*) total, SUM(order_status='Cancelled') cancels FROM orders WHERE Customer_ID=?");
        $stmt->execute([$customerId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $m['total_orders'] = (int)$row['total'];
            $m['cancelled_orders'] = (int)($row['cancels'] ?? 0);
            if ($m['total_orders']>0) $m['cancel_ratio'] = $m['cancelled_orders'] / $m['total_orders'];
        }

        // Orders last 24h
        $stmt = $con->prepare("SELECT COUNT(*) FROM orders WHERE Customer_ID=? AND Order_Date >= NOW()-INTERVAL 1 DAY");
        $stmt->execute([$customerId]);
        $m['orders_last_24h'] = (int)$stmt->fetchColumn();

        // Distinct addresses (delivery) last 24h (order_address table may not exist)
        try {
            $chk = $con->query("SHOW TABLES LIKE 'order_address'");
            if ($chk && $chk->rowCount()>0) {
                $stmt = $con->prepare("SELECT COUNT(DISTINCT CONCAT(COALESCE(a.Street,''),'|',COALESCE(a.Barangay,''),'|',COALESCE(a.City,'')))
                    FROM order_address a JOIN orders o ON a.Order_ID=o.Order_ID
                    WHERE o.Customer_ID=? AND o.Order_Date >= NOW()-INTERVAL 1 DAY");
                $stmt->execute([$customerId]);
                $m['distinct_addresses_last_24h'] = (int)$stmt->fetchColumn();
            }
        } catch(Throwable $e) {}

        // Recent unpaid (treat as streak if sequential) – simple count of unpaid in last 10 orders
        $stmt = $con->prepare("SELECT p.payment_status FROM orders o JOIN payment p ON p.Order_ID=o.Order_ID WHERE o.Customer_ID=? ORDER BY o.Order_Date DESC LIMIT 10");
        $stmt->execute([$customerId]);
        $unpaidStreak = 0;
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (($r['payment_status'] ?? '') === 'Unpaid') $unpaidStreak++; else break; // streak until first paid
        }
        $m['unpaid_recent'] = $unpaidStreak;

    // Burst orders last X minutes (use named placeholders only; mixing causes PDO errors)
    $stmt = $con->prepare("SELECT COUNT(*) FROM orders WHERE Customer_ID=:cid AND Order_Date >= NOW()-INTERVAL :mins MINUTE");
    $stmt->bindValue(':cid', $customerId, PDO::PARAM_INT);
    $stmt->bindValue(':mins', (int)$this->config['burst_minutes'], PDO::PARAM_INT);
    $stmt->execute();
    $m['burst_orders_20m'] = (int)$stmt->fetchColumn();

        // Decision logic
        if ($m['total_orders'] >= $this->config['min_orders_for_ratio']
            && $m['cancelled_orders'] >= $this->config['min_cancels_block']
            && $m['cancel_ratio'] >= $this->config['cancel_ratio_block']) {
            $m['decision'] = 'block';
            $m['reason'] = 'High cancel ratio';
        } elseif ($m['orders_last_24h'] >= $this->config['orders_24h_block']
            && $m['distinct_addresses_last_24h'] >= $this->config['distinct_addresses_24h']) {
            $m['decision'] = 'block';
            $m['reason'] = 'Many distinct addresses in 24h';
        } elseif ($m['unpaid_recent'] >= $this->config['unpaid_streak_block']) {
            $m['decision'] = 'block';
            $m['reason'] = 'Unpaid streak';
        } elseif ($m['burst_orders_20m'] >= $this->config['burst_count_block']) {
            $m['decision'] = 'block';
            $m['reason'] = 'Burst spam orders';
        }

        return $m;
    }

    /** Was this customer manually unblocked within the grace window? */
    private function recentlyUnblocked(int $customerId): bool {
        try {
            $con = $this->opencon();
            $stmt = $con->prepare("SELECT created_at FROM blocked_users_log WHERE customer_id=? AND action='UNBLOCK' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$customerId]);
            $ts = $stmt->fetchColumn();
            if (!$ts) return false;
            // If grace window is disabled (<=0), never treat a recent unblock as protected
            $graceHours = (int)$this->config['manual_unblock_grace_hours'];
            if ($graceHours <= 0) return false;
            $graceSeconds = $graceHours * 3600;
            return (strtotime($ts) >= (time() - $graceSeconds));
        } catch(Throwable $e) { return false; }
    }

    /** Execute detection across candidates */
    public function runDetection(): array {
        $blocked = [];
        $skipped_grace = [];
        $evaluated = [];
        $already = 0;
        $con = $this->opencon();
        foreach ($this->candidateCustomerIds() as $cid) {
            $metrics = $this->evaluateCustomer($cid);
            $evaluated[] = $metrics;
            if ($metrics['decision'] === 'block' && !$this->isCustomerBlocked($cid)) {
                // Honor manual unblocks within grace period; do not re-block immediately
                if ($this->recentlyUnblocked($cid)) {
                    $skipped_grace[] = $cid;
                    continue;
                }
                if (!$this->config['simulate']) {
                    $this->blockCustomer($cid, $metrics['reason']);
                }
                $blocked[] = $cid;
            } elseif ($metrics['decision'] === 'block') {
                $already++;
            }
        }
        return [
            'success' => true,
            'simulate' => (bool)$this->config['simulate'],
            'blocked_now' => $blocked,
            'skipped_due_to_grace' => $skipped_grace,
            'already_blocked' => $already,
            'evaluated_count' => count($evaluated),
            'evaluated' => $evaluated
        ];
    }

    /** Persist block + log + notification */
    public function blockCustomer(int $customerId, string $reason, ?int $adminId = null): bool {
        try {
            $con = $this->opencon();
            // Ensure tables/column exist (idempotent, ignore failures if permissions restricted)
            $this->ensureStructures($con);
            $ins = $con->prepare("INSERT INTO blocked_users (customer_id, reason, blocked_by, auto_block, blocked_at)
                VALUES (?,?,?,?,NOW())
                ON DUPLICATE KEY UPDATE reason=VALUES(reason), blocked_at=NOW(), auto_block=VALUES(auto_block)");
            $ins->execute([$customerId, $reason, $adminId, 1]);
            // Customer flag
            try { $con->exec("UPDATE customer SET is_blocked=1 WHERE Customer_ID=".(int)$customerId); } catch(Throwable $e){}
            $this->logEvent($customerId, 'BLOCK', $reason, $adminId);
            $this->notify("Fraud block", "Customer #{$customerId} blocked: {$reason}");
            return true;
        } catch(Throwable $e) {
            error_log('FraudBlocker blockCustomer error: '.$e->getMessage());
            return false;
        }
    }

    public function logEvent(int $customerId, string $action, string $reason, ?int $adminId = null): void {
        try {
            $con = $this->opencon();
            $con->prepare("INSERT INTO blocked_users_log (customer_id, action, reason, admin_id) VALUES (?,?,?,?)")
                ->execute([$customerId, $action, $reason, $adminId]);
        } catch(Throwable $e) { /* ignore */ }
    }

    private function notify(string $title, string $message): void {
        try {
            $con = $this->opencon();
            // notifications table may vary; attempt minimal insert
            $stmt = $con->prepare("INSERT INTO notifications (Title, Message, Type, Created_At) VALUES (?,?,?,NOW())");
            $stmt->execute([$title, $message, 'fraud']);
        } catch(Throwable $e) { /* ignore */ }
    }

    private function ensureStructures(PDO $con): void {
        try {
            $con->exec("CREATE TABLE IF NOT EXISTS blocked_users (
                customer_id INT PRIMARY KEY,
                blocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                reason VARCHAR(255) NOT NULL,
                blocked_by INT NULL,
                auto_block TINYINT(1) NOT NULL DEFAULT 1,
                last_eval DATETIME NULL,
                INDEX idx_blocked_at (blocked_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch(Throwable $e) {}
        try {
            $con->exec("CREATE TABLE IF NOT EXISTS blocked_users_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                action ENUM('BLOCK','UNBLOCK','EVALUATE') NOT NULL,
                reason VARCHAR(255) NOT NULL,
                admin_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_cid (customer_id),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch(Throwable $e) {}
        // customer.is_blocked column
        try {
            $col = $con->query("SHOW COLUMNS FROM customer LIKE 'is_blocked'");
            if (!$col || $col->rowCount() === 0) {
                $con->exec("ALTER TABLE customer ADD COLUMN is_blocked TINYINT(1) NOT NULL DEFAULT 0 AFTER Contact_Number");
            }
        } catch(Throwable $e) {}
    }
}
?>
