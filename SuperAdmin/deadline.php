<?php 
error_reporting(E_ALL);
include '../Includes/session.php';
include '../Includes/dbcon.php';
require_once '../vendor/autoload.php'; // Include the Twilio SDK
use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;
// Initialize status message variable
$statusMsg = "";

// Check if the connection is established
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all payments from tbl_payment and join with tbl_customer and tbl_subscription to get full name and subscription type
$result = $conn->query("
    SELECT p.id, p.transaction_id, c.firstname, c.lastname, c.contact_no, s.subs_type, p.price, p.status, p.date_created, p.due_date 
    FROM tbl_payment p 
    JOIN tbl_customer c ON p.customer_id = c.id
    JOIN tbl_subscription s ON p.subscription_id = s.id
");

if (!$result) {
    die("Query failed: " . $conn->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_sms'])) {
    // Your Account SID and Auth Token from console.twilio.com
    $sid = "xxxxxxxx"; // Replace with your Twilio SID
    $token = "xxxxxx"; // Replace with your Twilio Auth Token
    $client = new Client($sid, $token);

    // Get the payment ID from the POST request
    $paymentId = $_POST['payment_id'];

    // Fetch the customer's phone number and due date using the payment ID
    $paymentResult = $conn->query("SELECT c.contact_no, p.due_date FROM tbl_payment p JOIN tbl_customer c ON p.customer_id = c.id WHERE p.id = $paymentId");
    if (!$paymentResult) {
        die("Query failed: " . $conn->error);
    }
    $payment = $paymentResult->fetch_assoc();
    $contactNo = $payment['contact_no'];
    $dueDate = date('F j, Y', strtotime($payment['due_date'])); // Format the due date

    // Ensure you are using a valid Twilio number
    $twilioFromNumber = 'xxxxxxx'; // Replace with your valid Twilio number

    // Validate phone number format
    if (preg_match('/^\+\d{1,15}$/', $contactNo)) {
        // Send an SMS using Twilio's REST API
        $client->messages->create(
            $contactNo,
            [
                'from' => $twilioFromNumber, // Your Twilio phone number
                'body' => "Good day, this notification is from DoneLink, 
                reminding you that your internet bill is nearly due. To avoid service interruption, 
                please settle your payment on or before the due date. 
                You may pay through our online portal, or you can go to our payment center."
            ]
        );
        $statusMsg = "SMS sent successfully!";
    } else {
        $statusMsg = "Invalid phone number format.";
    }
}

// Ensure to close the database connection when done
$conn->close();
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Deadline Management</title>
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
    <link rel="stylesheet" href="assets/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="assets/css/sweetalert.css" />
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
                    <h3 class="fw-bold mb-3">Deadline Management</h3>
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
                                <div class="card-title">List</div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="multi-filter-select" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Transaction ID</th>
                                                <th>Customer Name</th>
                                                <th>Subscription Type</th>
                                                <th>Price</th>
                                                <th>Status</th>
                                                <th>Date Created</th>
                                                <th>Due Date</th>
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
                                                        <td><?php echo $row['subs_type']; ?></td>
                                                        <td><?php echo $row['price']; ?></td>
                                                        <td>
                                                            <?php 
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
                                                                    $badgeClass = 'badge badge-secondary';
                                                            }
                                                            ?>
                                                            <span class="<?php echo $badgeClass; ?>"><?php echo $row['status']; ?></span>
                                                        </td>
                                                        <td><?php echo date('F j, Y', strtotime($row['date_created'])); ?></td>
                                                        <td><?php echo date('F j, Y', strtotime($row['due_date'])); ?></td>
                                                        <td>
                                                            <form method="POST" action="">
                                                                <input type="hidden" name="payment_id" value="<?php echo $row['id']; ?>">
                                                                <button type="submit" name="send_sms" class="btn btn-primary" title="Send Action">
                                                                    <i class="fas fa-paper-plane"></i>
                                                                </button>
                                                            </form>
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

        <!--footer-->
        <?php include 'Includes/footer.php';?>
        
    </div>
    
    <!-- Core JS Files -->
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
    <script src="assets/js/jquery.dataTables.min.js"></script>
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
                let message = "<?php echo htmlspecialchars($statusMsg); ?>";
                swal(message, "", {
                    icon: "success",
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
