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
                $basePrice = $this->getProductPrice($product_id);
                $sizeCode = isset($item['size']) ? $item['size'] : '16oz';
                $sizeAdj = $this->resolveSizeUpcharge($product_id, $sizeCode, $basePrice);
                $finalPrice = $basePrice + $sizeAdj; // price per unit for chosen size
                $this->insertOrderItem([
                    'order_id'   => $order_id,
                    'product_id' => $product_id,
                    'quantity'   => $item['qty'],
                    'price'      => $finalPrice,
                    'size_code'  => $sizeCode,
                    'size_price' => $finalPrice
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
                $base = $this->getProductPrice($product_id);
                $sizeCode = isset($item['size']) ? $item['size'] : '16oz';
                $adj = $this->resolveSizeUpcharge($product_id, $sizeCode, $base);
                $total += ($base + $adj) * $item['qty'];
            }
        }
        return $total;
    }

    // Determine size upcharge using new schema (sizes + product_size_price). Falls back to legacy product_sizes.
    private function resolveSizeUpcharge($product_id, $sizeCode, $basePrice) {
        $sizeCodeNorm = strtolower($sizeCode);
        // First: new mapping tables
        try {
            $q = $this->con->prepare("SELECT psp.Price_Mode, psp.Price_Value FROM product_size_price psp
                JOIN sizes s ON psp.Size_ID = s.Size_ID
                WHERE psp.Product_ID=? AND s.Size_Code=? LIMIT 1");
            $q->execute([$product_id,$sizeCodeNorm]);
            if($row = $q->fetch(PDO::FETCH_ASSOC)){
                $mode = $row['Price_Mode'];
                $val  = (float)$row['Price_Value'];
                if($mode === 'ABS') return max(0.0, $val - $basePrice); // convert to delta
                return $val; // DELTA
            }
        } catch (Throwable $e) { /* ignore and fallback */ }

        // Legacy fallback
        try {
            $stmt = $this->con->prepare("SELECT Size_Code, Price_Amount, Is_Absolute FROM product_sizes WHERE Product_ID=?");
            $stmt->execute([$product_id]);
            while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
                if(strtolower($r['Size_Code']) === $sizeCodeNorm){
                    $amt = (float)$r['Price_Amount'];
                    $isAbs = isset($r['Is_Absolute']) ? ((int)$r['Is_Absolute'] === 1) : ($amt >= $basePrice);
                    return $isAbs ? max(0,$amt - $basePrice) : $amt;
                }
            }
        } catch (Throwable $e2) { /* ignore */ }

        // No variant found: no upcharge (do not auto +10 anymore since new system explicit)
        return 0.0;
    }

    private function getProductIdByName($name) {
        $stmt = $this->con->prepare("SELECT Product_ID FROM product WHERE Product_Name=?");
        $stmt->execute([$name]);
        $product = $stmt->fetch();
        return $product ? $product['Product_ID'] : null;
    }

    private function getProductPrice($product_id) {
        // Use database helper to resolve current product price (history-aware)
        try {
            return (float)$this->db->getCurrentProductPrice((int)$product_id);
        } catch (Throwable $e) {
            return 0;
        }
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
        if (!$success) return false;
        $oid = $this->con->lastInsertId();
        // Safety: force Pending if stored value ended up blank (enum mismatch / silent failure)
        try {
            $chk = $this->con->prepare("SELECT order_status FROM orders WHERE Order_ID=? LIMIT 1");
            $chk->execute([$oid]);
            $cur = $chk->fetchColumn();
            if ($cur === '' || $cur === null) {
                $fix = $this->con->prepare("UPDATE orders SET order_status='Pending' WHERE Order_ID=?");
                $fix->execute([$oid]);
            }
        } catch (Throwable $e) { /* ignore */ }
        return $oid;
    }

    private function insertOrderItem($data) {
        // Attempt to include size columns if they exist
        $hasSizeCols = false;
        try {
            $res = $this->con->query("SHOW COLUMNS FROM order_item LIKE 'Size_Code'");
            $hasSizeCols = $res && $res->rowCount()>0;
        } catch (Throwable $e) { }
        if ($hasSizeCols) {
            $stmt = $this->con->prepare("INSERT INTO order_item (Order_ID, Product_ID, Quantity, Price, Size_Code, Size_Price) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['order_id'],
                $data['product_id'],
                $data['quantity'],
                $data['price'],
                $data['size_code'] ?? null,
                $data['size_price'] ?? $data['price']
            ]);
        } else {
            $stmt = $this->con->prepare("INSERT INTO order_item (Order_ID, Product_ID, Quantity, Price) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data['order_id'],
                $data['product_id'],
                $data['quantity'],
                $data['price']
            ]);
        }
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
            SELECT oi.*, p.Product_Name, p.Product_Image
            FROM order_item oi
            JOIN product p ON oi.Product_ID = p.Product_ID
            WHERE oi.Order_ID = ?
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}