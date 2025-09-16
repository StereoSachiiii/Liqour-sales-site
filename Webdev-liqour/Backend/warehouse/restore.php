<?php
session_start();
include('../sql-config.php');

// Check admin login
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../manage-dashboard.php");
    exit();
}

$warehouse_id = $_GET['id'] ?? null;

if (!$warehouse_id) {
    echo "<script>
        alert('No warehouse ID provided.');
        window.location.href='../manage-dashboard.php#warehouse';
    </script>";
    exit();
}

// Check if warehouse exists and is inactive
$stmt = $conn->prepare("SELECT warehouse_id, is_active FROM warehouse WHERE warehouse_id = ?");
$stmt->bind_param("i", $warehouse_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>
        alert('Warehouse not found.');
        window.location.href='../manage-dashboard.php#warehouse';
    </script>";
    exit();
}

$warehouse = $result->fetch_assoc();
if ($warehouse['is_active'] == 1) {
    echo "<script>
        alert('Warehouse is already active.');
        window.location.href='../manage-dashboard.php#warehouse';
    </script>";
    exit();
}

// Restore the warehouse
$stmt_restore = $conn->prepare("UPDATE warehouse SET is_active = 1 WHERE warehouse_id = ?");
$stmt_restore->bind_param("i", $warehouse_id);
if ($stmt_restore->execute()) {
    echo "<script>
        alert('Warehouse restored successfully.');
        window.location.href='../manage-dashboard.php#warehouse';
    </script>";
} else {
    echo "<script>
        alert('Failed to restore warehouse.');
        window.location.href='../manage-dashboard.php#warehouse';
    </script>";
}

$stmt_restore->close();
$conn->close();
?>
