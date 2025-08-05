<?php
error_reporting(0);
include '../Includes/session.php'; // Include session management 
include '../Includes/dbcon.php';

// Step 1: Retrieve the User ID from the session
$userId = $_SESSION['userId']; // Assuming 'userId' is stored in the session

// Step 2: Query the database to get user information
$query = "SELECT firstname, middlename, lastname, address, contact_no, email, image_path FROM tbl_customer WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId); // Bind the user ID parameter
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch the user data
    $user = $result->fetch_assoc();
    $fullName = $user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname'];
    $address = $user['address'];
    $contactNo = $user['contact_no'];
    $email = $user['email']; // Retrieve email
    $profilePicture = $user['image_path'] ? $user['image_path'] : 'assets/img/default-profile.png'; // Default image if none
} else {
    // Handle case where user is not found
    $fullName = "N/A";
    $address = "N/A";
    $contactNo = "N/A";
    $email = "N/A"; // Default value for email
    $profilePicture = 'assets/img/default-profile.png'; // Default image
}

// Initialize status message
$statusMessage = "";

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image_path'])) {
    $targetDir = "profile/"; // Directory where images will be uploaded
    $targetFile = $targetDir . basename($_FILES["image_path"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["image_path"]["tmp_name"]);
    if ($check === false) {
        $statusMessage = "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size (limit to 2MB)
    if ($_FILES["image_path"]["size"] > 2000000) {
        $statusMessage = "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
        $statusMessage = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $statusMessage = "Sorry, your file was not uploaded.";
    } else {
        // If everything is ok, try to upload file
        if (move_uploaded_file($_FILES["image_path"]["tmp_name"], $targetFile)) {
            // Update the image path in the database
            $updateQuery = "UPDATE tbl_customer SET image_path = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("si", $targetFile, $userId);
            if ($updateStmt->execute()) {
                // Update the profile picture variable to reflect the new image
                $profilePicture = $targetFile;
                $statusMessage = "Image uploaded successfully.";
            } else {
                $statusMessage = "Error updating record: " . $conn->error;
            }
            $updateStmt->close();
        } else {
            $statusMessage = "Sorry, there was an error uploading your file.";
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];

    // Verify current password
    $query = "SELECT password FROM tbl_customer WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    if ($currentPassword === $userData['password']) { // Check without hashing
        // Update the password in the database
        $updatePasswordQuery = "UPDATE tbl_customer SET password = ? WHERE id = ?";
        $updatePasswordStmt = $conn->prepare($updatePasswordQuery);
        $updatePasswordStmt->bind_param("si", $newPassword, $userId);
        if ($updatePasswordStmt->execute()) {
            $statusMessage = "Password updated successfully.";
        } else {
            $statusMessage = "Error updating password: " . $conn->error;
        }
        $updatePasswordStmt->close();
    } else {
        $statusMessage = "Current password is incorrect.";
    }
    $stmt->close();
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Profile</title>
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
    <style>
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #007bff;
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
        .input-group {
            position: relative;
        }
        .input-group .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
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
                    <h3 class="fw-bold mb-3">Profile</h3>
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
                            <a href="#">Profile</a>
                        </li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">User  Profile</div>
                            </div>
                            <div class="card-body text-center">
                                <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture" class="profile-picture mb-3">
                                <p><strong>Full Name:</strong> <span id="fullName"><?php echo htmlspecialchars($fullName); ?></span></p>
                                <p><strong>Address:</strong> <span id="address"><?php echo htmlspecialchars($address); ?></span></p>
                                <p><strong>Email:</strong> <span id="email"><?php echo htmlspecialchars($email); ?></span></p>
                                <p><strong>Contact No:</strong> <span id="contactNo"><?php echo htmlspecialchars($contactNo); ?></span></p>
                                <p><strong>Status:</strong> <span id="status">Active</span></p>
                                <div class="mt-3">
                                    <form action="" method="POST" enctype="multipart/form-data">
                                        <input type="file" name="image_path" accept="image/*" required>
                                        <button type="submit" class="btn btn-custom mt-2">Upload Image</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Change Password</div>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <div class="input-group">
                                            <input type="password" name="current_password" class="form-control" id="current_password" required>
                                            <span class="toggle-password" onclick="togglePassword('current_password')">
                                                <i class="fa fa-eye" id="eye-icon-current"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <div class="input-group">
                                            <input type="password" name="new_password" class="form-control" id="new_password" required>
                                            <span class="toggle-password" onclick="togglePassword('new_password')">
                                                <i class="fa fa-eye" id="eye-icon-new"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm Password</label>
                                        <div class="input-group">
                                            <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
                                            <span class="toggle-password" onclick="togglePassword('confirm_password')">
                                                <i class="fa fa-eye" id="eye-icon-confirm"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-custom mt-2">Update Password</button>
                                </form>
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

    <!-- Sweet Alert -->
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- Kaiadmin JS -->
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
        function togglePassword(id) {
            var input = document.getElementById(id);
            var icon = document.getElementById('eye-icon-' + id.split('_')[1]);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // SweetAlert for image upload and password change
        <?php if (!empty($statusMessage)) { ?>
            let message = "<?php echo htmlspecialchars($statusMessage); ?>";
            let iconType = message.includes("successfully") ? "success" : "error";
            swal(message, "", {
                icon: iconType,
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
