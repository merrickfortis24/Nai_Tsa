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
                c.Category_Name, 
                pp.Price_Amount,
                a.Admin_Name
            FROM product p
            LEFT JOIN category c ON p.Category_ID = c.Category_ID
            LEFT JOIN product_price pp ON p.Price_ID = pp.Price_ID
            LEFT JOIN admin a ON p.Admin_ID = a.Admin_ID
            ORDER BY p.Created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch all categories
    function getAllCategories() {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Category_ID, Category_Name FROM category ORDER BY Category_Name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch all prices
    function getAllPrices() {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT Price_ID, Price_Amount FROM product_price ORDER BY Price_ID ASC");
        $stmt->execute();
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
            return ['success' => true, 'message' => 'Product deleted successfully!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database Error: ' . $e->getMessage()];
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
            SELECT 
                o.Order_ID,
                o.Order_Date,
                o.Order_Amount,
                o.Customer_ID,
                o.Street,
                o.Barangay,
                o.City,
                o.Contact_Number,
                o.order_status,
                p.payment_status
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

}