<?php
session_start();
if (!isset($_SESSION['userId'])) {
    header("Location: public/login-signup.php");
    exit();
}

include("../Backend/sql-config.php");

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die("Invalid order ID.");
}

$orderId = (int)$_GET['order_id'];
$userId  = $_SESSION['userId'];

// Soft delete only active orders
$stmt = $conn->prepare("UPDATE orders SET is_active = 0 WHERE order_id = ? AND user_id = ? AND is_active = 1");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    $conn->close();
    // Redirect back to My Orders page
    header("Location: my-orders.php?msg=Order removed successfully");
    exit();
} else {
    $stmt->close();
    $conn->close();
    die("Failed to remove order or order not found.");
}
?>
