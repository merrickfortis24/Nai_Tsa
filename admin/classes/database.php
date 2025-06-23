<?php
 
class database{
 
    function opencon() {
 
        return new PDO(
            'mysql:host=localhost;dbname=naitsa',
            'root',
            ''
        );
    }

    function addProduct($product_name, $product_desc, $category_id, $price_id, $admin_id, $image_name = '') {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("INSERT INTO product 
                (Product_Name, Product_desc, Product_Image, Category_ID, Price_ID, Created_at, Updated_at, Admin_ID)
                VALUES (:name, :desc, :image, :category, :price, NOW(), NOW(), :admin)");
            $stmt->execute([
                ':name' => $product_name,
                ':desc' => $product_desc,
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

    // Fetch all products with joins
    function getAllProducts() {
        $con = $this->opencon();
        $stmt = $con->prepare("
            SELECT 
                p.*, 
                pp.Price_Amount, 
                c.Category_Name, 
                a.Admin_Name
            FROM product p
            LEFT JOIN product_price pp ON p.Price_ID = pp.Price_ID
            LEFT JOIN category c ON p.Category_ID = c.Category_ID
            LEFT JOIN admin a ON p.Admin_ID = a.Admin_ID
            ORDER BY p.Created_at DESC
        ");
        $stmt->execute();
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

    function saveProduct($product_name, $product_desc, $category_id, $price_id, $admin_id, $image_name = '', $product_id = null) {
        $con = $this->opencon();

        // Validate foreign keys
        if (!$this->isValidId($con, 'category', $category_id)) {
            return ['success' => false, 'message' => 'Invalid Category ID'];
        }
        if (!$this->isValidId($con, 'product_price', $price_id)) {
            return ['success' => false, 'message' => 'Invalid Price ID'];
        }
        if (!$this->isValidId($con, 'admin', $admin_id)) {
            return ['success' => false, 'message' => 'Invalid Admin ID'];
        }

        try {
            if (!empty($product_id)) {
                // UPDATE existing product
                $sql = "UPDATE product SET 
                            Product_Name = :name, 
                            Product_desc = :desc, 
                            Category_ID = :category, 
                            Price_ID = :price, 
                            Updated_at = NOW()";
                if ($image_name) {
                    $sql .= ", Product_Image = :image";
                }
                $sql .= " WHERE Product_ID = :id";
                $stmt = $con->prepare($sql);
                $params = [
                    ':name' => $product_name,
                    ':desc' => $product_desc,
                    ':category' => $category_id,
                    ':price' => $price_id,
                    ':id' => $product_id
                ];
                if ($image_name) {
                    $params[':image'] = $image_name;
                }
                $stmt->execute($params);
                return ['success' => true, 'message' => 'Product updated successfully!'];
            } else {
                // INSERT new product
                $stmt = $con->prepare("INSERT INTO product 
                    (Product_Name, Product_desc, Product_Image, Category_ID, Price_ID, Created_at, Updated_at, Admin_ID)
                    VALUES (:name, :desc, :image, :category, :price, NOW(), NOW(), :admin)");
                $stmt->execute([
                    ':name' => $product_name,
                    ':desc' => $product_desc,
                    ':image' => $image_name,
                    ':category' => $category_id,
                    ':price' => $price_id,
                    ':admin' => $admin_id
                ]);
                return ['success' => true, 'message' => 'Product added successfully!'];
            }
        } catch (PDOException $e) {
            error_log("Product Save Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
        }
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
        $stmt = $con->prepare("SELECT * FROM Admin WHERE Admin_Name LIKE ? OR Admin_Email LIKE ? ORDER BY Created_At DESC");
        $search = '%' . $keyword . '%';
        $stmt->execute([$search, $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    function deleteAdmin($admin_id) {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("DELETE FROM Admin WHERE Admin_ID = ?");
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
            SELECT o.*, p.payment_status 
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

        $sql = "UPDATE Admin SET 
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
            $sql = "UPDATE Admin SET 
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
        $stmt = $con->prepare("UPDATE orders SET order_status=? WHERE Order_ID=?");
        return $stmt->execute([$order_status, $order_id]);
    }

    function updatePaymentStatusByOrder($order_id, $payment_status) {
        $con = $this->opencon();
        $stmt = $con->prepare("UPDATE payment SET payment_status=? WHERE Order_ID=?");
        return $stmt->execute([$payment_status, $order_id]);
    }

    function getAllPayments() {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT * FROM payment ORDER BY Payment_Date DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function countUnpaidPayments() {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT COUNT(*) FROM payment WHERE payment_status = 'Unpaid'");
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
        $stmt = $con->prepare("SELECT Admin_ID, Reset_Expires FROM Admin WHERE Reset_Token = ?");
        $stmt->execute([$token]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || strtotime($admin['Reset_Expires']) <= time()) {
            return ['success' => false, 'message' => 'Invalid or expired token.'];
        }

        if (!$password || $password !== $confirm) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $update = $con->prepare("UPDATE Admin SET Admin_Password = ?, Reset_Token = NULL, Reset_Expires = NULL WHERE Admin_ID = ?");
        $update->execute([$hash, $admin['Admin_ID']]);

        if ($update->rowCount() > 0) {
            return ['success' => true, 'message' => "Password reset successful! <a href='login.php'>Login here</a>."];
        } else {
            return ['success' => false, 'message' => 'Failed to reset password.'];
        }
    }

    function isValidAdminResetToken($token) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Reset_Expires FROM Admin WHERE Reset_Token = ?");
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
        $stmt = $con->prepare("SELECT Admin_ID, Admin_Name, Admin_Password, Admin_Role, Status FROM Admin WHERE Admin_Email = :email");
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
        $stmt = $con->prepare("SELECT COUNT(*) FROM Admin WHERE Admin_Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'This email is already taken!'];
        }
        // Insert new admin
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $con->prepare("INSERT INTO Admin (Admin_Name, Admin_Password, Admin_Email, Admin_Role, Created_At, Status) 
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
        $stmt = $con->prepare("SELECT * FROM Admin ORDER BY Created_At DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getAdminStats() {
        $con = $this->opencon();
        return [
            'total' => (int)$con->query("SELECT COUNT(*) FROM Admin")->fetchColumn(),
            'active' => (int)$con->query("SELECT COUNT(*) FROM Admin WHERE Status = 'Active'")->fetchColumn(),
            'inactive' => (int)$con->query("SELECT COUNT(*) FROM Admin WHERE Status = 'Inactive'")->fetchColumn(),
            'super' => (int)$con->query("SELECT COUNT(*) FROM Admin WHERE Admin_Role = 'Super Admin'")->fetchColumn(),
        ];
    }

    function createPasswordResetToken($email) {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Admin_ID FROM Admin WHERE Admin_Email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() === 1) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $update = $con->prepare("UPDATE Admin SET Reset_Token = ?, Reset_Expires = ? WHERE Admin_Email = ?");
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
        $stmt = $con->prepare("SELECT COUNT(*) FROM Admin WHERE Admin_Email = ?");
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
}