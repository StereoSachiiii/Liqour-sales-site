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
:root {
    --primary: #FFD700;
    --primary-light: #FFE766;
    --primary-dark: #E6B800;
    --accent: #FFFACD;
    --accent-dark: #FFF8DC;
    --accent-light: #FFFFE0;
    --success: #28a745;
    --warning: #ffc107;
    --danger: #dc3545;
    --text: #333;
    --bg: #fff;
    --border: #ddd;
    --radius: 6px;
    --transition: 0.3s;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--accent-light);
    margin: 0;
    padding: 20px;
    color: var(--text);
}

.container {
    max-width: 800px;
    margin: 30px auto;
    background: var(--bg);
    padding: 25px;
    border-radius: var(--radius);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
}

h1, h2 {
    margin-top: 0;
    margin-bottom: 15px;
    color: var(--text);
    font-weight: 600;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    border: 1px solid var(--border);
}

th, td {
    border: 1px solid var(--border);
    padding: 8px;
    text-align: left;
}

th {
    background: var(--accent);
    font-weight: 600;
}

.btn {
    display: inline-block;
    padding: 8px 15px;
    background: var(--primary);
    color: #fff;
    text-decoration: none;
    border-radius: var(--radius);
    margin-top: 10px;
    font-weight: 500;
    transition: var(--transition);
}

.btn:hover {
    background: var(--primary-dark);
}

.info p {
    margin: 6px 0;
    font-size: 0.9rem;
}

@media (max-width: 480px) {
    .container {
        padding: 15px;
        width: 95%;
    }
    table th, table td {
        font-size: 14px;
        padding: 6px;
    }
    .btn {
        width: 100%;
        text-align: center;
    }
}
</style>
</head>
<body>
<div class="container">
    <a href="order.php" class="btn">← Back to Orders</a>

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
