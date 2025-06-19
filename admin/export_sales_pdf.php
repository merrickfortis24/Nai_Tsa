<?php
require_once('classes/database.php');
require_once('fpdf.php');

$db = new database();
$sales = $db->viewSales();

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Sales Report', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);

// Table header
$pdf->Cell(20, 10, 'ID', 1);
$pdf->Cell(40, 10, 'Product', 1);
$pdf->Cell(20, 10, 'Qty', 1);
$pdf->Cell(30, 10, 'Amount', 1);
$pdf->Cell(40, 10, 'Date', 1);
$pdf->Cell(40, 10, 'Admin', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);
foreach ($sales as $sale) {
    $pdf->Cell(20, 10, $sale['Sale_ID'], 1);
    $pdf->Cell(40, 10, $sale['Product_Name'], 1);
    $pdf->Cell(20, 10, $sale['Quantity'], 1);
    $pdf->Cell(30, 10, '₱' . number_format($sale['Total_Amount'], 2), 1);
    $pdf->Cell(40, 10, date('Y-m-d', strtotime($sale['Sale_Date'])), 1);
    $pdf->Cell(40, 10, $sale['Admin_Name'], 1);
    $pdf->Ln();
}

$pdf->Output('D', 'sales_report.pdf');
exit;