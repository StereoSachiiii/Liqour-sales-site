<?php
session_start();
include("../sql-config.php");

// Ensure admin is logged in
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$category_id = $_GET['category_id'] ?? null;

if (!$category_id) {
    echo "<script>
        alert('No category ID provided.');
        window.location.href='../manage-dashboard.php#categories';
    </script>";
    exit();
}

// Check if category exists and is inactive
$stmt = $conn->prepare("SELECT liqour_category_id, is_active FROM liqour_categories WHERE liqour_category_id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>
        alert('Category not found.');
        window.location.href='../manage-dashboard.php#categories';
    </script>";
    exit();
}

$category = $result->fetch_assoc();
if ($category['is_active'] == 1) {
    echo "<script>
        alert('Category is already active.');
        window.location.href='../manage-dashboard.php#categories';
    </script>";
    exit();
}

// Restore the category
$stmt_restore = $conn->prepare("UPDATE liqour_categories SET is_active = 1, updated_at = NOW() WHERE liqour_category_id = ?");
$stmt_restore->bind_param("i", $category_id);
if ($stmt_restore->execute()) {
    echo "<script>
        alert('Category restored successfully.');
        window.location.href='../manage-dashboard.php#categories';
    </script>";
} else {
    echo "<script>
        alert('Failed to restore category.');
        window.location.href='../manage-dashboard.php#categories';
    </script>";
}

$stmt_restore->close();
$conn->close();
?>
