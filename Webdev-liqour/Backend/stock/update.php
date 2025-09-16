<?php
session_start();
include("../sql-config.php");

if (
    !isset($_SESSION['login'], $_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) ||
    $_SESSION['login'] !== 'success' ||
    $_SESSION['is_admin'] != 1
) {
    header('Location: ../process-login.php');
    exit();
}

$liqourId = $_GET['liqour_id'] ?? null;
$warehouseId = $_GET['warehouse_id'] ?? null;

if (!$liqourId || !$warehouseId) {
    die("Missing liquor or warehouse ID");
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $amount = intval($_POST['quantity']);

    if ($amount <= 0) {
        $error = "Quantity must be positive.";
    } else {
        if ($action === 'update') {
            $stmt = $conn->prepare("UPDATE stock SET quantity = ?, updated_at = NOW(), is_active=1 WHERE liqour_id = ? AND warehouse_id = ?");
            $stmt->bind_param("iii", $amount, $liqourId, $warehouseId);
            $stmt->execute();
            $success = "Stock updated successfully!";
        } elseif ($action === 'add') {
            $stmt = $conn->prepare("UPDATE stock SET quantity = quantity + ?, updated_at = NOW(), is_active=1 WHERE liqour_id = ? AND warehouse_id = ?");
            $stmt->bind_param("iii", $amount, $liqourId, $warehouseId);
            $stmt->execute();
            $success = "Stock added successfully!";
        } elseif ($action === 'subtract') {
            $stmt = $conn->prepare("UPDATE stock SET quantity = GREATEST(quantity - ?, 0), updated_at = NOW() WHERE liqour_id = ? AND warehouse_id = ?");
            $stmt->bind_param("iii", $amount, $liqourId, $warehouseId);
            $stmt->execute();
            $success = "Stock reduced successfully!";
        }
    }
}

// Fetch stock info
$stmt = $conn->prepare("
    SELECT s.quantity, l.name AS liqour_name, w.name AS warehouse_name
    FROM stock s
    JOIN liqours l ON s.liqour_id = l.liqour_id
    JOIN warehouse w ON s.warehouse_id = w.warehouse_id
    WHERE s.liqour_id = ? AND s.warehouse_id = ?
");
$stmt->bind_param("ii", $liqourId, $warehouseId);
$stmt->execute();
$stock = $stmt->get_result()->fetch_assoc();

if (!$stock) {
    die("Stock record not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Stock</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 20px;
}
.container {
    max-width: 500px;
    margin: 0 auto;
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
h2 { text-align:center; margin-bottom:20px; }
.info {
    background: #f0f0f0;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}
label { display:block; margin-top:10px; font-weight:bold; }
input[type="number"], input[type="submit"] {
    width:100%;
    padding:8px;
    margin-top:5px;
    border-radius:4px;
    border:1px solid #ccc;
    box-sizing:border-box;
}
input[type="submit"] {
    background:#000;
    color:white;
    border:none;
    cursor:pointer;
    margin-top:10px;
}
input[type="submit"]:hover { background:#333; }
.back-btn {
    display:inline-block;
    margin-bottom:20px;
    padding:8px 15px;
    background:#666;
    color:white;
    text-decoration:none;
    border-radius:5px;
}
.back-btn:hover { background:#444; }
.error { color:red; margin-top:10px; }
.success { color:green; margin-top:10px; }
.form-section {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius:5px;
    background:#fafafa;
}
@media (max-width:600px){
    .container { padding:15px; width:90%; }
    input[type="submit"], .back-btn { width:100%; margin-top:10px; }
}
</style>
</head>
<body>
<div class="container">
<a href="../manage-dashboard.php" class="back-btn">← Back to Stock</a>
<h2>Manage Stock</h2>

<div class="info">
    <strong>Liquor:</strong> <?= htmlspecialchars($stock['liqour_name']) ?><br>
    <strong>Warehouse:</strong> <?= htmlspecialchars($stock['warehouse_name']) ?><br>
    <strong>Current Quantity:</strong> <?= htmlspecialchars($stock['quantity']) ?>
</div>

<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="form-section">
    <h3>Set New Quantity</h3>
    <form method="post">
        <input type="hidden" name="action" value="update">
        <input type="number" name="quantity" value="<?= htmlspecialchars($stock['quantity']) ?>" required>
        <input type="submit" value="Update Stock">
    </form>
</div>

<div class="form-section">
    <h3>Add to Stock</h3>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <input type="number" name="quantity" min="1" required>
        <input type="submit" value="Add Stock">
    </form>
</div>

<div class="form-section">
    <h3>Subtract from Stock</h3>
    <form method="post">
        <input type="hidden" name="action" value="subtract">
        <input type="number" name="quantity" min="1" max="<?= $stock['quantity'] ?>" required>
        <input type="submit" value="Subtract Stock">
    </form>
</div>
</div>
</body>
</html>
