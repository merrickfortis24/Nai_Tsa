<?php
// Lightweight orders refresh endpoint for updating badge / quick view after cart modal closes
// Returns: success, orders (condensed), pending_count, total_count, latest_order_id
session_start();
// Attempt to restore session from remember-me cookie for AJAX endpoints
require_once __DIR__ . '/../../includes/remember.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['customer_id'])) {
	http_response_code(401);
	echo json_encode(['success'=>false,'message'=>'Unauthorized']);
	exit;
}

require_once __DIR__ . '/../classes/database.php';
$db = new database();

try {
	$con = $db->opencon();
	// Pull orders + minimal item data (first item only) to keep payload small
	$stmt = $con->prepare(
		"SELECT 
			o.Order_ID, o.Order_Date, o.order_status, o.Driver_Status, o.Order_Amount,
			o.order_type,
			COALESCE(addr.Street, '') AS Street,
			COALESCE(addr.City, '') AS City,
			COALESCE(c.Contact_Number, '') AS Contact_Number,
			p.payment_status,
			oi.Quantity, oi.Product_ID AS Item_Product_ID,
			pr.Product_ID AS Product_ID, pr.Product_Name, pr.Product_Image
		 FROM orders o
		 LEFT JOIN order_address addr ON addr.Order_ID = o.Order_ID
			 LEFT JOIN customer c ON c.Customer_ID = o.Customer_ID
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
		// Keep all items (client may want inline counts); still condensed (no add-ons here)
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

	$list = array_values($orders);
	$latestId = $list[0]['Order_ID'] ?? null;

	// Compute pending count for badge (treat these statuses as active/pending)
	$pendingStatuses = ['Pending','Processing','Ready to deliver','Ready to pick up','On the way','To Ship','To Receive'];
	$pendingCount = 0;
	foreach ($list as $o) {
		$st = $o['order_status'];
		if (in_array($st, $pendingStatuses, true)) { $pendingCount++; }
	}

	echo json_encode([
		'success'         => true,
		'orders'          => $list,
		'pending_count'   => $pendingCount,
		'total_count'     => count($list),
		'latest_order_id' => $latestId
	]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success'=>false,'message'=>'Server error','detail'=>$e->getMessage()]);
}
?>
