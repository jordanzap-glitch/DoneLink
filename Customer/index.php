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

// Fetch all subscriptions
$subsQuery = "SELECT id, subs_type, mbps, price FROM tbl_subscription";
$subsResult = mysqli_query($conn, $subsQuery);

// Handle subscription insert (AJAX)
if (isset($_POST['subscribe_id'])) {
    $subscriptionId = intval($_POST['subscribe_id']);
    $stmt = $conn->prepare("INSERT INTO tbl_subscribers (customer_id, subscription_id, date_created, status, payment_status) VALUES (?, ?, NOW(), 'Pending', 'Not Paid')");
    $stmt->bind_param("ii", $customerId, $subscriptionId);
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error: " . $stmt->error;
    }
    exit; // Stop further output for AJAX
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Dashboard</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <link rel="stylesheet" href="assets/css/jquery.dataTables.min.css" />
</head>
<body>
<div class="wrapper">
    <?php include 'Includes/sidebar.php';?>
    <?php include 'Includes/header.php'?>

    <div class="container">
        <div class="page-inner">
            <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                <div>
                    <h3 class="fw-bold mb-3">Dashboard</h3>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card card-round">
                        <div class="card-body">
                            <div class="card-head-row card-tools-still-right">
                                <div class="card-title">Speedtest</div>
                            </div>
                            <div class="card-list py-4">
                                <div style="text-align:center; width: 80%; max-width: 600px; min-height: 360px;">
                                    <div style="width:100%; height:0; padding-bottom:50%; position:relative;">
                                        <iframe style="border:none; position:absolute; top:0; left:0; width:100%; height:100%; min-height:360px;" src="//openspeedtest.com/speedtest"></iframe>
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
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="multi-filter-select" class="table align-items-center mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Payment Number</th>
                                            <th class="text-end">Date & Time</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $transactionsResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                                            <td class="text-end"><?php echo date('F j, Y, g:i a', strtotime($row['date_created'])); ?></td>
                                            <td class="text-end"><?php echo htmlspecialchars($row['price']); ?></td>
                                            <td class="text-end">
                                                <?php
                                                $status = $row['status'];
                                                $badgeClass = $status === 'Pending' ? 'badge-warning' : ($status === 'Paid' ? 'badge-success' : ($status === 'Denied' ? 'badge-danger' : ''));
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription Table -->
                    <div class="card card-round mt-4">
                        <div class="card-header">
                            <div class="card-head-row card-tools-still-right">
                                <div class="card-title">Available Subscriptions</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Speed (Mbps)</th>
                                            <th>Price ($)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($subs = mysqli_fetch_assoc($subsResult)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subs['id']); ?></td>
                                            <td><?php echo htmlspecialchars($subs['subs_type']); ?></td>
                                            <td><?php echo htmlspecialchars($subs['mbps']); ?></td>
                                            <td><?php echo htmlspecialchars($subs['price']); ?></td>
                                            <td>
                                                <button class="btn btn-primary btn-sm" onclick="subscribe(<?php echo $subs['id']; ?>)">Subscribe</button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- End Subscription Table -->
                </div>
            </div>
        </div>
    </div>

    <?php include 'Includes/footer.php';?>
</div>

<?php include 'Includes/settings.php';?>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/jquery.dataTables.min.js"></script>

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
                column.data().unique().sort().each(function (d) {
                    select.append('<option value="' + d + '">' + d + '</option>');
                });
            });
        },
    });
});

function subscribe(subId) {
    if (!confirm("Subscribe to this plan?")) return;
    $.post("", { subscribe_id: subId }, function(response){
        if(response.trim() === "success"){
            alert("Subscription successful! Status: Pending, Payment: Not Paid");
            location.reload();
        } else {
            alert("Error: " + response);
        }
    });
}
</script>
</body>
</html>
