<?php
session_start();
include("../sql-config.php");

// --- Admin check ---
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

// --- Validate ID and type ---
$userId = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'soft'; // default to soft
if (!$userId) die("User ID not specified.");
$userId = intval($userId);

// --- Fetch user ---
$stmt = $conn->prepare("SELECT id, name, is_active FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) die("User not found.");

// --- Check foreign key constraints ---
$checkOrders = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE user_id=?");
$checkOrders->bind_param("i", $userId);
$checkOrders->execute();
$orderCount = $checkOrders->get_result()->fetch_assoc()['cnt'] ?? 0;

$checkReviews = $conn->prepare("SELECT COUNT(*) AS cnt FROM reviews WHERE user_id=?");
$checkReviews->bind_param("i", $userId);
$checkReviews->execute();
$reviewCount = $checkReviews->get_result()->fetch_assoc()['cnt'] ?? 0;

if ($orderCount > 0 || $reviewCount > 0) {
    echo "<script>
        alert('❌ Cannot delete user: associated orders or reviews exist.');
        window.location.href='../manage-dashboard.php#users';
    </script>";
    exit();
}

// --- Perform deletion ---
if ($type === 'soft') {
    $stmtDel = $conn->prepare("UPDATE users SET is_active=0, updated_at=NOW() WHERE id=?");
    $stmtDel->bind_param("i", $userId);
    $stmtDel->execute();
    $msg = "User soft deleted successfully.";
} elseif ($type === 'hard') {
    $stmtDel = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmtDel->bind_param("i", $userId);
    $stmtDel->execute();
    $msg = "User permanently deleted.";
} else {
    die("Invalid delete type.");
}

header("Location: ../manage-dashboard.php#users&msg=" . urlencode($msg));
exit();
?>
