<?php
session_start();
include("../sql-config.php");

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$cid = $_GET['id'] ?? null;
if (!$cid) {
    die("No category ID provided.");
}

// Fetch category
$stmt = $conn->prepare("SELECT * FROM liqour_categories WHERE liqour_category_id = ?");
$stmt->bind_param("i", $cid);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Category not found.");
}
$category = $result->fetch_assoc();

// Count products
$product_stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM liqours WHERE category_id = ?");
$product_stmt->bind_param("i", $cid);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product_count = $product_result->fetch_assoc()['product_count'];

// Fetch stock info
$stock_stmt = $conn->prepare("
    SELECT l.liqour_id, l.name AS liqour_name, s.warehouse_id, w.name AS warehouse_name, s.quantity
    FROM liqours l
    LEFT JOIN stock s ON l.liqour_id = s.liqour_id
    LEFT JOIN warehouse w ON s.warehouse_id = w.warehouse_id
    WHERE l.category_id = ?
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

body {
    font-family: 'Inter', Arial, sans-serif;
    background: #f8f9fa;
    margin: 0;
    padding: 20px;
    min-height: 100vh;
    line-height: 1.6;
}

.back-button {
    position: fixed;
    top: 20px;
    left: 20px;
    background-color: #6c757d;
    color: white;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: 100;
}

.back-button:hover { background-color: #545b62; transform: translateY(-1px); }

.container {
    max-width: 900px;
    margin: 4rem auto 2rem;
    background: white;
    padding: 2rem;
    border-radius: 12px;
    border: 1px solid #dee2e6;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

h2 { text-align: center; margin-bottom: 1.5rem; color: #212529; }

.category-image {
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.category-image img {
    max-width: 150px;
    border-radius: 8px;
}

.table-responsive { overflow-x:auto; }

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

th, td {
    border: 1px solid #dee2e6;
    padding: 0.75rem;
    text-align: left;
}

th { background-color: #f1f3f5; }

@media (max-width: 768px) {
    .container { padding: 1.5rem; margin: 3rem 1rem; }
}

@media (max-width: 480px) {
    .container { padding: 1rem; }
    h2 { font-size: 1.25rem; }
}
</style>
</head>
<body>

<a href="../manage-dashboard.php#categories" class="back-button">← Back to Dashboard</a>

<div class="container">

    <?php if(!empty($category['image_url'])): ?>
        <div class="category-image">
            <img src="../../<?= htmlspecialchars($category['image_url']) ?>" alt="Category Image">
        </div>
    <?php endif; ?>

    <h2>Category Details</h2>
    <div class="table-responsive">
    <table>
        <tr>
            <th>Category ID</th>
            <td><?= htmlspecialchars($category['liqour_category_id']) ?></td>
        </tr>
        <tr>
            <th>Name</th>
            <td><?= htmlspecialchars($category['name']) ?></td>
        </tr>
        <tr>
            <th>Number of Products</th>
            <td><?= htmlspecialchars($product_count) ?></td>
        </tr>
    </table>
    </div>

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
        <?php
        if ($stock_result->num_rows > 0) {
            while ($row = $stock_result->fetch_assoc()) {
                $liqour_id = htmlspecialchars($row['liqour_id']);
                $liqour_name = htmlspecialchars($row['liqour_name']);
                $warehouse = $row['warehouse_name'] ? htmlspecialchars($row['warehouse_name']) : "No warehouse";
                $quantity = intval($row['quantity'] ?? 0);

                echo "<tr>
                        <td>{$liqour_id}</td>
                        <td>{$liqour_name}</td>
                        <td>{$warehouse}</td>
                        <td>{$quantity}</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No products or stock found for this category.</td></tr>";
        }
        ?>
        </tbody>
    </table>
    </div>

</div>
</body>
</html>
