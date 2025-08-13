<?php
ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'Includes/dbcon.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Load Composer's autoloader

// Function to generate a random OTP
function generateOTP($length = 6) {
    return rand(pow(10, $length-1), pow(10, $length)-1);
}

// Variable to control OTP field visibility
$error_message = ""; // Variable to hold error messages

if (isset($_POST['login'])) {
    // Get the submitted username and password
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check in Administrator table
    $query_superadmin = "SELECT * FROM tbl_superadmin WHERE username = '$username' AND password = '$password'";
    $rs_superadmin = $conn->query($query_superadmin);
    $num_superadmin = $rs_superadmin->num_rows;

    if ($num_superadmin > 0) {
        // Admin user detected
        $rows_superadmin = $rs_superadmin->fetch_assoc();
        $_SESSION['userId'] = $rows_superadmin['id'];
        $_SESSION['fullname'] = $rows_superadmin['fullname'];
        $_SESSION['user_type'] = 'superadmin'; // Set session user type

        // Redirect to admin dashboard
        header('Location:SuperAdmin/index.php');
        exit();
    } else {
        $query_admin = "SELECT * FROM tbl_admin WHERE (email = '$username' OR username = '$username') AND password = '$password'";
        $rs_admin = $conn->query($query_admin);
        $num_admin = $rs_admin->num_rows;

        if ($num_admin > 0) {
            // Admin user detected
            $rows_admin = $rs_admin->fetch_assoc();
            $_SESSION['userId'] = $rows_admin['id'];
            $_SESSION['firstname'] = $rows_admin['firstname'];
            $_SESSION['lastname'] = $rows_admin['lastname'];
            $_SESSION['email'] = $rows_admin['email'];
            $_SESSION['user_type'] = 'admin'; // Set session user type

            header('Location:Admin/index.php');
            exit();
        } else {
            // Check in Customer table
            $query_customer = "SELECT * FROM tbl_customer WHERE (email = '$username' OR username = '$username') AND password = '$password'";
            $rs_customer = $conn->query($query_customer);
            $num_customer = $rs_customer->num_rows;

            if ($num_customer > 0) {
                // Customer detected
                $rows_customer = $rs_customer->fetch_assoc();
                $_SESSION['userId'] = $rows_customer['id'];
                $_SESSION['firstname'] = $rows_customer['firstname'];
                $_SESSION['lastname'] = $rows_customer['lastname'];
                $_SESSION['email'] = $rows_customer['email'];
                $_SESSION['user_type'] = 'customer'; // Set session user type

                // Generate OTP
                $otp = generateOTP();
                $expirationTime = date('Y-m-d H:i:s', strtotime('+60 seconds')); // Current time + 60 seconds
                
                // Update customer record with OTP and expiration time
                $customerId = $rows_customer['id'];
                $update_query = "UPDATE tbl_customer SET otp = ?, otp_expiration = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ssi", $otp, $expirationTime, $customerId);
                $stmt->execute();

                // Send the OTP to the customer's email
                $mail = new PHPMailer(true);
                try {
                    //Server settings
                    $mail->isSMTP();                                            // Send using SMTP
                    $mail->Host       = 'smtp.gmail.com';                   // Set the SMTP server to send through
                    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                    $mail->Username   = 'jujuzapanta@gmail.com';             // SMTP username
                    $mail->Password   = 'fypo gwrw shsv hdqs';                // SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;      // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
                    $mail->Port       = 587;                                   // TCP port to connect to

                    //Recipients
                    $mail->setFrom('jujuzapanta@gmail.com', 'donelink');
                    $mail->addAddress($rows_customer['email']);               // Add a recipient

                    // Content
                    $mail->isHTML(true);                                      // Set email format to HTML
                    $mail->Subject = 'Your OTP Code';
                    $mail->Body    = "Your OTP code is: <strong>$otp</strong>";
                    $mail->AltBody = "Your OTP code is: $otp";

                    $mail->send();
                    // Redirect to OTP verification page
                    header('Location:otp.php');
                    exit();
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                // Invalid username or password
                $error_message = "Invalid Username/Password!";
            }
        }
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png" />
   
    <title>Login Page-Done Link</title>
    <!-- Fonts and icons -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <!-- Nucleo Icons -->
    <link href="assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Main Styling -->
    <link href="assets/css/soft-ui-dashboard-tailwind.css?v=1.0.5" rel="stylesheet" />
</head>

<body class="m-0 font-sans antialiased font-normal bg-white text-start text-base leading-default text-slate-500">
    <div class="container sticky top-0 z-sticky">
        <div class="flex flex-wrap -mx-3">
            <div class="w-full max-w-full px-3 flex-0">
                <!-- Navbar -->
                <nav class="absolute top-0 left-0 right-0 z-30 flex flex-wrap items-center px-4 py-2 mx-6 my-4 shadow-soft-2xl rounded-blur bg-white/80 backdrop-blur-2xl backdrop-saturate-200 lg:flex-nowrap lg:justify-start">
                    <div class="flex items-center justify-between w-full p-0 pl-6 mx-auto flex-wrap-inherit">
                        <a class="py-2.375 text-sm mr-4 ml-4 whitespace-nowrap font-bold text-slate-700 lg:ml-0" href="pages/dashboard.html">
                            Done Link
                        </a>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <main class="mt-0 transition-all duration-200 ease-soft-in-out">
        <section>
            <div class="relative flex items-center p-0 overflow-hidden bg-center bg-cover min-h-75-screen">
                <div class="container z-10">
                    <div class="flex flex-wrap mt-0 -mx-3">
                        <div class="flex flex-col w-full max-w-full px-3 mx-auto md:flex-0 shrink-0 md:w-6/12 lg:w-5/12 xl:w-4/12">
                            <div class="relative flex flex-col min-w-0 mt-32 break-words bg-transparent border-0 shadow-none rounded-2xl bg-clip-border">
                                <div class="p-6 pb-0 mb-0 bg-transparent border-b-0 rounded-t-2xl">
                                    <h3 class="relative z-10 font-bold text-transparent bg-gradient-to-tl from-blue-600 to-cyan-400 bg-clip-text">
                                        Welcome back
                                    </h3>
                                    <p class="mb-0">Enter your email and password to sign in</p>
                                    <?php if ($error_message): ?>
                                        <div class="alert alert-danger" role="alert" style="color:red;">
                                            <?php echo $error_message; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-auto p-6">
                                    <form role="form" method="POST" action="">
                                        <label class="mb-2 ml-1 font-bold text-xs text-slate-700">Email</label>
                                        <div class="mb-4">
                                            <input type="text" name="username" class="focus:shadow-soft-primary-outline text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:outline-none focus:transition-shadow" placeholder="Email or Username" aria-label="Email" aria-describedby="email-addon" required />
                                        </div>
                                        <label class="mb-2 ml-1 font-bold text-xs text-slate-700">Password</label>
                                        <div class="mb-4">
                                            <input type="password" name="password" id="password" class="focus:shadow-soft-primary-outline text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:outline-none focus:transition-shadow" placeholder="Password" aria-label="Password" aria-describedby="password-addon" required />
                                        </div>
                                        <div class="flex items-center mb-4">
                                            <div class="min-h-6 block pl-12">
                                                <input
                                                id="rememberMe"
                                                class="mt-0.54 rounded-10 duration-250 ease-soft-in-out after:rounded-circle after:shadow-soft-2xl after:duration-250 checked:after:translate-x-5.25 h-5 relative float-left -ml-12 w-10 cursor-pointer appearance-none border border-solid border-gray-200 bg-slate-800/10 bg-none bg-contain bg-left bg-no-repeat align-top transition-all after:absolute after:top-px after:h-4 after:w-4 after:translate-x-px after:bg-white after:content-[''] checked:border-slate-800/95 checked:bg-slate-800/95 checked:bg-none checked:bg-right"
                                                type="checkbox"
                                                checked="" />
                                                <label
                                                class="mb-2 ml-1 font-normal cursor-pointer select-none text-sm text-slate-700"
                                                for="rememberMe"
                                                >Remember me &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label
                                                >
                                            </div>
                                            <div class="min-h-7 block pl-12">
                                                
                                                <input
                                                id="showPassword"
                                                class="mt-0.54 rounded-10 duration-250 ease-soft-in-out after:rounded-circle after:shadow-soft-2xl after:duration-250 checked:after:translate-x-5.25 h-5 relative float-left -ml-12 w-10 cursor-pointer appearance-none border border-solid border-gray-200 bg-slate-800/10 bg-none bg-contain bg-left bg-no-repeat align-top transition-all after:absolute after:top-px after:h-4 after:w-4 after:translate-x-px after:bg-white after:content-[''] checked:border-slate-800/95 checked:bg-slate-800/95 checked:bg-none checked:bg-right"
                                                type="checkbox"
                                                onclick="togglePasswordVisibility()" />
                                                <label
                                                class="mb-2 ml-1 font-normal cursor-pointer select-none text-sm text-slate-700"
                                                for="showPassword"
                                                >Show Password</label
                                                >
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" name="login" class="inline-block w-full px-6 py-3 mt-6 mb-0 font-bold text-center text-white uppercase align-middle transition-all bg-transparent border-0 rounded-lg cursor-pointer shadow-soft-md bg-x-25 bg-150 leading-pro text-xs ease-soft-in tracking-tight-soft bg-gradient-to-tl from-blue-600 to-cyan-400 hover:scale-102 hover:shadow-soft-xs active:opacity-85">
                                                Sign in
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div class="p-6 px-1 pt-0 text-center bg-transparent border-t-0 border-t-solid rounded-b-2xl lg:px-2">
                                    <p class="mx-auto mb-6 leading-normal text-sm">
                                        Don't have an account?
                                        <a href="pages/sign-up.html" class="relative z-10 font-semibold text-transparent bg-gradient-to-tl from-blue-600 to-cyan-400 bg-clip-text">Sign up</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="w-full max-w-full px-3 lg:flex-0 shrink-0 md:w-6/12">
                            <div class="absolute top-0 hidden w-3/5 h-full -mr-32 overflow-hidden -skew-x-10 -right-40 rounded-bl-xl md:block">
                                <div class="absolute inset-x-0 top-0 z-0 h-full -ml-16 bg-cover skew-x-10" style="background-image: url('assets/img/curved-images/curved6.jpg');"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer class="py-12">
        <div class="container">
            <div class="flex flex-wrap -mx-3">
                <div class="flex-shrink-0 w-full max-w-full mx-auto mb-6 text-center lg:flex-0 lg:w-8/12">
                    <a href="javascript:;" target="_blank" class="mb-2 mr-4 text-slate-400 sm:mb-0 xl:mr-12">About Us</a>
                    <a href="javascript:;" target="_blank" class="mb-2 mr-4 text-slate-400 sm:mb-0 xl:mr-12">Pricing</a>
                </div>
                <div class="flex-shrink-0 w-full max-w-full mx-auto mt-2 mb-6 text-center lg:flex-0 lg:w-8/12">
                    <a href="javascript:;" target="_blank" class="mr-6 text-slate-400">
                        <span class="text-lg fab fa-dribbble"></span>
                    </a>
                    <a href="javascript:;" target="_blank" class="mr-6 text-slate-400">
                        <span class="text-lg fab fa-twitter"></span>
                    </a>
                    <a href="javascript:;" target="_blank" class="mr-6 text-slate-400">
                        <span class="text-lg fab fa-instagram"></span>
                    </a>
                    <a href="javascript:;" target="_blank" class="mr-6 text-slate-400">
                        <span class="text-lg fab fa-pinterest"></span>
                    </a>
                    <a href="javascript:;" target="_blank" class="mr-6 text-slate-400">
                        <span class="text-lg fab fa-github"></span>
                    </a>
                </div>
            </div>
            <div class="flex flex-wrap -mx-3">
                <div class="w-8/12 max-w-full px-3 mx-auto mt-1 text-center flex-0">
                    <p class="mb-0 text-slate-400">
                        Copyright ©
                        <script>
                            document.write(new Date().getFullYear());
                        </script>
                    
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <!-- plugin for scrollbar  -->
    <script src="assets/js/plugins/perfect-scrollbar.min.js" async></script>
    <!-- main script file  -->
    <script src="assets/js/soft-ui-dashboard-tailwind.js?v=1.0.5" async></script>
    <script>
        function togglePasswordVisibility() {
            var passwordInput = document.getElementById("password");
            var showPasswordCheckbox = document.getElementById("showPassword");
            if (showPasswordCheckbox.checked) {
                passwordInput.type = "text";
            } else {
                passwordInput.type = "password";
            }
        }
    </script>
</body>
</html>
