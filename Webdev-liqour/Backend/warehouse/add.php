<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../manage-dashboard.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '' || $address === '') {
        $error = "Both Name and Address are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO warehouse (name, address, is_active, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())");
        $stmt->bind_param("ss", $name, $address);
        if ($stmt->execute()) {
            header("Location: ../manage-dashboard.php#warehouse");
            exit();
        } else {
            $error = "Failed to add warehouse. Name might already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Warehouse</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
}
form {
    background: #fff;
    padding: 30px;
    border: 1px solid #000;
    border-radius: 8px;
    width: 100%;
    max-width: 400px;
    box-sizing: border-box;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
h2 {
    margin-bottom: 20px;
    text-align: center;
}
label {
    display: block;
    margin: 10px 0 5px;
    font-weight: bold;
}
input[type="text"], textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #000;
    border-radius: 4px;
    box-sizing: border-box;
}
input[type="submit"] {
    width: 100%;
    background: #000;
    color: #fff;
    margin-top: 15px;
    padding: 10px;
    cursor: pointer;
    border: none;
    border-radius: 4px;
    font-weight: bold;
}
input[type="submit"]:hover { background: #333; }
.error {
    color: red;
    margin-top: 10px;
    text-align: center;
}
</style>
</head>
<body>

<form method="POST" action="add.php">
    <h2>Add Warehouse</h2>

    <label for="name">Warehouse Name</label>
    <input type="text" name="name" id="name" required>

    <label for="address">Address</label>
    <textarea name="address" id="address" rows="3" required></textarea>

    <input type="submit" value="Add Warehouse">

    <?php if($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
</form>

</body>
</html>
