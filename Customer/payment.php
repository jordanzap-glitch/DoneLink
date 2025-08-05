<?php
include '../Includes/session.php'; // Include session management 
include '../Includes/dbcon.php';

// Function to generate a transaction ID
function generateTransactionId() {
    $letters = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 2);
    $numbers = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    return $letters . $numbers;
}

// Initialize status message variable
$statusMsg = "";

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
        $uploadFileDir = '../uploads/';
        $dest_path = $uploadFileDir . $fileName;

        // Check if the file is an image
        $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Move the file to the specified directory
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                // Get subscription_id, price, subs_type, and contact_no from tbl_subscription and tbl_customer
                $query = "SELECT c.subscription_id, s.price, s.subs_type, c.contact_no FROM tbl_customer c JOIN tbl_subscription s ON c.subscription_id = s.id WHERE c.id = ?";
                $stmt = $conn->prepare($query);
                
                // Check if the statement was prepared successfully
                if ($stmt === false) {
                    die('MySQL prepare error: ' . htmlspecialchars($conn->error));
                }

                // Bind parameters
                $stmt->bind_param("i", $customerId);
                $stmt->execute();
                $stmt->bind_result($subscriptionId, $price, $subsType, $contactNo);
                $stmt->fetch();
                $stmt->close();

                // Generate transaction ID and set status
                $transactionId = generateTransactionId();
                $status = 'Pending';
                $dateCreated = date('Y-m-d H:i:s'); // Current date and time
                $dueDate = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($dateCreated))); // Calculate due date

                // Insert the payment record into the database
                $query = "INSERT INTO tbl_payment (image_path, customer_id, subscription_id, price, transaction_id, status, date_created, due_date, contact_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);

                // Check if the statement was prepared successfully
                if ($stmt === false) {
                    die('MySQL prepare error: ' . htmlspecialchars($conn->error));
                }

                // Bind parameters
                $stmt->bind_param("siissssss", $dest_path, $customerId, $subscriptionId, $price, $transactionId, $status, $dateCreated, $dueDate, $contactNo);

                // Execute the statement
                if ($stmt->execute()) {
                    $statusMsg = "added"; // Set status message for success
                } else {
                    $statusMsg = "Error submitting payment."; // Set status message for error
                }
            } else {
                $statusMsg = "Error moving the uploaded file."; // Set status message for file move error
            }
        } else {
            $statusMsg = "Upload failed. Allowed file types: jpg, gif, png, jpeg."; // Set status message for invalid file type
        }
    } else {
        $statusMsg = "No file uploaded or there was an upload error."; // Set status message for upload error
    }
}

// Fetch receipts for the logged-in user
$customerId = $_SESSION['userId'];
$receiptsQuery = "SELECT transaction_id, status, date_created, customer_id, subscription_id, price FROM tbl_payment WHERE customer_id = ?";
$receiptsStmt = $conn->prepare($receiptsQuery);
$receiptsStmt->bind_param("i", $customerId);
$receiptsStmt->execute();
$receiptsResult = $receiptsStmt->get_result();

// Fetch full name of the customer
$fullNameQuery = "SELECT firstname, middlename, lastname FROM tbl_customer WHERE id = ?";
$fullNameStmt = $conn->prepare($fullNameQuery);
$fullNameStmt->bind_param("i", $customerId);
$fullNameStmt->execute();
$fullNameStmt->bind_result($firstname, $middlename, $lastname);
$fullNameStmt->fetch();
$fullNameStmt->close();

$fullName = trim($firstname . ' ' . ($middlename ? $middlename . ' ' : '') . $lastname);
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
                                </form>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Receipts</div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="multi-filter-select" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Transaction ID</th>
                                                <th>Status</th>
                                                <th>Date Created</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $receiptsResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                                                    <td>
                                                        <?php if ($row['status'] === 'Pending'): ?>
                                                            <span class="badge badge-warning"><?php echo htmlspecialchars($row['status']); ?></span>
                                                        <?php elseif ($row['status'] === 'Paid'): ?>
                                                            <span class="badge badge-success"><?php echo htmlspecialchars($row['status']); ?></span>
                                                        <?php elseif ($row['status'] === 'Denied'): ?>
                                                            <span class="badge badge-danger"><?php echo htmlspecialchars($row['status']); ?></span>
                                                        <?php else: ?>
                                                            <?php echo htmlspecialchars($row['status']); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('F j, Y, g:i a', strtotime($row['date_created'])); ?></td> <!-- Updated date format -->
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="openViewModal('<?php echo htmlspecialchars($row['transaction_id']); ?>', '<?php echo htmlspecialchars($row['status']); ?>', '<?php echo date('F j, Y, g:i a', strtotime($row['date_created'])); ?>', '<?php echo htmlspecialchars($fullName); ?>', '<?php echo htmlspecialchars($row['subscription_id']); ?>', '<?php echo htmlspecialchars($row['price']); ?>')">View</button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">QR Code</div>
                            </div>
                            <div class="card-body">
                                <img src="assets/img/qr-code.png" alt="Sample QR Code" class="img-fluid" />
                                <!-- Replace the src with the actual path to your QR code image -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Payment Modal -->
        <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewModalLabel">Payment Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Transaction ID:</strong> <span id="viewTransactionId"></span></p>
                        <p><strong>Full Name:</strong> <span id="viewCustomerName"></span></p>
                        <p><strong>Subscription Type:</strong> <span id="viewSubscriptionType"></span></p>
                        <p><strong>Price:</strong> <span id="viewPrice"></span></p>
                        <p><strong>Status:</strong> <span id="viewStatus"></span></p>
                        <p><strong>Date Created:</strong> <span id="viewDateCreated"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="window.location.href='payment.php';">OK</button>
                        <button type="button" class="btn btn-info" onclick="downloadReceipt()">Download PDF</button>
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

    <script>
        // Function to open the view modal
        function openViewModal(transactionId, status, dateCreated, customerName, subscriptionId, price) {
            // Fetch subscription type based on subscriptionId
            $.ajax({
                url: 'get_subscription_type.php', // Create this PHP file to fetch subscription type
                type: 'POST',
                data: { subscription_id: subscriptionId },
                success: function(response) {
                    $('#viewTransactionId').text(transactionId);
                    $('#viewStatus').text(status);
                    $('#viewDateCreated').text(dateCreated);
                    $('#viewCustomerName').text(customerName);
                    $('#viewSubscriptionType').text(response); // Set subscription type
                    $('#viewPrice').text(price);
                    $('#viewModal').modal('show');
                }
            });
        }

        function downloadReceipt() {
            const transactionId = $('#viewTransactionId').text();
            const customerName = $('#viewCustomerName').text();
            const subscriptionType = $('#viewSubscriptionType').text();
            const price = $('#viewPrice').text();
            const status = $('#viewStatus').text();
            const dateCreated = $('#viewDateCreated').text();

            // Create a form to send the data
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'generate_receipt.php';

            // Create hidden inputs for each piece of data
            const inputs = [
                { name: 'transaction_id', value: transactionId },
                { name: 'customer_name', value: customerName },
                { name: 'subscription_type', value: subscriptionType },
                { name: 'price', value: price },
                { name: 'status', value: status },
                { name: 'date_created', value: dateCreated }
            ];

            inputs.forEach(inputData => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = inputData.name;
                input.value = inputData.value;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit(); // Submit the form to download the PDF
        }

        $(document).ready(function () {
            $("#multi-filter-select").DataTable({
                pageLength: 5,
                initComplete: function () {
                    this.api()
                        .columns()
                        .every(function () {
                            var column = this;
                            var select = $(
                                '<select class="form-select"><option value=""></option></select>'
                            )
                                .appendTo($(column.footer()).empty())
                                .on("change", function () {
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    column
                                        .search(val ? "^" + val + "$" : "", true, false)
                                        .draw();
                                });
                            column
                                .data()
                                .unique()
                                .sort()
                                .each(function (d, j) {
                                    select.append(
                                        '<option value="' + d + '">' + d + "</option>"
                                    );
                                });
                        });
                },
            });

            // Status message handling
            <?php if (!empty($statusMsg)) { ?>
                let message = "";
                switch ("<?php echo $statusMsg; ?>") {
                    case "added":
                        message = "Uploaded successfully!";
                        break;
                    default:
                        message = "An error occurred: <?php echo htmlspecialchars($statusMsg); ?>";
                }
                swal(message, "", {
                    icon: message.includes("successfully") ? "success" : "error",
                    buttons: {
                        confirm: {
                            className: "btn btn-success",
                        },
                    },
                });
            <?php } ?>
        });
    </script>
</body>
</html>
