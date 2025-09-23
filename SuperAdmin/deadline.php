<?php 
error_reporting(E_ALL);
include '../Includes/session.php';
include '../Includes/dbcon.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

// Initialize status message variable
$statusMsg = "";

// Check if the connection is established
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all subscribers with pending payments, join with customer, subscription, and payment info
$result = $conn->query("
    SELECT 
        s.id AS subscriber_id,
        c.id AS customer_id,
        c.firstname, 
        c.lastname, 
        c.contact_no, 
        c.email,
        sub.subs_type,
        p.price,
        s.date_created,
        s.due_date,
        s.payment_status
    FROM tbl_subscribers s
    JOIN tbl_customer c ON s.customer_id = c.id
    JOIN tbl_subscription sub ON s.subscription_id = sub.id
    LEFT JOIN tbl_payment p ON s.payment_id = p.id
    WHERE s.payment_status = 'Pending'
");

if (!$result) {
    die("Query failed: " . $conn->error);
}

$rows = []; // array to store rows for display
while($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// Close DB connection
$conn->close();

// Handle AJAX email request
if(isset($_POST['send_email'])) {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $status = $_POST['status'];

    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jujuzapanta@gmail.com'; 
        $mail->Password   = 'fypo gwrw shsv hdqs'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('jujuzapanta@gmail.com', 'Subscription Admin');
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Subscription Payment Reminder";
        if($status == 'Due Soon') {
            $mail->Body    = "Hello $name,<br><br>Your subscription payment is due soon. Please make the payment before the due date to avoid interruption.<br><br>Thank you.";
        } elseif($status == 'Overdue') {
            $mail->Body    = "Hello $name,<br><br>Your subscription payment is overdue. Please make the payment immediately to continue enjoying the service.<br><br>Thank you.";
        }

        $mail->send();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $mail->ErrorInfo]);
    }
    exit;
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>Subscribers Management</title>
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
    active: function () { sessionStorage.fonts = true; },
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
    <?php include 'Includes/sidebar.php';?>
    <?php include 'Includes/header.php';?>

    <div class="container">
        <div class="page-inner">
            <div class="page-header">
                <h3 class="fw-bold mb-3">Subscribers Management</h3>
                <ul class="breadcrumbs mb-3">
                    <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="#">Forms</a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="#">Subscribers List</a></li>
                </ul>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header"><div class="card-title">List of Pending Subscribers</div></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="multi-filter-select" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Subscriber ID</th>
                                            <th>Customer Name</th>
                                            <th>Subscription Type</th>
                                            <th>Price</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($rows)): ?>
                                            <?php foreach($rows as $row): ?>
                                                <tr data-email="<?php echo $row['email']; ?>">
                                                    <td><?php echo $row['subscriber_id']; ?></td>
                                                    <td><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td>
                                                    <td><?php echo $row['subs_type']; ?></td>
                                                    <td><?php echo $row['price']; ?></td>
                                                    <td class="due-date" data-due="<?php echo $row['due_date']; ?>">
                                                        <?php echo date('F j, Y', strtotime($row['due_date'])); ?>
                                                    </td>
                                                    <td class="status"></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary send-email" disabled>
                                                            <i class="fas fa-envelope"></i> Send Email
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No pending subscribers found</td>
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

<!-- Core JS Files -->
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
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<script>
$(document).ready(function () {
    function calculateStatus(dueDate) {
        const today = new Date();
        const due = new Date(dueDate);
        const diffDays = Math.ceil((due - today) / (1000 * 60 * 60 * 24));
        if (diffDays < 0) return 'Overdue';
        else if (diffDays <= 5) return 'Due Soon';
        else return 'Pending';
    }

    // Update status column
    $('#multi-filter-select tbody tr').each(function () {
        const dueDate = $(this).find('.due-date').data('due');
        const statusText = calculateStatus(dueDate);
        let statusHTML = '';
        if(statusText === 'Overdue') {
            statusHTML = '<span class="text-danger fw-bold">Overdue</span>';
            $(this).find('.send-email').prop('disabled', false);
        } else if(statusText === 'Due Soon') {
            statusHTML = '<span class="text-warning fw-bold">Due Soon</span>';
            $(this).find('.send-email').prop('disabled', false);
        } else {
            statusHTML = '<span class="text-primary fw-bold">Pending</span>';
        }
        $(this).find('.status').html(statusHTML);
    });

    // Send Email button with SweetAlert feedback
    $('.send-email').click(function() {
        const row = $(this).closest('tr');
        const emailAddress = row.data('email');
        const status = row.find('.status').text().trim();
        const customerName = row.find('td:nth-child(2)').text();

        $.ajax({
            url: '', 
            type: 'POST',
            data: {
                send_email: true,
                email: emailAddress,
                name: customerName,
                status: status
            },
            success: function(response) {
                const res = JSON.parse(response);
                let message = '';
                if(res.success) {
                    message = 'Email sent successfully to ' + customerName;
                } else {
                    message = 'Email failed: ' + res.error;
                }
                swal(message, "", {
                    icon: message.includes("successfully") ? "success" : "error",
                    buttons: { confirm: { className: "btn btn-success" } },
                });
            }
        });
    });

    // Initialize DataTable
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
                    select.append('<option value="' + d + '">' + d + "</option>");
                });
            });
        },
    });
});
</script>
</body>
</html>
