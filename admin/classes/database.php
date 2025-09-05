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

    function addProduct($product_name, $product_desc, $category_id, $price_id, $admin_id, $image_name = '', $product_Allergens = '') {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("INSERT INTO product 
                (Product_Name, Product_desc, Product_allergens, Product_Image, Category_ID, Price_ID, Created_at, Updated_at, Admin_ID)
                VALUES (:name, :desc, :allergens, :image, :category, :price, NOW(), NOW(), :admin)");
            $stmt->execute([
                ':name' => $product_name,
                ':desc' => $product_desc,
                ':allergens' => $product_Allergens,
                ':image' => $image_name,
                ':category' => $category_id,
                ':price' => $price_id,
                ':admin' => $admin_id
            ]);
            return ['success' => true, 'message' => 'Product added successfully!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
        }
    }


    function getAllProducts($limit = 10, $offset = 0, $category_id = null) {
        $con = $this->opencon();
        $limit = (int)$limit;
        $offset = (int)$offset;
        $sql = "
            SELECT 
                p.*, 
                pp.Price_Amount, 
                c.Category_Name, 
                a.Admin_Name
            FROM product p
            LEFT JOIN product_price pp ON p.Price_ID = pp.Price_ID
            LEFT JOIN category c ON p.Category_ID = c.Category_ID
            LEFT JOIN admin a ON p.Admin_ID = a.Admin_ID
        ";
        $params = [];
        if ($category_id) {
            $sql .= " WHERE p.Category_ID = ?";
            $params[] = $category_id;
        }
        $sql .= " ORDER BY p.Created_at DESC LIMIT $limit OFFSET $offset";

        $stmt = $con->prepare($sql);
        if ($category_id) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch all categories
    function getAllCategories() {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT * FROM category ORDER BY Category_ID DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch all prices
    function getAllPrices($onlyCurrent = false) {
        $con = $this->opencon();
        if ($onlyCurrent) {
            $today = date('Y-m-d');
            $stmt = $con->prepare("
                SELECT Price_ID, Price_Amount, Effective_From, Effective_To
                FROM product_price
                WHERE Effective_From <= :today
                  AND (Effective_To IS NULL OR Effective_To >= :today)
                ORDER BY Price_ID ASC
            ");
            $stmt->execute([':today' => $today]);
        } else {
            $stmt = $con->prepare("SELECT Price_ID, Price_Amount, Effective_From, Effective_To FROM product_price ORDER BY Price_ID ASC");
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function saveProduct($product_name, $product_desc, $category_id, $price_id, $admin_id, $image_name, $product_id = null, $product_allergens = '')
{
    $con = $this->opencon();
    if ($product_id) {
        // Update
        $sql = "UPDATE product SET Product_Name=?, Product_desc=?, Product_allergens=?, Category_ID=?, Price_ID=?, Admin_ID=?, Product_Image=? WHERE Product_ID=?";
        $stmt = $con->prepare($sql);
        $stmt->execute([$product_name, $product_desc, $product_allergens, $category_id, $price_id, $admin_id, $image_name, $product_id]);
    } else {
        // Insert
        $sql = "INSERT INTO product (Product_Name, Product_desc, Product_allergens, Category_ID, Price_ID, Admin_ID, Product_Image) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        $stmt->execute([$product_name, $product_desc, $product_allergens, $category_id, $price_id, $admin_id, $image_name]);
    }
    return ['success' => true, 'message' => 'Product saved successfully'];
}

    // Helper function to validate foreign keys
    private function isValidId($con, $table, $id) {
        // Map table names to their primary key fields
        $primaryKeys = [
            'category' => 'Category_ID',
            'product_price' => 'Price_ID',
            'admin' => 'Admin_ID'
        ];
        $idField = $primaryKeys[$table] ?? ($table . '_ID');
        $stmt = $con->prepare("SELECT 1 FROM $table WHERE $idField = ?");
        $stmt->execute([$id]);
        return (bool)$stmt->fetch();
    }

    function deleteProduct($product_id) {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("DELETE FROM product WHERE Product_ID = ?");
            $stmt->execute([$product_id]);
            return ['success' => true, 'message' => 'Product deleted successfully.'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                // Foreign key constraint violation
                return [
                    'success' => false,
                    'message' => 'Cannot delete this product because it is used in existing orders.'
                ];
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    function deleteCategory($category_id) {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("DELETE FROM category WHERE Category_ID = ?");
            $stmt->execute([$category_id]);
            return ['success' => true, 'message' => 'Category deleted successfully!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
        }
    }

    function searchAdmin($keyword) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT * FROM admin WHERE Admin_Name LIKE ? OR Admin_Email LIKE ? ORDER BY Created_At DESC");
        $search = '%' . $keyword . '%';
        $stmt->execute([$search, $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function deleteAdmin($admin_id) {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("DELETE FROM admin WHERE Admin_ID = ?");
            $stmt->execute([$admin_id]);
            return ['success' => true, 'message' => 'Admin deleted successfully!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
        }
    }
    
    function viewSales() {
        $con = $this->opencon();
        $stmt = $con->prepare("
            SELECT 
                s.Sale_ID, 
                s.Product_ID, 
                p.Product_Name, 
                s.Quantity, 
                s.Total_Amount, 
                s.Sale_Date, 
                a.Admin_Name
            FROM sales s
            JOIN product p ON s.Product_ID = p.Product_ID
            JOIN admin a ON s.Admin_ID = a.Admin_ID
            ORDER BY s.Sale_Date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function fetchOrders() {
        $con = $this->opencon();
        $stmt = $con->prepare("
            SELECT o.*, p.payment_status, o.Driver_Status
            FROM orders o
            LEFT JOIN payment p ON o.Order_ID = p.Order_ID
            ORDER BY o.Order_Date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function fetchOrderItems($order_id) {
        $con = $this->opencon();
        $stmt = $con->prepare("
            SELECT oi.*, p.Product_Name 
            FROM order_item oi
            JOIN product p ON oi.Product_ID = p.Product_ID
            WHERE oi.Order_ID = ?
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function updateAdmin($admin_id, $admin_name, $admin_email, $admin_role, $status, $new_password = '', $confirm_password = '') {
        if (empty($admin_id)) {
            throw new Exception("Admin ID is missing");
        }
        if (empty($admin_name) || empty($admin_email) || empty($admin_role)) {
            throw new Exception("All fields are required!");
        }
        if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format!");
        }
        if (!empty($new_password) && $new_password !== $confirm_password) {
            throw new Exception("Passwords do not match!");
        }

        $con = $this->opencon();

        $sql = "UPDATE admin SET 
                Admin_Name = :name, 
                Admin_Email = :email, 
                Admin_Role = :role, 
                Status = :status,
                Updated_At = NOW()
                WHERE Admin_ID = :id";

        $params = [
            ':name' => $admin_name,
            ':email' => $admin_email,
            ':role' => $admin_role,
            ':status' => $status,
            ':id' => $admin_id
        ];

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE admin SET 
                    Admin_Name = :name, 
                    Admin_Email = :email, 
                    Admin_Role = :role, 
                    Status = :status, 
                    Admin_Password = :password,
                    Updated_At = NOW()
                    WHERE Admin_ID = :id";
            $params[':password'] = $hashed_password;
        }

        $stmt = $con->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Admin updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'No changes made or admin not found.'];
        }
    }



    function updatePaymentStatus($payment_id, $payment_status) {
        $con = $this->opencon();
        $stmt = $con->prepare("UPDATE payment SET payment_status=? WHERE Payment_ID=?");
        return $stmt->execute([$payment_status, $payment_id]);
    }

    function updateOrderStatus($order_id, $order_status) {
        $con = $this->opencon();
        // Fetch current status and infer order type
        $curStmt = $con->prepare("SELECT order_status, Street, City, Contact_Number FROM orders WHERE Order_ID = ? LIMIT 1");
        $curStmt->execute([$order_id]);
        $row = $curStmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        $current = $row['order_status'];
        $isDelivery = !empty($row['Street']) || !empty($row['City']) || !empty($row['Contact_Number']);

        $from = strtolower(trim($current));
        $to = strtolower(trim($order_status));

        // Normalize target case (DB uses capitalized words)
        $normalize = function($s){ return ucwords($s); };

        // Define allowed transitions
        // Delivery flow: Pending -> Processing -> Ready to deliver -> On the way -> Delivered
        $forwardDelivery = [
            'pending'          => ['processing', 'cancelled'],
            'processing'       => ['ready to deliver', 'cancelled'],
            'ready to deliver' => ['on the way', 'cancelled'],
            'on the way'       => ['delivered'],
            'delivered'        => [],
            'cancelled'        => [],
        ];
        // Pick up flow: Pending -> Processing -> Ready to pick up -> Received
        $forwardPickup = [
            'pending'          => ['processing', 'cancelled'],
            'processing'       => ['ready to pick up', 'cancelled'],
            'ready to pick up' => ['received', 'cancelled'],
            'received'         => [],
            'cancelled'        => [],
        ];
        $forward = $isDelivery ? $forwardDelivery : $forwardPickup;

        // Already the same
        if ($from === $to) return true;

        // Cancel only from non-terminal and non-delivered/received
        if ($to === 'cancelled' && in_array($from, ['delivered','received','cancelled'], true)) {
            return false;
        }
        // Cancelled/delivered are terminal
        if (in_array($from, ['cancelled','delivered','received'], true)) {
            return false;
        }
        // Forward progression must be allowed
        if (!in_array($to, $forward[$from] ?? [], true)) {
            return false;
        }

        $stmt = $con->prepare("UPDATE orders SET order_status = ? WHERE Order_ID = ?");
        return $stmt->execute([$normalize($to), $order_id]);
    }

    function updatePaymentStatusByOrder($order_id, $payment_status) {
        $con = $this->opencon();
        $stmt = $con->prepare("UPDATE payment SET payment_status=? WHERE Order_ID=?");
        return $stmt->execute([$payment_status, $order_id]);
    }

    function getAllPayments() {
        $con = $this->opencon();
        // Join orders to get order_status
        $stmt = $con->prepare("SELECT p.*, o.order_status FROM payment p LEFT JOIN orders o ON p.Order_ID = o.Order_ID ORDER BY p.Payment_Date DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function countUnpaidPayments() {
        $con = $this->opencon();
                // Exclude payments whose orders are Cancelled from the unpaid count
                $stmt = $con->prepare("SELECT COUNT(*)
                                                             FROM payment p
                                                             LEFT JOIN orders o ON p.Order_ID = o.Order_ID
                                                             WHERE p.payment_status = 'Unpaid'
                                                                 AND (o.order_status IS NULL OR o.order_status <> 'Cancelled')");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    function getAllOrdersStatus() {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT order_status FROM orders");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function countPendingOrProcessingOrders() {
        $orders = $this->fetchOrders();
        $count = 0;
        foreach ($orders as $order) {
            if ($order['order_status'] === 'Pending' || $order['order_status'] === 'Processing') {
                $count++;
            }
        }
        return $count;
    }

    function resetAdminPasswordByToken($token, $password, $confirm) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Admin_ID, Reset_Expires FROM admin WHERE Reset_Token = ?");
        $stmt->execute([$token]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || strtotime($admin['Reset_Expires']) <= time()) {
            return ['success' => false, 'message' => 'Invalid or expired token.'];
        }

        if (!$password || $password !== $confirm) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $update = $con->prepare("UPDATE admin SET Admin_Password = ?, Reset_Token = NULL, Reset_Expires = NULL WHERE Admin_ID = ?");
        $update->execute([$hash, $admin['Admin_ID']]);

        if ($update->rowCount() > 0) {
            return ['success' => true, 'message' => "Password reset successful! <a href='login.php'>Login here</a>."];
        } else {
            return ['success' => false, 'message' => 'Failed to reset password.'];
        }
    }

    function isValidAdminResetToken($token) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Reset_Expires FROM admin WHERE Reset_Token = ?");
        $stmt->execute([$token]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin && strtotime($admin['Reset_Expires']) > time()) {
            return true;
        }
        return false;
    }

function getCustomerNameById($customer_id) {
    $con = $this->opencon();
    $stmt = $con->prepare("SELECT Customer_Name FROM customer WHERE Customer_ID = ?");
    $stmt->execute([$customer_id]);
    $name = $stmt->fetchColumn();
    return $name ?: 'Unknown';
}

    function getCustomerNameByOrderId($order_id) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT c.Customer_Name FROM orders o JOIN customer c ON o.Customer_ID = c.Customer_ID WHERE o.Order_ID = ?");
        $stmt->execute([$order_id]);
        $name = $stmt->fetchColumn();
        return $name ?: 'Unknown';
    }

    function insertSalesIfDeliveredAndPaid($order_id, $admin_id) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT o.*, p.payment_status FROM orders o LEFT JOIN payment p ON o.Order_ID = p.Order_ID WHERE o.Order_ID = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            isset($order['order_status'], $order['payment_status']) &&
            $order['order_status'] === 'Delivered' &&
            $order['payment_status'] === 'Paid'
        ) {
            // Check if already in sales to avoid duplicates
            $check = $con->prepare("SELECT COUNT(*) FROM sales WHERE Order_ID = ?");
            $check->execute([$order_id]);
            if ($check->fetchColumn() == 0) {
                // Insert each item in the order into sales
                $items = $this->fetchOrderItems($order_id);
                foreach ($items as $item) {
                    $stmt = $con->prepare("INSERT INTO sales (Order_ID, Product_ID, Quantity, Total_Amount, Sale_Date, Admin_ID)
                        VALUES (?, ?, ?, ?, NOW(), ?)");
                    $stmt->execute([
                        $order_id,
                        $item['Product_ID'],
                        $item['Quantity'],
                        $order['Order_Amount'],
                        $admin_id
                    ]);
                }
            }
        }
    }

    function adminLogin($email, $password) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Admin_ID, Admin_Name, Admin_Password, Admin_Role, Status FROM admin WHERE Admin_Email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $admin['Admin_Password'])) {
                if ($admin['Status'] === 'Active') {
                    return [
                        'success' => true,
                        'admin' => $admin,
                        'message' => 'Login Successful'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Your account is inactive. Please contact the system administrator.'
                    ];
                }
            }
        }
        return [
            'success' => false,
            'message' => 'Invalid email or password!'
        ];
    }

    function addAdmin($name, $email, $password, $role, $status) {
        $con = $this->opencon();
        // Check if email exists
        $stmt = $con->prepare("SELECT COUNT(*) FROM admin WHERE Admin_Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'This email is already taken!'];
        }
        // Insert new admin
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $con->prepare("INSERT INTO admin (Admin_Name, Admin_Password, Admin_Email, Admin_Role, Created_At, Status) 
                               VALUES (:name, :password, :email, :role, NOW(), :status)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':status', $status);
        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Error adding admin: ' . implode(" ", $stmt->errorInfo())];
        }
    }

    function getAllAdmins() {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT * FROM admin ORDER BY Created_At DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getAdminStats() {
        $con = $this->opencon();
        return [
            'total' => (int)$con->query("SELECT COUNT(*) FROM admin")->fetchColumn(),
            'active' => (int)$con->query("SELECT COUNT(*) FROM admin WHERE Status = 'Active'")->fetchColumn(),
            'inactive' => (int)$con->query("SELECT COUNT(*) FROM admin WHERE Status = 'Inactive'")->fetchColumn(),
            'super' => (int)$con->query("SELECT COUNT(*) FROM admin WHERE Admin_Role = 'Super Admin'")->fetchColumn(),
        ];
    }

    function createPasswordResetToken($email) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Admin_ID FROM admin WHERE Admin_Email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() === 1) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $update = $con->prepare("UPDATE admin SET Reset_Token = ?, Reset_Expires = ? WHERE Admin_Email = ?");
            $update->execute([$token, $expires, $email]);
            return [
                'success' => true,
                'token' => $token,
                'expires' => $expires
            ];
        }
        return ['success' => false];
    }

    function adminEmailExists($email) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT COUNT(*) FROM admin WHERE Admin_Email = ?");
        $stmt->execute([trim($email)]);
        return $stmt->fetchColumn() > 0;
    }

function addPrice($price_amount, $effective_from, $effective_to = null) {
    $con = $this->opencon();
    try {
        $stmt = $con->prepare("INSERT INTO product_price (Price_Amount, Effective_From, Effective_To) VALUES (:amount, :from, :to)");
        $stmt->execute([
            ':amount' => $price_amount,
            ':from' => $effective_from,
            ':to' => $effective_to ?: null
        ]);
        return ['success' => true, 'message' => 'Price added successfully!'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
    }
}

function saveCategory($category_name, $category_id = null) {
    $con = $this->opencon();
    try {
        if (!empty($category_id)) {
            // Update existing category
            $stmt = $con->prepare("UPDATE category SET Category_Name = ? WHERE Category_ID = ?");
            $stmt->execute([$category_name, $category_id]);
            return ['success' => true, 'message' => 'Category updated successfully!'];
        } else {
            // Add new category
            $stmt = $con->prepare("INSERT INTO category (Category_Name) VALUES (?)");
            $stmt->execute([$category_name]);
            return ['success' => true, 'message' => 'Category added successfully!'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

public function getProductsCount($category_id = null) {
    $con = $this->opencon();
    if ($category_id) {
        $stmt = $con->prepare("SELECT COUNT(*) FROM product WHERE Category_ID = ?");
        $stmt->execute([$category_id]);
    } else {
        $stmt = $con->query("SELECT COUNT(*) FROM product");
    }
    return $stmt->fetchColumn();
}
}

// ===================== Add-ons (Admin) =====================
// CRUD for addons table and mapping to products via product_addons
class addons_helper extends database {
    public function getAllAddons(): array {
        $con = $this->opencon();
        $stmt = $con->query("SELECT Addon_ID, Addon_Name, Addon_Price, Status, Created_At, Updated_At FROM addons ORDER BY Status DESC, Addon_Name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addAddon(string $name, float $price, string $status = 'Active'): array {
        $con = $this->opencon();
        $stmt = $con->prepare("INSERT INTO addons (Addon_Name, Addon_Price, Status) VALUES (:n,:p,:s)");
        $ok = $stmt->execute([':n' => $name, ':p' => $price, ':s' => ($status === 'Inactive' ? 'Inactive' : 'Active')]);
        return ['success' => (bool)$ok, 'id' => $ok ? (int)$con->lastInsertId() : null];
    }

    public function updateAddon(int $id, string $name, float $price, string $status = 'Active'): bool {
        $con = $this->opencon();
        $stmt = $con->prepare("UPDATE addons SET Addon_Name=:n, Addon_Price=:p, Status=:s WHERE Addon_ID=:id");
        return $stmt->execute([':n' => $name, ':p' => $price, ':s' => ($status === 'Inactive' ? 'Inactive' : 'Active'), ':id' => $id]);
    }

    public function deleteAddon(int $id): bool {
        $con = $this->opencon();
        $stmt = $con->prepare("DELETE FROM addons WHERE Addon_ID=:id");
        return $stmt->execute([':id' => $id]);
    }

    public function getAllProductsLite(): array {
        $con = $this->opencon();
        $stmt = $con->query("SELECT Product_ID, Product_Name FROM product ORDER BY Product_Name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductAddons(int $productId): array {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Addon_ID FROM product_addons WHERE Product_ID = :pid");
        $stmt->execute([':pid' => $productId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function setProductAddons(int $productId, array $addonIds): bool {
        $con = $this->opencon();
        $con->beginTransaction();
        try {
            $del = $con->prepare("DELETE FROM product_addons WHERE Product_ID = :pid");
            $del->execute([':pid' => $productId]);

            if (!empty($addonIds)) {
                $ins = $con->prepare("INSERT INTO product_addons (Product_ID, Addon_ID) VALUES (:pid, :aid)");
                foreach ($addonIds as $aid) {
                    $aid = (int)$aid;
                    if ($aid <= 0) continue;
                    $ins->execute([':pid' => $productId, ':aid' => $aid]);
                }
            }
            $con->commit();
            return true;
        } catch (Throwable $e) {
            $con->rollBack();
            return false;
        }
    }
}