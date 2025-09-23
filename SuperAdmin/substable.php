<?php 
error_reporting(E_ALL);
include '../Includes/session.php';
include '../Includes/dbcon.php';

// Include PHPMailer and FPDF libraries
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require('fpdf/fpdf.php');

// Initialize status message variable
$statusMsg = "";

// Handle approve/deny actions for subscribers
if (isset($_GET['id']) && isset($_GET['action'])) {
    $subscriberId = intval($_GET['id']);
    $action = $_GET['action'];

    // Fetch subscriber and customer info
    $stmt = $conn->prepare("
        SELECT sub.id as subscriber_id, sub.date_created, c.firstname, c.middlename, c.lastname, c.email, s.subs_type
        FROM tbl_subscribers sub
        JOIN tbl_customer c ON sub.customer_id = c.id
        JOIN tbl_subscription s ON sub.subscription_id = s.id
        WHERE sub.id = ?
    ");
    $stmt->bind_param("i", $subscriberId);
    $stmt->execute();
    $resultSub = $stmt->get_result();
    $subscriberData = $resultSub->fetch_assoc();
    $stmt->close();

    if ($subscriberData) {
        if ($action === 'approve' || $action === 'deny') {
            $newStatus = $action === 'approve' ? 'Active' : 'Denied';

            if ($action === 'approve') {
                // Calculate due_date: 1 month from date_created
                $dateCreated = new DateTime($subscriberData['date_created']);
                $dateCreated->modify('+1 month');
                $dueDate = $dateCreated->format('Y-m-d');

                $stmt = $conn->prepare("UPDATE tbl_subscribers SET status = ?, due_date = ? WHERE id = ?");
                $stmt->bind_param("ssi", $newStatus, $dueDate, $subscriberId);
            } else {
                $stmt = $conn->prepare("UPDATE tbl_subscribers SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $newStatus, $subscriberId);
            }

            if ($stmt->execute()) {
                $statusMsg = $action === 'approve' ? "approved" : "denied";

                // Send email if approved
                if ($action === 'approve') {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'jujuzapanta@gmail.com';
                        $mail->Password   = 'fypo gwrw shsv hdqs'; // Use app password or env variable in production
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;

                        $mail->setFrom('jujuzapanta@gmail.com', 'DoneLink');
                        $mail->addAddress($subscriberData['email'], $subscriberData['firstname'] . ' ' . $subscriberData['lastname']);
                        $mail->Subject = 'Subscription Approved';
                        $mail->Body    = "Dear " . $subscriberData['firstname'] . ",\n\nYour subscription (" . $subscriberData['subs_type'] . ") has been approved.\nYour due date is " . $dueDate . ".\n\nThank you for subscribing!\n\n- DoneLink Team";

                        $mail->send();
                    } catch (Exception $e) {
                        $statusMsg .= " | Email sending failed: {$mail->ErrorInfo}";
                    }
                }
            } else {
                $statusMsg = "Error updating subscriber: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch subscriber info joined with customer and subscription
$query = "
    SELECT sub.id as subscriber_id, 
           c.firstname, c.middlename, c.lastname, c.email, 
           s.subs_type, 
           sub.date_created, sub.status, sub.due_date
    FROM tbl_subscribers sub
    JOIN tbl_customer c ON sub.customer_id = c.id
    JOIN tbl_subscription s ON sub.subscription_id = s.id
    ORDER BY sub.date_created DESC
";
$result = $conn->query($query);

$conn->close();
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Subscribers Management</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="" type="image/x-icon" />
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: ["Font Awesome 5 Solid","Font Awesome 5 Regular","Font Awesome 5 Brands","simple-line-icons"],
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
                <h3 class="fw-bold mb-3">Subscribers Management</h3>
                <ul class="breadcrumbs mb-3">
                    <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="#">Tables</a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="#">Subscriber List</a></li>
                </ul>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header"><div class="card-title">Subscriber List</div></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="subscriber-table" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Full Name</th>
                                            <th>Subscription Type</th>
                                            <th>Date Created</th>
                                            <th>Status</th>
                                            <th>Due Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['firstname'].' '.$row['middlename'].' '.$row['lastname']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['subs_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['date_created']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $badgeClass = '';
                                                        switch ($row['status']) {
                                                            case 'Pending': $badgeClass = 'badge badge-warning'; break;
                                                            case 'Active': $badgeClass = 'badge badge-success'; break;
                                                            case 'Denied': $badgeClass = 'badge badge-danger'; break;
                                                            default: $badgeClass = 'badge badge-secondary';
                                                        }
                                                        ?>
                                                        <span class="<?php echo $badgeClass; ?>"><?php echo $row['status']; ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['due_date'] ?? '-'); ?></td>
                                                    <td>
                                                        <a href="?id=<?php echo $row['subscriber_id']; ?>&action=approve" class="btn btn-success btn-sm" title="Approve"><i class="fas fa-check"></i></a>
                                                        <a href="?id=<?php echo $row['subscriber_id']; ?>&action=deny" class="btn btn-danger btn-sm" title="Deny"><i class="fas fa-times"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No subscribers found</td>
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
    $("#subscriber-table").DataTable({
        pageLength: 5,
        responsive: true
    });

    <?php if (!empty($statusMsg)) { ?>
        let message = "";
        switch ("<?php echo $statusMsg; ?>") {
            case "approved": message = "Subscriber approved successfully! Email sent."; break;
            case "denied": message = "Subscriber denied successfully!"; break;
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
