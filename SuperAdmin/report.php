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

// Handle form submission for reply
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_message_id'])) {
        // Handle delete request
        $delete_id = $_POST['delete_message_id'];
        $delete_query = "DELETE FROM tbl_reports WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $delete_id);

        if ($delete_stmt->execute()) {
            $status_message = "Message deleted!";
        } else {
            $status_message = "Error deleting message.";
        }
        $delete_stmt->close();

    } else {
        // Handle reply request
        $to_user = $_POST['to_user'];
        $recipient_name = $_POST['to'];
        $message_id = $_POST['message_id']; // Get the message ID from the form

        $customer_id_query = "SELECT id FROM tbl_customer WHERE CONCAT(firstname, ' ', lastname) = ?";
        $stmt = $conn->prepare($customer_id_query);
        $stmt->bind_param("s", $recipient_name);
        $stmt->execute();
        $customer_result = $stmt->get_result();

        if ($customer_result->num_rows > 0) {
            $customer_row = $customer_result->fetch_assoc();
            $customer_id = $customer_row['id'];

            $update_query = "UPDATE tbl_reports SET to_user = ?, status = 'replied', date_created = NOW() WHERE id = ? AND customer_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sii", $to_user, $message_id, $customer_id);

            if ($update_stmt->execute()) {
                $status_message = "Reply sent!";
            } else {
                $status_message = "Error updating message.";
            }

            $update_stmt->close();
        } else {
            $status_message = "Recipient not found.";
        }
        $stmt->close();
    }

    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?status_message=" . urlencode($status_message));
    exit();
}

// Check for status message in the URL
if (isset($_GET['status_message'])) {
    $status_message = $_GET['status_message'];
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

        function populateRecipientName(firstname, lastname, messageId) {
            document.getElementById('to').value = firstname + ' ' + lastname;
            document.getElementById('message_id').value = messageId;
        }

        function confirmDelete(messageId) {
            swal({
                title: "Are you sure?",
                text: "Once deleted, you will not be able to recover this message!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    // Create a form to submit the deletion
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'delete_message_id';
                    input.value = messageId;
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />
    <style>
        .message-highlight {
            background-color: #f0f8ff;
            border-left: 5px solid #007bff;
            padding: 10px;
            margin-bottom: 10px;
        }
        .reply-highlight {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 10px;
            margin-bottom: 10px;
        }
        .inbox-scroll {
            max-height: 400px;
            overflow-y: auto;
        }
        .action-buttons {
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'Includes/sidebar.php';?>
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
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Send Reply to Users</div>
                        </div>
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
                                                    <div class="action-buttons float-right">
                                                        <a href="#" class="btn btn-primary" title="Reply" onclick="populateRecipientName('<?php echo htmlspecialchars($message['firstname']); ?>', '<?php echo htmlspecialchars($message['lastname']); ?>', <?php echo $message['id']; ?>)">
                                                            <i class="fas fa-reply"></i> Reply
                                                        </a>
                                                        <button class="btn btn-danger" onclick="confirmDelete(<?php echo $message['id']; ?>)">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </button>
                                                    </div>
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

    <?php include 'Includes/footer.php';?>
</div>

<!-- Core JS Files -->
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/js/plugin/chart.js/chart.min.js"></script>
<script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>
<script src="assets/js/plugin/chart-circle/c
