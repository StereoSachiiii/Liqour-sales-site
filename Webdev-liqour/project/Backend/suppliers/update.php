<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$supplier_id = intval($_GET['supplier_id'] ?? 0);
if (!$supplier_id) die("Supplier ID not specified.");

// Fetch supplier (active or inactive)
$stmt = $conn->prepare("SELECT * FROM suppliers WHERE supplier_id=?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) die("Supplier not found.");
$supplier = $res->fetch_assoc();
$stmt->close();

// Handle POST update
$message = "";
$messageType = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']) ?: null;
    $phone = trim($_POST['phone']) ?: null;

    if ($name) {
        // Check for duplicate name (excluding current supplier)
        $stmtCheck = $conn->prepare("SELECT supplier_id FROM suppliers WHERE name=? AND supplier_id != ?");
        $stmtCheck->bind_param("si", $name, $supplier_id);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        
        if ($stmtCheck->num_rows > 0) {
            $message = "Supplier name already exists. Please use a different name.";
            $messageType = "error";
            $stmtCheck->close();
        } else {
            $stmtCheck->close();
            
            // Check for duplicate email if provided (excluding current supplier)
            if ($email) {
                $stmtEmailCheck = $conn->prepare("SELECT supplier_id FROM suppliers WHERE email=? AND supplier_id != ?");
                $stmtEmailCheck->bind_param("si", $email, $supplier_id);
                $stmtEmailCheck->execute();
                $stmtEmailCheck->store_result();
                
                if ($stmtEmailCheck->num_rows > 0) {
                    $message = "Email already exists. Please use a different email address.";
                    $messageType = "error";
                    $stmtEmailCheck->close();
                } else {
                    $stmtEmailCheck->close();
                    
                    $stmt = $conn->prepare("UPDATE suppliers SET name=?, email=?, phone=?, updated_at=NOW() WHERE supplier_id=?");
                    $stmt->bind_param("sssi", $name, $email, $phone, $supplier_id);
                    
                    if ($stmt->execute()) {
                        $message = "Supplier updated successfully.";
                        $messageType = "success";
                        // Update the supplier array with new values for display
                        $supplier = array_merge($supplier, [
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone
                        ]);
                    } else {
                        $message = "Update failed: " . $stmt->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare("UPDATE suppliers SET name=?, email=?, phone=?, updated_at=NOW() WHERE supplier_id=?");
                $stmt->bind_param("sssi", $name, $email, $phone, $supplier_id);
                
                if ($stmt->execute()) {
                    $message = "Supplier updated successfully.";
                    $messageType = "success";
                    // Update the supplier array with new values for display
                    $supplier = array_merge($supplier, [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone
                    ]);
                } else {
                    $message = "Update failed: " . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
            }
        }
    } else {
        $message = "Supplier name is required.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Supplier</title>
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
    padding: 20px;
    display: flex;
    justify-content: center;
    color: var(--text);
}

.container {
    background: var(--bg);
    padding: 25px;
    border-radius: var(--radius);
    max-width: 500px;
    width: 100%;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
}

h2 {
    color: var(--text);
    margin-bottom: 20px;
    text-align: center;
    font-size: 1.75rem;
    font-weight: 600;
}

label {
    display: block;
    margin-bottom: 12px;
    font-weight: bold;
    color: var(--text);
    font-size: 0.875rem;
}

input[type="text"], 
input[type="email"] {
    width: 100%;
    padding: 8px 10px;
    margin-top: 5px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 14px;
    box-sizing: border-box;
    background: #f5f5f5;
    font-family: inherit;
    transition: border-color var(--transition);
}

input[type="text"]:focus,
input[type="email"]:focus {
    outline: none;
    border-color: var(--primary-dark);
}

button {
    width: 100%;
    padding: 10px 20px;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-weight: bold;
    font-size: 1rem;
    transition: var(--transition);
    margin-top: 10px;
}

button:hover {
    background: var(--primary-dark);
}

.back-btn {
    display: inline-block;
    margin-bottom: 15px;
    padding: 8px 15px;
    background: var(--primary-dark);
    color: #fff;
    text-decoration: none;
    border-radius: var(--radius);
    font-weight: 500;
    transition: var(--transition);
}

.back-btn:hover {
    background: var(--primary);
}

.message {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: var(--radius);
    text-align: center;
    font-weight: bold;
}

.message.success {
    color: var(--success);
    background: #e0ffe0;
    border: 1px solid #b0ffb0;
}

.message.error {
    color: var(--danger);
    background: #ffe0e0;
    border: 1px solid #ffb0b0;
}

.supplier-info {
    background: var(--accent);
    padding: 15px;
    border-radius: var(--radius);
    margin-bottom: 15px;
    border-left: 4px solid var(--primary-dark);
    font-size: 0.9rem;
}

.supplier-info strong {
    color: var(--text);
}

.supplier-info small {
    color: #666;
    font-size: 12px;
}

@media (max-width: 600px) {
    .container {
        padding: 15px;
        width: 90%;
    }
    
    button,
    .back-btn {
        width: 100%;
        margin-top: 10px;
    }
}
</style>
</head>
<body>
<div class="container">
    <a href="suppliers.php" class="back-btn">← Back to Suppliers</a>
    <h2>Update Supplier: <?= htmlspecialchars($supplier['name']) ?></h2>

    <div class="supplier-info">
        <strong>Supplier ID:</strong> <?= $supplier['supplier_id'] ?><br>
        <strong>Status:</strong> <?= $supplier['is_active'] ? 'Active' : 'Inactive' ?><br>
        <strong>Created:</strong> <?= date('M d, Y', strtotime($supplier['created_at'])) ?><br>
        <small>Last updated: <?= $supplier['updated_at'] ? date('M d, Y H:i', strtotime($supplier['updated_at'])) : 'Never' ?></small>
    </div>

    <?php if($message): ?>
        <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($supplier['name']) ?>" required></label>
        <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($supplier['email'] ?? '') ?>"></label>
        <label>Phone: <input type="text" name="phone" value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>"></label>
        <button type="submit">Update Supplier</button>
    </form>
</div>
</body>
</html>