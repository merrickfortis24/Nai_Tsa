<?php
require_once('classes/database.php');
require_once('fpdf.php');

function ellipsize($str, $max, $ellipsis = '…') {
    if ($max <= 0) return '';
    $useMb = function_exists('mb_strlen') && function_exists('mb_substr');
    $len = $useMb ? mb_strlen($str, 'UTF-8') : strlen($str);
    if ($len <= $max) return $str;
    $cut = max(0, $max - 1);
    $s = $useMb ? mb_substr($str, 0, $cut, 'UTF-8') : substr($str, 0, $cut);
    return $s . $ellipsis;
}

// Parse inputs: allow GET or POST for convenience
$preset = isset($_REQUEST['preset']) ? trim($_REQUEST['preset']) : '';
$from = isset($_REQUEST['from']) ? trim($_REQUEST['from']) : '';
$to   = isset($_REQUEST['to']) ? trim($_REQUEST['to']) : '';

// Compute date range if preset provided
$today = new DateTime('today');
if ($preset) {
    switch (strtolower($preset)) {
        case 'week':
        case 'this_week':
            // Monday to Sunday of this week
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
            // Unknown preset; ignore
    }
}

$db = new database();
$sales = $db->viewSalesRange($from, $to);

$totalQty = 0; $totalAmount = 0.0;
foreach ($sales as $s) {
    $totalQty += (int)$s['Quantity'];
    $totalAmount += (float)$s['Total_Amount'];
}

$rangeLabel = '';
if ($from && $to) { $rangeLabel = "Date Range: $from to $to"; }
elseif ($from) { $rangeLabel = "From: $from"; }
elseif ($to) { $rangeLabel = "Up to: $to"; }
else { $rangeLabel = 'All Dates'; }

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Sales Report', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 8, $rangeLabel, 0, 1, 'C');
$pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i'), 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 12);
// Table header
$pdf->Cell(20, 10, 'ID', 1);
$pdf->Cell(50, 10, 'Product', 1);
$pdf->Cell(20, 10, 'Qty', 1);
$pdf->Cell(30, 10, 'Amount', 1);
$pdf->Cell(35, 10, 'Date', 1);
$pdf->Cell(35, 10, 'Admin', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 11);
foreach ($sales as $sale) {
    $pdf->Cell(20, 10, $sale['Sale_ID'], 1);
    $pdf->Cell(50, 10, ellipsize($sale['Product_Name'], 22), 1);
    $pdf->Cell(20, 10, $sale['Quantity'], 1);
    $pdf->Cell(30, 10, '₱' . number_format($sale['Total_Amount'], 2), 1);
    $pdf->Cell(35, 10, date('Y-m-d', strtotime($sale['Sale_Date'])), 1);
    $pdf->Cell(35, 10, ellipsize($sale['Admin_Name'], 14), 1);
    $pdf->Ln();
}

// Totals row
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(70, 10, 'Totals', 1);
$pdf->Cell(20, 10, (string)$totalQty, 1);
$pdf->Cell(30, 10, '₱' . number_format($totalAmount, 2), 1);
$pdf->Cell(70, 10, '', 1);

$fname = 'sales_report_' . ($from ?: 'ALL') . '_' . ($to ?: 'ALL') . '.pdf';
$pdf->Output('D', $fname);
exit;