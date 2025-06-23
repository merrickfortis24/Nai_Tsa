<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "classes/database.php";
$db = new database();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$customer_name = $_SESSION['customer_name'] ?? 'Guest';

$result = $db->processCheckout($data, $customer_name);

echo json_encode($result);
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