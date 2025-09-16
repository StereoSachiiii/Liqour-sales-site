<?php
session_start();
include("../sql-config.php");

// Check admin login
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../adminlogin.php');
    exit();
}

// Validate liquor ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../manage-dashboard.php");
    exit();
}

$lid = intval($_GET['id']);
$hardDelete = isset($_GET['hard_delete']) && $_GET['hard_delete'] == 1;

// Check if liquor is linked to orders
$sqlCheck = "SELECT 1 FROM order_items WHERE liqour_id = ? LIMIT 1";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $lid);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

// Function to render message box
function renderBox($title, $msg, $backLink = "../manage-dashboard.php") {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?></title>
        <style>
            * { box-sizing: border-box; }
            body {
                font-family: Arial, sans-serif;
                background: #f8f9fa;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                padding: 1rem;
            }
            .box {
                background: white;
                padding: 2rem;
                border-radius: 12px;
                border: 1px solid #dee2e6;
                text-align: center;
                max-width: 400px;
                width: 100%;
                box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            }
            h2 { margin-bottom: 1rem; color: #212529; }
            p { margin-bottom: 1.5rem; color: #495057; }
            .btn {
                display: inline-block;
                padding: 0.75rem 1.25rem;
                background: #212529;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 500;
                transition: all 0.2s;
            }
            .btn:hover { background: #343a40; transform: translateY(-1px); }
        </style>
    </head>
    <body>
        <div class="box">
            <h2><?= htmlspecialchars($title) ?></h2>
            <p><?= htmlspecialchars($msg) ?></p>
            <a class="btn" href="<?= $backLink ?>">Back to Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If linked to orders and not hard delete -> block deletion
if ($resultCheck->num_rows > 0 && !$hardDelete) {
    renderBox("❌ Delete Blocked", "This liquor is linked to existing orders and cannot be deleted.");
}

// For soft delete, check total stock across all warehouses
if (!$hardDelete) {
    $stmtStock = $conn->prepare("SELECT SUM(quantity) AS total_stock FROM stock WHERE liqour_id = ?");
    $stmtStock->bind_param("i", $lid);
    $stmtStock->execute();
    $resStock = $stmtStock->get_result()->fetch_assoc();
    $totalStock = $resStock['total_stock'] ?? 0;

    if ($totalStock > 0) {
        renderBox("❌ Delete Blocked", "Cannot delete this liquor because stock is remaining.");
    }
}

// Proceed with deletion
if ($hardDelete) {
    $stmtDel = $conn->prepare("DELETE FROM liqours WHERE liqour_id = ?");
} else {
    $stmtDel = $conn->prepare("UPDATE liqours SET is_active=0 WHERE liqour_id = ?");
}

$stmtDel->bind_param("i", $lid);
$stmtDel->execute();

if ($stmtDel->affected_rows > 0) {
    $msg = $hardDelete ? "Liquor permanently deleted." : "Liquor successfully deactivated (soft delete).";
    renderBox("✅ Success", $msg);
} else {
    renderBox("❌ Delete Failed", "The liquor may not exist or could not be deleted.");
}
?>
