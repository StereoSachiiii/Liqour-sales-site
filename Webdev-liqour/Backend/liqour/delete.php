<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../adminlogin.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../manage-dashboard.php");
    exit();
}

$lid = intval($_GET['id']);
$hardDelete = isset($_GET['hard_delete']) && $_GET['hard_delete'] == 1;
$restore = isset($_GET['restore']) && $_GET['restore'] == 1;

// Helper function to render message box
function renderBox($title, $msg, $backLink = "../manage-dashboard.php") {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:1rem; }
        .box { background:white; padding:2rem; border-radius:12px; border:1px solid #ccc; max-width:600px; width:100%; text-align:center; }
        h2 { margin-bottom:1rem; }
        p { margin-bottom:1.5rem; }
        a.btn { display:inline-block; padding:0.75rem 1.25rem; background:#212529; color:white; text-decoration:none; border-radius:6px; margin:0.25rem; }
        a.btn:hover { background:#343a40; }
        a.btn-danger { background:#dc3545; }
        a.btn-danger:hover { background:#c82333; }
        a.btn-success { background:#28a745; }
        a.btn-success:hover { background:#218838; }
        table { width:100%; border-collapse: collapse; margin-bottom:15px; }
        th, td { border:1px solid #ccc; padding:8px; text-align:left; }
        th { background:#eee; }
    </style>
    </head>
    <body>
        <div class="box">
            <h2><?= htmlspecialchars($title) ?></h2>
            <p><?= htmlspecialchars($msg) ?></p>
            <a href="<?= $backLink ?>" class="btn">Back to Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Fetch liquor info
$stmtLiquor = $conn->prepare("SELECT name, is_active FROM liqours WHERE liqour_id = ?");
$stmtLiquor->bind_param("i", $lid);
$stmtLiquor->execute();
$liquor = $stmtLiquor->get_result()->fetch_assoc();
$stmtLiquor->close();

if (!$liquor) {
    renderBox("❌ Not Found", "Liquor not found.");
}

$liquorName = $liquor['name'];
$isActive = $liquor['is_active'];

// Restore logic
if ($restore) {
    if ($isActive) {
        renderBox("⚠️ Already Active", "This liquor is already active.");
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE liqours SET is_active=1, updated_at=NOW() WHERE liqour_id=?");
        $stmt->bind_param("i", $lid);
        $stmt->execute();

        $stmtStock = $conn->prepare("UPDATE stock SET is_active=1, updated_at=NOW() WHERE liqour_id=?");
        $stmtStock->bind_param("i", $lid);
        $stmtStock->execute();

        $conn->commit();
        renderBox("✅ Restored", "Liquor '$liquorName' and its stock have been restored.");
    } catch (Exception $e) {
        $conn->rollback();
        renderBox("❌ Restore Failed", $e->getMessage());
    }
}

// Fetch active stock for display
$stmtStock = $conn->prepare("SELECT warehouse_id, quantity FROM stock WHERE liqour_id=?");
$stmtStock->bind_param("i", $lid);
$stmtStock->execute();
$stocks = $stmtStock->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtStock->close();

// Check if liquor is linked to orders
$stmtOrders = $conn->prepare("SELECT SUM(quantity) AS total_ordered FROM order_items WHERE liqour_id=?");
$stmtOrders->bind_param("i", $lid);
$stmtOrders->execute();
$orderData = $stmtOrders->get_result()->fetch_assoc();
$totalOrdered = $orderData['total_ordered'] ?? 0;
$stmtOrders->close();

// Soft delete blocked if stock exists
if (!$hardDelete && $totalOrdered > 0) {
    renderBox("❌ Soft Delete Blocked", "This liquor has been ordered {$totalOrdered} times. Consider handling orders before deactivating.");
}

// If already inactive
if (!$isActive && !$restore) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Already Soft Deleted</title>
        <style>
            body { font-family: Arial, sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:1rem; }
            .box { background:white; padding:2rem; border-radius:12px; border:1px solid #ccc; max-width:700px; width:100%; text-align:center; }
            a.btn { display:inline-block; padding:0.75rem 1.25rem; background:#212529; color:white; text-decoration:none; border-radius:6px; margin:0.25rem; }
            a.btn:hover { background:#343a40; }
            a.btn-success { background:#28a745; }
            a.btn-success:hover { background:#218838; }
            a.btn-danger { background:#dc3545; }
            a.btn-danger:hover { background:#c82333; }
            table { width:100%; border-collapse: collapse; margin-bottom:15px; }
            th, td { border:1px solid #ccc; padding:8px; text-align:left; }
            th { background:#eee; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>⚠️ Already Soft Deleted</h2>
            <p>Liquor '<strong><?= htmlspecialchars($liquorName) ?></strong>' is already inactive.</p>

            <?php if(count($stocks) > 0): ?>
            <p>Associated Stock:</p>
            <table>
                <tr><th>Warehouse ID</th><th>Quantity</th></tr>
                <?php foreach($stocks as $s): ?>
                    <tr>
                        <td><?= $s['warehouse_id'] ?></td>
                        <td><?= $s['quantity'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <p>What would you like to do?</p>
            <a class="btn btn-success" href="?id=<?= $lid ?>&restore=1">🔄 Restore Liquor</a>
            <a class="btn btn-danger" href="?id=<?= $lid ?>&hard_delete=1" onclick="return confirm('⚠️ PERMANENT DELETE\\nThis will delete the liquor and all its stock permanently!')">🗑️ Delete Permanently</a>
            <a class="btn" href="../manage-dashboard.php">← Back to Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Perform deletion
$conn->begin_transaction();
try {
    if ($hardDelete) {
        if ($totalOrdered > 0) {
            renderBox("❌ Hard Delete Blocked", "This liquor has been ordered {$totalOrdered} times. Cannot hard delete.");
        }

        // Delete stock
        $stmtDelStock = $conn->prepare("DELETE FROM stock WHERE liqour_id=?");
        $stmtDelStock->bind_param("i", $lid);
        $stmtDelStock->execute();

        // Delete reviews
        $stmtDelReviews = $conn->prepare("DELETE FROM reviews WHERE liqour_id=?");
        $stmtDelReviews->bind_param("i", $lid);
        $stmtDelReviews->execute();

        // Delete liquor
        $stmtDel = $conn->prepare("DELETE FROM liqours WHERE liqour_id=?");
        $stmtDel->bind_param("i", $lid);
        $stmtDel->execute();
        $stmtDel->close();

        $conn->commit();
        renderBox("✅ Permanently Deleted", "Liquor '$liquorName' has been permanently deleted along with its stock and reviews.");
    } else {
        // Soft delete
        $stmtDel = $conn->prepare("UPDATE liqours SET is_active=0, updated_at=NOW() WHERE liqour_id=?");
        $stmtDel->bind_param("i", $lid);
        $stmtDel->execute();

        $stmtDelStock = $conn->prepare("UPDATE stock SET is_active=0, updated_at=NOW() WHERE liqour_id=?");
        $stmtDelStock->bind_param("i", $lid);
        $stmtDelStock->execute();

        $stmtDelReviews = $conn->prepare("UPDATE reviews SET is_active=0, updated_at=NOW() WHERE liqour_id=?");
        $stmtDelReviews->bind_param("i", $lid);
        $stmtDelReviews->execute();

        $conn->commit();
        renderBox("✅ Soft Deleted", "Liquor '$liquorName' and its stock/reviews have been deactivated. It can be restored later.");
    }
} catch (Exception $e) {
    $conn->rollback();
    renderBox("❌ Delete Failed", $e->getMessage());
}
?>
