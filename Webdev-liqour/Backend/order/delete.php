<?php
session_start();
include('../sql-config.php');

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

// Validate order_id
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    echo "<script>
    alert('Invalid order ID.');
    window.location.href='../manage-dashboard.php#orders';
    </script>";
    exit();
}

$order_id = intval($_GET['order_id']);
$type = $_GET['type'] ?? 'soft'; // default to soft delete

if ($type === 'soft') {
    // Soft delete: mark as inactive
    $stmt = $conn->prepare("UPDATE orders SET is_active=0 WHERE order_id=?");
    $stmt->bind_param("i", $order_id);
    $actionText = "soft-deleted";
} elseif ($type === 'hard') {
    // Hard delete: remove from database
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id=?");
    $stmt->bind_param("i", $order_id);
    $actionText = "permanently deleted";
} else {
    echo "<script>
    alert('Invalid delete type.');
    window.location.href='../manage-dashboard.php#orders';
    </script>";
    exit();
}

// Execute deletion
if ($stmt->execute()) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Order Deleted</title>
        <style>
            body { font-family: Arial, sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
            .message-box { background:white; border:1px solid #ccc; padding:30px; border-radius:8px; text-align:center; max-width:400px; width:90%; }
            .btn { display:inline-block; margin-top:15px; padding:8px 12px; background:black; color:white; text-decoration:none; border-radius:4px; }
            .btn:hover { opacity:0.9; }
            @media (max-width: 480px) {
                .message-box { padding:20px; }
                .btn { width:100%; }
            }
        </style>
    </head>
    <body>
        <div class='message-box'>
            <h2>✅ Order {$actionText}</h2>
            <p>The order ID {$order_id} has been {$actionText} successfully.</p>
            <a href='../manage-dashboard.php#orders' class='btn'>Back to Orders</a>
        </div>
    </body>
    </html>";
} else {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Delete Failed</title>
        <style>
            body { font-family: Arial, sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
            .message-box { background:white; border:1px solid #ccc; padding:30px; border-radius:8px; text-align:center; max-width:400px; width:90%; }
            .btn { display:inline-block; margin-top:15px; padding:8px 12px; background:black; color:white; text-decoration:none; border-radius:4px; }
            .btn:hover { opacity:0.9; }
            @media (max-width: 480px) {
                .message-box { padding:20px; }
                .btn { width:100%; }
            }
        </style>
    </head>
    <body>
        <div class='message-box'>
            <h2>❌ Delete Failed</h2>
            <p>Could not delete the order. It may not exist or there was a database error.</p>
            <a href='../manage-dashboard.php#orders' class='btn'>Back to Orders</a>
        </div>
    </body>
    </html>";
}

$stmt->close();
$conn->close();
?>
