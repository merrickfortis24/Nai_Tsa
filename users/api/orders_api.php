  <?php
  // DEPRECATED ENDPOINT
  // This endpoint has been superseded by ajax/fetch_orders.php which returns:
  // { success, groups, flat, counts }
  // Any new code should use the new endpoint. This file now returns only a deprecation notice.
  session_start();
  header('Content-Type: application/json');
  http_response_code(410); // Gone
  echo json_encode([
    'success' => false,
    'deprecated' => true,
    'message' => 'orders_api.php is deprecated. Use ajax/fetch_orders.php instead.'
  ]);
