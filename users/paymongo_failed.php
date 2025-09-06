<?php
// PayMongo failed redirect page
$sourceId = $_GET['source'] ?? null;
?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Payment Failed</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head><body class="bg-light">
<div class="container py-5">
  <div class="card shadow-sm p-4">
    <h3 class="text-danger">Payment Failed / Cancelled</h3>
    <p>The GCash payment was not completed. Source ID: <code><?= htmlspecialchars($sourceId) ?></code></p>
    <a href="index.php" class="btn btn-soft-orange">Back to Home</a>
  </div>
</div>
</body></html>
