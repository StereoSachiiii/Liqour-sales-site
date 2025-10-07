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
    max-width: 500px;
    margin: 0 auto;
    background: var(--bg);
    padding: 25px;
    border-radius: var(--radius);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
}

h2 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text);
}

h3 {
    margin-bottom: 10px;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text);
}

.info {
    background: var(--accent);
    padding: 15px;
    margin-bottom: 20px;
    border-radius: var(--radius);
    font-size: 0.9rem;
}

label {
    display: block;
    margin-top: 10px;
    font-weight: bold;
    font-size: 0.875rem;
    color: var(--text);
}

input[type="number"] {
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

input[type="number"]:focus {
    outline: none;
    border-color: var(--primary-dark);
}

input[type="submit"] {
    width: 100%;
    padding: 10px;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    margin-top: 10px;
    font-size: 1rem;
    font-weight: bold;
    transition: var(--transition);
}

input[type="submit"]:hover {
    background: var(--primary-dark);
}

.back-btn {
    display: inline-block;
    margin-bottom: 20px;
    padding: 8px 15px;
    background: var(--primary-dark);
    color: #fff;
    text-decoration: none;
    border-radius: var(--radius);
    font-weight: 500;
    transition: var(--transition);
}

.back-btn:hover {
    background: var(--primary);
}

.error {
    color: var(--danger);
    margin-top: 10px;
    font-size: 0.875rem;
    text-align: center;
}

.success {
    color: var(--success);
    margin-top: 10px;
    font-size: 0.875rem;
    text-align: center;
}

.form-section {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--accent-dark);
}

@media (max-width: 600px) {
    .container {
        padding: 15px;
        width: 90%;
    }
    input[type="submit"],
    .back-btn {
        width: 100%;
        margin-top: 10px;
    }
}
</style>
</head>
<body>
<div class="container">
<a href="stock.php" class="back-btn">← Back to Stock</a>
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
