<?php

require_once "database.php";

class Order {
    protected $db;
    protected $con;

    function __construct() {
        $this->db = new database();
        $this->con = $this->db->opencon();
    }

    function createOrder($data) {
        // Calculate total amount
        $total = $this->calculateTotal($data['cart']);

        // Insert order
        $order_id = $this->insertOrder([
            'customer_id' => $data['customer_id'],
            'amount' => $total,
            'street' => $data['street'],
            'barangay' => $data['barangay'],
            'city' => $data['city'],
            'contact' => $data['contact']
        ]);

        if (!$order_id) return false;

        // Insert order items
        foreach ($data['cart'] as $item) {
            $product_id = $this->getProductIdByName($item['name']);
            if ($product_id) {
                $this->insertOrderItem([
                    'order_id' => $order_id,
                    'product_id' => $product_id,
                    'quantity' => $item['qty'],
                    'price' => $this->getProductPrice($product_id)
                ]);
            }
        }

        // Insert payment (set Staff_ID and Admin_ID as needed)
        $this->insertPayment([
            'payment_method' => $data['payment_method'],
            'amount' => $total,
            'order_id' => $order_id,
            'staff_id' => 1,
            'admin_id' => 1
        ]);

        return $order_id;
    }

    private function calculateTotal($cart) {
        $total = 0;
        foreach ($cart as $item) {
            $product_id = $this->getProductIdByName($item['name']);
            if ($product_id) {
                $total += $this->getProductPrice($product_id) * $item['qty'];
            }
        }
        return $total;
    }

    private function getProductIdByName($name) {
        $stmt = $this->con->prepare("SELECT Product_ID FROM product WHERE Product_Name=?");
        $stmt->execute([$name]);
        $product = $stmt->fetch();
        return $product ? $product['Product_ID'] : null;
    }

    private function getProductPrice($product_id) {
        $stmt = $this->con->prepare("SELECT Price_ID FROM product WHERE Product_ID=?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        if ($product) {
            $stmt2 = $this->con->prepare("SELECT Price_Amount FROM product_price WHERE Price_ID=?");
            $stmt2->execute([$product['Price_ID']]);
            $price = $stmt2->fetchColumn();
            return $price ? $price : 0;
        }
        return 0;
    }

    private function insertOrder($data) {
        $stmt = $this->con->prepare("INSERT INTO orders (Order_Amount, Customer_ID, Street, Barangay, City, Contact_Number, order_status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        $success = $stmt->execute([
            $data['amount'],
            $data['customer_id'],
            $data['street'] ?: null,
            $data['barangay'] ?: null,
            $data['city'] ?: null,
            $data['contact'] ?: null
        ]);
        return $success ? $this->con->lastInsertId() : false;
    }

    private function insertOrderItem($data) {
        $stmt = $this->con->prepare("INSERT INTO order_item (Order_ID, Product_ID, Quantity, Price) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['order_id'],
            $data['product_id'],
            $data['quantity'],
            $data['price']
        ]);
    }

    private function insertPayment($data) {
        $stmt = $this->con->prepare("INSERT INTO payment (Payment_Method, Payment_Amount, Order_ID, Staff_ID, Admin_ID, payment_status) VALUES (?, ?, ?, ?, ?, 'Unpaid')");
        $stmt->execute([
            $data['payment_method'],
            $data['amount'],
            $data['order_id'],
            $data['staff_id'],
            $data['admin_id']
        ]);
    }

    function getOrderItems($order_id) {
        $stmt = $this->con->prepare("
            SELECT oi.*, p.Product_Name 
            FROM order_item oi
            JOIN product p ON oi.Product_ID = p.Product_ID
            WHERE oi.Order_ID = ?
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}