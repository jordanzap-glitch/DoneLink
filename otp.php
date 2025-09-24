<?php
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

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve the user ID from the session
    $userId = $_SESSION['userId']; // Assuming user_id is stored in session

    // Verify OTP
    if (isset($_POST['otp'])) {
        $otp = $_POST['otp'];

        // Fetch the OTP and expiration time from the database
        $query = "SELECT otp, otp_expiration FROM tbl_customer WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($dbOtp, $otpExpiration);
        $stmt->fetch();
        $stmt->close();

        // Check if OTP is valid and not expired
        if ($otp == $dbOtp && strtotime($otpExpiration) > time()) {
            // OTP is correct, proceed with the next steps (e.g., login success)
            header("Location: Customer/index.php");
            exit();
        } else {
            // OTP is incorrect or expired
            $message = "Invalid or expired OTP. Please try again.";
            $showResendButton = true; // Show resend button if OTP is incorrect
        }
    }

    // Handle resend OTP request
    if (isset($_POST['resend_otp'])) {
        // Generate new OTP
        $otp = generateOTP();
        $expirationTime = date('Y-m-d H:i:s', strtotime('+60 seconds')); // Current time + 60 seconds

        // Update customer record with new OTP and expiration time
        $update_query = "UPDATE tbl_customer SET otp = ?, otp_expiration = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $otp, $expirationTime, $userId);
        $stmt->execute();

        // Send the new OTP to the customer's email
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
            $mail->addAddress($_SESSION['email']);                    // Add a recipient

            // Content
            $mail->isHTML(true);                                      // Set email format to HTML
            $mail->Subject = 'Your OTP Code';
            $mail->Body    = "Your new OTP code is: <strong>$otp</strong>";
            $mail->AltBody = "Your new OTP code is: $otp";

            $mail->send();
            $message = "A new OTP has been sent to your email.";
        } catch (Exception $e) {
            $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="assets/img/favicon.png" />
    <title>OTP Verification</title>
    <!-- Fonts and icons -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <!-- Main Styling -->
    <link href="assets/css/soft-ui-dashboard-tailwind.css?v=1.0.5" rel="stylesheet" />
</head>

<body class="m-0 font-sans antialiased font-normal bg-white text-start text-base leading-default text-slate-500">
    <!-- Navbar -->
    <nav class="absolute top-0 z-30 flex flex-wrap items-center justify-between w-full px-4 py-2 mt-6 mb-4 shadow-none lg:flex-nowrap lg:justify-start">
        <div class="container flex items-center justify-between py-0 flex-wrap-inherit">
            <a class="py-2.375 text-sm mr-4 ml-4 whitespace-nowrap font-bold text-white lg:ml-0" href="pages/dashboard.html">
               Done Link 
            </a>
        </div>
    </nav>

    <main class="mt-0 transition-all duration-200 ease-soft-in-out">
        <section class="min-h-screen mb-32">
            <div class="relative flex items-start pt-12 pb-56 m-4 overflow-hidden bg-center bg-cover min-h-50-screen rounded-xl" style="background-image: url('assets/img/curved-images/curved14.jpg');">
                <span class="absolute top-0 left-0 w-full h-full bg-center bg-cover bg-gradient-to-tl from-gray-900 to-slate-800 opacity-60"></span>
                <div class="container z-10">
                    <div class="flex flex-wrap justify-center -mx-3">
                        <div class="w-full max-w-full px-3 mx-auto mt-0 text-center lg:flex-0 shrink-0 lg:w-5/12">
                            <h1 class="mt-12 mb-2 text-white">OTP Verification</h1>
                            <?php if (isset($message)) { echo "<div class='alert alert-info text-white'>$message</div>"; } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="flex flex-wrap -mx-3 -mt-48 md:-mt-56 lg:-mt-48">
                    <div class="w-full max-w-full px-3 mx-auto mt-0 md:flex-0 shrink-0 md:w-7/12 lg:w-5/12 xl:w-4/12">
                        <div class="relative z-0 flex flex-col min-w-0 break-words bg-white border-0 shadow-soft-xl rounded-2xl bg-clip-border">
                            <div class="p-6 mb-0 flex items-center justify-start bg-white border-b-0 rounded-t-2xl">
                                <a href="index.php" class="inline-flex items-center text-gray-700 hover:text-gray-900 font-semibold">
                                    <i class="fas fa-arrow-left mr-2"></i> Back
                                </a>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<h5 class="ml-4">Enter your OTP</h5>
                            </div>
                            <div class="flex-auto p-6">
                                <form method="POST" action="">
                                    <div class="mb-4">
                                        <input type="text" class="text-sm focus:shadow-soft-primary-outline leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding py-2 px-3 font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:bg-white focus:text-gray-700 focus:outline-none focus:transition-shadow" name="otp" placeholder="Enter OTP" value="<?php echo isset($_POST['otp']) ? htmlspecialchars($_POST['otp']) : ''; ?>" required>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" class="inline-block w-full px-6 py-3 mt-6 mb-2 font-bold text-center text-white uppercase align-middle transition-all bg-transparent border-0 rounded-lg cursor-pointer active:opacity-85 hover:scale-102 hover:shadow-soft-xs leading-pro text-xs ease-soft-in tracking-tight-soft shadow-soft-md bg-150 bg-x-25 bg-gradient-to-tl from-gray-900 to-slate-800 hover:border-slate-700 hover:bg-slate-700 hover:text-white">
                                            Verify OTP
                                        </button>
                                    </div>
                                </form>
                                <div class="text-center mt-4">
                                    <p>Need help? <a href="#">Contact support</a></p>
                                    <p id="countdown" style="display:none;">You can resend OTP in <span id="timer">60</span> seconds.</p>
                                    <form id="resendForm" method="POST" action="" style="display:none;">
                                        <input type="hidden" name="resend_otp" value="1">
                                    </form>
                                    <div id="resendButton" style="display:<?php echo isset($showResendButton) ? 'block' : 'none'; ?>; text-align:center;">
                                        <button type="button" class="btn btn-secondary" name="resend_otp" onclick="document.getElementById('resendForm').submit();">
                                            <i class="fas fa-redo"></i> Resend OTP
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <<footer class="py-12">
        <div class="container">
            <div class="flex flex-wrap -mx-3">
               
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

    <script src="assets/js/plugins/perfect-scrollbar.min.js" async></script>
    <script src="assets/js/soft-ui-dashboard-tailwind.js?v=1.0.5" async></script>
    <script>
        // Countdown timer for resend OTP
        let countdown = 60;
        const timerElement = document.getElementById('timer');
        const countdownElement = document.getElementById('countdown');
        const resendButton = document.getElementById('resendButton');

        function startCountdown() {
            countdownElement.style.display = 'block';
            const interval = setInterval(() => {
                countdown--;
                timerElement.textContent = countdown;
                if (countdown <= 0) {
                    clearInterval(interval);
                    resendButton.style.display = 'block'; // Show the resend button
                    countdownElement.style.display = 'none';
                }
            }, 1000);
        }

        // Start the countdown when the page loads
        window.onload = startCountdown;
    </script>
</body>
</html>
