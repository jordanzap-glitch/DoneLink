<?php 
error_reporting();
include '../Includes/session.php';
include '../Includes/dbcon.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Load Composer's autoloader

// Initialize status message variable
$statusMsg = "";

// Fetch all subscriptions from tbl_subscription for the dropdown
$subscriptionResult = $conn->query("SELECT id, subs_type, mbps FROM tbl_subscription");

// Check if the form is submitted for adding a customer
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['edit']) && !isset($_POST['delete'])) {
    // Get the form data
    $firstName = $_POST['firstName'];
    $middleName = $_POST['middleName'];
    $lastName = $_POST['lastName'];
    $address = $_POST['address'];
    $subscriptionId = $_POST['subscription_id'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];  // Store the password as plain text
    $contactNo = $_POST['contact_no']; // New contact number field
    $dateCreated = date('Y-m-d H:i:s'); // Get the current date and time

    // Prepare an SQL statement for tbl_customer
    $stmtCustomer = $conn->prepare("INSERT INTO tbl_customer (firstname, middlename, lastname, address, subscription_id, email, username, password, contact_no, datecreated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Bind parameters for tbl_customer
    $stmtCustomer->bind_param("ssssisssss", $firstName, $middleName, $lastName, $address, $subscriptionId, $email, $username, $password, $contactNo, $dateCreated);

    // Execute the statement for tbl_customer
    if ($stmtCustomer->execute()) {
        // Get the last inserted customer ID
        $customerId = $stmtCustomer->insert_id;

        // Prepare an SQL statement for tbl_user
        $stmtUser      = $conn->prepare("INSERT INTO tbl_user (customer_id, email, username, password, user_type, date_created) VALUES (?, ?, ?, ?, 'customer', ?)");
        
        // Bind parameters for tbl_user
        $stmtUser     ->bind_param("issss", $customerId, $email, $username, $password, $dateCreated);

        // Execute the statement for tbl_user
        if ($stmtUser     ->execute()) {
            // Send email with the customer details
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();                                            // Send using SMTP
                $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                $mail->Username   = 'jujuzapanta@gmail.com';                // SMTP username
                $mail->Password   = 'fypo gwrw shsv hdqs';                 // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;      // Enable TLS encryption
                $mail->Port       = 587;                                   // TCP port to connect to

                //Recipients
                $mail->setFrom('jujuzapanta@gmail.com', 'donelink');
                $mail->addAddress($email);                                 // Add a recipient

                // Content
                $mail->isHTML(true);                                      // Set email format to HTML
                $mail->Subject = 'Please do not share';
                $mail->Body    = "Your account has been created successfully!<br>
                                  <strong>Email:</strong> $email<br>
                                  <strong>Username:</strong> $username<br>
                                  <strong>Password:</strong> $password<br>
                                  <strong>Contact No:</strong> $contactNo<br>
                                  Please change your password after logging in.";
                $mail->AltBody = "Your account has been created successfully!\n
                                  Email: $email\n
                                  Username: $username\n
                                  Password: $password\n
                                  Contact No: $contactNo\n
                                  Please change your password after logging in.";

                $mail->send();
                $statusMsg = "added";
            } catch (Exception $e) {
                $statusMsg = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $statusMsg = "Error inserting user: " . $stmtUser     ->error;
        }

        // Close the statement for tbl_user
        $stmtUser     ->close();
    } else {
        $statusMsg = "Error: " . $stmtCustomer->error;
    }

    // Close the statement for tbl_customer
    $stmtCustomer->close();
}

// Check if the form is submitted for editing a customer
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit'])) {
    // Get the form data
    $id = $_POST['id'];
    $firstName = $_POST['editFirstName'];
    $middleName = $_POST['editMiddleName'];
    $lastName = $_POST['editLastName'];
    $address = $_POST['editAddress'];
    $subscriptionId = $_POST['editSubscriptionId'];
    $email = $_POST['editEmail'];
    $username = $_POST['editUsername'];
    $password = $_POST['editPassword'];
    $contactNo = $_POST['editContactNo']; // New contact number field

    // Prepare an SQL statement for updating tbl_customer
    if (!empty($password)) {
        // If a new password is provided, update it
        $stmtEdit = $conn->prepare("UPDATE tbl_customer SET firstname=?, middlename=?, lastname=?, address=?, subscription_id=?, email=?, username=?, password=?, contact_no=? WHERE id=?");
        $stmtEdit->bind_param("ssssissssi", $firstName, $middleName, $lastName, $address, $subscriptionId, $email, $username, $password, $contactNo, $id);
    } else {
        // If no new password is provided, update without changing the password
        $stmtEdit = $conn->prepare("UPDATE tbl_customer SET firstname=?, middlename=?, lastname=?, address=?, subscription_id=?, email=?, username=?, contact_no=? WHERE id=?");
        $stmtEdit->bind_param("ssssisssi", $firstName, $middleName, $lastName, $address, $subscriptionId, $email, $username, $contactNo, $id);
    }

    // Execute the statement for updating tbl_customer
    if ($stmtEdit->execute()) {
        $statusMsg = "updated";
    } else {
        $statusMsg = "Error: " . $stmtEdit->error;
    }

    // Close the statement for updating tbl_customer
    $stmtEdit->close();
}

// Check if the form is submitted for deleting a customer
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $id = $_POST['id'];

    // Prepare an SQL statement for deleting from tbl_customer
    $stmtDelete = $conn->prepare("DELETE FROM tbl_customer WHERE id=?");
    $stmtDelete->bind_param("i", $id);

    // Execute the statement for deleting from tbl_customer
    if ($stmtDelete->execute()) {
        $statusMsg = "deleted";
    } else {
        $statusMsg = "Error: " . $stmtDelete->error;
    }

    // Close the statement for deleting from tbl_customer
    $stmtDelete->close();
}

// Fetch all customers from tbl_customer
$result = $conn->query("SELECT id, firstname, middlename, lastname, address, subscription_id, email, username, contact_no, datecreated FROM tbl_customer");

// Ensure to close the database connection when done
$conn->close();
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Customer Management</title>
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
                    <h3 class="fw-bold mb-3">Customer Management</h3>
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
                            <a href="#">Customer Form</a>
                        </li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Add Customer</div>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="firstName">First Name</label>
                                                <input type="text" name="firstName" class="form-control" id="firstName" placeholder="Enter First Name" required />
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="middleName">Middle Name</label>
                                                <input type="text" name="middleName" class="form-control" id="middleName" placeholder="Enter Middle Name" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="lastName">Last Name</label>
                                                <input type="text" name="lastName" class="form-control" id="lastName" placeholder="Enter Last Name" required />
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="address">Address</label>
                                                <input type="text" name="address" class="form-control" id="address" placeholder="Enter Address" required />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="subscription_id">Subscription</label>
                                                <select name="subscription_id" class="form-control" id="subscription_id" required>
                                                    <option value="">Select Subscription</option>
                                                    <?php if ($subscriptionResult->num_rows > 0): ?>
                                                        <?php while($sub = $subscriptionResult->fetch_assoc()): ?>
                                                            <option value="<?php echo $sub['id']; ?>"><?php echo $sub['subs_type'] . " (" . $sub['mbps'] . " Mbps)"; ?></option>
                                                        <?php endwhile; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" name="email" class="form-control" id="email" placeholder="Enter Email" required />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="username">Username</label>
                                                <input type="text" name="username" class="form-control" id="username" placeholder="Enter Username" required />
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="contact_no">Contact Number</label>
                                                <input type="text" name="contact_no" class="form-control" id="contact_no" placeholder="Enter Contact Number" required />
                                            </div>
                                        </div>
                                    </div>
                                   <div class="row">
                                        <div class="col-md-6 col-lg-5">
                                            <div class="form-group">
                                                <label for="password">Password</label>
                                                <div class="input-group">
                                                    <input type="password" name="password" class="form-control" id="password" placeholder="Enter Password" required />
                                                    <div class="input-group-append">
                                                       <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()">Generate</button>
                                                    </div>
                                                </div>
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
                                <div class="card-title">Customer List</div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive"> <!-- Added responsive wrapper -->
                                    <table id="multi-filter-select" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>First Name</th>
                                                <th>Middle Name</th>
                                                <th>Last Name</th>
                                                <th>Address</th>
                                                <th>Subscription ID</th>
                                                <th>Email</th>
                                                <th>Username</th>
                                                <th>Contact No</th> <!-- New column for contact number -->
                                                <th>Date Created</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result->num_rows > 0): ?>
                                                <?php while($row = $result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo $row['id']; ?></td>
                                                        <td><?php echo $row['firstname']; ?></td>
                                                        <td><?php echo $row['middlename']; ?></td>
                                                        <td><?php echo $row['lastname']; ?></td>
                                                        <td><?php echo $row['address']; ?></td>
                                                        <td><?php echo $row['subscription_id']; ?></td>
                                                        <td><?php echo $row['email']; ?></td>
                                                        <td><?php echo $row['username']; ?></td>
                                                        <td><?php echo $row['contact_no']; ?></td> <!-- Display contact number -->
                                                        <td><?php echo $row['datecreated']; ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['firstname']); ?>', '<?php echo htmlspecialchars($row['middlename']); ?>', '<?php echo htmlspecialchars($row['lastname']); ?>', '<?php echo htmlspecialchars($row['address']); ?>', '<?php echo $row['subscription_id']; ?>', '<?php echo htmlspecialchars($row['email']); ?>', '<?php echo htmlspecialchars($row['username']); ?>', '<?php echo htmlspecialchars($row['contact_no']); ?>')">Edit</button>
                                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete</button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="11" class="text-center">No customers found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div> <!-- End of responsive wrapper -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Customer Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Customer</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="">
                            <input type="hidden" name="id" id="editId" />
                            <div class="form-group">
                                <label for="editFirstName">First Name</label>
                                <input type="text" name="editFirstName" class="form-control" id="editFirstName" required />
                            </div>
                            <div class="form-group">
                                <label for="editMiddleName">Middle Name</label>
                                <input type="text" name="editMiddleName" class="form-control" id="editMiddleName" />
                            </div>
                            <div class="form-group">
                                <label for="editLastName">Last Name</label>
                                <input type="text" name="editLastName" class="form-control" id="editLastName" required />
                            </div>
                            <div class="form-group">
                                <label for="editAddress">Address</label>
                                <input type="text" name="editAddress" class="form-control" id="editAddress" required />
                            </div>
                            <div class="form-group">
                                <label for="editSubscriptionId">Subscription</label>
                                <select name="editSubscriptionId" class="form-control" id="editSubscriptionId" required>
                                    <option value="">Select Subscription</option>
                                    <?php
                                    // Reset the subscription result pointer to the beginning
                                    $subscriptionResult->data_seek(0);
                                    if ($subscriptionResult->num_rows > 0): 
                                        while($sub = $subscriptionResult->fetch_assoc()): ?>
                                            <option value="<?php echo $sub['id']; ?>"><?php echo $sub['subs_type'] . " (" . $sub['mbps'] . " Mbps)"; ?></option>
                                        <?php endwhile; 
                                    endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="editEmail">Email</label>
                                <input type="email" name="editEmail" class="form-control" id="editEmail" required />
                            </div>
                            <div class="form-group">
                                <label for="editUsername">Username</label>
                                <input type="text" name="editUsername" class="form-control" id="editUsername" required />
                            </div>
                            <div class="form-group">
                                <label for="editContactNo">Contact Number</label>
                                <input type="text" name="editContactNo" class="form-control" id="editContactNo" required />
                            </div>
                            <div class="form-group">
                                <label for="editPassword">Password</label>
                                <input type="password" name="editPassword" class="form-control" id="editPassword" placeholder="Leave blank to keep current password" />
                            </div>
                            <button type="submit" name="edit" class="btn btn-primary">Update</button>
                        </form>
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

    <!-- Sweet Alert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- DataTables JS -->
    <script src="assets/js/jquery.dataTables.min.js"></script> <!-- DataTables JS -->
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    
    <!-- Kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
        function generatePassword() {
            const length = 8; // Length of the password
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"; // Letters and numbers
            let password = "";
            for (let i = 0; i < length; i++) {
                const randomIndex = Math.floor(Math.random() * charset.length);
                password += charset[randomIndex];
            }
            document.getElementById("password").value = password; // Set the generated password in the input field
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

            // Check if the status message is set and show SweetAlert
            <?php if (!empty($statusMsg)) { ?>
                $(document).ready(function() {
                    let message = "";
                    switch ("<?php echo $statusMsg; ?>") {
                        case "added":
                            message = "Customer added successfully!";
                            break;
                        case "updated":
                            message = "Customer updated successfully!";
                            break;
                        case "deleted":
                            message = "Customer deleted successfully!";
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
        });

        // Function to open the edit modal
        function openEditModal(id, firstName, middleName, lastName, address, subscriptionId, email, username, contactNo) {
            $('#editId').val(id);
            $('#editFirstName').val(firstName);
            $('#editMiddleName').val(middleName);
            $('#editLastName').val(lastName);
            $('#editAddress').val(address);
            $('#editSubscriptionId').val(subscriptionId);
            $('#editEmail').val(email);
            $('#editUsername').val(username);
            $('#editContactNo').val(contactNo);
            $('#editModal').modal('show');
        }

        // Function to confirm delete action
        function confirmDelete(id) {
            swal({
                title: "Are you sure?",
                text: "Once deleted, you will not be able to recover this customer!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    // If confirmed, create a form and submit it
                    var form = document.createElement("form");
                    form.method = "POST";
                    form.action = "";
                    var input = document.createElement("input");
                    input.type = "hidden";
                    input.name = "id";
                    input.value = id;
                    form.appendChild(input);
                    var deleteInput = document.createElement("input");
                    deleteInput.type = "hidden";
                    deleteInput.name = "delete";
                    form.appendChild(deleteInput);
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    swal("Your customer is safe!");
                }
            });
        }
    </script>

</body>
</html>