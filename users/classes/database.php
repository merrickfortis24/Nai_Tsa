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

    public function fetchAllProducts() {
        $con = $this->opencon();
        $stmt = $con->prepare("
            SELECT p.*, c.Category_Name, pp.Price_Amount
            FROM product p
            JOIN category c ON p.Category_ID = c.Category_ID
            JOIN product_price pp ON p.Price_ID = pp.Price_ID
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ensure order_item_addons table exists (idempotent)
    private function ensureOrderItemAddons(PDO $con) {
        $con->exec("CREATE TABLE IF NOT EXISTS order_item_addons (
            Order_Item_Addon_ID INT NOT NULL AUTO_INCREMENT,
            Order_ID INT NOT NULL,
            Order_Item_ID INT NULL,
            Product_ID INT NOT NULL,
            Addon_ID INT NOT NULL,
            Addon_Name VARCHAR(100) NOT NULL,
            Addon_Price DECIMAL(10,2) NOT NULL,
            Quantity INT NOT NULL DEFAULT 1,
            PRIMARY KEY (Order_Item_Addon_ID),
            INDEX idx_oia_order (Order_ID),
            CONSTRAINT fk_oia_order FOREIGN KEY (Order_ID) REFERENCES orders (Order_ID) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_oia_product FOREIGN KEY (Product_ID) REFERENCES product (Product_ID) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_oia_addon FOREIGN KEY (Addon_ID) REFERENCES addons (Addon_ID) ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    // Add this helper (place near ensureOrderItemAddons)
    private function ensureOrderItemInstruction(PDO $con): void {
        try {
            $res = $con->query("SHOW COLUMNS FROM order_item LIKE 'Instruction'");
            if (!$res || $res->rowCount() === 0) {
                $con->exec("ALTER TABLE order_item ADD COLUMN Instruction TEXT NULL AFTER Price");
            }
        } catch (Throwable $e) {
            // ignore if no privilege
        }
    }

    // Insert order and return the new order ID
    function insertOrder($data) {
        $con = $this->opencon();
        $stmt = $con->prepare("INSERT INTO orders (Customer_ID, Order_Amount, Street, Barangay, City, Contact_Number, order_status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([
            $data['customer_id'],
            $data['amount'],
            $data['street'] ?: null,
            $data['barangay'] ?: null,
            $data['city'] ?: null,
            $data['contact'] ?: null
        ]);
        return $con->lastInsertId();
    }

    // Insert order item
    function insertOrderItem($data) {
        $con = $this->opencon();
        $stmt = $con->prepare("INSERT INTO order_item (Order_ID, Product_ID, Quantity, Price) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['order_id'],
            $data['product_id'],
            $data['quantity'],
            $data['price']
        ]);
    }

    // Fetch product by name
    function fetchProductByName($name) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT * FROM product WHERE Product_Name = ?");
        $stmt->execute([$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch product price by product_id
    function fetchProductPrice($product_id) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Price_Amount FROM product_price WHERE Price_ID = (SELECT Price_ID FROM product WHERE Product_ID = ?)");
        $stmt->execute([$product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['Price_Amount'] : 0;
    }

  
function setResetToken($email, $token) {
    $con = $this->opencon();
    $stmt = $con->prepare("UPDATE customer SET reset_token = ? WHERE Customer_Email = ?");
    return $stmt->execute([$token, $email]);
}

function getOrdersByStatus($customer_id) {
    $orders_by_status = [
        'To Ship' => [],
        'To Receive' => [],
        'Delivered' => []
    ];
    $con = $this->opencon();
    $stmt = $con->prepare("
        SELECT o.*, p.payment_status 
        FROM orders o
        LEFT JOIN payment p ON o.Order_ID = p.Order_ID
        WHERE o.Customer_ID = ?
        ORDER BY o.Order_Date DESC
    ");
    $stmt->execute([$customer_id]);
    $all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_orders as $order) {
        if ($order['order_status'] === 'Pending' && $order['payment_status'] === 'Unpaid') {
            $orders_by_status['To Ship'][] = $order;
        } elseif ($order['order_status'] === 'Processing' && $order['payment_status'] === 'Paid') {
            $orders_by_status['To Receive'][] = $order;
        } elseif ($order['order_status'] === 'Delivered' && $order['payment_status'] === 'Paid') {
            $orders_by_status['Delivered'][] = $order;
        }
    }
    return $orders_by_status;
}

function getOrdersByStatusWithItems($customer_id, $orderObj) {
    $orders_by_status = [
        'To Ship' => [],
        'To Receive' => [],
        'Delivered' => []
    ];
    $con = $this->opencon();
    $stmt = $con->prepare("
        SELECT o.*, p.payment_status 
        FROM orders o
        LEFT JOIN payment p ON o.Order_ID = p.Order_ID
        WHERE o.Customer_ID = ?
        ORDER BY o.Order_Date DESC
    ");
    $stmt->execute([$customer_id]);
    $all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_orders as $order) {
        // Fetch items for this order
        $items = $orderObj->getOrderItems($order['Order_ID']);
        $order['items'] = $items;

        if ($order['order_status'] === 'Pending' && $order['payment_status'] === 'Unpaid') {
            $orders_by_status['To Ship'][] = $order;
        } elseif ($order['order_status'] === 'Processing' && $order['payment_status'] === 'Paid') {
            $orders_by_status['To Receive'][] = $order;
        } elseif ($order['order_status'] === 'Delivered') {
            $orders_by_status['Delivered'][] = $order;
        }
    }
    return $orders_by_status;
}

function getAverageRatings() {
    $avg_ratings = [];
    $con = $this->opencon();
    $stmt = $con->query("SELECT Product_ID, AVG(Rating) as avg_rating, COUNT(*) as num_reviews FROM reviews GROUP BY Product_ID");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $avg_ratings[$row['Product_ID']] = [
            'avg' => round($row['avg_rating'], 2),
            'count' => $row['num_reviews']
        ];
    }
    return $avg_ratings;
}

public function hasReview(int $product_id, int $customer_id): bool
{
    $con = $this->opencon();
    $stmt = $con->prepare("SELECT 1 FROM reviews WHERE Product_ID = :pid AND Customer_ID = :cid LIMIT 1");
    $stmt->execute([':pid'=>$product_id, ':cid'=>$customer_id]);
    return (bool)$stmt->fetchColumn();
}

public function addReview(int $product_id, int $customer_id, int $rating, string $review_text = ''): bool
{
    $con = $this->opencon();
    // Insert-only to prevent re-rating existing reviews
    $sql = "
        INSERT INTO reviews (Product_ID, Customer_ID, Rating, Review_Text, Review_Date, Updated_At)
        VALUES (:pid, :cid, :rating, :review, NOW(), NOW())
    ";
    $stmt = $con->prepare($sql);
    return $stmt->execute([
        ':pid'    => $product_id,
        ':cid'    => $customer_id,
        ':rating' => $rating,
        ':review' => $review_text
    ]);
}

function createPasswordResetToken($email) {
    $con = $this->opencon();
    $stmt = $con->prepare("SELECT Customer_ID FROM customer WHERE Customer_Email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() === 1) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $update = $con->prepare("UPDATE customer SET reset_token = ?, reset_expires = ? WHERE Customer_Email = ?");
        $update->execute([$token, $expires, $email]);
        return [
            'success' => true,
            'token' => $token,
            'expires' => $expires
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Email not found'
        ];
    }
}

    function processCheckout($data, $customer_name) {
        $con = $this->opencon();
        $this->ensureOrderItemAddons($con);
        $this->ensureOrderItemInstruction($con); // ensure Instruction column

    $orderType = $data['orderType'] ?? '';
    $street = $data['street'] ?? '';
    $barangay = $data['barangay'] ?? '';
    $city = $data['city'] ?? '';
    $contact = $data['contact'] ?? '';
    $cart = $data['cart'] ?? [];

    if ($orderType === 'Pick Up') {
        $street = null;
        $barangay = null;
        $city = null;
        $contact = null;
    }

    // 1. Determine customer_id using logged-in session when available to avoid mismatches by name
    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    $customer_id = $_SESSION['customer_id'] ?? null;
    if ($customer_id) {
        // Verify the customer actually exists; if not, fallback to name-based creation
        $chk = $con->prepare("SELECT Customer_ID FROM customer WHERE Customer_ID = ? LIMIT 1");
        $chk->execute([$customer_id]);
        if (!$chk->fetchColumn()) {
            $customer_id = null; // force fallback
        }
    }
    if (!$customer_id) {
        // Fallback: locate by exact name (may not be unique) else create
        $stmt = $con->prepare("SELECT Customer_ID FROM customer WHERE Customer_Name=? LIMIT 1");
        $stmt->execute([$customer_name]);
        $customer = $stmt->fetch();
        if ($customer) {
            $customer_id = $customer['Customer_ID'];
        } else {
            $stmt = $con->prepare("INSERT INTO customer (Customer_Name, Customer_Email, Customer_Password) VALUES (?, '', '')");
            $stmt->execute([$customer_name]);
            $customer_id = $con->lastInsertId();
        }
    // If session was missing, set it now so subsequent APIs (fetch_orders.php) will see new orders
        if (!isset($_SESSION['customer_id'])) {
            $_SESSION['customer_id'] = $customer_id;
        }
    }

    // 1.a Fraud / block check (fast). If blocked -> abort early.
    if ($this->isCustomerBlockedFast($customer_id)) {
        return ['success'=>false,'blocked'=>true,'message'=>'Ordering disabled for this account. Please contact support.'];
    }

    // 2. Calculate total (base products + selected add-ons per item)
    $total = 0;
    foreach ($cart as $item) {
        $stmt = $con->prepare("SELECT Price_ID FROM product WHERE Product_Name=?");
        $stmt->execute([$item['name']]);
        $product = $stmt->fetch();
        if ($product) {
            $stmt2 = $con->prepare("SELECT Price_Amount FROM product_price WHERE Price_ID=?");
            $stmt2->execute([$product['Price_ID']]);
            $price = $stmt2->fetchColumn();
        $prodQty = (int)($item['qty'] ?? 1);
        $line = ($price ?: 0) * $prodQty;
            // Add-ons: cart items may include addons: [{id,name,price,qty}]
            if (!empty($item['addons']) && is_array($item['addons'])) {
                foreach ($item['addons'] as $ad) {
            $ap = isset($ad['price']) ? (float)$ad['price'] : 0;
            $aq = isset($ad['qty']) ? (int)$ad['qty'] : 1;
            $line += $ap * $aq * $prodQty; // addon price applies per product unit
                }
            }
            $total += $line;
        }
    }

    // 2.1 Delivery fee (distance-based if lat/lng provided, fallback to flat)
    $delivery_fee = 0.0;
    $distance_km = null;
    // Ensure variables exist for later binding
    $lat = isset($data['lat']) ? (float)$data['lat'] : null;
    $lng = isset($data['lng']) ? (float)$data['lng'] : null;
    if (strcasecmp($orderType, 'Delivery') === 0) {
    // Store coordinates (PJ LIZA STORE) — keep in sync with frontend
    $storeLat = 13.929589; $storeLng = 121.09449;

        if ($lat && $lng) {
            // Haversine distance in KM
            $toRad = function($v){ return $v * M_PI / 180; };
            $R = 6371; // earth radius km
            $dLat = $toRad($lat - $storeLat);
            $dLng = $toRad($lng - $storeLng);
            $a = sin($dLat/2)**2 + cos($toRad($storeLat)) * cos($toRad($lat)) * sin($dLng/2)**2;
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $distance_km = $R * $c;

            // Tiered pricing policy to match frontend
            if ($distance_km <= 2) {
                $delivery_fee = 29.0;
            } elseif ($distance_km <= 5) {
                $delivery_fee = 49.0;
            } elseif ($distance_km <= 8) {
                $delivery_fee = 69.0;
            } elseif ($distance_km <= 12) {
                $delivery_fee = 89.0;
            } else {
                $extra = max(0, ceil($distance_km - 12));
                $delivery_fee = 99.0 + (8.0 * $extra);
            }
        } else {
            // Fallback flat fee if no coords
            $delivery_fee = 49.00;
        }
    }

    // Add delivery fee to total
    $total_with_fee = $total + $delivery_fee;

    // 3. Insert order (normalized schema: address & delivery stored in separate tables)
    $dbOrderType = (strcasecmp($orderType, 'Pick Up') === 0) ? 'Pickup' : 'Delivery';
    // Minimal orders columns now: Order_Amount, Customer_ID, order_type (if exists), order_status
    $hasTypeCol = false;
    try {
        $cType = $con->query("SHOW COLUMNS FROM orders LIKE 'order_type'");
        $hasTypeCol = $cType && $cType->rowCount() > 0;
    } catch (Exception $e) { /* ignore */ }
    $orderCols = ['Order_Amount','Customer_ID'];
    if ($hasTypeCol) $orderCols[] = 'order_type';
    $orderCols[] = 'order_status';
    $order_sql = 'INSERT INTO orders (' . implode(', ', $orderCols) . ') VALUES (' . implode(', ', array_fill(0,count($orderCols),'?')) . ')';
    $order_stmt = $con->prepare($order_sql);
    $bi = 1;
    $order_stmt->bindValue($bi++, $total_with_fee);
    $order_stmt->bindValue($bi++, $customer_id, PDO::PARAM_INT);
    if ($hasTypeCol) $order_stmt->bindValue($bi++, $dbOrderType);
    $order_stmt->bindValue($bi++, 'Pending');
    $order_success = $order_stmt->execute();
    $order_id = (int)$con->lastInsertId();

    // 3a. Address table (order_address) if delivery info present & table exists
    if ($dbOrderType === 'Delivery' && ($street || $barangay || $city || ($lat && $lng))) {
        $hasAddrTable = false;
        try { $chk = $con->query("SHOW TABLES LIKE 'order_address'"); $hasAddrTable = $chk && $chk->rowCount()>0; } catch(Exception $e){}
        if ($hasAddrTable) {
            $addrStmt = $con->prepare("INSERT INTO order_address (Order_ID, Street, Barangay, City, customer_lat, customer_lng) VALUES (?,?,?,?,?,?)");
            $addrStmt->execute([
                $order_id,
                $street ?: null,
                $barangay ?: null,
                $city ?: null,
                $lat ?: null,
                $lng ?: null
            ]);
        }
    }

    // 3b. Delivery meta (order_delivery)
    if ($dbOrderType === 'Delivery' && $delivery_fee > 0) {
        $hasDelTable = false;
        try { $chk2 = $con->query("SHOW TABLES LIKE 'order_delivery'"); $hasDelTable = $chk2 && $chk2->rowCount()>0; } catch(Exception $e){}
        if ($hasDelTable) {
            $delStmt = $con->prepare("INSERT INTO order_delivery (Order_ID, Delivery_Fee, Delivery_Distance_Km) VALUES (?,?,?)");
            $delStmt->execute([$order_id, $delivery_fee, $distance_km]);
        }
    }

    // 3c. Update customer contact number if supplied and empty in profile
    if ($contact) {
        try {
            $cUpd = $con->prepare("UPDATE customer SET Contact_Number = COALESCE(Contact_Number, ?) WHERE Customer_ID = ? AND (Contact_Number IS NULL OR Contact_Number='')");
            $cUpd->execute([$contact, $customer_id]);
        } catch(Throwable $e) { /* ignore */ }
    }

    $payment_stmt = $con->prepare("INSERT INTO payment (Payment_Method, Payment_Amount, Order_ID, Admin_ID, payment_status) VALUES (?, ?, ?, ?, ?)");
    $payment_success = $payment_stmt->execute([
        $data['paymentMethod'],
        $total_with_fee,
        $order_id,
        1, // Admin_ID
        'Unpaid'
    ]);

    // Insert each cart item into order_item, then add-ons
    // Detect if Instruction column exists (avoid failure if ALTER not allowed)
    $hasInstructionCol = false;
    try {
        $ci = $con->query("SHOW COLUMNS FROM order_item LIKE 'Instruction'");
        $hasInstructionCol = $ci && $ci->rowCount() > 0;
    } catch (Throwable $e) {}

    foreach ($cart as $item) {
        $stmt = $con->prepare("SELECT Product_ID, Price_ID FROM product WHERE Product_Name=?");
        $stmt->execute([$item['name']]);
        $product = $stmt->fetch();
        if ($product) {
            $stmt2 = $con->prepare("SELECT Price_Amount FROM product_price WHERE Price_ID=?");
            $stmt2->execute([$product['Price_ID']]);
            $price = $stmt2->fetchColumn();

            $instruction = isset($item['instruction']) && $item['instruction'] !== '' ? $item['instruction'] : null;

            if ($hasInstructionCol) {
                $stmt3 = $con->prepare("INSERT INTO order_item (Order_ID, Product_ID, Quantity, Price, Instruction) VALUES (?, ?, ?, ?, ?)");
                $stmt3->execute([$order_id, $product['Product_ID'], $item['qty'], $price, $instruction]);
            } else {
                $stmt3 = $con->prepare("INSERT INTO order_item (Order_ID, Product_ID, Quantity, Price) VALUES (?, ?, ?, ?)");
                $stmt3->execute([$order_id, $product['Product_ID'], $item['qty'], $price]);
            }

            $orderItemId = $con->lastInsertId();

            // Persist selected add-ons for this item
            if (!empty($item['addons']) && is_array($item['addons'])) {
                $ins = $con->prepare("INSERT INTO order_item_addons (Order_ID, Order_Item_ID, Product_ID, Addon_ID, Addon_Name, Addon_Price, Quantity)
                                      VALUES (:oid,:oiid,:pid,:aid,:aname,:aprice,:qty)");
                foreach ($item['addons'] as $ad) {
                    $prodQty = (int)($item['qty'] ?? 1);
                    $addonQty = (int)($ad['qty'] ?? 1);
                    $totalQty = max(1, $prodQty * $addonQty);
                    $ins->execute([
                        ':oid' => $order_id,
                        ':oiid' => $orderItemId ?: null,
                        ':pid' => $product['Product_ID'],
                        ':aid' => (int)($ad['id'] ?? 0),
                        ':aname' => (string)($ad['name'] ?? ''),
                        ':aprice' => (float)($ad['price'] ?? 0),
                        ':qty' => $totalQty,
                    ]);
                }
            }
        }
    }

    if (!$order_success) {
        $error = $order_stmt->errorInfo();
        return ['success' => false, 'message' => 'Order insert failed: ' . ($error[2] ?? 'unknown')];
    }
    if (!$payment_success) {
        $error = $payment_stmt->errorInfo();
        return ['success' => false, 'message' => 'Payment insert failed: ' . $error[2]];
    }

    return ['success' => true, 'order_id' => (int)$order_id, 'delivery_fee' => $delivery_fee, 'amount' => $total_with_fee, 'distance_km' => $distance_km];
}

public function getRecommendedProducts($customer_id, $limit = 4) {
    $con = $this->opencon();
    $limit = (int)$limit;
    // Recommend products the user ordered most, fallback to top sellers
        $sql = "
            SELECT p.*, pp.Price_Amount
            FROM product p
            JOIN order_item oi ON p.Product_ID = oi.Product_ID
            JOIN orders o ON oi.Order_ID = o.Order_ID
            LEFT JOIN product_price pp ON p.Price_ID = pp.Price_ID
            WHERE o.Customer_ID = ?
            GROUP BY p.Product_ID
            ORDER BY COUNT(*) DESC
            LIMIT $limit
        ";
        $stmt = $con->prepare($sql);
        $stmt->execute([$customer_id]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$products) {
            $sql2 = "
                SELECT p.*, pp.Price_Amount
                FROM product p
                JOIN order_item oi ON p.Product_ID = oi.Product_ID
                LEFT JOIN product_price pp ON p.Price_ID = pp.Price_ID
                GROUP BY p.Product_ID
                ORDER BY COUNT(*) DESC
                LIMIT $limit
            ";
            $stmt2 = $con->prepare($sql2);
            $stmt2->execute();
            $products = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        return $products;
}

public function getBestsellerProducts($limit = 4) {
    $con = $this->opencon();
    $limit = (int)$limit;
        $sql = "
            SELECT 
                p.Product_ID,
                p.Product_Name,
                p.Product_desc,
                p.Product_Image,
                p.Product_allergens,
                c.Category_Name,
                pp.Price_Amount,
                COUNT(oi.Product_ID) AS order_count
            FROM order_item oi
            JOIN product p ON oi.Product_ID = p.Product_ID
            JOIN category c ON p.Category_ID = c.Category_ID
            LEFT JOIN product_price pp ON p.Price_ID = pp.Price_ID
            GROUP BY p.Product_ID
            ORDER BY order_count DESC
            LIMIT :lim
        ";
        $stmt = $con->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function fetchAllCategories() {
    $con = $this->opencon();
    $stmt = $con->prepare("SELECT * FROM category ORDER BY Category_Name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Quick block check used before creating orders */
public function isCustomerBlockedFast(int $customerId): bool {
    try {
        $con = $this->opencon();
        // Prefer is_blocked column if present
        $hasCol = false;
        try { $c = $con->query("SHOW COLUMNS FROM customer LIKE 'is_blocked'"); $hasCol = $c && $c->rowCount()>0; } catch(Throwable $e){}
        if ($hasCol) {
            $s = $con->prepare("SELECT is_blocked FROM customer WHERE Customer_ID=? LIMIT 1");
            $s->execute([$customerId]);
            $val = $s->fetchColumn();
            if ($val !== false) return (int)$val === 1;
        }
        // Fallback blocked_users table
        try {
            $chk = $con->query("SHOW TABLES LIKE 'blocked_users'");
            if ($chk && $chk->rowCount()>0) {
                $s2 = $con->prepare("SELECT 1 FROM blocked_users WHERE customer_id=? LIMIT 1");
                $s2->execute([$customerId]);
                return (bool)$s2->fetchColumn();
            }
        } catch(Throwable $e){}
        return false;
    } catch(Throwable $e) { return false; }
}
}