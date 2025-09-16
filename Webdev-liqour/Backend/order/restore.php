<?php
session_start();
include('../sql-config.php');

// Check admin login
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    echo "<script>
        alert('No order ID provided.');
        window.location.href='../manage-dashboard.php#orders';
    </script>";
    exit();
}

// Check if order exists and is inactive
$stmt = $conn->prepare("SELECT order_id, is_active FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>
        alert('Order not found.');
        window.location.href='../manage-dashboard.php#orders';
    </script>";
    exit();
}

$order = $result->fetch_assoc();
if ($order['is_active'] == 1) {
    echo "<script>
        alert('Order is already active.');
        window.location.href='../manage-dashboard.php#orders';
    </script>";
    exit();
}

// Restore the order
$stmt_restore = $conn->prepare("UPDATE orders SET is_active = 1 WHERE order_id = ?");
$stmt_restore->bind_param("i", $order_id);
if ($stmt_restore->execute()) {
    echo "<script>
        alert('Order restored successfully.');
        window.location.href='../manage-dashboard.php#orders';
    </script>";
} else {
    echo "<script>
        alert('Failed to restore order.');
        window.location.href='../manage-dashboard.php#orders';
    </script>";
}

$stmt_restore->close();
$conn->close();
?>
