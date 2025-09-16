<?php
session_start();
include('../sql-config.php');

// Check admin login
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$review_id = $_GET['review_id'] ?? null;
$type = $_GET['type'] ?? 'soft'; // default to soft delete

if (!$review_id) {
    echo "<script>
        alert('No review ID provided.');
        window.location.href='../manage-dashboard.php#reviews';
    </script>";
    exit();
}

// Check if review exists
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

// Perform delete
if ($type === 'soft') {
    $stmt_update = $conn->prepare("UPDATE reviews SET is_active = 0 WHERE review_id = ?");
    $stmt_update->bind_param("i", $review_id);
    $stmt_update->execute();
    $stmt_update->close();
    $msg = "Review soft-deleted successfully.";
} elseif ($type === 'hard') {
    $stmt_delete = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
    $stmt_delete->bind_param("i", $review_id);
    $stmt_delete->execute();
    $stmt_delete->close();
    $msg = "Review permanently deleted.";
} else {
    $msg = "Invalid delete type.";
}

echo "<script>
    alert('$msg');
    window.location.href='../manage-dashboard.php#reviews';
</script>";

$conn->close();
?>
