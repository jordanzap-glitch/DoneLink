<?php
include '../Includes/dbcon.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subscription_id'])) {
    $subscriptionId = $_POST['subscription_id'];

    // Fetch subscription type from tbl_subscription
    $query = "SELECT subs_type FROM tbl_subscription WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subscriptionId);
    $stmt->execute();
    $stmt->bind_result($subsType);
    $stmt->fetch();
    $stmt->close();

    echo htmlspecialchars($subsType); // Return the subscription type
}
?>
