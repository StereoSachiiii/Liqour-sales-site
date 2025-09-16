<?php
session_start();
include('../sql-config.php');

// Check admin login
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$user_id = $_GET['id'] ?? null; // matches table link

if (!$user_id) {
    echo "<script>
        alert('No user ID provided.');
        window.location.href='../manage-dashboard.php#users';
    </script>";
    exit();
}

// Check if user exists and is inactive
$stmt = $conn->prepare("SELECT id, is_active FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>
        alert('User not found.');
        window.location.href='../manage-dashboard.php#users';
    </script>";
    exit();
}

$user = $result->fetch_assoc();
if ($user['is_active'] == 1) {
    echo "<script>
        alert('User is already active.');
        window.location.href='../manage-dashboard.php#users';
    </script>";
    exit();
}

// Restore the user
$stmt_restore = $conn->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?");
$stmt_restore->bind_param("i", $user_id);
if ($stmt_restore->execute()) {
    echo "<script>
        alert('User restored successfully.');
        window.location.href='../manage-dashboard.php#users';
    </script>";
} else {
    echo "<script>
        alert('Failed to restore user.');
        window.location.href='../manage-dashboard.php#users';
    </script>";
}

$stmt_restore->close();
$conn->close();
?>
