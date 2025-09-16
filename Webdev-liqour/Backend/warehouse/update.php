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

if (!isset($_GET['id']) && !isset($_POST['warehouse_id'])) {
    die("❌ Invalid request: missing warehouse ID.");
}

$warehouse_id = isset($_GET['id']) ? intval($_GET['id']) : intval($_POST['warehouse_id']);

$sql = "SELECT * FROM warehouse WHERE warehouse_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $warehouse_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("❌ Warehouse not found.");
}

$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Update Warehouse</title>
  
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f8f9fa;
      padding: 40px;
    }
    .form-container {
      max-width: 500px;
      background: #fff;
      padding: 25px;
      margin: 0 auto;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      border:1px solid #333 ;
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #333;
    }
    label {
      display: block;
      margin: 12px 0 6px;
      font-weight: bold;
      color: #444;
    }
    input[type="text"], textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 18px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
    }
    button {
      width: 100%;
      background:rgb(0, 0, 0);
      color: #fff;
      border: none;
      padding: 12px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      transition: background 0.2s;
    }
    button:hover {
      background: #0056b3;
    }
    .message {
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 6px;
      font-weight: bold;
      text-align: center;
    }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .back-link {
      display: block;
      margin-top: 15px;
      text-align: center;
      text-decoration: none;
      color: #555;
    }
    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Update Warehouse</h2>

    <?php if (!empty($success)): ?>
      <div class="message success"><?php echo $success; ?></div>
    <?php elseif (!empty($error)): ?>
      <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="update.php">
      <input type="hidden" name="warehouse_id" value="<?php echo $row['warehouse_id']; ?>">

      <label for="name">Warehouse Name:</label>
      <input type="text" id="name" name="name" 
             value="<?php echo htmlspecialchars($row['name']); ?>" required>

      <label for="address">Address:</label>
      <textarea id="address" name="address" rows="4"><?php echo htmlspecialchars($row['address']); ?></textarea>

      <button type="submit">💾 Save Changes</button>
    </form>

    <a href="../manage-dashboard.php#warehouse" class="back-link">⬅ Back to Warehouse List</a>
  </div>
</body>
</html>
