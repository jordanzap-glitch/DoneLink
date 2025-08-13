<?php 
include '../Includes/session.php'; // Include session management 
include '../Includes/dbcon.php';

$adminCount = 0; // Initialize admin count 
$userCount = 0; // Initialize user count 
$pendingCount = 0; // Initialize pending approval count
$salesPerMonth = 0; // Initialize sales per month

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

// Query to count pending approvals from tbl_payment 
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM tbl_payment WHERE status = 'Pending'"); 
if ($result) { 
    if ($row = mysqli_fetch_assoc($result)) { 
        $pendingCount = $row['count']; 
    } 
} else { 
    echo "Error fetching pending approval count: " . mysqli_error($conn); 
} 

// Query to fetch transaction history from tbl_payment where status is 'Paid'
$transactions = [];
$result = mysqli_query($conn, "SELECT transaction_id, date_created, price, status FROM tbl_payment WHERE status = 'Paid'");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
} else {
    echo "Error fetching transaction history: " . mysqli_error($conn);
}

// Calculate total sales for the current month
$currentMonth = date('Y-m'); // Get current month in 'YYYY-MM' format
$salesPerMonth = 0;

foreach ($transactions as $transaction) {
    $transactionMonth = date('Y-m', strtotime($transaction['date_created']));
    if ($transactionMonth === $currentMonth) {
        $salesPerMonth += $transaction['price'];
    }
}

// Query to fetch reports with customer full name from tbl_reports and tbl_customer
$reports = [];
$result = mysqli_query($conn, "
    SELECT r.customer_id, r.to_admin, r.status, r.date_created, c.firstname, c.lastname 
    FROM tbl_reports r 
    JOIN tbl_customer c ON r.customer_id = c.id 
    WHERE r.status = 'sent'
");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $reports[] = $row;
    }
} else {
    echo "Error fetching reports: " . mysqli_error($conn);
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Done link</title>
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
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="assets/css/demo.css" />
    <style>
      .report-list {
        max-height: 300px; /* Set a maximum height for the report list */
        overflow-y: auto; /* Enable vertical scrolling */
      }
    </style>
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
              <h3 class="fw-bold mb-3">Super Admin Dashboard</h3>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
              <a href="#" class="btn btn-label-info btn-round me-2">Manage</a>
              <a href="add_customer.php" class="btn btn-primary btn-round">Add Customer</a>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-primary bubble-shadow-small">
                        <i class="fas fa-users"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Users</p>
                        <h4 class="card-title"><?php echo $userCount; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-info bubble-shadow-small">
                        <i class="fas fa-user-tie"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Admin</p>
                        <h4 class="card-title"><?php echo $adminCount; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card card-stats card-round">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col-icon">
                      <div class="icon-big text-center icon-success bubble-shadow-small">
                        <i class="fas fa-clock"></i>
                      </div>
                    </div>
                    <div class="col col-stats ms-3 ms-sm-0">
                      <div class="numbers">
                        <p class="card-category">Pending Approval</p>
                        <h4 class="card-title"><?php echo $pendingCount; ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-8">
              <div class="card card-round">
                <div class="card-header">
                  <div class="card-head-row">
                    <div class="card-title">Transaction History</div>
                    <div class="card-tools">
                      <a href="#" class="btn btn-label-success btn-round btn-sm me-2">
                        <span class="btn-label">
                          <i class="fa fa-pencil"></i>
                        </span>
                        Export
                      </a>
                      <a href="#" class="btn btn-label-info btn-round btn-sm">
                        <span class="btn-label">
                          <i class="fa fa-print"></i>
                        </span>
                        Print
                      </a>
                    </div>
                  </div>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <!-- Projects table -->
                    <table class="table align-items-center mb-0" id="multi-filter-select">
                      <thead class="thead-light">
                        <tr>
                          <th scope="col">Payment Number</th>
                          <th scope="col" class="text-end">Date & Time</th>
                          <th scope="col" class="text-end">Amount</th>
                          <th scope="col" class="text-end">Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                          <tr>
                            <th scope="row">
                              <button class="btn btn-icon btn-round btn-success btn-sm me-2">
                                <i class="fa fa-check"></i>
                              </button>
                              Payment from #<?php echo $transaction['transaction_id']; ?>
                            </th>
                            <td class="text-end"><?php echo date('M d, Y, h:i A', strtotime($transaction['date_created'])); ?></td>
                            <td class="text-end">₱<?php echo number_format($transaction['price'], 2); ?></td>
                            <td class="text-end">
                              <span class="badge badge-success"><?php echo $transaction['status']; ?></span>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4"> <!-- Moved report section here -->
              <div class="card card-round">
                <div class="card-body">
                  <div class="card-head-row card-tools-still-right">
                    <div class="card-title">Reports</div>
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
                  <div class="report-list py-4">
                    <ul class="list-group">
                      <?php foreach ($reports as $report): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                          <div>
                            <strong>Customer Name:</strong> <?php echo $report['firstname'] . ' ' . $report['lastname']; ?><br><br>
                            <strong>To Admin:</strong> <br><?php echo $report['to_admin']; ?><br><br>
                            <strong>Date:</strong> <?php echo date('M d, Y', strtotime($report['date_created'])); ?>
                          </div>
                          <span class="badge badge-danger"><?php echo $report['status']; ?></span> <!-- Changed to badge-danger -->
                        </li>
                      <?php endforeach; ?>
                    </ul>
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
          paging: true, // Enable pagination
          searching: false, // Disable search
        });
      });

      function updateLineChart(transactions) {
        const salesData = {};
        transactions.forEach(transaction => {
          const month = new Date(transaction.date_created).toISOString().slice(0, 7);
          if (!salesData[month]) {
            salesData[month] = 0;
          }
          salesData[month] += parseFloat(transaction.price);
        });

        const labels = Object.keys(salesData);
        const data = Object.values(salesData);

        const ctx = document.getElementById('lineChart').getContext('2d');
        const lineChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Sales per Month',
              data: data,
              borderColor: '#177dff',
              backgroundColor: 'rgba(23, 125, 255, 0.14)',
              fill: true,
            }]
          },
          options: {
            responsive: true,
            scales: {
              y: {
                beginAtZero: true
              }
            }
          }
        });
      }

      updateLineChart(<?php echo json_encode($transactions); ?>);
    </script>
  </body> 
</html>
