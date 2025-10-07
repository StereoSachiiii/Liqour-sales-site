<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['login'], $_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) ||
    $_SESSION['login'] !== 'success' || $_SESSION['is_admin'] != 1) {
    header('Location: ../process-login.php');
    exit();
}

$liqourId = $_GET['liqour_id'] ?? null;
$warehouseId = $_GET['warehouse_id'] ?? null;

if (!$liqourId || !$warehouseId) die("Missing liquor or warehouse ID.");

// Fetch stock record
$stmt = $conn->prepare("
    SELECT s.quantity, s.is_active, l.name AS liqour_name, w.name AS warehouse_name
    FROM stock s
    JOIN liqours l ON s.liqour_id = l.liqour_id
    JOIN warehouse w ON s.warehouse_id = w.warehouse_id
    WHERE s.liqour_id=? AND s.warehouse_id=?
");
$stmt->bind_param("ii", $liqourId, $warehouseId);
$stmt->execute();
$stock = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$stock) die("Stock record not found.");

// If already active
if ($stock['is_active']) {
    echo "<script>alert('Stock is already active.'); window.location='../manage-dashboard.php#stock';</script>";
    exit();
}

// Restore stock
$upd = $conn->prepare("UPDATE stock SET is_active=1, updated_at=NOW() WHERE liqour_id=? AND warehouse_id=?");
$upd->bind_param("ii", $liqourId, $warehouseId);
$upd->execute();
$upd->close();

echo "<script>alert('Stock restored successfully!'); window.location='../manage-dashboard.php#stock';</script>";
exit();
