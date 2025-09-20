<?php
session_start();
include('../sql-config.php');

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

// Validate order_id
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die("Invalid order ID.");
}

$order_id = intval($_GET['order_id']);
$type = $_GET['type'] ?? 'soft'; // default to soft delete

// Fetch order and user info
$stmt = $conn->prepare("
    SELECT o.order_id, o.status, o.total, o.is_active, u.id AS user_id, u.name AS user_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) die("Order not found.");

// Fetch order items
$itemStmt = $conn->prepare("
    SELECT oi.liqour_id, oi.quantity, oi.price, oi.is_active, l.name AS liqour_name
    FROM order_items oi
    JOIN liqours l ON oi.liqour_id = l.liqour_id
    WHERE oi.order_id = ?
");
$itemStmt->bind_param("i", $order_id);
$itemStmt->execute();
$orderItems = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['confirm'] === 'yes') {
        if ($type === 'soft') {
            // Soft delete order and items
            $conn->begin_transaction();
            try {
                $stmt1 = $conn->prepare("UPDATE orders SET is_active=0, updated_at=NOW() WHERE order_id=?");
                $stmt1->bind_param("i", $order_id);
                $stmt1->execute();

                $stmt2 = $conn->prepare("UPDATE order_items SET is_active=0 WHERE order_id=?");
                $stmt2->bind_param("i", $order_id);
                $stmt2->execute();

                $conn->commit();
                $msg = "Order and its items soft-deleted successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "Error during soft delete: " . $e->getMessage();
            }
        } else {
            // Hard delete (items cascade automatically)
            $stmt3 = $conn->prepare("DELETE FROM orders WHERE order_id=?");
            $stmt3->bind_param("i", $order_id);
            $stmt3->execute();
            $msg = "Order and its items permanently deleted.";
        }

        echo "<script>alert('" . addslashes($msg) . "'); window.location.href='../manage-dashboard.php#orders';</script>";
        exit();
    } else {
        echo "<script>window.location.href='../manage-dashboard.php#orders';</script>";
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm Order Deletion</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:20px; display:flex; justify-content:center; }
.container { background:white; padding:25px; border-radius:8px; max-width:600px; width:100%; box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h2 { margin-bottom:15px; }
table { width:100%; border-collapse: collapse; margin-bottom:15px; }
th, td { border:1px solid #ccc; padding:8px; text-align:left; }
th { background:#eee; }
.actions { text-align:center; }
button { padding:10px 20px; margin:5px; border:none; border-radius:4px; cursor:pointer; font-weight:bold; }
.confirm { background:#c0392b; color:white; }
.confirm:hover { background:#a93226; }
.cancel { background:#666; color:white; }
.cancel:hover { background:#444; }
.notice { margin-bottom:15px; padding:10px; background:#f9f9f9; border-radius:4px; border:1px solid #ccc; }
</style>
</head>
<body>
<div class="container">
    <h2>Confirm <?= ucfirst($type) ?> Deletion</h2>
    <div class="notice">
        <strong>User:</strong> <?= htmlspecialchars($order['user_name']) ?><br>
        <strong>Order ID:</strong> <?= $order['order_id'] ?><br>
        <strong>Status:</strong> <?= $order['status'] ?><br>
        <strong>Total:</strong> $<?= number_format($order['total'],2) ?><br>
        <strong>Items in this order:</strong> <?= count($orderItems) ?>
    </div>

    <table>
        <tr>
            <th>Liquor</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Status</th>
        </tr>
        <?php foreach($orderItems as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['liqour_name']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td>$<?= number_format($item['price'],2) ?></td>
            <td><?= $item['is_active'] ? 'Active' : 'Inactive' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p class="notice">
        <?php if($type === 'soft'): ?>
        Soft deleting will mark this order <strong>and all its items</strong> as inactive. They can be restored later.
        <?php else: ?>
        Hard deleting will <strong>permanently remove</strong> this order and all its items. This action cannot be undone.
        <?php endif; ?>
    </p>

    <form method="post" class="actions">
        <button type="submit" name="confirm" value="yes" class="confirm">Yes, <?= $type === 'hard' ? 'Delete Permanently' : 'Soft Delete' ?></button>
        <button type="submit" name="confirm" value="no" class="cancel">Cancel</button>
    </form>
</div>
</body>
</html>
