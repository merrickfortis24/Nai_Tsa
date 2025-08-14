<?php
 
class database{
 
    function opencon() {
 
        return new PDO(
            'mysql:host=localhost;dbname=naitsa',
            'root',
            ''
        );
    }

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
        // Example: Recommend products the user ordered most, fallback to top sellers
        $stmt = $con->prepare("
            SELECT p.*
            FROM product p
            JOIN order_item oi ON p.Product_ID = oi.Product_ID
            JOIN orders o ON oi.Order_ID = o.Order_ID
            WHERE o.Customer_ID = ?
            GROUP BY p.Product_ID
            ORDER BY COUNT(*) DESC
            LIMIT ?
        ");
        $stmt->execute([$customer_id, $limit]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$products) {
            // Fallback: top sellers
            $stmt = $con->prepare("
                SELECT p.*
                FROM product p
                JOIN order_item oi ON p.Product_ID = oi.Product_ID
                GROUP BY p.Product_ID
                ORDER BY COUNT(*) DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $products;
    }
}