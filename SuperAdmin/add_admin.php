<?php 
error_reporting();
//include '../Includes/session.php';
include '../Includes/dbcon.php';

// Initialize status message variable
$statusMsg = "";

// Check if the form is submitted for adding an admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['edit']) && !isset($_POST['delete'])) {
    // Get the form data
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];  // Store the password as plain text
    $dateCreated = date('Y-m-d H:i:s'); // Get the current date and time

    // Prepare an SQL statement for tbl_admin
    $stmtAdmin = $conn->prepare("INSERT INTO tbl_admin (firstName, lastName, email, username, password, datecreated) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Bind parameters for tbl_admin
    $stmtAdmin->bind_param("ssssss", $firstName, $lastName, $email, $username, $password, $dateCreated);

    // Execute the statement for tbl_admin
    if ($stmtAdmin->execute()) {
        // Get the last inserted ID
        $adminId = $stmtAdmin->insert_id;

        // Prepare an SQL statement for tbl_user
        $stmtUser     = $conn->prepare("INSERT INTO tbl_user (admin_id, email, username, password, user_type, date_created) VALUES (?, ?, ?, ?, ?, ?)");
        
        // Bind parameters for tbl_user
        $userType = 'admin'; // Set user type as admin
        $stmtUser    ->bind_param("isssss", $adminId, $email, $username, $password, $userType, $dateCreated);

        // Execute the statement for tbl_user
        if ($stmtUser    ->execute()) {
            $statusMsg = "added";
        } else {
            $statusMsg = "Error: " . $stmtUser    ->error;
        }

        // Close the statement for tbl_user
        $stmtUser    ->close();
    } else {
        $statusMsg = "Error: " . $stmtAdmin->error;
    }

    // Close the statement for tbl_admin
    $stmtAdmin->close();
}

// Check if the form is submitted for editing an admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit'])) {
    // Get the form data
    $id = $_POST['id'];
    $firstName = $_POST['editFirstName'];
    $lastName = $_POST['editLastName'];
    $email = $_POST['editEmail'];
    $username = $_POST['editUsername'];
    $password = $_POST['editPassword'];

    // Prepare an SQL statement for updating tbl_admin
    if (!empty($password)) {
        // If a new password is provided, update it
        $stmtEdit = $conn->prepare("UPDATE tbl_admin SET firstName=?, lastName=?, email=?, username=?, password=? WHERE id=?");
        $stmtEdit->bind_param("sssssi", $firstName, $lastName, $email, $username, $password, $id);
    } else {
        // If no new password is provided, update without changing the password
        $stmtEdit = $conn->prepare("UPDATE tbl_admin SET firstName=?, lastName=?, email=?, username=? WHERE id=?");
        $stmtEdit->bind_param("ssssi", $firstName, $lastName, $email, $username, $id);
    }

    // Execute the statement for updating tbl_admin
    if ($stmtEdit->execute()) {
        // Update tbl_user with the same admin_id
        if (!empty($password)) {
            $stmtUserEdit = $conn->prepare("UPDATE tbl_user SET email=?, username=?, password=? WHERE admin_id=?");
            $stmtUserEdit->bind_param("sssi", $email, $username, $password, $id);
        } else {
            $stmtUserEdit = $conn->prepare("UPDATE tbl_user SET email=?, username=? WHERE admin_id=?");
            $stmtUserEdit->bind_param("ssi", $email, $username, $id);
        }

        // Execute the statement for updating tbl_user
        if ($stmtUserEdit->execute()) {
            $statusMsg = "updated";
        } else {
            $statusMsg = "Error: " . $stmtUserEdit->error;
        }

        // Close the statement for updating tbl_user
        $stmtUserEdit->close();
    } else {
        $statusMsg = "Error: " . $stmtEdit->error;
    }

    // Close the statement for updating tbl_admin
    $stmtEdit->close();
}

// Check if the form is submitted for deleting an admin
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $id = $_POST['id'];

    // Prepare an SQL statement for deleting from tbl_admin
    $stmtDeleteAdmin = $conn->prepare("DELETE FROM tbl_admin WHERE id=?");
    $stmtDeleteAdmin->bind_param("i", $id);

    // Execute the statement for deleting from tbl_admin
    if ($stmtDeleteAdmin->execute()) {
        // Also delete from tbl_user
        $stmtDeleteUser   = $conn->prepare("DELETE FROM tbl_user WHERE admin_id=?");
        $stmtDeleteUser  ->bind_param("i", $id);
        $stmtDeleteUser  ->execute();
        $stmtDeleteUser  ->close();

        $statusMsg = "deleted";
    } else {
        $statusMsg = "Error: " . $stmtDeleteAdmin->error;
    }

    // Close the statement for deleting from tbl_admin
    $stmtDeleteAdmin->close();
}

// Fetch all admins from tbl_admin
$result = $conn->query("SELECT id, firstName, lastName, email, username, datecreated FROM tbl_admin");

// Ensure to close the database connection when done
$conn->close();
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Admin Management</title>
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
                    <h3 class="fw-bold mb-3">Admin Management</h3>
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
                            <a href="#">Admin Form</a>
                        </li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Add Admin</div>
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
                                                <label for="lastName">Last Name</label>
                                                <input type="text" name="lastName" class="form-control" id="lastName" placeholder="Enter Last Name" required />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-8 col-lg-10">
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
                                                <label for="password">Password</label>
                                                <input type="password" name="password" class="form-control" id="password" placeholder="Enter Password" required />
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
                                <div class="card-title">Admin List</div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive"> <!-- Added responsive wrapper -->
                                    <table id="multi-filter-select" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>First Name</th>
                                                <th>Last Name</th>
                                                <th>Email</th>
                                                <th>Username</th>
                                                <th>Date Created</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result->num_rows > 0): ?>
                                                <?php while($row = $result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo $row['id']; ?></td>
                                                        <td><?php echo $row['firstName']; ?></td>
                                                        <td><?php echo $row['lastName']; ?></td>
                                                        <td><?php echo $row['email']; ?></td>
                                                        <td><?php echo $row['username']; ?></td>
                                                        <td><?php echo $row['datecreated']; ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['firstName']); ?>', '<?php echo htmlspecialchars($row['lastName']); ?>', '<?php echo htmlspecialchars($row['email']); ?>', '<?php echo htmlspecialchars($row['username']); ?>')">Edit</button>
                                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $row['id']; ?>)">Delete</button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No admins found</td>
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

        <!-- Edit Admin Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Admin</h5>
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
                                <label for="editLastName">Last Name</label>
                                <input type="text" name="editLastName" class="form-control" id="editLastName" required />
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

        // Function to open the edit modal
        function openEditModal(id, firstName, lastName, email, username) {
            $('#editId').val(id);
            $('#editFirstName').val(firstName);
            $('#editLastName').val(lastName);
            $('#editEmail').val(email);
            $('#editUsername').val(username);
            $('#editModal').modal('show');
        }

        // Function to confirm delete action
        function confirmDelete(id) {
            swal({
                title: "Are you sure?",
                text: "Once deleted, you will not be able to recover this admin!",
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
                    swal("Your admin is safe!");
                }
            });
        }

        // Check if the status message is set and show SweetAlert
        <?php if (!empty($statusMsg)) { ?>
            $(document).ready(function() {
                let message = "";
                switch ("<?php echo $statusMsg; ?>") {
                    case "added":
                        message = "Admin added successfully!";
                        break;
                    case "updated":
                        message = "Admin updated successfully!";
                        break;
                    case "deleted":
                        message = "Admin deleted successfully!";
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
    </script>

</body>
</html>