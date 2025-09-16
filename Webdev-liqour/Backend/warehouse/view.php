<?php
session_start();
include("../sql-config.php");

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../public/login-signup.php");
    exit();
}

$wid = $_GET['id'] ?? null;
if (!$wid) {
    die("No warehouse ID provided.");
}

$stmt = $conn->prepare("SELECT * FROM warehouse WHERE warehouse_id=? AND is_active=1");
$stmt->bind_param("i", $wid);
$stmt->execute();
$warehouseResult = $stmt->get_result();
if ($warehouseResult->num_rows === 0) {
    die("Warehouse not found or inactive.");
}
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
.section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
h1 {
    font-size: 24px;
    margin-bottom: 10px;
    color: #111;
}
h2 {
    font-size: 20px;
    margin-bottom: 15px;
    color: #222;
}
p {
    font-size: 14px;
    color: #555;
    margin-bottom: 15px;
}
.table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 6px;
    overflow: hidden;
}
.table th, .table td {
    padding: 12px 15px;
    border: 1px solid #ccc;
    text-align: left;
    font-size: 14px;
}
.table th {
    background: #111;
    color: #fff;
    font-weight: bold;
}
.table tr:nth-child(even) {
    background: #f9f9f9;
}
.table tr:hover {
    background: #f1f1f1;
}
</style>
</head>
<body>

<section class="section">
    <h1>Warehouse: <?= htmlspecialchars($warehouse['name']) ?></h1>
    <p><strong>Address:</strong> <?= htmlspecialchars($warehouse['address']) ?></p>
</section>

<section class="section">
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
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $cat = htmlspecialchars($row['category_name']);
                $liq = htmlspecialchars($row['liqour_name']);
                $qty = htmlspecialchars($row['quantity']);
                echo "<tr><td>{$cat}</td><td>{$liq}</td><td>{$qty}</td></tr>";
            }
        } else {
            echo "<tr><td colspan='3' style='text-align:center;'>No stock in this warehouse.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</section>

</body>
</html>
