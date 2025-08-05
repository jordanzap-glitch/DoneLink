<?php 
include '../Includes/session.php'; // Include session management 
include '../Includes/dbcon.php';

$adminCount = 0; // Initialize admin count 
$userCount = 0; // Initialize user count 

// Query to count admins from tbl_admin 
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM tbl_admin"); 
if ($result) { 
    if ($row = mysqli_fetch_assoc($result)) { 
        $adminCount = $row['count']; 
    } 
} else { 
    echo "Error fetching admin count: " . mysqli_error($conn); 
} 

// Query to count customers from tbl_customer 
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM tbl_customer"); 
if ($result) { 
    if ($row = mysqli_fetch_assoc($result)) { 
        $userCount = $row['count']; 
    } 
} else { 
    echo "Error fetching customer count: " . mysqli_error($conn); 
} 

// Fetch transaction history for the logged-in user
$customerId = $_SESSION['userId'];
$transactionsQuery = "SELECT transaction_id, date_created, price, status FROM tbl_payment WHERE customer_id = ?";
$transactionsStmt = $conn->prepare($transactionsQuery);
$transactionsStmt->bind_param("i", $customerId);
$transactionsStmt->execute();
$transactionsResult = $transactionsStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Done link</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/css/jquery.dataTables.min.css" /> <!-- DataTables CSS -->
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'Includes/sidebar.php';?>
        <!-- End Sidebar -->

        <!-- Navbar Header -->
        <?php include 'Includes/header.php'?>

        <div class="container">
            <div class="page-inner">
                <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                    <div>
                        <h3 class="fw-bold mb-3">Dashboard</h3>
                    </div>
                    <div class="ms-md-auto py-2 py-md-0"></div>
                </div>

                <div class="row">
                    <div class="col-md-4"></div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card card-round">
                            <div class="card-body">
                                <div class="card-head-row card-tools-still-right">
                                    <div class="card-title">Speedtest</div>
                                    <div class="card-tools"></div>
                                </div>
                                <div class="card-list py-4">
                                    <div style="text-align:center; width: 80%; max-width: 600px; min-height: 360px;">
                                        <div style="width:100%; height:0; padding-bottom:50%; position:relative;">
                                            <iframe style="border:none; position:absolute; top:0; left:0; width:100%; height:100%; min-height:360px; border:none; overflow:hidden !important;" src="//openspeedtest.com/speedtest"></iframe>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card card-round">
                            <div class="card-header">
                                <div class="card-head-row card-tools-still-right">
                                    <div class="card-title">Transaction History</div>
                                    <div class="card-tools">
                                        <div class="dropdown">
                                            <button class="btn btn-icon btn-clean me-0" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">Action</a>
                                                <a class="dropdown-item" href="#">Another action</a>
                                                <a class="dropdown-item" href="#">Something else here</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <!-- Projects table -->
                                    <table id="multi-filter-select" class="table align-items-center mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th scope="col">Payment Number</th>
                                                <th scope="col" class="text-end">Date & Time</th>
                                                <th scope="col" class="text-end">Amount</th>
                                                <th scope="col" class="text-end">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $transactionsResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                                                    <td class="text-end"><?php echo date('F j, Y, g:i a', strtotime($row['date_created'])); ?></td>
                                                    <td class="text-end"><?php echo htmlspecialchars($row['price']); ?></td>
                                                    <td class="text-end">
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

        <!-- footer -->
        <?php include 'Includes/footer.php';?>
    </div>

    <!-- settings -->
    <?php include 'Includes/settings.php';?>

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
    <script src="assets/js/jquery.dataTables.min.js"></script> <!-- DataTables JS -->

    <!-- Bootstrap Notify -->
    <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

    <!-- jQuery Vector Maps -->
    <script src="assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
    <script src="assets/js/plugin/jsvectormap/world.js"></script>

    <!-- Sweet Alert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- Kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>

    <!-- Kaiadmin DEMO methods, don't include it in your project! -->
    <script src="assets/js/setting-demo.js"></script>
    <script src="assets/js/demo.js"></script>

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
        });
    </script>
</body>
</html>
