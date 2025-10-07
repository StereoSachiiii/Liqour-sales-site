<?php
session_start();
include('../sql-config.php');

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$order_id = $_GET['order_id'] ?? null;
if (!$order_id || !is_numeric($order_id)) die("No order ID provided.");

$stmt = $conn->prepare("SELECT user_id, status FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
if ($order_result->num_rows === 0) die("Order not found.");

$order = $order_result->fetch_assoc();
$user_id = $order['user_id'];
$current_status = $order['status'];

$users_result = $conn->query("SELECT id, name FROM users ORDER BY name");

$total_query = $conn->prepare("SELECT SUM(price*quantity) AS total FROM order_items WHERE order_id = ?");
$total_query->bind_param("i", $order_id);
$total_query->execute();
$total_result = $total_query->get_result();
$total_row = $total_result->fetch_assoc();
$total = $total_row['total'] ?? 0;

$items_stmt = $conn->prepare("
    SELECT oi.quantity, oi.price, l.name, oi.liqour_id
    FROM order_items oi
    JOIN liqours l ON oi.liqour_id = l.liqour_id
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'];

    $conn->begin_transaction();
    try {
        // Get fresh items for stock operations
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $fresh_items = $items_stmt->get_result();

        // Stock logic: Only handle cancellation and reactivation
        // Frontend already deducts stock when order is created with 'processing' status
        if ($current_status !== 'cancelled' && $new_status === 'cancelled') {
            // Order being cancelled - add stock back
            while ($item = $fresh_items->fetch_assoc()) {
                $liqour_id = $item['liqour_id'];
                $qty = $item['quantity'];
                
                // Find first available warehouse or default to warehouse 1
                $wh_query = $conn->query("SELECT warehouse_id FROM warehouse WHERE is_active=1 ORDER BY warehouse_id ASC LIMIT 1");
                $wh_result = $wh_query->fetch_assoc();
                $warehouse_id = $wh_result ? $wh_result['warehouse_id'] : 1;
                
                // Check if stock record exists
                $stock_check = $conn->prepare("SELECT warehouse_id FROM stock WHERE liqour_id=? AND warehouse_id=?");
                $stock_check->bind_param("ii", $liqour_id, $warehouse_id);
                $stock_check->execute();
                $exists = $stock_check->get_result()->num_rows > 0;
                $stock_check->close();
                
                if ($exists) {
                    $upd = $conn->prepare("UPDATE stock SET quantity = quantity + ?, updated_at=NOW() WHERE liqour_id=? AND warehouse_id=?");
                } else {
                    $upd = $conn->prepare("INSERT INTO stock (liqour_id, warehouse_id, quantity, updated_at, is_active) VALUES (?, ?, ?, NOW(), 1)");
                }
                $upd->bind_param("iii", $liqour_id, $warehouse_id, $qty);
                $upd->execute();
                $upd->close();
            }
        } elseif ($current_status === 'cancelled' && $new_status !== 'cancelled') {
            // Order being reactivated - deduct stock again
            while ($item = $fresh_items->fetch_assoc()) {
                $liqour_id = $item['liqour_id'];
                $qty_needed = $item['quantity'];

                // Check total available stock first
                $stock_check = $conn->prepare("SELECT SUM(quantity) as total FROM stock WHERE liqour_id=? AND is_active=1");
                $stock_check->bind_param("i", $liqour_id);
                $stock_check->execute();
                $available = $stock_check->get_result()->fetch_assoc()['total'] ?? 0;
                $stock_check->close();
                
                if ($available < $qty_needed) {
                    throw new Exception("Not enough stock for liquor ID $liqour_id. Need: $qty_needed, Available: $available");
                }

                // Deduct stock using same logic as frontend
                $wh_stmt = $conn->prepare("SELECT warehouse_id, quantity FROM stock WHERE liqour_id=? AND quantity>0 AND is_active=1 ORDER BY warehouse_id ASC");
                $wh_stmt->bind_param("i", $liqour_id);
                $wh_stmt->execute();
                $wh_result = $wh_stmt->get_result();

                while ($qty_needed > 0 && ($wh_row = $wh_result->fetch_assoc())) {
                    $wh_id = $wh_row['warehouse_id'];
                    $available_qty = $wh_row['quantity'];
                    
                    $deduct = min($qty_needed, $available_qty);
                    $new_qty = $available_qty - $deduct;
                    
                    $update_stock = $conn->prepare("UPDATE stock SET quantity=?, updated_at=NOW() WHERE liqour_id=? AND warehouse_id=?");
                    $update_stock->bind_param("iii", $new_qty, $liqour_id, $wh_id);
                    $update_stock->execute();
                    $update_stock->close();
                    
                    $qty_needed -= $deduct;
                }
                $wh_stmt->close();
            }
        }

        // Only update the status, but don't touch the user_id
        $update_stmt = $conn->prepare("UPDATE orders SET status=?, total=? WHERE order_id=?");
        $update_stmt->bind_param("sdi", $new_status, $total, $order_id);
        $update_stmt->execute();

        $conn->commit();
        echo "<script>alert('Order updated successfully'); window.location.href='../manage-dashboard.php#orders';</script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error updating order: ".$e->getMessage()."');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Order</title>
<style>:root {
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
    background: var(--bg);
    max-width: 500px;
    margin: 30px auto;
    padding: 30px;
    border-radius: var(--radius);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
}

h2 {
    text-align: center;
    margin-top: 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text);
}

label {
    margin-top: 12px;
    display: block;
    font-weight: bold;
    font-size: 0.875rem;
    color: var(--text);
}

select, input {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-sizing: border-box;
    font-size: 1rem;
    font-family: inherit;
    transition: border-color var(--transition);
}

select:focus, input:focus {
    outline: none;
    border-color: var(--primary-dark);
}

input[readonly] {
    background: var(--accent);
}

.order-items {
    margin-top: 20px;
    border-top: 1px solid var(--border);
    padding-top: 10px;
}

.order-items div {
    margin-bottom: 6px;
    font-size: 0.9rem;
}

.submit-btn {
    margin-top: 20px;
    width: 100%;
    padding: 10px;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: var(--transition);
}

.submit-btn:hover {
    background: var(--primary-dark);
}

.back-btn {
    display: inline-block;
    margin-bottom: 15px;
    text-decoration: none;
    padding: 8px 15px;
    background: var(--primary-dark);
    color: #fff;
    border-radius: var(--radius);
    font-weight: 500;
    transition: var(--transition);
}

.back-btn:hover {
    background: var(--primary);
}

@media (max-width: 480px) {
    .container {
        padding: 20px;
        width: 90%;
    }
    .submit-btn, .back-btn {
        width: 100%;
    }
}
</style>
</head>
<body>
<div class="container">
<a href="order.php" class="back-btn">← Back to Orders</a>
<h2>Update Order #<?= htmlspecialchars($order_id) ?></h2>

<form method="POST">
    <label>User</label>
    <input type="text" value="<?= htmlspecialchars($order['user_id']) ?>" readonly>

    <label>Status</label>
    <select name="status" required>
        <?php 
        $status_options = ['pending','processing','completed','cancelled'];
        foreach($status_options as $s): ?>
            <option value="<?= $s ?>" <?= $s==$current_status?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Total</label>
    <input type="text" name="total" value="<?= number_format($total,2) ?>" readonly>

    <div class="order-items">
        <strong>Order Items:</strong>
        <?php 
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        while($item = $items_result->fetch_assoc()): ?>
            <div><?= htmlspecialchars($item['name']) ?> x <?= $item['quantity'] ?> = $<?= number_format($item['price']*$item['quantity'],2) ?></div>
        <?php endwhile; ?>
    </div>

    <input type="submit" class="submit-btn" value="Update Order">
</form>
</div>
</body>
</html>
