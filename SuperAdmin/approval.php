<?php
error_reporting(E_ALL);
include '../Includes/session.php';
include '../Includes/dbcon.php';

// Include PHPMailer and FPDF
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';
require('fpdf/fpdf.php');

// Initialize status message variable
$statusMsg = "";

// Check if an ID is provided for approval or denial
if (isset($_GET['id'])) {
    $paymentId = intval($_GET['id']);

    // Fetch payment details including customer email
    $stmt = $conn->prepare("
        SELECT p.id as payment_id, p.transaction_id, p.customer_id, p.subscription_id,
               c.firstname, c.lastname, c.email, c.address,
               s.subs_type, p.price, p.status, p.date_created
        FROM tbl_payment p
        JOIN tbl_customer c ON p.customer_id = c.id
        JOIN tbl_subscription s ON p.subscription_id = s.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $resultPayment = $stmt->get_result();
    $paymentData = $resultPayment->fetch_assoc();
    $stmt->close();

    if ($paymentData) {
        // Approve payment
        if (isset($_GET['action']) && $_GET['action'] === 'approve') {
            $conn->begin_transaction(); // Start transaction
            try {
                // 1. Update payment status in tbl_payment
                $updateQuery = "UPDATE tbl_payment SET status = 'Paid' WHERE id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("i", $paymentId);
                $stmt->execute();
                $stmt->close();

                // 2. Update tbl_subscribers: payment_id, date_approved, payment_status
                $dateApproved = date('Y-m-d H:i:s');

                // Check if a subscriber exists for this customer and subscription
                $subQuery = "SELECT id, due_date FROM tbl_subscribers WHERE customer_id = ? AND subscription_id = ? ORDER BY id DESC LIMIT 1";
                $subStmt = $conn->prepare($subQuery);
                $subStmt->bind_param("ii", $paymentData['customer_id'], $paymentData['subscription_id']);
                $subStmt->execute();
                $subResult = $subStmt->get_result();
                $subData = $subResult->fetch_assoc();
                $subStmt->close();

                if ($subData) {
                    // Update existing subscriber row
                    $updateSub = "UPDATE tbl_subscribers SET payment_id = ?, date_approved = ?, payment_status = 'Paid' WHERE id = ?";
                    $updStmt = $conn->prepare($updateSub);
                    $updStmt->bind_param("isi", $paymentId, $dateApproved, $subData['id']);
                    $updStmt->execute();
                    $updStmt->close();

                    // Create new subscriber row for next due date
                    $newDueDate = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($subData['due_date'])));
                    $insertSub = "INSERT INTO tbl_subscribers (customer_id, subscription_id, payment_id, payment_status, date_approved, due_date) VALUES (?, ?, ?, 'Pending', ?, ?)";
                    $insStmt = $conn->prepare($insertSub);
                    $insStmt->bind_param("iisss", $paymentData['customer_id'], $paymentData['subscription_id'], $paymentId, $dateApproved, $newDueDate);
                    $insStmt->execute();
                    $insStmt->close();
                }

                $conn->commit(); // Commit transaction
                $statusMsg = "added";

                // Generate PDF receipt
                $pdf = new FPDF();
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(0, 10, 'DoneLink (Receipt)', 0, 1, 'C');
                $pdf->Ln(10);
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(0, 10, 'Transaction ID: ' . $paymentData['transaction_id'], 0, 1);
                $pdf->Cell(0, 10, 'Customer Name: ' . $paymentData['firstname'] . ' ' . $paymentData['lastname'], 0, 1);
                $pdf->Cell(0, 10, 'Subscription Type: ' . $paymentData['subs_type'], 0, 1);
                $pdf->Cell(0, 10, 'Price: ' . $paymentData['price'], 0, 1);
                $pdf->Cell(0, 10, 'Status: Paid', 0, 1);
                $pdf->Cell(0, 10, 'Date: ' . $paymentData['date_created'], 0, 1);

                $pdfFilePath = sys_get_temp_dir() . '/receipt_' . $paymentData['transaction_id'] . '.pdf';
                $pdf->Output('F', $pdfFilePath);

                // Send email with PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'jujuzapanta@gmail.com';
                    $mail->Password   = 'fypo gwrw shsv hdqs';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;

                    $mail->setFrom('jujuzapanta@gmail.com', 'DoneLink');
                    $mail->addAddress($paymentData['email'], $paymentData['firstname'] . ' ' . $paymentData['lastname']);
                    $mail->Subject = 'Payment Approved - Receipt';
                    $mail->Body    = "Dear " . $paymentData['firstname'] . ",\n\nYour payment has been approved. Please find the receipt attached.\n\nThank you!";
                    $mail->addAttachment($pdfFilePath, 'receipt_' . $paymentData['transaction_id'] . '.pdf');

                    $mail->send();
                } catch (Exception $e) {
                    $statusMsg = "Error sending email: {$mail->ErrorInfo}";
                }

                // Delete temp PDF
                unlink($pdfFilePath);

            } catch (Exception $e) {
                $conn->rollback();
                $statusMsg = "Error approving payment: " . $e->getMessage();
            }

        // Deny payment
        } elseif (isset($_GET['action']) && $_GET['action'] === 'deny') {
            $updateQuery = "UPDATE tbl_payment SET status = 'Denied' WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $paymentId);
            if ($stmt->execute()) {
                $statusMsg = "denied";
            } else {
                $statusMsg = "Error denying payment: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all payments
$result = $conn->query("
    SELECT p.id, p.transaction_id, c.firstname, c.lastname, c.address, s.subs_type, p.price, p.status, p.date_created
    FROM tbl_payment p
    JOIN tbl_customer c ON p.customer_id = c.id
    JOIN tbl_subscription s ON p.subscription_id = s.id
");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Approval Management</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="" type="image/x-icon" />
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons",
                ],
                urls: ["assets/css/fonts.min.css"],
            },
            active: function () { sessionStorage.fonts = true; },
        });
    </script>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="assets/css/sweetalert.css" />
</head>
<body>
<div class="wrapper">
    <?php include 'Includes/sidebar.php';?>
    <?php include 'Includes/header.php';?>

    <div class="container">
        <div class="page-inner">
            <div class="page-header">
                <h3 class="fw-bold mb-3">Approval Management</h3>
                <ul class="breadcrumbs mb-3">
                    <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="#">Forms</a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="#">Approval List</a></li>
                </ul>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header"><div class="card-title">Payment List</div></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="multi-filter-select" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Transaction ID</th>
                                            <th>Customer Name</th>
                                            <th>Address</th>
                                            <th>Subscription Type</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Date Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $row['id']; ?></td>
                                                    <td><?php echo $row['transaction_id']; ?></td>
                                                    <td><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td>
                                                    <td><?php echo $row['address']; ?></td>
                                                    <td><?php echo $row['subs_type']; ?></td>
                                                    <td><?php echo $row['price']; ?></td>
                                                    <td>
                                                        <?php
                                                        $badgeClass = '';
                                                        switch ($row['status']) {
                                                            case 'Pending': $badgeClass = 'badge badge-warning'; break;
                                                            case 'Paid': $badgeClass = 'badge badge-success'; break;
                                                            case 'Denied': $badgeClass = 'badge badge-danger'; break;
                                                            default: $badgeClass = 'badge badge-secondary';
                                                        }
                                                        ?>
                                                        <span class="<?php echo $badgeClass; ?>"><?php echo $row['status']; ?></span>
                                                    </td>
                                                    <td><?php echo $row['date_created']; ?></td>
                                                    <td>
                                                        <a href="?id=<?php echo $row['id']; ?>&action=approve" class="btn btn-success btn-sm" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                        <a href="?id=<?php echo $row['id']; ?>&action=deny" class="btn btn-danger btn-sm" title="Deny">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">No payments found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div> 
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php include 'Includes/footer.php';?>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/js/plugin/chart.js/chart.min.js"></script>
<script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>
<script src="assets/js/plugin/chart-circle/circles.min.js"></script>
<script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
<script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<script src="assets/js/jquery.dataTables.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>

<script>
$(document).ready(function () {
    $("#multi-filter-select").DataTable({
        pageLength: 5,
        initComplete: function () {
            this.api().columns().every(function () {
                var column = this;
                var select = $('<select class="form-select"><option value=""></option></select>')
                    .appendTo($(column.footer()).empty())
                    .on("change", function () {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? "^" + val + "$" : "", true, false).draw();
                    });
                column.data().unique().sort().each(function (d, j) {
                    select.append('<option value="' + d + '">' + d + "</option>");
                });
            });
        },
    });

    <?php if (!empty($statusMsg)) { ?>
        let message = "";
        switch ("<?php echo $statusMsg; ?>") {
            case "added": message = "Approved successfully!"; break;
            case "denied": message = "Denied successfully!"; break;
            default: message = "An error occurred: <?php echo htmlspecialchars($statusMsg); ?>";
        }
        swal(message, "", {
            icon: message.includes("successfully") ? "success" : "error",
            buttons: { confirm: { className: "btn btn-success" } },
        });
    <?php } ?>
});
</script>

</body>
</html>
