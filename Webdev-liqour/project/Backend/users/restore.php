<?php
session_start();
include('../sql-config.php');

// Check admin login
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    $message_js = json_encode('No user ID provided.');
    echo "<script>
        alert({$message_js});
        window.location.href='../manage-dashboard.php#users';
    </script>";
    exit();
}

// Check if user exists and get details
$stmt = $conn->prepare("SELECT id, name, email, is_active FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $message_js = json_encode('User not found.');
    echo "<script>
        alert({$message_js});
        window.location.href='../manage-dashboard.php#users';
    </script>";
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if ($user['is_active'] == 1) {
    $message_js = json_encode("User \"{$user['name']}\" is already active.");
    echo "<script>
        alert({$message_js});
        window.location.href='../manage-dashboard.php#users';
    </script>";
    exit();
}

// Get the deleted user ID to check for transferred data
$stmt = $conn->prepare("SELECT id FROM users WHERE email = 'deleted@system.local' AND name = 'Deleted User'");
$stmt->execute();
$deleted_user_result = $stmt->get_result();
$deleted_user_id = null;
if ($deleted_user_result->num_rows > 0) {
    $deleted_user_id = $deleted_user_result->fetch_assoc()['id'];
}
$stmt->close();

// Count data that was transferred to deleted user (for information only)
$transferred_orders = 0;
$transferred_reviews = 0;

if ($deleted_user_id) {
    // Count orders that might have been from this user (we can't definitively know which ones)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $stmt->bind_param("i", $deleted_user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $transferred_orders = $result['count'];
    $stmt->close();
    
    // Count reviews that might have been from this user
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
    $stmt->bind_param("i", $deleted_user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $transferred_reviews = $result['count'];
    $stmt->close();
}

// Start transaction for data integrity
$conn->autocommit(FALSE);

try {
    // Simply restore the user account - no data recovery possible
    $stmt_restore = $conn->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?");
    $stmt_restore->bind_param("i", $user_id);
    
    if (!$stmt_restore->execute()) {
        throw new Exception("Failed to restore user account");
    }
    $stmt_restore->close();
    
    // Commit the change
    $conn->commit();
    
    // Success message explaining data loss
    $message = "User '{$user['name']}' has been restored successfully.\n\n";
    $message .= "IMPORTANT: This user's previous orders and reviews were transferred to the system during deletion and cannot be recovered. ";
    $message .= "The user account is now active but starts with a clean history.";

    if ($deleted_user_id && ($transferred_orders > 0 || $transferred_reviews > 0)) {
        $message .= "\n\nNote: There are currently {$transferred_orders} orders and {$transferred_reviews} reviews in the system from various deleted users.";
    }

    $message_js = json_encode($message);
    echo "<script>
        alert({$message_js});
        window.location.href='../manage-dashboard.php#users';
    </script>";
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    $error_message_js = json_encode("Failed to restore user: {$e->getMessage()}");
    echo "<script>
        alert({$error_message_js});
        window.location.href='../manage-dashboard.php#users';
    </script>";
}

// Restore autocommit and close connection
$conn->autocommit(TRUE);
$conn->close();
?>
