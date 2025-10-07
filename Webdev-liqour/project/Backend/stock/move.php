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
    die("Missing liquor or warehouse ID.");
}

// --- FETCH CURRENT STOCK ---
$stmt = $conn->prepare("
    SELECT s.quantity, l.name AS liqour_name, w.name AS warehouse_name
    FROM stock s
    JOIN liqours l ON s.liqour_id = l.liqour_id
    JOIN warehouse w ON s.warehouse_id = w.warehouse_id
    WHERE s.liqour_id = ? AND s.warehouse_id = ? AND s.is_active = 1
");
$stmt->bind_param("ii", $liqourId, $warehouseId);
$stmt->execute();
$result = $stmt->get_result();
$stock = $result->fetch_assoc();

if (!$stock) die("Stock record not found.");

$error = "";
$success = "";

// Fetch warehouses for dropdown
$whResult = $conn->query("SELECT warehouse_id, name FROM warehouse ORDER BY name");
$warehouses = $whResult->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destWarehouse = intval($_POST['dest_warehouse']);
    $moveQty = intval($_POST['quantity']);

    if ($destWarehouse == $warehouseId) {
        $error = "Destination warehouse must be different.";
    } elseif ($moveQty <= 0) {
        $error = "Quantity must be positive.";
    } elseif ($moveQty > $stock['quantity']) {
        $error = "Not enough stock available to move.";
    } else {
        $conn->begin_transaction();
        try {
            // Reduce stock in source but keep is_active=1 even if 0
            $updSource = $conn->prepare("
                UPDATE stock 
                SET quantity = GREATEST(quantity - ?, 0), updated_at = NOW(), is_active = 1
                WHERE liqour_id = ? AND warehouse_id = ? AND is_active = 1
            ");
            $updSource->bind_param("iii", $moveQty, $liqourId, $warehouseId);
            $updSource->execute();

            // Check if destination exists
            $checkDest = $conn->prepare("
                SELECT quantity, is_active FROM stock 
                WHERE liqour_id = ? AND warehouse_id = ?
                FOR UPDATE
            ");
            $checkDest->bind_param("ii", $liqourId, $destWarehouse);
            $checkDest->execute();
            $destResult = $checkDest->get_result();

            if ($destRow = $destResult->fetch_assoc()) {
                // If soft-deleted, reactivate
                $updDest = $conn->prepare("
                    UPDATE stock 
                    SET quantity = quantity + ?, is_active = 1, updated_at = NOW() 
                    WHERE liqour_id = ? AND warehouse_id = ?
                ");
                $updDest->bind_param("iii", $moveQty, $liqourId, $destWarehouse);
                $updDest->execute();
            } else {
                // Insert new stock
                $insertDest = $conn->prepare("
                    INSERT INTO stock (liqour_id, warehouse_id, quantity, is_active, updated_at)
                    VALUES (?, ?, ?, 1, NOW())
                ");
                $insertDest->bind_param("iii", $liqourId, $destWarehouse, $moveQty);
                $insertDest->execute();
            }

            $conn->commit();
            $success = "Stock moved successfully!";

            // Refresh stock info
            $refresh = $conn->prepare("
                SELECT s.quantity, l.name AS liqour_name, w.name AS warehouse_name
                FROM stock s
                JOIN liqours l ON s.liqour_id = l.liqour_id
                JOIN warehouse w ON s.warehouse_id = w.warehouse_id
                WHERE s.liqour_id = ? AND s.warehouse_id = ?
            ");
            $refresh->bind_param("ii", $liqourId, $warehouseId);
            $refresh->execute();
            $stock = $refresh->get_result()->fetch_assoc();

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error moving stock: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Move Stock</title>
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

select,
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

select:focus,
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
    margin-top: 15px;
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
<h2>Move Stock</h2>

<div class="info">
    <strong>Liquor:</strong> <?= htmlspecialchars($stock['liqour_name']) ?><br>
    <strong>Source Warehouse:</strong> <?= htmlspecialchars($stock['warehouse_name']) ?><br>
    <strong>Available Quantity:</strong> <?= htmlspecialchars($stock['quantity']) ?>
</div>

<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="post">
    <label for="dest_warehouse">Destination Warehouse:</label>
    <select name="dest_warehouse" id="dest_warehouse" required>
        <option value="">-- Select Warehouse --</option>
        <?php foreach ($warehouses as $wh): ?>
            <?php if ($wh['warehouse_id'] != $warehouseId): ?>
                <option value="<?= $wh['warehouse_id'] ?>"><?= htmlspecialchars($wh['name']) ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>

    <label for="quantity">Quantity to Move:</label>
    <input type="number" name="quantity" id="quantity" min="1" max="<?= $stock['quantity'] ?>" required>

    <input type="submit" value="Move Stock">
</form>
</div>
</body>
</html>
