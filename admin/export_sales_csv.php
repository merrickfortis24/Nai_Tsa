<?php
// Secure CSV export of sales data with date range presets
require_once __DIR__ . '/../includes/remember.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo 'Forbidden';
    exit();
}

require_once __DIR__ . '/classes/database.php';

// Read inputs
$from  = isset($_GET['from']) ? trim($_GET['from']) : '';
$to    = isset($_GET['to']) ? trim($_GET['to']) : '';
$preset = isset($_GET['preset']) ? trim($_GET['preset']) : '';

// Compute date range from preset if provided
date_default_timezone_set('Asia/Manila');
$today = new DateTime('today');
if ($preset) {
    switch ($preset) {
        case 'this_week':
            // Monday to today (or full week Mon-Sun)
            $startOfWeek = (clone $today)->modify('monday this week');
            $endOfWeek   = (clone $startOfWeek)->modify('sunday this week');
            $from = $startOfWeek->format('Y-m-d');
            $to   = $endOfWeek->format('Y-m-d');
            break;
        case 'this_month':
            $startOfMonth = new DateTime($today->format('Y-m-01'));
            $endOfMonth   = (clone $startOfMonth)->modify('last day of this month');
            $from = $startOfMonth->format('Y-m-d');
            $to   = $endOfMonth->format('Y-m-d');
            break;
        case 'this_year':
            $startOfYear = new DateTime($today->format('Y-01-01'));
            $endOfYear   = new DateTime($today->format('Y-12-31'));
            $from = $startOfYear->format('Y-m-d');
            $to   = $endOfYear->format('Y-m-d');
            break;
        default:
            // Unknown preset; ignore
            break;
    }
}

// Validate basic formats (YYYY-MM-DD) if provided
foreach (['from' => $from, 'to' => $to] as $k => $v) {
    if ($v !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        http_response_code(400);
        echo 'Invalid date format for ' . $k;
        exit();
    }
}

// Fetch data
$db = new database();
$rows = [];
try {
    $rows = $db->viewSalesRange($from, $to);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error fetching sales data';
    exit();
}

// Prepare CSV output
$filenameParts = ['sales'];
if ($from) $filenameParts[] = 'from-' . str_replace('-', '', $from);
if ($to)   $filenameParts[] = 'to-' . str_replace('-', '', $to);
$filenameParts[] = date('Ymd_His');
$filename = implode('_', $filenameParts) . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Add UTF-8 BOM so Excel shows UTF-8 correctly (e.g., ₱)
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// CSV header
fputcsv($out, ['Sale ID', 'Sale Date', 'Product', 'Quantity', 'Total Amount', 'Admin']);

// Rows
foreach ($rows as $r) {
    // Ensure consistent types and formatting
    $saleDate = $r['Sale_Date'];
    // If Sale_Date has time, keep as-is; else ensure date format
    // Amount: leave as numeric string; Excel will parse. Avoid adding currency symbol in CSV.
    fputcsv($out, [
        $r['Sale_ID'],
        $saleDate,
        $r['Product_Name'],
        (int)$r['Quantity'],
        number_format((float)$r['Total_Amount'], 2, '.', ''),
        $r['Admin_Name'],
    ]);
}

fclose($out);
exit();
?>
