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
    $new_user_id = $_POST['user_id'];
    $new_status = $_POST['status'];

    $conn->begin_transaction();
    try {
        // Handle stock adjustments when status changes
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();

        if ($current_status !== 'Completed' && $new_status === 'Completed') {
            while ($item = $items_result->fetch_assoc()) {
                $liqour_id = $item['liqour_id'];
                $qty_needed = $item['quantity'];

                $wh_result = $conn->query("SELECT warehouse_id, quantity FROM stock WHERE liqour_id=$liqour_id AND quantity>0 ORDER BY quantity DESC");

                while ($qty_needed > 0 && ($wh_row = $wh_result->fetch_assoc())) {
                    $wh_id = $wh_row['warehouse_id'];
                    $available = $wh_row['quantity'];

                    if ($available >= $qty_needed) {
                        $upd = $conn->prepare("UPDATE stock SET quantity = quantity - ? WHERE liqour_id=? AND warehouse_id=?");
                        $upd->bind_param("iii", $qty_needed, $liqour_id, $wh_id);
                        $upd->execute();
                        $qty_needed = 0;
                        break;
                    } else {
                        $upd = $conn->prepare("UPDATE stock SET quantity = 0 WHERE liqour_id=? AND warehouse_id=?");
                        $upd->bind_param("ii", $liqour_id, $wh_id);
                        $upd->execute();
                        $qty_needed -= $available;
                    }
                }
                if ($qty_needed > 0) throw new Exception("Not enough stock for liquor ID $liqour_id");
            }
        } elseif ($current_status === 'Completed' && $new_status !== 'Completed') {
            // Revert stock
            while ($item = $items_result->fetch_assoc()) {
                $liqour_id = $item['liqour_id'];
                $qty = $item['quantity'];
                $warehouse_id = 1; // default warehouse
                $upd = $conn->prepare("UPDATE stock SET quantity = quantity + ? WHERE liqour_id=? AND warehouse_id=?");
                $upd->bind_param("iii", $qty, $liqour_id, $warehouse_id);
                $upd->execute();
            }
        }

        $update_stmt = $conn->prepare("UPDATE orders SET user_id=?, status=?, total=? WHERE order_id=?");
        $update_stmt->bind_param("isdi", $new_user_id, $new_status, $total, $order_id);
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
<style>
body {font-family: Arial,sans-serif; background:#f5f5f5; margin:0; padding:20px;}
.container {background:white; max-width:500px; margin:30px auto; padding:30px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1);}
h2 {text-align:center; margin-top:0;}
label {margin-top:12px; display:block; font-weight:bold;}
select, input {width:100%; padding:8px; margin-top:5px; border-radius:4px; border:1px solid #ccc; box-sizing:border-box;}
input[readonly] {background:#eee;}
.order-items {margin-top:20px; border-top:1px solid #ccc; padding-top:10px;}
.order-items div {margin-bottom:6px;}
.submit-btn {margin-top:20px; width:100%; padding:10px; background:black; color:white; border:none; border-radius:5px; cursor:pointer;}
.submit-btn:hover {opacity:0.9;}
.back-btn {display:inline-block; margin-bottom:15px; text-decoration:none; padding:8px 15px; background:#666; color:white; border-radius:5px;}
.back-btn:hover {background:#444;}
@media (max-width: 480px) {
    .container { padding:20px; width:90%; }
    .submit-btn, .back-btn { width:100%; }
}
</style>
</head>
<body>
<div class="container">
<a href="../manage-dashboard.php#orders" class="back-btn">← Back to Orders</a>
<h2>Update Order #<?= htmlspecialchars($order_id) ?></h2>

<form method="POST">
    <label>User</label>
    <select name="user_id" required>
        <?php while($user = $users_result->fetch_assoc()): ?>
            <option value="<?= $user['id'] ?>" <?= $user['id']==$user_id?'selected':'' ?>>
                <?= htmlspecialchars($user['name']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label>Status</label>
    <select name="status" required>
        <?php 
        $status_options = ['Pending','Completed','Cancelled'];
        foreach($status_options as $s): ?>
            <option value="<?= $s ?>" <?= $s==$current_status?'selected':'' ?>><?= $s ?></option>
        <?php endforeach; ?>
    </select>

    <label>Total</label>
    <input type="text" name="total" value="<?= number_format($total,2) ?>" readonly>

    <div class="order-items">
        <strong>Order Items:</strong>
        <?php 
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
