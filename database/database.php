<?php
 
class database {
    // Hostinger DB credentials
    private string $host = 'mysql.hostinger.com';                 // from hPanel
    private string $db   = 'u677397674_naitsa';            // MySQL Database
    private string $user = 'u677397674_naitsa_user';            // MySQL User
    private string $pass = 'Naitsa@123';                // set in hPanel

    private static ?PDO $pdo = null;

    public function opencon(): PDO {
        if (self::$pdo instanceof PDO) return self::$pdo;

        $dsn = "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4";
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // real prepares
            PDO::ATTR_PERSISTENT         => false,   // safer on shared hosting
        ];
        try {
            self::$pdo = new PDO($dsn, $this->user, $this->pass, $opts);
        } catch (PDOException $e) {
            error_log('DB connect error: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed.');
        }
        return self::$pdo;
    }

    // (Email verification columns ensured manually via SQL migration)

    function addCustomer($name, $email, $password) {
        $con = $this->opencon();
        $stmt = $con->prepare("INSERT INTO customer (Customer_Name, Customer_Email, Customer_Password) VALUES (?, ?, ?)");
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        return $stmt->execute([$name, $email, $hashed]);
    }

    function checkEmailExists($email) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT COUNT(*) FROM customer WHERE Customer_Email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    // New: Register customer with duplicate check
    function registerCustomer($name, $email, $password) {
        if ($this->checkEmailExists($email)) {
            return "Email already registered!";
        }
        $success = $this->addCustomer($name, $email, $password);
        if ($success) {
            return true;
        } else {
            return "Sign up failed. Please try again.";
        }
    }

    public function getUserByEmail($email, $account_type) {
        $con = $this->opencon();
        if ($account_type === 'admin') {
            $stmt = $con->prepare("SELECT * FROM admin WHERE Admin_Email = ?");
        } else {
            $stmt = $con->prepare("SELECT * FROM customer WHERE Customer_Email = ?");
        }
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getRecommendedProducts($customer_id, $limit = 4) {
        $con = $this->opencon();
        // Native MySQL prepares can't bind LIMIT with emulation off. Cast and inline.
        $limit = max(1, (int)$limit);

        $sql = "
            SELECT p.*
            FROM product p
            JOIN order_item oi ON p.Product_ID = oi.Product_ID
            JOIN orders o ON oi.Order_ID = o.Order_ID
            WHERE o.Customer_ID = ?
            GROUP BY p.Product_ID
            ORDER BY COUNT(*) DESC
            LIMIT $limit
        ";
        $stmt = $con->prepare($sql);
        $stmt->execute([(int)$customer_id]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$products) {
            $sql = "
                SELECT p.*
                FROM product p
                JOIN order_item oi ON p.Product_ID = oi.Product_ID
                GROUP BY p.Product_ID
                ORDER BY COUNT(*) DESC
                LIMIT $limit
            ";
            $stmt = $con->prepare($sql);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $products;
    }

    // Generate and store a verification token for a customer email
    public function issueVerificationToken($email) {
        $con = $this->opencon();
        $token = bin2hex(random_bytes(32));
        $stmt = $con->prepare("UPDATE customer SET verification_token = ?, verification_sent_at = NOW(), is_verified = 0 WHERE Customer_Email = ?");
        $stmt->execute([$token, $email]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        return $token;
    }

    // Verify account by token
    public function verifyByToken($token) {
        $con = $this->opencon();
        // Find customer by token
        $stmt = $con->prepare("SELECT Customer_ID FROM customer WHERE verification_token = ?");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        $cid = (int)$row['Customer_ID'];
        // Mark verified and clear token
        $upd = $con->prepare("UPDATE customer SET is_verified = 1, verified_at = NOW(), verification_token = NULL WHERE Customer_ID = ?");
        $upd->execute([$cid]);
        return $upd->rowCount() > 0;
    }

    // Issue a 6-digit OTP for email verification; stores a hash in verification_token and timestamp in verification_sent_at
    public function issueEmailOtp(string $email, int $digits = 6): array|false {
        $con = $this->opencon();
        // Generate numeric code
        $min = (int) pow(10, $digits - 1);
        $max = (int) pow(10, $digits) - 1;
        $code = (string) random_int($min, $max);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $stmt = $con->prepare("UPDATE customer SET verification_token = ?, verification_sent_at = NOW(), is_verified = 0 WHERE Customer_Email = ?");
        $stmt->execute([$hash, $email]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        return ['code' => $code, 'digits' => $digits];
    }

    // Verify a 6-digit OTP for the given email with TTL seconds (default 5 minutes)
    public function verifyEmailOtp(string $email, string $code, int $ttlSeconds = 300): bool {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Customer_ID, verification_token, verification_sent_at, is_verified FROM customer WHERE Customer_Email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        if ((int)($row['is_verified'] ?? 0) === 1) return true; // already verified
        $hash = $row['verification_token'] ?? null;
        $sentAt = $row['verification_sent_at'] ?? null;
        if (!$hash || !$sentAt) return false;
        // Check expiry
        $sentTs = strtotime($sentAt);
        if ($sentTs === false || ($sentTs + $ttlSeconds) < time()) {
            return false; // expired
        }
        if (!password_verify($code, $hash)) {
            return false;
        }
        // Mark verified and clear token
        $upd = $con->prepare("UPDATE customer SET is_verified = 1, verified_at = NOW(), verification_token = NULL WHERE Customer_ID = ?");
        $upd->execute([(int)$row['Customer_ID']]);
        return $upd->rowCount() > 0;
    }

    // -----------------------------
    // Remember-me token methods
    // -----------------------------
    public function createRememberToken(string $selector, string $token_hash, int $user_id, string $account_type, string $expires_at, ?string $user_agent = null, ?string $ip = null): bool {
        $con = $this->opencon();
        $stmt = $con->prepare("INSERT INTO remember_tokens (selector, token_hash, user_id, account_type, expires_at, user_agent, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return (bool)$stmt->execute([$selector, $token_hash, $user_id, $account_type, $expires_at, $user_agent, $ip]);
    }

    public function getRememberTokenBySelector(string $selector): ?array {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT * FROM remember_tokens WHERE selector = ? LIMIT 1");
        $stmt->execute([$selector]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function deleteRememberToken(string $selector): bool {
        $con = $this->opencon();
        $stmt = $con->prepare("DELETE FROM remember_tokens WHERE selector = ?");
        return (bool)$stmt->execute([$selector]);
    }

    public function deleteAllRememberTokensForUser(int $user_id, string $account_type = 'customer'): bool {
        $con = $this->opencon();
        $stmt = $con->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND account_type = ?");
        return (bool)$stmt->execute([$user_id, $account_type]);
    }

    public function updateRememberToken(string $oldSelector, string $newSelector, string $newTokenHash, string $newExpires): bool {
        $con = $this->opencon();
        $stmt = $con->prepare("UPDATE remember_tokens SET selector = ?, token_hash = ?, expires_at = ?, last_used = NOW() WHERE selector = ?");
        return (bool)$stmt->execute([$newSelector, $newTokenHash, $newExpires, $oldSelector]);
    }

    // Helper: get user by id and account type
    public function getUserById(int $id, string $account_type) {
        $con = $this->opencon();
        if ($account_type === 'admin') {
            $stmt = $con->prepare("SELECT * FROM admin WHERE Admin_ID = ?");
        } else {
            $stmt = $con->prepare("SELECT * FROM customer WHERE Customer_ID = ?");
        }
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}