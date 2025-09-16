<?php
session_start();
include('../sql-config.php');

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$error = "";
$success = "";

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $liqour_id = intval($_POST['liqour_id']);
    $warehouse_id = intval($_POST['warehouse_id']);
    $quantity = intval($_POST['quantity']);

    if ($liqour_id <= 0 || $warehouse_id <= 0 || $quantity <= 0) {
        $error = "Please select liquor, warehouse, and enter a positive quantity.";
    } else {
        $stmt = $conn->prepare("SELECT quantity FROM stock WHERE liqour_id=? AND warehouse_id=?");
        $stmt->bind_param("ii", $liqour_id, $warehouse_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            // Stock exists: update
            $upd = $conn->prepare("UPDATE stock SET quantity = quantity + ?, is_active = 1, updated_at = NOW() WHERE liqour_id=? AND warehouse_id=?");
            $upd->bind_param("iii", $quantity, $liqour_id, $warehouse_id);
            if ($upd->execute()) {
                $success = "Stock updated successfully.";
            } else {
                $error = "Failed to update stock.";
            }
        } else {
            // Stock doesn't exist: insert
            $ins = $conn->prepare("INSERT INTO stock (liqour_id, warehouse_id, quantity, is_active, updated_at) VALUES (?, ?, ?, 1, NOW())");
            $ins->bind_param("iii", $liqour_id, $warehouse_id, $quantity);
            if ($ins->execute()) {
                $success = "Stock added successfully.";
            } else {
                $error = "Failed to add stock.";
            }
        }
    }
}

// Fetch liquors and warehouses
$liqours = $conn->query("SELECT liqour_id, name FROM liqours ORDER BY name");
$warehouses = $conn->query("SELECT warehouse_id, name FROM warehouse ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Stock Record</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 20px;
    display: flex;
    justify-content: center;
}
.container {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    max-width: 450px;
    width: 100%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
h2 {text-align:center; margin-bottom:20px;}
label {display:block; font-weight:bold; margin-top:10px;}
select, input[type=number] {width:100%; padding:8px; margin-top:5px; border-radius:4px; border:1px solid #ccc;}
input[type="submit"] {
    margin-top:20px;
    width:100%;
    padding:10px;
    background:#000;
    color:#fff;
    border:none;
    border-radius:5px;
    cursor:pointer;
    transition: background 0.3s;
}
input[type="submit"]:hover {background:#333;}
.back-btn {
    display:inline-block;
    margin-bottom:15px;
    text-decoration:none;
    padding:8px 15px;
    background:#666;
    color:#fff;
    border-radius:5px;
}
.back-btn:hover {background:#444;}
.success {color:#28a745; margin-top:10px;}
.error {color:#dc3545; margin-top:10px;}
@media (max-width:500px){
    .container {padding:15px;}
    input[type="submit"], .back-btn {padding:8px;}
}
</style>
</head>
<body>
<div class="container">
<a href="../manage-dashboard.php#stock" class="back-btn">← Back to Stock</a>
<h2>Add Stock Record</h2>

<?php if ($success): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST">
    <label for="liqour">Liquor:</label>
    <select name="liqour_id" id="liqour" required>
        <option value="">-- Select Liquor --</option>
        <?php while($l = $liqours->fetch_assoc()): ?>
            <option value="<?= $l['liqour_id'] ?>"><?= htmlspecialchars($l['name']) ?></option>
        <?php endwhile; ?>
    </select>

    <label for="warehouse">Warehouse:</label>
    <select name="warehouse_id" id="warehouse" required>
        <option value="">-- Select Warehouse --</option>
        <?php while($w = $warehouses->fetch_assoc()): ?>
            <option value="<?= $w['warehouse_id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
        <?php endwhile; ?>
    </select>

    <label for="quantity">Quantity:</label>
    <input type="number" name="quantity" id="quantity" min="1" required>

    <input type="submit" value="Add Stock">
</form>
</div>
</body>
</html>
