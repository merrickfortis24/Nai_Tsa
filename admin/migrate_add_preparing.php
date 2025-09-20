<?php
// Safe migration helper: add 'Preparing' to orders.order_status enum (if missing)
// then convert existing rows with 'Processing' -> 'Preparing'.
// Usage (from project root):
//  - CLI: php admin/migrate_add_preparing.php
//  - Browser: visit http://localhost/naitsa/Nai_Tsa/admin/migrate_add_preparing.php (ensure you restrict access after use)

require_once __DIR__ . '/classes/database.php';
$db = new database();
$con = $db->opencon();
try {
    // Get current enum definition and column properties
    $sql = "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'order_status' LIMIT 1";
    $stmt = $con->query($sql);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$info) throw new RuntimeException('orders.order_status column not found in INFORMATION_SCHEMA');

    $colType = $info['COLUMN_TYPE']; // like: enum('Pending','Processing',...)
    $isNullable = ($info['IS_NULLABLE'] === 'YES');
    $colDefault = $info['COLUMN_DEFAULT'];

    // Parse enum values
    if (!preg_match("/^enum\\((.*)\\)$/i", $colType, $m)) {
        throw new RuntimeException('order_status is not an enum column or unexpected COLUMN_TYPE: ' . $colType);
    }
    $inner = $m[1];
    // Split respecting quotes
    $vals = preg_split('/\s*,\s*/', $inner);
    $vals = array_map(function($v){
        $v = trim($v);
        // remove surrounding single or double quotes if present
        if ((strlen($v) >= 2) && (($v[0] === "'" && substr($v, -1) === "'") || ($v[0] === '"' && substr($v, -1) === '"'))) {
            $v = substr($v, 1, -1);
        }
        return $v;
    }, $vals);

    if (in_array('Preparing', $vals, true)) {
        echo "'Preparing' already present in enum.\n";
    } else {
        // Insert 'Preparing' after 'Pending' if present, otherwise append
        $new = [];
        $inserted = false;
        foreach ($vals as $v) {
            $new[] = $v;
            if (!$inserted && strcasecmp($v, 'Pending') === 0) {
                $new[] = 'Preparing';
                $inserted = true;
            }
        }
        if (!$inserted) $new[] = 'Preparing';

        // Build new enum SQL fragment
        $escaped = array_map(function($v){ return "'" . str_replace("'", "\\'", $v) . "'"; }, $new);
        $enumSql = "enum(" . implode(',', $escaped) . ")";

        // Construct ALTER TABLE statement respecting NULL/DEFAULT
        $nullSql = $isNullable ? 'NULL' : 'NOT NULL';
        $defaultSql = '';
        if ($colDefault !== null) {
            // Ensure default value exists in new enum
            if (!in_array($colDefault, $new, true)) {
                // If old default isn't present in new (unlikely), set default to 'Pending'
                $colDefault = 'Pending';
            }
            $defaultSql = " DEFAULT '" . str_replace("'", "\\'", $colDefault) . "'";
        }

        $alter = "ALTER TABLE orders MODIFY COLUMN order_status $enumSql $nullSql $defaultSql";
        echo "Running: $alter\n";
        $con->exec($alter);
        echo "Enum updated to include 'Preparing'.\n";
    }

    // Now update existing rows
    $u = $con->prepare("UPDATE orders SET order_status = 'Preparing' WHERE order_status = 'Processing'");
    $u->execute();
    $count = $u->rowCount();
    echo "Updated $count rows from 'Processing' to 'Preparing'.\n";

    echo "Migration complete. Remove this script or restrict access when done.\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
