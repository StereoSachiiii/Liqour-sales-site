<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../public/login-signup.php");
    exit();
}

$cid = $_GET['id'] ?? null;
if (!$cid) die("No category ID provided.");

// Fetch category
$stmt = $conn->prepare("SELECT * FROM liqour_categories WHERE liqour_category_id = ?");
$stmt->bind_param("i", $cid);
$stmt->execute();
$category = $stmt->get_result()->fetch_assoc();
if (!$category) die("Category not found.");

// Count active products
$product_stmt = $conn->prepare("SELECT COUNT(*) AS product_count FROM liqours WHERE category_id=? AND is_active=1");
$product_stmt->bind_param("i", $cid);
$product_stmt->execute();
$product_count = $product_stmt->get_result()->fetch_assoc()['product_count'];

// Fetch active products with stock info
$stock_stmt = $conn->prepare("
    SELECT l.liqour_id, l.name AS liqour_name, l.image_url, s.warehouse_id, w.name AS warehouse_name, s.quantity
    FROM liqours l
    LEFT JOIN stock s ON l.liqour_id = s.liqour_id
    LEFT JOIN warehouse w ON s.warehouse_id = w.warehouse_id
    WHERE l.category_id=? AND l.is_active=1
    ORDER BY l.name, w.name
");
$stock_stmt->bind_param("i", $cid);
$stock_stmt->execute();
$stock_result = $stock_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Category</title>
<style>
* { box-sizing: border-box; }
body { font-family: 'Inter', Arial, sans-serif; background: #f8f9fa; margin:0; padding:20px; line-height:1.6; }
.back-button { display:inline-block; margin-bottom:20px; background:#6c757d; color:white; padding:0.5rem 1rem; border-radius:6px; text-decoration:none; }
.back-button:hover { background:#545b62; }
.container { max-width:900px; margin:0 auto; background:white; padding:2rem; border-radius:12px; border:1px solid #dee2e6; box-shadow:0 4px 6px rgba(0,0,0,0.05); }
h2 { text-align:center; margin-bottom:1rem; }
.category-image { display:flex; justify-content:center; margin-bottom:1.5rem; }
.category-image img { max-width:150px; border-radius:8px; }
.table-responsive { overflow-x:auto; margin-top:1rem; }
table { width:100%; border-collapse:collapse; }
th, td { border:1px solid #dee2e6; padding:0.75rem; text-align:left; }
th { background:#f1f3f5; }
.product-thumbnail { height:50px; border-radius:4px; object-fit:cover; }
@media(max-width:768px) { .container { padding:1.5rem; } }
</style>
</head>
<body>

<a href="../manage-dashboard.php#categories" class="back-button">← Back to Dashboard</a>

<div class="container">

    <h2><?= htmlspecialchars($category['name']) ?></h2>

    <?php if(!empty($category['image_url'])): ?>
        <div class="category-image">
            <img src="../../<?= htmlspecialchars($category['image_url']) ?>" alt="Category Image">
        </div>
    <?php endif; ?>

    <p><strong>Number of Active Products:</strong> <?= htmlspecialchars($product_count) ?></p>

    <h2>Products & Stock Levels</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    
                    <th>Warehouse</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php if($stock_result->num_rows > 0): ?>
                    <?php while($row = $stock_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['liqour_id'] ?></td>
                            <td><?= htmlspecialchars($row['liqour_name']) ?></td>
                           
                            <td><?= htmlspecialchars($row['warehouse_name'] ?? 'No warehouse') ?></td>
                            <td><?= intval($row['quantity'] ?? 0) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No active products or stock found for this category.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
