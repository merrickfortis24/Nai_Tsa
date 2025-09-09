<?php
// Return the authenticated user's orders (condensed) for quick post-checkout refresh
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../classes/database.php';
$db = new database();

try {
    $con = $db->opencon();
    // Pull orders + items similar to orders_api.php but keep payload lean
    $stmt = $con->prepare(
        "SELECT 
            o.Order_ID, o.Order_Date, o.order_status, o.Driver_Status, o.Order_Amount,
            o.order_type, o.Street, o.City, o.Contact_Number,
            p.payment_status,
            oi.Quantity, oi.Product_ID AS Item_Product_ID,
            pr.Product_ID AS Product_ID, pr.Product_Name, pr.Product_Image
         FROM orders o
         LEFT JOIN payment p ON o.Order_ID = p.Order_ID
         LEFT JOIN order_item oi ON o.Order_ID = oi.Order_ID
         LEFT JOIN product pr ON oi.Product_ID = pr.Product_ID
         WHERE o.Customer_ID = ?
         ORDER BY o.Order_Date DESC, o.Order_ID DESC"
    );
    $stmt->execute([$_SESSION['customer_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orders = [];
    foreach ($rows as $r) {
        $id = (int)$r['Order_ID'];
        if (!isset($orders[$id])) {
            $orders[$id] = [
                'Order_ID'       => $id,
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
                'Quantity'      => (int)$r['Quantity']
            ];
        }
    }

    $ordered = array_values($orders);
    $latestId = $ordered[0]['Order_ID'] ?? null;
    echo json_encode(['success'=>true,'orders'=>$ordered,'latest_order_id'=>$latestId]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Server error','detail'=>$e->getMessage()]);
}
?>