<?php
session_start();
include("../sql-config.php");

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../public/login-signup.php");
    exit();
}

$wid = $_GET['id'] ?? null;
if (!$wid) die("No warehouse ID provided.");

$stmt = $conn->prepare("SELECT * FROM warehouse WHERE warehouse_id=? AND is_active=1");
$stmt->bind_param("i", $wid);
$stmt->execute();
$warehouseResult = $stmt->get_result();
if ($warehouseResult->num_rows === 0) die("Warehouse not found or inactive.");
$warehouse = $warehouseResult->fetch_assoc();

$sql = "SELECT c.name AS category_name, l.name AS liqour_name, s.quantity
        FROM stock s
        JOIN liqours l ON s.liqour_id = l.liqour_id AND l.is_active=1
        JOIN liqour_categories c ON l.category_id = c.liqour_category_id AND c.is_active=1
        WHERE s.warehouse_id = ? AND s.is_active=1
        ORDER BY c.name, l.name";

$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("i", $wid);
$stmt2->execute();
$result = $stmt2->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Warehouse Details</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    margin: 0;
    padding: 20px;
}
.container {
    background: white;
    max-width: 800px;
    margin: 30px auto;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.back-btn {
    display: inline-block;
    margin-bottom: 20px;
    text-decoration: none;
    padding: 8px 15px;
    background: #888; /* ash/gray */
    color: white;
    border-radius: 5px;
    font-weight: bold;
}
.back-btn:hover {
    background: #666;
}
h1, h2 {
    margin: 0 0 15px 0;
    color: #111;
}
p {
    margin-bottom: 20px;
    color: #555;
}
.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
.table th, .table td {
    padding: 12px;
    border: 1px solid #ccc;
    text-align: left;
}
.table th {
    background: #111;
    color: #fff;
}
.table tr:nth-child(even) {
    background: #f9f9f9;
}
.table tr:hover {
    background: #f1f1f1;
}
@media (max-width: 480px) {
    .container { width: 95%; padding: 15px; }
    .back-btn { width: 100%; text-align: center; margin-bottom: 15px; }
}
</style>
</head>
<body>

<div class="container">
    <a href="../manage-dashboard.php#warehouses" class="back-btn">← Back to Dashboard</a>
    <h1>Warehouse: <?= htmlspecialchars($warehouse['name']) ?></h1>
    <p><strong>Address:</strong> <?= htmlspecialchars($warehouse['address']) ?></p>

    <h2>Stock Details</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Brand / Liquor</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['category_name']) ?></td>
                        <td><?= htmlspecialchars($row['liqour_name']) ?></td>
                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align:center;">No stock in this warehouse.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
