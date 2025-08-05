<?php 
error_reporting();
include '../Includes/session.php';
include '../Includes/dbcon.php';

// Initialize status message variable
$statusMsg = "";

// Check if an ID is provided for approval or denial
if (isset($_GET['id'])) {
    $paymentId = intval($_GET['id']);
    
    // Check if the action is to approve or deny
    if (isset($_GET['action']) && $_GET['action'] === 'approve') {
        // Update the payment status to "Paid"
        $updateQuery = "UPDATE tbl_payment SET status = 'Paid' WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $paymentId);
        
        if ($stmt->execute()) {
            $statusMsg = "added"; // Set status message for success
        } else {
            $statusMsg = "Error approving payment: " . $conn->error; // Set status message for error
        }
        
        $stmt->close();
    } elseif (isset($_GET['action']) && $_GET['action'] === 'deny') {
        // Update the payment status to "Denied"
        $updateQuery = "UPDATE tbl_payment SET status = 'Denied' WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $paymentId);
        
        if ($stmt->execute()) {
            $statusMsg = "denied"; // Set status message for denial
        } else {
            $statusMsg = "Error denying payment: " . $conn->error; // Set status message for error
        }
        
        $stmt->close();
    }
}

// Fetch all payments from tbl_payment and join with tbl_customer and tbl_subscription to get full name and subscription type
$result = $conn->query("
    SELECT p.id, p.transaction_id, c.firstname, c.lastname, s.subs_type, p.price, p.status, p.date_created 
    FROM tbl_payment p 
    JOIN tbl_customer c ON p.customer_id = c.id
    JOIN tbl_subscription s ON p.subscription_id = s.id
");

// Ensure to close the database connection when done
$conn->close();
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Approval Management</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" /> <!--soon change this code -->

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
    <link rel="stylesheet" href="assets/css/sweetalert.css" /> <!-- SweetAlert CSS -->
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
                    <h3 class="fw-bold mb-3">Approval Management</h3>
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
                            <a href="#">Approval List</a>
                        </li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Payment List</div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive"> <!-- Added responsive wrapper -->
                                    <table id="multi-filter-select" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Transaction ID</th>
                                                <th>Customer Name</th> <!-- Updated header -->
                                                <th>Subscription Type</th> <!-- Updated header -->
                                                <th>Price</th>
                                                <th>Status</th>
                                                <th>Date Created</th>
                                                <th>Action</th> <!-- New header for action -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result->num_rows > 0): ?>
                                                <?php while($row = $result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo $row['id']; ?></td>
                                                        <td><?php echo $row['transaction_id']; ?></td>
                                                        <td><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td> <!-- Updated to show full name -->
                                                        <td><?php echo $row['subs_type']; ?></td> <!-- Updated to show subscription type -->
                                                        <td><?php echo $row['price']; ?></td>
                                                        <td>
                                                            <?php 
                                                            // Set badge class based on status
                                                            $badgeClass = '';
                                                            switch ($row['status']) {
                                                                case 'Pending':
                                                                    $badgeClass = 'badge badge-warning';
                                                                    break;
                                                                case 'Paid':
                                                                    $badgeClass = 'badge badge-success';
                                                                    break;
                                                                case 'Denied':
                                                                    $badgeClass = 'badge badge-danger';
                                                                    break;
                                                                default:
                                                                    $badgeClass = 'badge badge-secondary'; // Default class for unknown status
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
                                                        </td> <!-- Approval and Denial actions -->
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">No payments found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div> <!-- End of responsive wrapper -->
                            </div>
                        </div>
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

            <?php if (!empty($statusMsg)) { ?>
                let message = "";
                switch ("<?php echo $statusMsg; ?>") {
                    case "added":
                        message = "Approved successfully!";
                        break;
                    case "denied":
                        message = "Denied successfully!";
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
