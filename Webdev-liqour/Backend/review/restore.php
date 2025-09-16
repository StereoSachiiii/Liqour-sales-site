<?php
session_start();
include('../sql-config.php');

// Check admin login
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$review_id = $_GET['review_id'] ?? null;

if (!$review_id) {
    echo "<script>
        alert('No review ID provided.');
        window.location.href='../manage-dashboard.php#reviews';
    </script>";
    exit();
}

// Check if review exists and is inactive
$stmt = $conn->prepare("SELECT review_id, is_active FROM reviews WHERE review_id = ?");
$stmt->bind_param("i", $review_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>
        alert('Review not found.');
        window.location.href='../manage-dashboard.php#reviews';
    </script>";
    exit();
}

$review = $result->fetch_assoc();
if ($review['is_active'] == 1) {
    echo "<script>
        alert('Review is already active.');
        window.location.href='../manage-dashboard.php#reviews';
    </script>";
    exit();
}

// Restore the review
$stmt_restore = $conn->prepare("UPDATE reviews SET is_active = 1 WHERE review_id = ?");
$stmt_restore->bind_param("i", $review_id);
if ($stmt_restore->execute()) {
    echo "<script>
        alert('Review restored successfully.');
        window.location.href='../manage-dashboard.php#reviews';
    </script>";
} else {
    echo "<script>
        alert('Failed to restore review.');
        window.location.href='../manage-dashboard.php#reviews';
    </script>";
}

$stmt_restore->close();
$conn->close();
?>
