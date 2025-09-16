<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['login']) || $_SESSION['login'] !== 'success') {
    header('Location: ../adminlogin.php');
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../manage-dashboard.php");
    exit();
}

$cid = intval($_GET['id']);
$hardDelete = isset($_GET['hard']) && $_GET['hard'] == 1;

// Check if category has active products
$stmtCheck = $conn->prepare("SELECT COUNT(*) FROM liqours WHERE category_id = ?");
$stmtCheck->bind_param("i", $cid);
$stmtCheck->execute();
$stmtCheck->bind_result($productCount);
$stmtCheck->fetch();
$stmtCheck->close();

// Show alert if soft delete not allowed
if ($productCount > 0 && !$hardDelete) {
    echo "<script>
        alert('Cannot delete category: it has active products. Use hard delete if intended.');
        window.location.href = '../manage-dashboard.php';
    </script>";
    exit();
}

// Attempt delete
try {
    if ($hardDelete) {
        $sql = "DELETE FROM liqour_categories WHERE liqour_category_id = ?";
    } else {
        $sql = "UPDATE liqour_categories SET is_active = 0 WHERE liqour_category_id = ?";
    }

    $stmtDel = $conn->prepare($sql);
    $stmtDel->bind_param("i", $cid);
    $stmtDel->execute();

    if ($stmtDel->affected_rows > 0) {
        // Success
        echo "<script>
            alert('Category " . ($hardDelete ? "hard-deleted" : "soft-deleted") . " successfully.');
            window.location.href = '../manage-dashboard.php';
        </script>";
    } else {
        // Nothing deleted
        echo "<script>
            alert('Delete failed: category not found or already deleted.');
            window.location.href = '../manage-dashboard.php';
        </script>";
    }

    $stmtDel->close();
} catch (mysqli_sql_exception $e) {
    // FK constraint or other SQL error
    $msg = addslashes($e->getMessage());
    echo "<script>
        alert('Cannot delete category due to dependencies or constraint violation.\\nError: $msg');
        window.location.href = '../manage-dashboard.php';
    </script>";
    exit();
}
?>
