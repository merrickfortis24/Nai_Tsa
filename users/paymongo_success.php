<?php
// PayMongo success redirect - confirm source/intent and mark payment
require_once __DIR__ . '/classes/database.php';
if (file_exists(__DIR__ . '/../.paymongo.env.php')) { include __DIR__ . '/../.paymongo.env.php'; }

$sourceId = $_GET['source'] ?? null; // Provided by PayMongo redirect query
?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Payment Successful</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head><body class="bg-light">
<div class="container py-5">
  <div class="card shadow-sm p-4">
    <h3 class="text-success">Payment Successful</h3>
    <p>Your GCash payment was processed. Source ID: <code><?= htmlspecialchars($sourceId) ?></code></p>
    <p>You can close this window or return to the app.</p>
    <a href="index.php" class="btn btn-soft-orange">Back to Home</a>
  </div>
</div>
</body></html>
