<?php
include '../Includes/session.php';
include '../Includes/dbcon.php';

// Fetch messages from the database with user details
$messages = [];
$query = "
    SELECT r.id, r.customer_id, r.to_admin, r.to_user, r.status, r.date_created, c.firstname, c.lastname
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_user = $_POST['to_user'];
    $message_id = $_POST['message_id'];

    // Get the customer_id from message_id
    $stmt = $conn->prepare("SELECT customer_id FROM tbl_reports WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $customer_id = $row['customer_id'];

        // Update the message in tbl_reports
        $update_stmt = $conn->prepare("UPDATE tbl_reports SET to_user = ?, status = 'replied', date_created = NOW() WHERE id = ? AND customer_id = ?");
        $update_stmt->bind_param("sii", $to_user, $message_id, $customer_id);

        if ($update_stmt->execute()) {
            $_SESSION['status_message'] = "Reply sent!";
        } else {
            $_SESSION['status_message'] = "Error updating message.";
        }
        $update_stmt->close();
    } else {
        $_SESSION['status_message'] = "Message not found.";
    }

    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
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
            families: ["Font Awesome 5 Solid","Font Awesome 5 Regular","Font Awesome 5 Brands","simple-line-icons"],
            urls: ["assets/css/fonts.min.css"]
        },
        active: function(){ sessionStorage.fonts = true; }
    });

    function populateRecipientName(firstname, lastname, messageId) {
        document.getElementById('to').value = firstname + ' ' + lastname;
        document.getElementById('message_id').value = messageId;
    }
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <style>
    .message-highlight { background-color: #f0f8ff; border-left: 5px solid #007bff; padding: 10px; margin-bottom: 10px; }
    .reply-highlight { background-color: #fff3cd; border-left: 5px solid #ffc107; padding: 10px; margin-bottom: 10px; }
    .inbox-scroll { max-height: 400px; overflow-y: auto; }
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
                    <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="#">Forms</a></li>
                    <li class="separator"><i class="icon-arrow-right"></i></li>
                    <li class="nav-item"><a href="#">Reply to Users</a></li>
                </ul>
            </div>

            <div class="row">
                <!-- Reply Form -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><div class="card-title">Send Reply to the Users</div></div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="message_id" id="message_id" value="" />
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="to">To:</label>
                                            <input type="text" name="to" class="form-control" id="to" placeholder="Enter recipient's name" readonly />
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="to_user">Message</label>
                                            <input type="text" name="to_user" class="form-control" id="to_user" placeholder="Enter the message" required />
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <button type="submit" class="btn btn-success">Send</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Inbox -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><div class="card-title">Inbox</div></div>
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
                                                    <strong>To Admin</strong><br><br>
                                                    <strong>From:</strong> <?php echo htmlspecialchars($message['firstname'] . ' ' . $message['lastname']); ?><br><br>
                                                    <strong>Message:</strong><br><?php echo nl2br(htmlspecialchars($message['to_admin'])); ?><br><br>
                                                </div>
                                                <?php if(!empty($message['to_user'])): ?>
                                                <div class="reply-highlight">
                                                    <strong>Admin Reply:</strong><br><?php echo nl2br(htmlspecialchars($message['to_user'])); ?><br><br>
                                                </div>
                                                <?php endif; ?>
                                                <strong>Status:</strong> <?php echo htmlspecialchars($message['status']); ?><br>
                                                <strong>Date:</strong> <?php echo htmlspecialchars($message['date_created']); ?>
                                            </div>
                                            <div class="float-right mt-2">
                                                <a href="#" class="btn btn-primary" title="Reply"
                                                   onclick="populateRecipientName('<?php echo htmlspecialchars($message['firstname']); ?>', '<?php echo htmlspecialchars($message['lastname']); ?>', <?php echo $message['id']; ?>)">
                                                    <i class="fas fa-reply"></i> Reply
                                                </a>
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

    <!-- Footer -->
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
<script src="assets/js/kaiadmin.min.js"></script>
<script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

<script>
<?php if(!empty($_SESSION['status_message'])): ?>
    let message = "<?php echo $_SESSION['status_message']; ?>";
    swal(message, "", {
        icon: message.includes("sent") ? "success" : "error",
        buttons: { confirm: { className: "btn btn-success" } }
    });
    <?php unset($_SESSION['status_message']); ?>
<?php endif; ?>
</script>

</body>
</html>
