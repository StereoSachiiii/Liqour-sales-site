<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['login'], $_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) ||
    $_SESSION['login'] !== 'success' || $_SESSION['is_admin'] != 1) {
    header('Location: /adminlogin.php');
    exit();
}

$liqourId = $_GET['liqour_id'] ?? null;
$warehouseId = $_GET['warehouse_id'] ?? null;
$type = $_GET['type'] ?? 'soft'; // default soft delete
$restore = isset($_GET['restore']) && $_GET['restore'] == 1;

if (!$liqourId || !$warehouseId) {
    die("Missing liquor or warehouse ID.");
}

// Fetch stock record
$stmt = $conn->prepare("
    SELECT s.quantity, s.is_active, l.name AS liqour_name, w.name AS warehouse_name
    FROM stock s
    JOIN liqours l ON s.liqour_id = l.liqour_id
    JOIN warehouse w ON s.warehouse_id = w.warehouse_id
    WHERE s.liqour_id = ? AND s.warehouse_id = ?
");
$stmt->bind_param("ii", $liqourId, $warehouseId);
$stmt->execute();
$stock = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$stock) {
    die("Stock record not found.");
}

// Helper: render consistent message box
function renderBox($title, $msg, $backLink = "../manage-dashboard.php#stock") {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?></title>
        <style>
            body { font-family: Arial, sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:1rem; }
            .box { background:white; padding:2rem; border-radius:12px; border:1px solid #ccc; max-width:500px; width:100%; text-align:center; }
            h2 { margin-bottom:1rem; }
            p { margin-bottom:1.5rem; }
            table { width:100%; border-collapse: collapse; margin-bottom:15px; }
            th, td { border:1px solid #ccc; padding:8px; text-align:left; }
            th { background:#eee; }
            a.btn { display:inline-block; padding:0.75rem 1.25rem; background:#212529; color:white; text-decoration:none; border-radius:6px; margin:0.25rem; }
            a.btn:hover { background:#343a40; }
            a.btn-danger { background:#dc3545; }
            a.btn-danger:hover { background:#c82333; }
            a.btn-success { background:#28a745; }
            a.btn-success:hover { background:#218838; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2><?= htmlspecialchars($title) ?></h2>
            <p><?= htmlspecialchars($msg) ?></p>
            <a href="<?= $backLink ?>" class="btn">Back to Stock</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// RESTORE functionality
if ($restore) {
    if ($stock['is_active']) {
        renderBox("⚠️ Already Active", "This stock record is already active and cannot be restored.");
    }

    $stmtRestore = $conn->prepare("UPDATE stock SET is_active=1, updated_at=NOW() WHERE liqour_id=? AND warehouse_id=?");
    $stmtRestore->bind_param("ii", $liqourId, $warehouseId);
    $stmtRestore->execute();
    $stmtRestore->close();

    renderBox("✅ Restored", "Stock for '{$stock['liqour_name']}' in '{$stock['warehouse_name']}' has been restored.");
}

// Already soft-deleted confirmation
if (!$stock['is_active'] && !$restore) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Already Soft Deleted</title>
        <style>
            body { font-family: Arial, sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:1rem; }
            .box { background:white; padding:2rem; border-radius:12px; border:1px solid #ccc; max-width:600px; width:100%; text-align:center; }
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
            <p>Stock for '<strong><?= htmlspecialchars($stock['liqour_name']) ?></strong>' in '<strong><?= htmlspecialchars($stock['warehouse_name']) ?></strong>' is already inactive.</p>
            <table>
                <tr><th>Liquor</th><td><?= htmlspecialchars($stock['liqour_name']) ?></td></tr>
                <tr><th>Warehouse</th><td><?= htmlspecialchars($stock['warehouse_name']) ?></td></tr>
                <tr><th>Quantity</th><td><?= $stock['quantity'] ?></td></tr>
                <tr><th>Status</th><td><?= $stock['is_active'] ? 'Active' : 'Inactive' ?></td></tr>
            </table>
            <p>Actions you can take:</p>
            <a class="btn btn-success" href="?liqour_id=<?= $liqourId ?>&warehouse_id=<?= $warehouseId ?>&restore=1">🔄 Restore Stock</a>
            <a class="btn btn-danger" href="?liqour_id=<?= $liqourId ?>&warehouse_id=<?= $warehouseId ?>&type=hard" 
               onclick="return confirm('⚠️ PERMANENT DELETE\\nThis will permanently delete the stock and cannot be undone!\\nAre you sure?')">
               🗑️ Delete Permanently
            </a>
            <a class="btn" href="../manage-dashboard.php#stock">← Back to Stock</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Confirmation form for soft/hard delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['confirm'] === 'yes') {
        if ($type === 'hard') {
            $stmtDel = $conn->prepare("DELETE FROM stock WHERE liqour_id=? AND warehouse_id=?");
        } else {
            $stmtDel = $conn->prepare("UPDATE stock SET is_active=0, updated_at=NOW() WHERE liqour_id=? AND warehouse_id=?");
        }
        $stmtDel->bind_param("ii", $liqourId, $warehouseId);
        $stmtDel->execute();
        $stmtDel->close();

        renderBox($type === 'hard' ? "✅ Permanently Deleted" : "✅ Soft Deleted",
                  $type === 'hard' ? "Stock has been permanently deleted." : "Stock has been soft-deleted. It can be restored later.");
    } else {
        renderBox("❌ Cancelled", "Stock deletion cancelled.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm <?= ucfirst($type) ?> Deletion</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:1rem; }
.container { background:#fff; padding:2rem; border-radius:12px; max-width:500px; width:100%; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
h2 { margin-bottom:1rem; }
table { width:100%; border-collapse:collapse; margin-bottom:15px; }
th, td { border:1px solid #ccc; padding:8px; text-align:left; }
th { background:#eee; }
.actions {margin-top:20px;}
button { padding:10px 20px; margin:5px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; transition:all 0.2s;}
.confirm {background:#c0392b; color:#fff;}
.confirm:hover {background:#a93226;}
.cancel {background:#666; color:#fff;}
.cancel:hover {background:#444;}
@media(max-width:500px){ button { width:45%; margin:5px 0; } }
</style>
</head>
<body>
<div class="container">
    <h2>Confirm <?= ucfirst($type) ?> Deletion</h2>
    <table>
        <tr><th>Liquor</th><td><?= htmlspecialchars($stock['liqour_name']) ?></td></tr>
        <tr><th>Warehouse</th><td><?= htmlspecialchars($stock['warehouse_name']) ?></td></tr>
        <tr><th>Quantity</th><td><?= $stock['quantity'] ?></td></tr>
        <tr><th>Status</th><td><?= $stock['is_active'] ? 'Active' : 'Inactive' ?></td></tr>
    </table>
    <p>Are you sure you want to <strong><?= $type === 'hard' ? 'permanently delete' : 'soft delete' ?></strong> this stock record?</p>
    <form method="post">
        <div class="actions">
            <button type="submit" name="confirm" value="yes" class="confirm">Yes</button>
            <button type="submit" name="confirm" value="no" class="cancel">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>
