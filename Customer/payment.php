<?php
include '../Includes/session.php'; // Include session management 
include '../Includes/dbcon.php';

// Function to generate a transaction ID
function generateTransactionId() {
    $letters = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 2);
    $numbers = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    return $letters . $numbers;
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the file was uploaded without errors
    if (isset($_FILES['customerImage']) && $_FILES['customerImage']['error'] == 0) {
        $customerId = $_SESSION['userId']; // Get customer ID from session

        // Get the file details
        $fileName = $_FILES['customerImage']['name'];
        $fileTmpPath = $_FILES['customerImage']['tmp_name'];
        $fileSize = $_FILES['customerImage']['size'];
        $fileType = $_FILES['customerImage']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Specify the directory where the file will be uploaded
        $uploadFileDir = 'uploads/';
        $dest_path = $uploadFileDir . $fileName;

        // Check if the file is an image
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Move the file to the specified directory
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Get subscription_id and price from tbl_subscription
                $query = "SELECT c.subscription_id, s.price FROM tbl_customer c JOIN tbl_subscription s ON c.subscription_id = s.id WHERE c.id = ?";
                $stmt = $conn->prepare($query);
                
                // Check if the statement was prepared successfully
                if ($stmt === false) {
                    die('MySQL prepare error: ' . htmlspecialchars($conn->error));
                }

                // Bind parameters
                $stmt->bind_param("i", $customerId);
                $stmt->execute();
                $stmt->bind_result($subscriptionId, $price);
                $stmt->fetch();
                $stmt->close();

                // Generate transaction ID and set status
                $transactionId = generateTransactionId();
                $status = 'pending';
                $dateCreated = date('Y-m-d H:i:s'); // Current date and time

                // Insert the payment record into the database
                $query = "INSERT INTO tbl_payment (image_path, customer_id, subscription_id, price, transaction_id, status, date_created) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);

                // Check if the statement was prepared successfully
                if ($stmt === false) {
                    die('MySQL prepare error: ' . htmlspecialchars($conn->error));
                }

                // Bind parameters
                $stmt->bind_param("siissss", $dest_path, $customerId, $subscriptionId, $price, $transactionId, $status, $dateCreated);

                // Execute the statement
                if ($stmt->execute()) {
                    echo "<script>alert('Payment submitted successfully!');</script>";
                } else {
                    echo "<script>alert('Error submitting payment.');</script>";
                }
            } else {
                echo "<script>alert('Error moving the uploaded file.');</script>";
            }
        } else {
            echo "<script>alert('Upload failed. Allowed file types: jpg, gif, png, jpeg.');</script>";
        }
    } else {
        echo "<script>alert('No file uploaded or there was an upload error.');</script>";
    }
}

// Fetch the latest payment record for the receipt modal
$latestPayment = null;
$query = "SELECT transaction_id, customer_id, subscription_id, price, status FROM tbl_payment WHERE id = ? ORDER BY date_created DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$stmt->bind_result($transactionId, $customerId, $subscriptionId, $price, $status);
if ($stmt->fetch()) {
    $latestPayment = [
        'transaction_id' => $transactionId,
        'customer_id' => $customerId,
        'subscription_id' => $subscriptionId,
        'price' => $price,
        'status' => $status
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Payment</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

    <!-- Fonts and icons -->
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
            active: function () {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/css/jquery.dataTables.min.css" /> <!-- DataTables CSS -->
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'Includes/sidebar.php';?>

        <!-- Navbar Header -->
        <?php include 'Includes/header.php';?>

        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Payment</h3>
                    <ul class="breadcrumbs mb-3">
                        <li class="nav-home">
                            <a href="#">
                                <i class="icon-home"></i>
                            </a>
                        </li>
                        <li class="separator">
                            <i class="icon-arrow-right"></i>
                        </li>
                        <li class="nav-item">
                            <a href="#">Forms</a>
                        </li>
                        <li class="separator">
                            <i class="icon-arrow-right"></i>
                        </li>
                        <li class="nav-item">
                            <a href="#">Payment Form</a>
                        </li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Payment</div>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="customerImage">Upload Image</label>
                                        <input type="file" name="customerImage" class="form-control" id="customerImage" required />
                                    </div>
                                    <br>
                                    <button type="submit" class="btn btn-success">Submit</button>
                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#receiptModal">View Receipt</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">QR Code</div>
                            </div>
                            <div class="card-body">
                                <img src="img/qr-code2.png" alt="Sample QR Code" class="img-fluid" />
                                <!-- Replace the src with the actual path to your QR code image -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Receipt Modal -->
        <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="receiptModalLabel">View Receipt</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php if ($latestPayment): ?>
                            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($latestPayment['transaction_id']); ?></p>
                            <p><strong>Customer ID:</strong> <?php echo htmlspecialchars($latestPayment['customer_id']); ?></p>
                            <p><strong>Subscription ID:</strong> <?php echo htmlspecialchars($latestPayment['subscription_id']); ?></p>
                            <p><strong>Price:</strong> <?php echo htmlspecialchars($latestPayment['price']); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars($latestPayment['status']); ?></p>
                        <?php else: ?>
                            <p>No payment records found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!--footer-->
        <?php include 'Includes/footer.php';?>
        
    </div>
    
    <!--   Core JS Files   -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Chart JS -->
    <script src="assets/js/plugin/chart.js/chart.min.js"></script>

    <!-- jQuery Sparkline -->
    <script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

    <!-- Chart Circle -->
    <script src="assets/js/plugin/chart-circle/circles.min.js"></script>

    <!-- Bootstrap Notify -->
    <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

    <!-- Sweet Alert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- DataTables JS -->
    <script src="assets/js/jquery.dataTables.min.js"></script> <!-- DataTables JS -->
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    
    <!-- Kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>
</body>
</html>
