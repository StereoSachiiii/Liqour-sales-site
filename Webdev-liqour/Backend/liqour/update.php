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

// Fetch liquor details
$stmt = $conn->prepare("SELECT * FROM liqours WHERE liqour_id = ?");
$stmt->bind_param("i", $lid);
$stmt->execute();
$liqour = $stmt->get_result()->fetch_assoc();
if (!$liqour) die("Liquor not found.");

// Fetch all categories
$categories = [];
$res = $conn->query("SELECT liqour_category_id, name FROM liqour_categories WHERE is_active=1 ORDER BY name");
while ($row = $res->fetch_assoc()) {
    $categories[$row['liqour_category_id']] = $row['name'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $category = intval($_POST['category']);
    $desc = trim($_POST['description']);
    $imageUrl = trim($_POST['imageUrl']);

    if ($name === '' || $price <= 0 || !array_key_exists($category, $categories)) {
        $error = "Please fill all fields correctly.";
    } else {
        $sql = "UPDATE liqours SET name=?, price=?, image_url=?, category_id=?, description=? WHERE liqour_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdsisi", $name, $price, $imageUrl, $category, $desc, $lid);
        if ($stmt->execute()) {
            $success = "Liquor updated successfully!";
            $liqour = [
                'name' => $name,
                'price' => $price,
                'category_id' => $category,
                'image_url' => $imageUrl,
                'description' => $desc
            ];
        } else {
            $error = "Failed to update liquor.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Liquor</title>
<style>
* { box-sizing: border-box; }
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    margin: 0;
    padding: 20px;
}
.back-button {
    position: absolute;
    top: 20px;
    left: 20px;
    background-color: #6c757d;
    color: white;
    padding: 10px 15px;
    border-radius: 4px;
    text-decoration: none;
}
.back-button:hover { background-color: #545b62; }
.container {
    max-width: 500px;
    margin: 0 auto;
    background: white;
    padding: 25px;
    border-radius: 8px;
    border: 1px solid #ccc;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
h2 { text-align: center; margin-bottom: 20px; }
label { display: block; margin-top: 12px; font-weight: bold; }
input[type=text], input[type=number], textarea, select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1rem;
}
textarea { resize: vertical; }
input[type=submit] {
    background: black;
    color: white;
    padding: 10px 15px;
    border: none;
    cursor: pointer;
    border-radius: 4px;
    width: 100%;
    font-size: 1rem;
    margin-top: 15px;
}
input[type=submit]:hover { background: #333; }
.error { color: #dc3545; margin-bottom: 10px; }
.success { color: #28a745; margin-bottom: 10px; }
img.preview { max-width: 100%; height: auto; margin-top: 10px; border-radius: 6px; }
.stats-link {
    display: inline-block;
    margin-top: 15px;
    text-decoration: none;
    color: white;
    background: #007bff;
    padding: 10px 15px;
    border-radius: 4px;
    text-align: center;
}
.stats-link:hover { background: #0056b3; }
@media (max-width: 600px) {
    .container { padding: 20px; }
    input[type=submit], .stats-link { font-size: 0.9rem; padding: 8px 12px; }
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

    <form method="POST" action="">
        <label for="name">Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($liqour['name']) ?>" required>

        <label for="price">Price</label>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($liqour['price']) ?>" required>

        <label for="category">Category</label>
        <select name="category" required>
            <?php foreach($categories as $id => $catName): ?>
                <option value="<?= $id ?>" <?= $liqour['category_id']==$id?'selected':'' ?>><?= htmlspecialchars($catName) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="description">Description</label>
        <textarea name="description" rows="4"><?= htmlspecialchars($liqour['description']) ?></textarea>

        <label for="imageUrl">Image URL</label>
        <input type="text" name="imageUrl" value="<?= htmlspecialchars($liqour['image_url']) ?>">

        <?php if(!empty($liqour['image_url'])): ?>
            <img class="preview" src="<?= htmlspecialchars('../../public/'.$liqour['image_url']) ?>" alt="Preview">
        <?php endif; ?>

        <input type="submit" value="Update Liquor">
    </form>

    <a href="../manage-dashboard.php" class="stats-link">View Stock / Category Stats</a>
</div>

</body>
</html>
