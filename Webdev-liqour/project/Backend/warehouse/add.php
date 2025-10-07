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
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    color: var(--text);
}

form {
    background: var(--bg);
    padding: 30px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    width: 100%;
    max-width: 400px;
    box-sizing: border-box;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

h2 {
    margin-bottom: 20px;
    text-align: center;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text);
}

label {
    display: block;
    margin: 10px 0 5px;
    font-weight: bold;
    font-size: 0.875rem;
    color: var(--text);
}

input[type="text"],
textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-sizing: border-box;
    font-size: 1rem;
    font-family: inherit;
    transition: border-color var(--transition);
}

input[type="text"]:focus,
textarea:focus {
    outline: none;
    border-color: var(--primary-dark);
}
.back-button {
    display: inline-block;
    background: var(--primary-dark);
    color: #fff;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: var(--transition);
}

.back-button:hover {
    background: var(--primary);
    transform: translateY(-1px);
}
input[type="submit"] {
    width: 100%;
    background: var(--primary);
    color: #fff;
    margin-top: 15px;
    padding: 10px;
    cursor: pointer;
    border: none;
    border-radius: var(--radius);
    font-weight: bold;
    font-size: 1rem;
    transition: var(--transition);
}

input[type="submit"]:hover {
    background: var(--primary-dark);
}

.error {
    color: var(--danger);
    margin-top: 10px;
    text-align: center;
    font-size: 0.875rem;
}
</style>
</head>
<body>
<form method="POST" action="add.php">
    <a href="warehouse.php" class="back-button">← Back to Warehouse</a>

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
