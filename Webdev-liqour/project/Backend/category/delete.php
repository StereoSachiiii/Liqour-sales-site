<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['login']) || $_SESSION['login'] !== 'success') {
    header('Location: ../adminlogin.php');
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../manage-dashboard.php");
    exit();
}

$cid = intval($_GET['id']);
$type = $_GET['type'] ?? 'soft'; // default soft delete
$hardDelete = $type === 'hard';

// Fetch category info
$stmtCat = $conn->prepare("SELECT liqour_category_id, name, is_active FROM liqour_categories WHERE liqour_category_id = ?");
$stmtCat->bind_param("i", $cid);
$stmtCat->execute();
$category = $stmtCat->get_result()->fetch_assoc();
$stmtCat->close();

if (!$category) {
    echo "<script>
        alert('Category not found.');
        window.location.href='../manage-dashboard.php';
    </script>";
    exit();
}

// Fetch all products in this category
$stmtProd = $conn->prepare("SELECT liqour_id, name, price, is_active FROM liqours WHERE category_id = ?");
$stmtProd->bind_param("i", $cid);
$stmtProd->execute();
$products = $stmtProd->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtProd->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['confirm'] === 'yes') {
        try {
            if ($hardDelete) {
                // Hard delete (will fail if foreign key constraints exist)
                $stmtDel = $conn->prepare("DELETE FROM liqour_categories WHERE liqour_category_id=?");
                $stmtDel->bind_param("i", $cid);
                $stmtDel->execute();
                $msg = "Category and its products permanently deleted (if allowed by DB constraints).";
            } else {
                // Soft delete
                $stmtDel = $conn->prepare("UPDATE liqour_categories SET is_active=0 WHERE liqour_category_id=? AND is_active=1");
                $stmtDel->bind_param("i", $cid);
                $stmtDel->execute();

                $stmtProdUpdate = $conn->prepare("UPDATE liqours SET is_active=0 WHERE category_id=? AND is_active=1");
                $stmtProdUpdate->bind_param("i", $cid);
                $stmtProdUpdate->execute();
                $stmtProdUpdate->close();

                $msg = "Category and all its products soft-deleted successfully.";
            }

            $stmtDel->close();

            echo "<script>
                alert('" . addslashes($msg) . "');
                window.location.href='../manage-dashboard.php';
            </script>";
            exit();
        } catch (mysqli_sql_exception $e) {
            $msg = addslashes($e->getMessage());
            echo "<script>
                alert('Cannot delete category due to database constraints.\\nError: {$msg}');
                window.location.href='../manage-dashboard.php';
            </script>";
            exit();
        }
    } else {
        echo "<script>window.location.href='../manage-dashboard.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm Category Deletion</title>
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
    display: flex;
    justify-content: center;
    color: var(--text);
}

.container {
    background: var(--bg);
    padding: 25px;
    border-radius: var(--radius);
    max-width: 700px;
    width: 100%;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

h2 {
    margin-bottom: 15px;
    font-size: 1.8rem;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
    border: 1px solid var(--border);
}

th, td {
    border: 1px solid var(--border);
    padding: 8px;
    text-align: left;
}

th {
    background: var(--accent);
    font-weight: 600;
}

.actions {
    text-align: center;
}

button {
    padding: 10px 20px;
    margin: 5px;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-weight: bold;
    transition: var(--transition);
}

.confirm {
    background: var(--danger);
    color: #fff;
}

.confirm:hover {
    background: #c0392b;
}

.cancel {
    background: #666;
    color: #fff;
}

.cancel:hover {
    background: #444;
}

.notice {
    margin-bottom: 15px;
    padding: 10px;
    background: var(--accent);
    border-radius: var(--radius);
    border: 1px solid var(--border);
}
</style>
</head>
<body>
<div class="container">
    <h2>Confirm <?= $hardDelete ? 'Hard' : 'Soft' ?> Deletion</h2>
    <div class="notice">
        <strong>Category:</strong> <?= htmlspecialchars($category['name']) ?><br>
        <strong>Status:</strong> <?= $category['is_active'] ? 'Active' : 'Inactive' ?><br>
        <strong>Products in this category:</strong> <?= count($products) ?>
    </div>

    <?php if(count($products) > 0): ?>
    <table>
        <tr>
            <th>Product Name</th>
            <th>Price</th>
            <th>Status</th>
        </tr>
        <?php foreach($products as $prod): ?>
        <tr>
            <td><?= htmlspecialchars($prod['name']) ?></td>
            <td>$<?= number_format($prod['price'],2) ?></td>
            <td><?= $prod['is_active'] ? 'Active' : 'Inactive' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <p class="notice">
        <?php if($hardDelete): ?>
            Hard deleting this category will <strong>permanently remove</strong> it. 
            Any products still present may cause a database error due to foreign key constraints.
        <?php else: ?>
            Soft deleting this category will mark it <strong>and all its products</strong> as inactive. 
            They can be restored later.
        <?php endif; ?>
    </p>

    <form method="post" class="actions">
        <button type="submit" name="confirm" value="yes" class="confirm"><?= $hardDelete ? 'Delete Permanently' : 'Soft Delete' ?></button>
        <button type="submit" name="confirm" value="no" class="cancel">Cancel</button>
    </form>
</div>
</body>
</html>
