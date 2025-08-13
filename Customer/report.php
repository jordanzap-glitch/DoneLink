<?php
include '../Includes/session.php';
include '../Includes/dbcon.php';

// Fetch messages from the database with user details
$messages = [];
$query = "
    SELECT r.id, r.customer_id, r.to_admin, r.to_user, r.status, r.date_created, 
           c.firstname, c.lastname 
    FROM tbl_reports r
    LEFT JOIN tbl_customer c ON r.customer_id = c.id
";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

// Initialize status message variable
$status_message = "";

// Check for status message in the URL
if (isset($_GET['status_message'])) {
    $status_message = $_GET['status_message'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the message from the form
    $to_admin = $_POST['to_admin'];
    $customer_id = $_SESSION['userId']; // Assuming userId is stored in session
    $status = 'sent';
    $date_created = date('Y-m-d H:i:s'); // Current date and time

    // Insert the message into the database
    $insert_query = "
        INSERT INTO tbl_reports (customer_id, to_admin, status, date_created) 
        VALUES (?, ?, ?, ?)
    ";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("isss", $customer_id, $to_admin, $status, $date_created);

    if ($insert_stmt->execute()) {
        // Redirect with success message
        header("Location: " . $_SERVER['PHP_SELF'] . "?status_message=Message sent successfully!");
        exit();
    } else {
        // Redirect with error message
        header("Location: " . $_SERVER['PHP_SELF'] . "?status_message=Error sending message.");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Report Management</title>
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
    <style>
        .message-highlight {
            background-color: #f0f8ff; /* Light blue background for messages */
            border-left: 5px solid #007bff; /* Blue left border */
            padding: 10px;
            margin-bottom: 10px;
        }
        .reply-highlight {
            background-color: #fff3cd; /* Light yellow background for replies */
            border-left: 5px solid #ffc107; /* Yellow left border */
            padding: 10px;
            margin-bottom: 10px;
        }
        .inbox-scroll {
            max-height: 400px; /* Set a maximum height for the inbox */
            overflow-y: auto; /* Enable vertical scrolling */
        }
    </style>
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
                    <h3 class="fw-bold mb-3">Reply to Users</h3>
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
                            <a href="#">Reply to Users</a>
                        </li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Send Reply to Admin</div>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="to_admin">Message</label>
                                                <textarea name="to_admin" class="form-control" id="to_admin" placeholder="Enter your message" rows="5" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <button type="submit" class="btn btn-success">Send</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Inbox</div>
                            </div>
                            <div class="card-body inbox-scroll">
                                <ul class="list-group">
                                    <?php if (empty($messages)): ?>
                                        <li class="list-group-item">No messages found.</li>
                                    <?php else: ?>
                                        <?php foreach ($messages as $message): ?>
                                            <li class="list-group-item">
                                                <div class="media">
                                                    <div class="media-body">
                                                        <div class="message-highlight">
                                                            <strong>To Admin</strong> <br><br>
                                                            <strong>From:</strong> <?php echo htmlspecialchars($message['firstname'] . ' ' . $message['lastname']); ?><br><br>
                                                            <strong>Message:</strong> <br><?php echo nl2br(htmlspecialchars($message['to_admin'])); ?><br><br>
                                                        </div>
                                                        <div class="reply-highlight">
                                                            <strong>Admin Reply:</strong> <br><?php echo nl2br(htmlspecialchars($message['to_user'])); ?><br><br>
                                                        </div>
                                                        <strong>Status:</strong> <?php echo htmlspecialchars($message['status']); ?><br>
                                                        <strong>Date:</strong> <?php echo htmlspecialchars($message['date_created']); ?>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
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

    <!-- Kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>

    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script>
        // Status message handling
        <?php if (!empty($status_message)) { ?>
            let message = "";
            switch ("<?php echo $status_message; ?>") {
                case "Message sent successfully!":
                    message = "Message sent successfully!";
                    break;
                case "Error sending message.":
                    message = "An error occurred while sending the message.";
                    break;
                default:
                    message = "An unexpected error occurred.";
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
    </script>
</body>
</html>
