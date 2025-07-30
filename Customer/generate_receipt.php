<?php
require('fpdf/fpdf.php'); // Include the FPDF library

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get data from the POST request
    $transactionId = $_POST['transaction_id'];
    $customerName = $_POST['customer_name'];
    $subscriptionType = $_POST['subscription_type'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $dateCreated = $_POST['date_created'];

    // Create a new PDF document
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);

    // Add content to the PDF
    $pdf->Cell(0, 10, 'Receipt', 0, 1, 'C');
    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Transaction ID: ' . $transactionId, 0, 1);
    $pdf->Cell(0, 10, 'Customer Name: ' . $customerName, 0, 1);
    $pdf->Cell(0, 10, 'Subscription Type: ' . $subscriptionType, 0, 1);
    $pdf->Cell(0, 10, 'Price: ' . $price, 0, 1);
    $pdf->Cell(0, 10, 'Status: ' . $status, 0, 1);
    $pdf->Cell(0, 10, 'Date Created: ' . $dateCreated, 0, 1);

    // Output the PDF
    $pdf->Output('D', 'receipt_' . $transactionId . '.pdf'); // Download the PDF
}
?>
