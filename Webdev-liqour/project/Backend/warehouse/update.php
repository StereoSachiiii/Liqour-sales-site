<?php
require_once "../sql-config.php"; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $warehouse_id = intval($_POST['warehouse_id']);
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);

    if (empty($name)) {
        $error = "❌ Warehouse name cannot be empty.";
    } else {
        $sql = "UPDATE warehouse 
                SET name = ?, address = ? 
                WHERE warehouse_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $address, $warehouse_id);

        if ($stmt->execute()) {
            $success = "✅ Warehouse updated successfully.";
        } else {
            $error = "❌ Error updating warehouse.";
        }
    }
}

$warehouse_id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['warehouse_id'] ?? 0);
if (!$warehouse_id) die("❌ Invalid request: missing warehouse ID.");

$sql = "SELECT * FROM warehouse WHERE warehouse_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $warehouse_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) die("❌ Warehouse not found.");
$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Warehouse</title>
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
    padding: 40px 20px;
    margin: 0;
    color: var(--text);
}

.container {
    max-width: 500px;
    background: var(--bg);
    margin: 0 auto;
    padding: 25px;
    border-radius: var(--radius);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
}

h2 {
    text-align: center;
    margin-bottom: 20px;
    color: var(--text);
    font-size: 1.75rem;
    font-weight: 600;
}

label {
    display: block;
    margin: 12px 0 6px;
    font-weight: bold;
    font-size: 0.875rem;
    color: var(--text);
}

input[type="text"],
textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 14px;
    margin-bottom: 18px;
    font-family: inherit;
    transition: border-color var(--transition);
}

input[type="text"]:focus,
textarea:focus {
    outline: none;
    border-color: var(--primary-dark);
}

button {
    width: 100%;
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: var(--transition);
}

button:hover {
    background: var(--primary-dark);
}

.message {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: var(--radius);
    font-weight: bold;
    text-align: center;
    font-size: 0.875rem;
}

.success {
    background: var(--success);
    color: #fff;
}

.error {
    background: var(--danger);
    color: #fff;
}

.back-link {
    display: block;
    margin-top: 15px;
    text-align: center;
    text-decoration: none;
    background: var(--primary-dark);
    color: #fff;
    padding: 8px 12px;
    border-radius: var(--radius);
    font-weight: bold;
    font-size: 0.875rem;
    transition: var(--transition);
}

.back-link:hover {
    background: var(--primary);
}
</style>
</head>
<body>
<div class="container">
      <a href="warehouse.php" class="back-link">Back to Warehousest</a>
    <h2>Update Warehouse</h2>

    <?php if (!empty($success)): ?>
        <div class="message success"><?= $success ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="message error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="update.php">
        <input type="hidden" name="warehouse_id" value="<?= $row['warehouse_id'] ?>">

        <label for="name">Warehouse Name:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>

        <label for="address">Address:</label>
        <textarea id="address" name="address" rows="4"><?= htmlspecialchars($row['address']) ?></textarea>

        <button type="submit">💾 Save Changes</button>
    </form>

  
</div>
</body>
</html>
