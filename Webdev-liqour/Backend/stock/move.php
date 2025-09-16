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
    FOR UPDATE
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
            // Reduce stock in source
            $updSource = $conn->prepare("
                UPDATE stock 
                SET quantity = quantity - ?, updated_at = NOW() 
                WHERE liqour_id = ? AND warehouse_id = ? AND is_active = 1
            ");
            $updSource->bind_param("iii", $moveQty, $liqourId, $warehouseId);
            $updSource->execute();

            // Check if destination exists
            $checkDest = $conn->prepare("
                SELECT quantity FROM stock 
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
                    SET quantity = quantity + ?, is_active=1, updated_at = NOW() 
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
            $stmt->execute();
            $stock = $stmt->get_result()->fetch_assoc();

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
select, input[type="number"] {
    width:100%;
    padding:8px;
    margin-top:5px;
    border-radius:4px;
    border:1px solid #ccc;
    box-sizing:border-box;
}
input[type="submit"] {
    width:100%;
    padding:10px;
    background:#000;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
    margin-top:15px;
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
@media (max-width:600px){
    .container { padding:15px; width:90%; }
    input[type="submit"], .back-btn { width:100%; margin-top:10px; }
}
</style>
</head>
<body>
<div class="container">
<a href="../manage-site.php" class="back-btn">← Back to Stock</a>
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
