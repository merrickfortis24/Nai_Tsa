<?php
class database {
    function opencon() {
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

function addReview($product_id, $customer_id, $rating, $review_text) {
    $con = $this->opencon();
    $stmt = $con->prepare("INSERT INTO reviews (Product_ID, Customer_ID, Rating, Review_Text, Review_Date) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt->execute([$product_id, $customer_id, $rating, $review_text])) {
        return true;
    } else {
        return $stmt->errorInfo();
    }
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

    // 1. Get or create customer
    $stmt = $con->prepare("SELECT Customer_ID FROM customer WHERE Customer_Name=?");
    $stmt->execute([$customer_name]);
    $customer = $stmt->fetch();
    if ($customer) {
        $customer_id = $customer['Customer_ID'];
    } else {
        $stmt = $con->prepare("INSERT INTO customer (Customer_Name, Customer_Email, Customer_Password) VALUES (?, '', '')");
        $stmt->execute([$customer_name]);
        $customer_id = $con->lastInsertId();
    }

    // 2. Calculate total
    $total = 0;
    foreach ($cart as $item) {
        $stmt = $con->prepare("SELECT Price_ID FROM product WHERE Product_Name=?");
        $stmt->execute([$item['name']]);
        $product = $stmt->fetch();
        if ($product) {
            $stmt2 = $con->prepare("SELECT Price_Amount FROM product_price WHERE Price_ID=?");
            $stmt2->execute([$product['Price_ID']]);
            $price = $stmt2->fetchColumn();
            $total += $price * $item['qty'];
        }
    }

    // 3. Insert order
    $order_stmt = $con->prepare("INSERT INTO orders (Order_Amount, Customer_ID, Street, Barangay, City, Contact_Number, order_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $order_stmt->bindValue(1, $total);
    $order_stmt->bindValue(2, $customer_id);
    $order_stmt->bindValue(3, $street, $street === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $order_stmt->bindValue(4, $barangay, $barangay === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $order_stmt->bindValue(5, $city, $city === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $order_stmt->bindValue(6, $contact, $contact === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $order_stmt->bindValue(7, 'Pending');
    $order_success = $order_stmt->execute();
    $order_id = $con->lastInsertId();

    $payment_stmt = $con->prepare("INSERT INTO payment (Payment_Method, Payment_Amount, Order_ID, Admin_ID, payment_status) VALUES (?, ?, ?, ?, ?)");
    $payment_success = $payment_stmt->execute([
        $data['paymentMethod'],
        $total,
        $order_id,
        1, // Admin_ID
        'Unpaid'
    ]);

    // Insert each cart item into order_item
    foreach ($cart as $item) {
        $stmt = $con->prepare("SELECT Product_ID, Price_ID FROM product WHERE Product_Name=?");
        $stmt->execute([$item['name']]);
        $product = $stmt->fetch();
        if ($product) {
            $stmt2 = $con->prepare("SELECT Price_Amount FROM product_price WHERE Price_ID=?");
            $stmt2->execute([$product['Price_ID']]);
            $price = $stmt2->fetchColumn();

            $stmt3 = $con->prepare("INSERT INTO order_item (Order_ID, Product_ID, Quantity, Price) VALUES (?, ?, ?, ?)");
            $stmt3->execute([
                $order_id,
                $product['Product_ID'],
                $item['qty'],
                $price
            ]);
        }
    }

    if (!$order_success) {
        $error = $order_stmt->errorInfo();
        return ['success' => false, 'message' => 'Order insert failed: ' . $error[2]];
    }
    if (!$payment_success) {
        $error = $payment_stmt->errorInfo();
        return ['success' => false, 'message' => 'Payment insert failed: ' . $error[2]];
    }

    return ['success' => true];
}
}