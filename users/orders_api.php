  <?php
  session_start();
  header('Content-Type: application/json');
  if (!isset($_SESSION['customer_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
  require_once "classes/database.php";
  $db = new database();

  try {
    $con = $db->opencon();
  $stmt = $con->prepare(
    "SELECT 
      o.Order_ID, o.Order_Date, o.order_status, o.Driver_Status, o.Order_Amount,
      o.order_type, o.Street, o.City, o.Contact_Number,
      p.payment_status,
      oi.Quantity, oi.Product_ID AS Item_Product_ID,
      pr.Product_ID AS Product_ID, pr.Product_Name, pr.Product_Image,
      CASE WHEN r.Review_ID IS NULL THEN 0 ELSE 1 END AS Already_Reviewed,
      r.Rating AS Existing_Rating,
      r.Review_Text AS Existing_Review_Text
     FROM orders o
     LEFT JOIN payment p ON o.Order_ID = p.Order_ID
     LEFT JOIN order_item oi ON o.Order_ID = oi.Order_ID
     LEFT JOIN product pr ON oi.Product_ID = pr.Product_ID
     LEFT JOIN reviews r ON r.Product_ID = oi.Product_ID AND r.Customer_ID = o.Customer_ID
     WHERE o.Customer_ID = ?
     ORDER BY o.Order_Date DESC, o.Order_ID DESC"
  );
    $stmt->execute([$_SESSION['customer_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orders = [];
    foreach ($rows as $r) {
      $id = $r['Order_ID'];
      if (!isset($orders[$id])) {
        $orders[$id] = [
          'Order_ID'       => (int)$id,
          'Order_Date'     => $r['Order_Date'],
          'order_status'   => $r['order_status'],
          'Driver_Status'  => $r['Driver_Status'] ?? null,
          'payment_status' => $r['payment_status'],
          'Order_Amount'   => (float)$r['Order_Amount'],
          'order_type'     => ($r['order_type']) ?: ((empty($r['Street']) && empty($r['City']) && empty($r['Contact_Number'])) ? 'Pickup' : 'Delivery'),
          'items'          => []
        ];
      }
  if (!empty($r['Product_Name'])) {
        $pid = !empty($r['Product_ID']) ? (int)$r['Product_ID'] : (!empty($r['Item_Product_ID']) ? (int)$r['Item_Product_ID'] : 0);
        $orders[$id]['items'][] = [
          'Product_ID'    => $pid,
          'Product_Name'  => $r['Product_Name'],
          'Product_Image' => $r['Product_Image'],
    'Quantity'      => (int)$r['Quantity'],
    'Already_Reviewed' => (int)$r['Already_Reviewed'] === 1,
    'Existing_Rating'  => isset($r['Existing_Rating']) ? (int)$r['Existing_Rating'] : null,
    'Existing_Review_Text' => $r['Existing_Review_Text']
        ];
      }
    }

    echo json_encode(array_values($orders));
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'detail' => $e->getMessage()]);
  }
