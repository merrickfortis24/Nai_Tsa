<?php

require_once('classes/database.php');
$db = new database();

try {
    $pendingProcessingCount = $db->countPendingOrProcessingOrders();
} catch (PDOException $e) {
    $pendingProcessingCount = 0;
}

try {
    $unpaidPayments = $db->countUnpaidPayments();
} catch (PDOException $e) {
    $unpaidPayments = 0;
}
?>