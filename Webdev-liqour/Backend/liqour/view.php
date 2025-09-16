<?php
session_start();
include("../sql-config.php");

if (!isset($_SESSION['login'], $_SESSION['is_admin']) || $_SESSION['login'] !== 'success' || !$_SESSION['is_admin']) {
    header('Location: ../adminlogin.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) die("Invalid liquor ID.");
$lid = intval($_GET['id']);

// Fetch liquor info
$stmt = $conn->prepare("SELECT name, category_id FROM liqours WHERE liqour_id = ?");
$stmt->bind_param("i",$lid);
$stmt->execute();
$liqour = $stmt->get_result()->fetch_assoc();
if(!$liqour) die("Liquor not found.");

// Fetch stock per warehouse
$stmt2 = $conn->prepare("
    SELECT w.warehouse_id, w.name as warehouse_name, COALESCE(s.quantity,0) as stock
    FROM warehouse w
    LEFT JOIN stock s ON w.warehouse_id = s.warehouse_id AND s.liqour_id = ?
");
$stmt2->bind_param("i",$lid);
$stmt2->execute();
$stocks = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch other products in same category
$catId = $liqour['category_id'];
$stmt3 = $conn->prepare("SELECT liqour_id, name FROM liqours WHERE category_id = ? AND liqour_id != ?");
$stmt3->bind_param("ii",$catId,$lid);
$stmt3->execute();
$sameCategory = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Liquor Stats</title>
<style>
* { box-sizing: border-box; }
body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
.container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    padding: 25px;
    border-radius: 8px;
    border: 1px solid #ccc;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
h2, h3 { margin-top: 0; text-align: center; }
.back {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 15px;
    background: #6c757d;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
}
.back:hover { background: #545b62; }
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
th, td {
    border: 1px solid #ccc;
    padding: 10px;
    text-align: left;
}
th { background: #f0f0f0; }
p { text-align: center; }
@media (max-width: 600px) {
    .container { padding: 15px; }
    .back { padding: 8px 12px; font-size: 0.9rem; }
    th, td { padding: 8px; font-size: 0.9rem; }
}
</style>
</head>
<body>

<div class="container">
<a href="../manage-dashboard.php#liqours" class="back">← Back to Dashboard</a>

<h2>Stats for: <?= htmlspecialchars($liqour['name']) ?></h2>

<h3>Stock per Warehouse</h3>
<?php if($stocks): ?>
<table>
<tr><th>Warehouse</th><th>Stock</th></tr>
<?php foreach($stocks as $s): ?>
<tr>
    <td><?= htmlspecialchars($s['warehouse_name']) ?></td>
    <td><?= intval($s['stock']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p>No stock data available.</p>
<?php endif; ?>

<h3>Other Products in Same Category</h3>
<?php if($sameCategory): ?>
<table>
<tr><th>Liquor ID</th><th>Name</th></tr>
<?php foreach($sameCategory as $p): ?>
<tr>
    <td><?= $p['liqour_id'] ?></td>
    <td><?= htmlspecialchars($p['name']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p>No other products in this category.</p>
<?php endif; ?>

</div>
</body>
</html>
