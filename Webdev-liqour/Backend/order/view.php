<?php
session_start();
include("../sql-config.php");

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die("Order ID not specified or invalid.");
}

$orderId = intval($_GET['order_id']);

$stmt = $conn->prepare("
    SELECT o.*, u.name AS username, u.email, u.phone, u.address
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_id = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) die("Order not found.");

$stmt_items = $conn->prepare("
    SELECT oi.liqour_id, l.name, l.price, oi.quantity, (l.price * oi.quantity) AS total
    FROM order_items oi
    JOIN liqours l ON oi.liqour_id = l.liqour_id
    WHERE oi.order_id = ?
");
$stmt_items->bind_param("i", $orderId);
$stmt_items->execute();
$items = $stmt_items->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Order #<?= $orderId ?></title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 20px;
}
.container {
    max-width: 800px;
    margin: 30px auto;
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
h1, h2 { margin-top: 0; margin-bottom: 15px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
th { background: #f0f0f0; }
.btn {
    display: inline-block;
    padding: 8px 15px;
    background: black;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    margin-top: 10px;
}
.btn:hover { background: #333; }
.info p { margin: 6px 0; }
@media (max-width: 480px) {
    .container { padding: 15px; width: 95%; }
    table th, table td { font-size: 14px; padding:6px; }
    .btn { width: 100%; text-align: center; }
}
</style>
</head>
<body>
<div class="container">
    <a href="../manage-dashboard.php#orders" class="btn">← Back to Orders</a>

    <h1>Order #<?= $orderId ?></h1>

    <h2>Customer Details</h2>
    <div class="info">
        <p><strong>Name:</strong> <?= htmlspecialchars($order['username']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
    </div>

    <h2>Order Details</h2>
    <div class="info">
        <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
        <p><strong>Created At:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
        <p><strong>Updated At:</strong> <?= htmlspecialchars($order['updated_at']) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Liquor</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $grandTotal = 0;
        while ($item = $items->fetch_assoc()):
            $grandTotal += $item['total'];
        ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= intval($item['quantity']) ?></td>
                <td>$<?= number_format($item['price'],2) ?></td>
                <td>$<?= number_format($item['total'],2) ?></td>
            </tr>
        <?php endwhile; ?>
            <tr>
                <td colspan="3"><strong>Grand Total</strong></td>
                <td><strong>$<?= number_format($grandTotal,2) ?></strong></td>
            </tr>
        </tbody>
    </table>

</div>
</body>
</html>
