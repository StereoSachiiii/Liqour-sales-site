<?php
session_start();
include('../sql-config.php');

// Check admin login
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$supplier_id = $_GET['supplier_id'] ?? null;

if (!$supplier_id) {
    $message_js = json_encode('No supplier ID provided.');
    echo "<script>
        alert({$message_js});
        window.location.href='../manage-dashboard.php#suppliers';
    </script>";
    exit();
}

// Check if supplier exists and get details
$stmt = $conn->prepare("SELECT supplier_id, name, email, is_active FROM suppliers WHERE supplier_id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $message_js = json_encode('Supplier not found.');
    echo "<script>
        alert({$message_js});
        window.location.href='../manage-dashboard.php#suppliers';
    </script>";
    exit();
}

$supplier = $result->fetch_assoc();
$stmt->close();

if ($supplier['is_active'] == 1) {
    $message_js = json_encode("Supplier \"{$supplier['name']}\" is already active.");
    echo "<script>
        alert({$message_js});
        window.location.href='../manage-dashboard.php#suppliers';
    </script>";
    exit();
}

// Count linked liquor products for information
$linked_liquors = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM liqours WHERE supplier_id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$linked_liquors = $result['count'];
$stmt->close();

// Start transaction for data integrity
$conn->autocommit(FALSE);

try {
    // Restore the supplier account
    $stmt_restore = $conn->prepare("UPDATE suppliers SET is_active = 1, updated_at = NOW() WHERE supplier_id = ?");
    $stmt_restore->bind_param("i", $supplier_id);
    
    if (!$stmt_restore->execute()) {
        throw new Exception("Failed to restore supplier account");
    }
    $stmt_restore->close();
    
    // Commit the change
    $conn->commit();
    
    // Success message
    $message = "Supplier '{$supplier['name']}' has been restored successfully.";
    
    if ($linked_liquors > 0) {
        $message .= "\n\nThe supplier now shows as active again and all {$linked_liquors} linked liquor products will display the supplier as active.";
    }

    $message_js = json_encode($message);
    echo "<script>
        alert({$message_js});
        window.location.href='../manage-dashboard.php#suppliers';
    </script>";
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    $error_message_js = json_encode("Failed to restore supplier: {$e->getMessage()}");
    echo "<script>
        alert({$error_message_js});
        window.location.href='../manage-dashboard.php#suppliers';
    </script>";
}

// Restore autocommit and close connection
$conn->autocommit(TRUE);
$conn->close();
?>