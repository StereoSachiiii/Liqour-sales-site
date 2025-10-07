<?php
session_start();
include("../sql-config.php");

// Check admin login
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$error = '';
$success = '';

// Validate liquor ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid liquor ID.");
}
$lid = intval($_GET['id']);

// Fetch liquor details first
$stmt = $conn->prepare("SELECT * FROM liqours WHERE liqour_id = ? AND is_active = 1");
$stmt->bind_param("i", $lid);
$stmt->execute();
$liqour = $stmt->get_result()->fetch_assoc();
if (!$liqour) die("Liquor not found or inactive.");

// Fetch category name
$stmt = $conn->prepare("SELECT name FROM liqour_categories WHERE liqour_category_id = ?");
$stmt->bind_param("i", $liqour['category_id']);
$stmt->execute();
$categoryResult = $stmt->get_result()->fetch_assoc();
$liqour['category_name'] = $categoryResult ? $categoryResult['name'] : 'Unknown';

// Fetch active categories
$categories = [];
$res = $conn->query("SELECT liqour_category_id, name FROM liqour_categories WHERE is_active=1 ORDER BY name");
while ($row = $res->fetch_assoc()) {
    $categories[$row['liqour_category_id']] = $row['name'];
}

// Check if suppliers table exists and fetch suppliers
$suppliers = [];
$suppliersEnabled = false;
$liqour['supplier_name'] = null;

try {
    $res = $conn->query("SHOW TABLES LIKE 'suppliers'");
    if ($res->num_rows > 0) {
        $suppliersEnabled = true;
        $res = $conn->query("SELECT supplier_id, name FROM suppliers WHERE is_active=1 ORDER BY name");
        while ($row = $res->fetch_assoc()) {
            $suppliers[$row['supplier_id']] = $row['name'];
        }
        
        // Get supplier name if exists
        if ($liqour['supplier_id']) {
            $stmt = $conn->prepare("SELECT name FROM suppliers WHERE supplier_id = ?");
            $stmt->bind_param("i", $liqour['supplier_id']);
            $stmt->execute();
            $supplierResult = $stmt->get_result()->fetch_assoc();
            $liqour['supplier_name'] = $supplierResult ? $supplierResult['name'] : null;
        }
    }
} catch (Exception $e) {
    // Suppliers table doesn't exist or isn't accessible
    $suppliersEnabled = false;
}

// Fetch current stock levels for this liquor
$stockInfo = [];
$totalStock = 0;
try {
    $res = $conn->query("SHOW TABLES LIKE 'stock'");
    if ($res->num_rows > 0) {
        $stmt = $conn->prepare("
            SELECT s.warehouse_id, w.name as warehouse_name, s.quantity 
            FROM stock s 
            JOIN warehouse w ON s.warehouse_id = w.warehouse_id 
            WHERE s.liqour_id = ? AND s.is_active = 1 AND w.is_active = 1
            ORDER BY w.name
        ");
        $stmt->bind_param("i", $lid);
        $stmt->execute();
        $stockResult = $stmt->get_result();
        while ($row = $stockResult->fetch_assoc()) {
            $stockInfo[] = $row;
        }
        $totalStock = array_sum(array_column($stockInfo, 'quantity'));
    }
} catch (Exception $e) {
    // Stock table doesn't exist or isn't accessible
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $category = intval($_POST['category']);
    $supplier = null;
    if ($suppliersEnabled && !empty($_POST['supplier'])) {
        $supplier = intval($_POST['supplier']);
    }
    $desc = trim($_POST['description']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $imagePath = $liqour['image_url']; // keep old image by default

    // Validate inputs
    if (empty($name)) {
        $error = "Name is required.";
    } elseif ($price <= 0) {
        $error = "Price must be greater than 0.";
    } elseif (!array_key_exists($category, $categories)) {
        $error = "Please select a valid category.";
    } elseif ($suppliersEnabled && $supplier !== null && !array_key_exists($supplier, $suppliers)) {
        $error = "Please select a valid supplier.";
    }

    // Handle image upload
    if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                $error = "Only JPG, PNG, GIF, or WEBP images are allowed.";
            } elseif ($file['size'] > $maxSize) {
                $error = "Image size must be less than 5MB.";
            } else {
                $uploadDir = __DIR__ . "/../../public/uploads/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = "liqour_" . uniqid() . "." . $ext;
                $targetPath = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    // Delete old image if it exists and is not the default
                    if (!empty($liqour['image_url']) && 
                        strpos($liqour['image_url'], 'uploads/') === 0 && 
                        file_exists(__DIR__ . "/../../public/" . $liqour['image_url'])) {
                        unlink(__DIR__ . "/../../public/" . $liqour['image_url']);
                    }
                    $imagePath = "uploads/" . $filename;
                } else {
                    $error = "Failed to upload image.";
                }
            }
        } else {
            $error = "File upload error: " . $file['error'];
        }
    }

    // Update liquor if no errors
    if (!$error) {
        $conn->begin_transaction();
        
        try {
            if ($suppliersEnabled) {
                $sql = "UPDATE liqours SET name=?, description=?, price=?, image_url=?, category_id=?, supplier_id=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE liqour_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdsiiii", $name, $desc, $price, $imagePath, $category, $supplier, $isActive, $lid);
            } else {
                $sql = "UPDATE liqours SET name=?, description=?, price=?, image_url=?, category_id=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE liqour_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdsiii", $name, $desc, $price, $imagePath, $category, $isActive, $lid);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update liquor: " . $stmt->error);
            }
            
            $conn->commit();
            $success = "Liquor updated successfully!";
            
            // Refresh liquor data
            $stmt = $conn->prepare("SELECT * FROM liqours WHERE liqour_id = ?");
            $stmt->bind_param("i", $lid);
            $stmt->execute();
            $liqour = $stmt->get_result()->fetch_assoc();
            
            // Get category name
            $stmt = $conn->prepare("SELECT name FROM liqour_categories WHERE liqour_category_id = ?");
            $stmt->bind_param("i", $liqour['category_id']);
            $stmt->execute();
            $categoryResult = $stmt->get_result()->fetch_assoc();
            $liqour['category_name'] = $categoryResult ? $categoryResult['name'] : 'Unknown';
            
            // Get supplier name if suppliers are enabled
            if ($suppliersEnabled && $liqour['supplier_id']) {
                $stmt = $conn->prepare("SELECT name FROM suppliers WHERE supplier_id = ?");
                $stmt->bind_param("i", $liqour['supplier_id']);
                $stmt->execute();
                $supplierResult = $stmt->get_result()->fetch_assoc();
                $liqour['supplier_name'] = $supplierResult ? $supplierResult['name'] : null;
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Check if liquor has been ordered (to prevent deletion if needed)
$orderCount = 0;
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM order_items WHERE liqour_id = ?");
    $stmt->bind_param("i", $lid);
    $stmt->execute();
    $orderCount = $stmt->get_result()->fetch_assoc()['order_count'];
} catch (Exception $e) {
    // Order tracking not available
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Liquor - <?= htmlspecialchars($liqour['name']) ?></title>
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

* { box-sizing: border-box; }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--accent-light);
    margin: 0;
    padding: 20px;
    color: var(--text);
}

.back-button {
    position: absolute;
    top: 20px;
    left: 20px;
    background: var(--primary-dark);
    color: #fff;
    padding: 10px 15px;
    border-radius: var(--radius);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: var(--transition);
}

.back-button:hover {
    background: var(--primary);
}

.container {
    max-width: 500px;
    margin: 0 auto;
    background: var(--bg);
    padding: 25px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

h2 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text);
}

label {
    display: block;
    margin-top: 12px;
    font-weight: bold;
    font-size: 0.875rem;
    color: var(--text);
}

input[type="text"],
input[type="number"],
textarea,
select,
input[type="file"] {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 1rem;
    font-family: inherit;
    transition: border-color var(--transition);
}

textarea {
    resize: vertical;
}

input[type="text"]:focus,
input[type="number"]:focus,
textarea:focus,
select:focus,
input[type="file"]:focus {
    outline: none;
    border-color: var(--primary-dark);
}

input[type="submit"] {
    background: var(--primary);
    color: #fff;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    border-radius: var(--radius);
    width: 100%;
    font-size: 1rem;
    font-weight: 600;
    margin-top: 15px;
    transition: var(--transition);
}

input[type="submit"]:hover {
    background: var(--primary-dark);
}

.error {
    color: var(--danger);
    margin-bottom: 10px;
    font-size: 0.875rem;
}

.success {
    color: var(--success);
    margin-bottom: 10px;
    font-size: 0.875rem;
}

img.preview {
    max-width: 100%;
    height: auto;
    margin-top: 10px;
    border-radius: var(--radius);
}

.stats-link {
    display: inline-block;
    margin-top: 15px;
    text-decoration: none;
    color: #fff;
    background: var(--primary-light);
    padding: 10px 15px;
    border-radius: var(--radius);
    text-align: center;
    font-weight: 500;
    transition: var(--transition);
}

.stats-link:hover {
    background: var(--primary);
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
}

.warning {
    color: var(--warning);
    font-style: italic;
    margin-top: 10px;
    font-size: 0.9rem;
}

.info-section {
    margin-top: 20px;
    padding: 15px;
    background: var(--accent);
    border-radius: var(--radius);
    border: 1px solid var(--border);
}

.info-section h4 {
    margin: 0 0 10px 0;
    color: var(--text);
    font-size: 1.1rem;
}

.info-section p {
    margin: 5px 0;
    font-size: 0.9rem;
}

@media (max-width: 600px) {
    .container {
        padding: 20px;
    }
    input[type="submit"],
    .stats-link {
        font-size: 0.9rem;
        padding: 8px 12px;
    }
}
</style>
</head>
<body>

<a href="../manage-dashboard.php" class="back-button">← Back to Dashboard</a>

<div class="container">
    <h2>Update Liquor</h2>

    <?php if($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($liqour['name']) ?>" required>

        <label for="price">Price</label>
        <input type="number" step="0.01" name="price" id="price" value="<?= htmlspecialchars($liqour['price']) ?>" required>

        <label for="category">Category</label>
        <select name="category" id="category" required>
            <?php foreach($categories as $id => $catName): ?>
                <option value="<?= $id ?>" <?= $liqour['category_id']==$id?'selected':'' ?>>
                    <?= htmlspecialchars($catName) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="supplier">Supplier</label>
        <?php if($suppliersEnabled): ?>
            <select name="supplier" id="supplier">
                <option value="">No Supplier</option>
                <?php foreach($suppliers as $id => $supplierName): ?>
                    <option value="<?= $id ?>" <?= $liqour['supplier_id']==$id?'selected':'' ?>>
                        <?= htmlspecialchars($supplierName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <input type="text" value="Suppliers not available" disabled>
        <?php endif; ?>

        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"><?= htmlspecialchars($liqour['description']) ?></textarea>

        <label for="image">Image</label>
        <input type="file" name="image" id="image" accept="image/*">
        <?php if(!empty($liqour['image_url'])): ?>
            <img class="preview" src="<?= htmlspecialchars('../../public/'.$liqour['image_url']) ?>" alt="Preview">
        <?php endif; ?>

        <label>Status</label>
        <div class="checkbox-group">
            <input type="checkbox" name="is_active" id="is_active" <?= $liqour['is_active'] ? 'checked' : '' ?>>
            <label for="is_active">Active</label>
        </div>
        <?php if($orderCount > 0): ?>
            <p class="warning">⚠️ This liquor has been ordered <?= $orderCount ?> times.</p>
        <?php endif; ?>

        <input type="submit" value="Update Liquor">
    </form>

    <?php if(!empty($stockInfo) || $orderCount > 0): ?>
    <div class="info-section">
        <h4>Additional Information</h4>
        
        <?php if(!empty($stockInfo)): ?>
            <p><strong>Stock Information:</strong></p>
            <?php foreach($stockInfo as $stock): ?>
                <p>• <?= htmlspecialchars($stock['warehouse_name']) ?>: <?= $stock['quantity'] ?> units</p>
            <?php endforeach; ?>
            <p><strong>Total Stock: <?= $totalStock ?> units</strong></p>
        <?php endif; ?>
        
        <?php if($orderCount > 0): ?>
            <p><strong>Order Statistics:</strong></p>
            <p>• Times Ordered: <?= $orderCount ?></p>
        <?php endif; ?>
        
        <p><strong>Last Updated:</strong> <?= date('M j, Y g:i A', strtotime($liqour['updated_at'])) ?></p>
    </div>
    <?php endif; ?>

    <a href="../manage-dashboard.php" class="stats-link">View Stock / Category Stats</a>
</div>

<script>
// Preview image on selection
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const existingPreview = document.querySelector('.preview');
            if (existingPreview) {
                existingPreview.src = e.target.result;
            } else {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview';
                img.alt = 'New Image Preview';
                document.getElementById('image').parentNode.appendChild(img);
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>

</body>
</html>