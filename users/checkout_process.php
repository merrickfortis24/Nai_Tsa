<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "classes/database.php";
$db = new database();
$con = $db->opencon();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$orderType = $data['orderType'] ?? '';

$street = $data['street'] ?? '';
$barangay = $data['barangay'] ?? '';
$city = $data['city'] ?? '';
$contact = $data['contact'] ?? '';
$cart = $data['cart'] ?? [];
$customer_name = $_SESSION['customer_name'] ?? 'Guest';

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
    // Get product ID by name
    $stmt = $con->prepare("SELECT Product_ID, Price_ID FROM product WHERE Product_Name=?");
    $stmt->execute([$item['name']]);
    $product = $stmt->fetch();
    if ($product) {
        // Get price
        $stmt2 = $con->prepare("SELECT Price_Amount FROM product_price WHERE Price_ID=?");
        $stmt2->execute([$product['Price_ID']]);
        $price = $stmt2->fetchColumn();

        // Insert into order_item
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
    echo json_encode(['success' => false, 'message' => 'Order insert failed: ' . $error[2]]);
    exit;
}
if (!$payment_success) {
    $error = $payment_stmt->errorInfo();
    echo json_encode(['success' => false, 'message' => 'Payment insert failed: ' . $error[2]]);
    exit;
}

echo json_encode(['success' => true]);
exit;
?>
<script>
fetch('checkout_process.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    orderType,
    paymentMethod,
    street,
    barangay,
    city,
    contact,
    cart
  })
})
.then(async res => {
  const text = await res.text();
  console.log('Raw response:', text);
  try {
    return JSON.parse(text);
  } catch (e) {
    throw new Error('Invalid JSON: ' + text);
  }
})
.then(data => {
  // ...existing SweetAlert logic...
})
.catch(err => {
  Swal.fire({
    icon: 'error',
    title: 'Order Failed',
    text: err.message || 'A network or server error occurred.',
    confirmButtonColor: '#FFB27A'
  });
});
</script>