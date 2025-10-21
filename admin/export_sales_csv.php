<?php
// Sales export as CSV or Excel-compatible (XLS via HTML table)
require_once(__DIR__ . '/classes/database.php');

// Parse inputs (GET/POST)
$preset = isset($_REQUEST['preset']) ? trim((string)$_REQUEST['preset']) : '';
$from   = isset($_REQUEST['from']) ? trim((string)$_REQUEST['from']) : '';
$to     = isset($_REQUEST['to']) ? trim((string)$_REQUEST['to']) : '';
$format = isset($_REQUEST['format']) ? strtolower(trim((string)$_REQUEST['format'])) : 'csv';

// Compute date range when a preset is provided
$today = new DateTime('today');
if ($preset) {
	switch ($preset) {
		case 'week':
		case 'this_week':
			$start = clone $today; $start->modify('monday this week');
			$end = clone $start; $end->modify('sunday this week');
			$from = $start->format('Y-m-d');
			$to = $end->format('Y-m-d');
			break;
		case 'month':
		case 'this_month':
			$start = new DateTime(date('Y-m-01'));
			$end = new DateTime(date('Y-m-t'));
			$from = $start->format('Y-m-d');
			$to = $end->format('Y-m-d');
			break;
		case 'year':
		case 'this_year':
			$start = new DateTime(date('Y-01-01'));
			$end = new DateTime(date('Y-12-31'));
			$from = $start->format('Y-m-d');
			$to = $end->format('Y-m-d');
			break;
		default:
			// Unknown preset -> ignore
			break;
	}
}

$db = new database();
$sales = $db->viewSalesRange($from, $to);

// Prepare filename suffix
$suffixFrom = $from !== '' ? $from : 'ALL';
$suffixTo   = $to !== '' ? $to : 'ALL';

// Totals
$totalQty = 0; $totalAmount = 0.0;
foreach ($sales as $s) {
	$totalQty += (int)$s['Quantity'];
	$totalAmount += (float)$s['Total_Amount'];
}

// Helper to safely convert any value to UTF-8 string
function to_utf8($v) {
	if ($v === null) return '';
	if (is_numeric($v)) return (string)$v;
	if (!is_string($v)) $v = strval($v);
	// Assume input already UTF-8; if not, you may apply mb_convert_encoding here if needed
	return $v;
}

if ($format === 'xls') {
	// Excel-compatible HTML table
	$filename = "sales_${suffixFrom}_${suffixTo}.xls";
	header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Cache-Control: max-age=0');

	echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Sales Export</title></head><body>";
	echo '<table border="1" cellspacing="0" cellpadding="4">';
	echo '<thead><tr>'
		. '<th>Sale ID</th>'
		. '<th>Product</th>'
		. '<th>Quantity</th>'
		. '<th>Total Amount</th>'
		. '<th>Sale Date</th>'
		. '<th>Admin</th>'
		. '</tr></thead><tbody>';
	foreach ($sales as $row) {
		echo '<tr>';
		echo '<td>' . htmlspecialchars((string)$row['Sale_ID']) . '</td>';
		echo '<td>' . htmlspecialchars(to_utf8($row['Product_Name'])) . '</td>';
		echo '<td>' . htmlspecialchars((string)((int)$row['Quantity'])) . '</td>';
		// Keep numeric format for Excel; avoid currency text so Excel recognizes as number
		echo '<td>' . htmlspecialchars(number_format((float)$row['Total_Amount'], 2, '.', '')) . '</td>';
		echo '<td>' . htmlspecialchars(date('Y-m-d', strtotime($row['Sale_Date']))) . '</td>';
		echo '<td>' . htmlspecialchars(to_utf8($row['Admin_Name'])) . '</td>';
		echo '</tr>';
	}
	// Totals row
	echo '<tr>'
		. '<td colspan="2"><strong>Totals</strong></td>'
		. '<td><strong>' . htmlspecialchars((string)$totalQty) . '</strong></td>'
		. '<td><strong>' . htmlspecialchars(number_format($totalAmount, 2, '.', '')) . '</strong></td>'
		. '<td colspan="2"></td>'
		. '</tr>';
	echo '</tbody></table></body></html>';
	exit;
}

// Default: CSV
$filename = "sales_${suffixFrom}_${suffixTo}.csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Emit UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
// Header row
fputcsv($out, ['Sale ID', 'Product', 'Quantity', 'Total Amount', 'Sale Date', 'Admin']);
foreach ($sales as $row) {
	fputcsv($out, [
		$row['Sale_ID'],
		to_utf8($row['Product_Name']),
		(int)$row['Quantity'],
		number_format((float)$row['Total_Amount'], 2, '.', ''), // keep numeric format
		date('Y-m-d', strtotime($row['Sale_Date'])),
		to_utf8($row['Admin_Name'])
	]);
}
// Totals
fputcsv($out, ['Totals', '', (int)$totalQty, number_format($totalAmount, 2, '.', ''), '', '']);
fclose($out);
exit;

