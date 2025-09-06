<?php
http_response_code(410);
header('Content-Type: application/json');
echo json_encode(['success'=>false,'message'=>'Endpoint removed']);
exit;
