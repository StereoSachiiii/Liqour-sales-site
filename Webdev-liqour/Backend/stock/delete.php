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

if (!$liqourId || !$warehouseId) die("Missing liquor or warehouse ID.");

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
if (!$stock) die("Stock record not found.");

$error = "";

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['confirm'] === 'yes') {
        if ($type === 'hard') {
            $deleteStmt = $conn->prepare("DELETE FROM stock WHERE liqour_id=? AND warehouse_id=?");
            $deleteStmt->bind_param("ii", $liqourId, $warehouseId);
            $success = $deleteStmt->execute() ? "Stock permanently deleted." : "Error deleting stock.";
        } else {
            $softStmt = $conn->prepare("UPDATE stock SET is_active=0, updated_at=NOW() WHERE liqour_id=? AND warehouse_id=?");
            $softStmt->bind_param("ii", $liqourId, $warehouseId);
            $success = $softStmt->execute() ? "Stock soft-deleted successfully." : "Error deleting stock.";
        }
        header("Location: ../manage-dashboard.php#stock&msg=" . urlencode($success));
        exit();
    } else {
        header("Location: ../manage-dashboard.php#stock&msg=" . urlencode("Stock deletion cancelled"));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Stock</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    margin: 0; padding: 20px;
    display: flex; justify-content: center;
}
.container {
    background: #fff; padding: 25px; border-radius: 8px;
    max-width: 450px; width: 100%; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    text-align: center;
}
h2 {margin-bottom: 20px;}
.info {
    margin-bottom: 15px;
    padding: 10px;
    border: 1px solid #ccc;
    background: #f9f9f9;
    border-radius: 4px;
}
.actions {margin-top: 20px;}
button {
    padding: 10px 20px; margin: 0 5px; border: none; border-radius: 4px;
    cursor: pointer; font-weight: bold; transition: background 0.3s;
}
.confirm {background: #c0392b; color: #fff;}
.confirm:hover {background: #a93226;}
.cancel {background: #666; color: #fff;}
.cancel:hover {background: #444;}
.error {color: #dc3545; margin-top: 15px;}
@media(max-width:500px){
    .container {padding: 15px;}
    button {padding: 8px 12px; margin: 5px 0; width: 45%;}
}
</style>
</head>
<body>
<div class="container">
    <h2>Confirm <?= ucfirst($type) ?> Deletion</h2>
    <div class="info">
        <strong>Liquor:</strong> <?= htmlspecialchars($stock['liqour_name']) ?><br>
        <strong>Warehouse:</strong> <?= htmlspecialchars($stock['warehouse_name']) ?><br>
        <strong>Quantity:</strong> <?= htmlspecialchars($stock['quantity']) ?><br>
        <strong>Status:</strong> <?= $stock['is_active'] ? 'Active' : 'Inactive' ?>
    </div>
    <p>Are you sure you want to <strong><?= $type === 'hard' ? 'permanently delete' : 'soft delete' ?></strong> this stock record?</p>
    <form method="post">
        <div class="actions">
            <button type="submit" name="confirm" value="yes" class="confirm">Yes</button>
            <button type="submit" name="confirm" value="no" class="cancel">Cancel</button>
        </div>
    </form>
    <?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
</div>
</body>
</html>
