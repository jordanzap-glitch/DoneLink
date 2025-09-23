<?php
error_reporting(E_ALL);
include '../Includes/dbcon.php';
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send email based on deadline status
function sendDeadlineEmail($row, $deadlineStatus) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // Replace with your SMTP host
        $mail->SMTPAuth = true;
        $mail->Username = 'jujuzapanta@gmail.com'; // SMTP username
        $mail->Password = 'fypo gwrw shsv hdqs'; // SMTP password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('jujuzapanta@gmail.com', 'DoneLink');
        $mail->addAddress($row['email'], $row['firstname'] . ' ' . $row['lastname']);

        $mail->isHTML(true);
        $mail->Subject = "Payment Deadline Notification";

        if ($deadlineStatus == 'Due Soon') {
            $mail->Body = "
                <p>Good day, {$row['firstname']} {$row['lastname']},</p>
                <p>This is a reminder from DoneLink that your payment (Transaction ID: {$row['transaction_id']}) is due on <strong>".date('F j, Y', strtotime($row['due_date']))."</strong>. 
                Please settle your payment on time to avoid service interruption.</p>
                <p>Thank you.</p>
            ";
        } elseif ($deadlineStatus == 'Overdue') {
            $mail->Body = "
                <p>Good day, {$row['firstname']} {$row['lastname']},</p>
                <p>Your payment (Transaction ID: {$row['transaction_id']}) was due on <strong>".date('F j, Y', strtotime($row['due_date']))."</strong> and is now overdue. 
                Please make your payment immediately to avoid service disruption.</p>
                <p>Thank you.</p>
            ";
        } else {
            return false; // No email for "On Time"
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Check if ID is posted
if(isset($_POST['id'])){
    $id = intval($_POST['id']);

    // Fetch the payment row
    $stmt = $conn->prepare("SELECT p.*, c.firstname, c.lastname, c.email FROM tbl_payment p JOIN tbl_customer c ON p.customer_id = c.id WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        $deadlineStatus = $row['deadline_status'];

        // Send email
        $sent = sendDeadlineEmail($row, $deadlineStatus);
        if($sent){
            echo json_encode(['status'=>'success', 'message'=>'Email sent successfully.']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>'Email could not be sent.']);
        }
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Payment not found.']);
    }

    $stmt->close();
} else {
    echo json_encode(['status'=>'error', 'message'=>'No payment ID provided.']);
}

$conn->close();
?>
