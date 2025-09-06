<?php
// Creates a PayMongo GCash source and returns redirect URL
header('Content-Type: application/json');

require_once __DIR__ . '/classes/database.php';
if (file_exists(__DIR__ . '/../.paymongo.env.php')) { include __DIR__ . '/../.paymongo.env.php'; }

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid JSON']); exit; }

$amount = isset($data['amount']) ? (float)$data['amount'] : 0; // in PHP pesos
if ($amount <= 0) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'Amount missing']); exit; }

$secretKey = getenv('PAYMONGO_SECRET_KEY');
if (!$secretKey) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'PayMongo secret key missing']); exit; }

$publicReturnBase = getenv('PAYMONGO_RETURN_BASE');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https':'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = $publicReturnBase ?: ($scheme . '://' . $host . dirname($_SERVER['SCRIPT_NAME']));
$successUrl = rtrim($base,'/') . '/paymongo_success.php';
$failedUrl  = rtrim($base,'/') . '/paymongo_failed.php';

$payload = [
  'data' => [
    'attributes' => [
      'amount' => (int) round($amount * 100), // centavos
      'currency' => 'PHP',
      'type' => 'gcash',
      'redirect' => [
        'success' => $successUrl,
        'failed'  => $failedUrl
      ],
      'description' => 'Nai Tsa Order Payment'
    ]
  ]
];

$ch = curl_init('https://api.paymongo.com/v1/sources');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode($secretKey . ':'),
  ],
  CURLOPT_POSTFIELDS => json_encode($payload)
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($err) { http_response_code(502); echo json_encode(['success'=>false,'message'=>'cURL error: '.$err]); exit; }

$json = json_decode($resp,true);
if ($code >= 400 || !$json) {
  http_response_code($code ?: 500);
  echo json_encode(['success'=>false,'message'=>'PayMongo error','raw'=>$resp]);
  exit;
}

$redirect = $json['data']['attributes']['redirect']['checkout_url'] ?? null;
$sourceId = $json['data']['id'] ?? null;
if (!$redirect) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Missing redirect URL','raw'=>$json]);
  exit;
}

// Optionally store source pending state for reconciliation (skipped here)

echo json_encode(['success'=>true,'redirect'=>$redirect,'source_id'=>$sourceId]);
