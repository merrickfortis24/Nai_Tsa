<?php
class database {
    public function opencon() {
        return new PDO(
            'mysql:host=localhost;dbname=naitsa',
            'root',
            ''
        );
    }

    function fetchAllProducts() {
        $con = $this->opencon();
        $stmt = $con->prepare("SELECT * FROM product");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
}