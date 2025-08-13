<?php 
error_reporting(0);
//include '../Includes/session.php';
include '../Includes/dbcon.php';

// Initialize status message variable
$statusMsg = "";

// Check if the form is submitted for adding a subscription
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['edit']) && !isset($_POST['delete'])) {
    // Get the form data
    $subscriptionName = $_POST['subscriptionName'];
    $mbps = $_POST['mbps'];
    $price = $_POST['price']; // Get the price
    $dateCreated = date('Y-m-d H:i:s'); // Get the current date and time

    // Prepare an SQL statement for tbl_subscription
    $stmtSubscription = $conn->prepare("INSERT INTO tbl_subscription (subs_type, mbps, price, date_created) VALUES (?, ?, ?, ?)");
    
    // Bind parameters for tbl_subscription
    $stmtSubscription->bind_param("sids", $subscriptionName, $mbps, $price, $dateCreated);

    // Execute the statement for tbl_subscription
    if ($stmtSubscription->execute()) {
        $statusMsg = "added";
    } else {
        $statusMsg = "Error: " . $stmtSubscription->error;
    }

    // Close the statement for tbl_subscription
    $stmtSubscription->close();
}

// Check if the form is submitted for editing a subscription
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit'])) {
    $id = $_POST['id'];
    $subscriptionName = $_POST['subscriptionName'];
    $mbps = $_POST['mbps'];
    $price = $_POST['price']; // Get the price

    // Prepare an SQL statement for updating tbl_subscription
    $stmtUpdate = $conn->prepare("UPDATE tbl_subscription SET subs_type = ?, mbps = ?, price = ? WHERE id = ?");
    $stmtUpdate->bind_param("sidi", $subscriptionName, $mbps, $price, $id);

    // Execute the statement for updating tbl_subscription
    if ($stmtUpdate->execute()) {
        $statusMsg = "edited";
    } else {
        $statusMsg = "Error: " . $stmtUpdate->error;
    }

    // Close the statement for updating tbl_subscription
    $stmtUpdate->close();
}

// Check if the form is submitted for deleting a subscription
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $id = $_POST['id'];

    // Prepare an SQL statement for deleting from tbl_subscription
    $stmtDelete = $conn->prepare("DELETE FROM tbl_subscription WHERE id = ?");
    $stmtDelete->bind_param("i", $id);

    // Execute the statement for deleting from tbl_subscription
    if ($stmtDelete->execute()) {
        $statusMsg = "deleted";
    } else {
        $statusMsg = "Error: " . $stmtDelete->error;
    }

    // Close the statement for deleting from tbl_subscription
    $stmtDelete->close();
}

// Fetch subscriptions from the database
$result = $conn->query("SELECT * FROM tbl_subscription");

// Ensure to close the database connection when done
$conn->close();
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Subscription Management</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="" type="image/x-icon" />

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
                    <h3 class="fw-bold mb-3">Subscriptions</h3>
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
                            <a href="#">Basic Form</a>
                        </li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Add Subscriptions</div>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="subscriptionType">Subscription Type</label>
                                                <input type="text" name="subscriptionName" class="form-control" id="subscriptionType" placeholder="Enter Subscription Type" required />
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="mbps">Mbps</label>
                                                <input type="number" name="mbps" class="form-control" id="mbps" placeholder="Enter Mbps" required />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="price">Price</label>
                                                <input type="number" name="price" class="form-control" id="price" placeholder="Enter Price" required />
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <button type="submit" class="btn btn-success">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Existing Subscriptions</div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="multi-filter-select" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Subscription</th>
                                                <th>Speed (Mbps)</th>
                                                <th>Price</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['subs_type']); ?></td>
                                                <td><?php echo htmlspecialchars($row['mbps']); ?></td>
                                                <td><?php echo htmlspecialchars($row['price']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['subs_type']); ?>', <?php echo $row['mbps']; ?>, <?php echo $row['price']; ?>)">Edit</button>
                                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete</button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Subscription</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <input type="hidden" name="id" id="editSubscriptionId" />
                            <div class="form-group">
                                <label for="editSubscriptionName">Subscription Type</label>
                                <input type="text" name="subscriptionName" class="form-control" id="editSubscriptionName" placeholder="Enter Subscription Type" required />
                            </div>
                            <div class="form-group">
                                <label for="editMbps">Mbps</label>
                                <input type="number" name="mbps" class="form-control" id="editMbps" placeholder="Enter Mbps" required />
                            </div>
                            <div class="form-group">
                                <label for="editPrice">Price</label>
                                <input type="number" name="price" class="form-control" id="editPrice" placeholder="Enter Price" required />
                            </div>
                            <button type="submit" name="edit" class="btn btn-primary">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Form -->
        <form id="deleteForm" method="POST" action="" style="display: none;">
            <input type="hidden" name="id" id="deleteSubscriptionId" />
            <input type="hidden" name="delete" value="true" />
        </form>

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

    <!-- Datatables -->
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- Bootstrap Notify -->
    <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

    <!-- jQuery Vector Maps -->
    <script src="assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
    <script src="assets/js/plugin/jsvectormap/world.js"></script>

    <!-- Google Maps Plugin -->
    <script src="assets/js/plugin/gmaps/gmaps.js"></script>

    <!-- Sweet Alert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- Kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>
     <!-- DataTables JS -->
    <script src="assets/js/jquery.dataTables.min.js"></script> <!-- DataTables JS -->
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- Kaiadmin DEMO methods, don't include it in your project! -->
    <script src="assets/js/setting-demo2.js"></script>

    <script>
        // Check if the status message is set and show SweetAlert
        <?php if (!empty($statusMsg)) { ?>
            $(document).ready(function() {
                let message = "";
                switch ("<?php echo $statusMsg; ?>") {
                    case "added":
                        message = "Subscription added successfully!";
                        break;
                    case "edited":
                        message = "Subscription updated successfully!";
                        break;
                    case "deleted":
                        message = "Subscription deleted successfully!";
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
            });
        <?php } ?>

        function openEditModal(id, subscriptionName, mbps, price) {
            $('#editSubscriptionId').val(id);
            $('#editSubscriptionName').val(subscriptionName);
            $('#editMbps').val(mbps);
            $('#editPrice').val(price);
            $('#editModal').modal('show');
        }

        function confirmDelete(id) {
            swal({
                title: "Are you sure?",
                text: "Once deleted, you will not be able to recover this subscription!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    // Set the ID in the hidden delete form and submit it
                    $('#deleteSubscriptionId').val(id);
                    $('#deleteForm').submit();
                }
            });
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
        });
    </script>

</body>
</html>
