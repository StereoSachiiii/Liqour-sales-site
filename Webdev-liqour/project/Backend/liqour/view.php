<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

// Validate liquor ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) die("Invalid liquor ID.");
$lid = intval($_GET['id']);

// Fetch liquor info including supplier
$stmt = $conn->prepare("
    SELECT l.name, l.category_id, l.image_url, s.name AS supplier_name
    FROM liqours l
    LEFT JOIN suppliers s ON l.supplier_id = s.supplier_id AND s.is_active = 1
    WHERE l.liqour_id = ? AND l.is_active = 1
");
$stmt->bind_param("i", $lid);
$stmt->execute();
$liqour = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$liqour) die("Liquor not found or inactive.");

// Fetch stock per warehouse
$stmt = $conn->prepare("
    SELECT w.warehouse_id, w.name AS warehouse_name, COALESCE(s.quantity,0) AS stock
    FROM warehouse w
    LEFT JOIN stock s ON w.warehouse_id = s.warehouse_id AND s.liqour_id = ?
    WHERE w.is_active = 1
    ORDER BY w.name ASC
");
$stmt->bind_param("i", $lid);
$stmt->execute();
$stocks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch other active products in same category
$catId = $liqour['category_id'];
$stmt = $conn->prepare("
    SELECT liqour_id, name, image_url
    FROM liqours
    WHERE category_id = ? AND liqour_id != ? AND is_active = 1
");
$stmt->bind_param("ii", $catId, $lid);
$stmt->execute();
$sameCategory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Total stock (optional)
$totalStock = array_sum(array_column($stocks, 'stock'));

// Total number of orders for this liquor
$stmt = $conn->prepare("SELECT COUNT(DISTINCT order_id) AS total_orders FROM order_items WHERE liqour_id = ?");
$stmt->bind_param("i", $lid);
$stmt->execute();
$orderData = $stmt->get_result()->fetch_assoc();
$totalOrders = $orderData['total_orders'] ?? 0;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Liquor Stats</title>
<style>
:root {
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

* { box-sizing: border-box; }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--accent-light);
    margin: 0;
    padding: 20px;
    color: var(--text);
}

.container {
    max-width: 900px;
    margin: 0 auto;
    background: var(--bg);
    padding: 25px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

h2, h3 {
    margin-top: 0;
    text-align: center;
    color: var(--text);
    font-weight: 600;
}

.back {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 15px;
    background: var(--primary-dark);
    color: #fff;
    text-decoration: none;
    border-radius: var(--radius);
    font-weight: 500;
    transition: var(--transition);
}

.back:hover {
    background: var(--primary);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    display: block;
    overflow-x: auto;
    border: 0px solid var(--border);
}

th, td {
    border: 1px solid var(--border);
    padding: 10px;
    text-align: left;
}

th {
    background: var(--accent);
    font-weight: 600;
}

img.thumb {
    height: 60px;
    border-radius: var(--radius);
}

p {
    text-align: center;
    font-weight: bold;
}

@media (max-width: 600px) {
    .container {
        padding: 15px;
    }
    .back {
        padding: 8px 12px;
        font-size: 0.9rem;
    }
    th, td {
        padding: 8px;
        font-size: 0.9rem;
    }
    img.thumb {
        height: 40px;
    }
}
</style>
</head>
<body>

<div class="container">
<a href="../manage-dashboard.php#liqours" class="back">← Back to Dashboard</a>

<h2>Stats for: <?= htmlspecialchars($liqour['name']) ?></h2>

<?php if(!empty($liqour['supplier_name'])): ?>
<p style="text-align:center; color:#666; font-style:italic;">
    Supplied by: <?= htmlspecialchars($liqour['supplier_name']) ?>
</p>
<?php endif; ?>

<?php if(!empty($liqour['image_url'])): ?>
<p style="text-align:center;">
    <img src="<?= htmlspecialchars('../../public/'.$liqour['image_url']) ?>" alt="Liquor Image" style="max-height:120px; border-radius:6px;">
</p>
<?php endif; ?>

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
<p>Total Stock: <?= $totalStock ?></p>
<?php else: ?>
<p>No stock data available.</p>
<?php endif; ?>

<h3>Total Orders for this Liquor</h3>
<p><?= $totalOrders ?></p>

<h3>Other Active Products in Same Category</h3>
<?php if($sameCategory): ?>
<table>
<tr><th>Liquor ID</th><th>Name</th><th>Image</th></tr>
<?php foreach($sameCategory as $p): ?>
<tr>
    <td><?= $p['liqour_id'] ?></td>
    <td><?= htmlspecialchars($p['name']) ?></td>
    <td>
        <?php if(!empty($p['image_url'])): ?>
            <img class="thumb" src="<?= htmlspecialchars('../../public/'.$p['image_url']) ?>" alt="Image">
        <?php else: ?>
            -
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p>No other active products in this category.</p>
<?php endif; ?>

</div>
</body>
</html>
