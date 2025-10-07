<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) die("User ID not specified.");

// Fetch user (active or inactive)
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) die("User not found.");
$user = $res->fetch_assoc();
$stmt->close();

// Handle POST update
$message = "";
$messageType = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']) ?: null;
    $address = trim($_POST['address']) ?: null;
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if ($name && $email) {
        // Check for duplicate email (excluding current user)
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email=? AND id != ?");
        $stmtCheck->bind_param("si", $email, $id);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        
        if ($stmtCheck->num_rows > 0) {
            $message = "Email already exists. Please use a different email address.";
            $messageType = "error";
            $stmtCheck->close();
        } else {
            $stmtCheck->close();
            
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, address=?, is_admin=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param("ssssii", $name, $email, $phone, $address, $is_admin, $id);
            
            if ($stmt->execute()) {
                $message = "User updated successfully.";
                $messageType = "success";
                // Update the user array with new values for display
                $user = array_merge($user, [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'is_admin' => $is_admin
                ]);
            } else {
                $message = "Update failed: " . $stmt->error;
                $messageType = "error";
            }
            $stmt->close();
        }
    } else {
        $message = "Name and email are required.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update User</title>
<style>
:root {
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
    color: var(--text);
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
}

.container {
    max-width: 500px;
    width: 100%;
    background: var(--bg);
    padding: 25px;
    border-radius: var(--radius);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
    position: relative;
}

h2 {
    text-align: center;
    margin-bottom: 20px;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text);
}

.user-info {
    background: var(--accent-dark);
    padding: 10px;
    border-radius: var(--radius);
    margin-bottom: 15px;
    border-left: 4px solid var(--primary);
    font-size: 0.9rem;
}

.user-info strong {
    font-weight: 600;
    color: var(--text);
}

.user-info small {
    color: var(--text);
    font-size: 0.75rem;
    opacity: 0.8;
}

label {
    display: block;
    margin-top: 10px;
    font-weight: bold;
    font-size: 0.875rem;
    color: var(--text);
}

input[type="text"],
input[type="email"],
textarea {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-sizing: border-box;
    font-size: 1rem;
    font-family: inherit;
    background: var(--accent);
    transition: border-color var(--transition);
}

textarea {
    min-height: 60px;
    resize: vertical;
}

input[type="text"]:focus,
input[type="email"]:focus,
textarea:focus {
    outline: none;
    border-color: var(--primary-dark);
}

input[type="checkbox"] {
    transform: scale(1.2);
    margin-right: 5px;
}

button[type="submit"] {
    width: 100%;
    padding: 10px;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    margin-top: 15px;
    font-size: 1rem;
    font-weight: bold;
    transition: var(--transition);
}

button[type="submit"]:hover {
    background: var(--primary-dark);
}

.back-btn {
    display: inline-block;
    margin-bottom: 20px;
    padding: 8px 15px;
    background: var(--primary-dark);
    color: #fff;
    text-decoration: none;
    border-radius: var(--radius);
    font-weight: 500;
    transition: var(--transition);
    position: absolute;
    top: 15px;
    left: 15px;
}

.back-btn:hover {
    background: var(--primary);
}

.message {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: var(--radius);
    text-align: center;
    font-size: 0.875rem;
    font-weight: bold;
}

.message.success {
    background: var(--success);
    color: #fff;
    border: 1px solid var(--success);
}

.message.error {
    background: var(--danger);
    color: #fff;
    border: 1px solid var(--danger);
}

@media (max-width: 600px) {
    .container {
        padding: 15px;
        width: 90%;
    }
    button[type="submit"],
    .back-btn {
        width: 100%;
        margin-top: 10px;
    }
}
</style>
</head>
<body>
<div class="container">
    <a href="users.php" class="back-btn">← Back to Users</a>
    <h2>Update User: <?= htmlspecialchars($user['name']) ?></h2>

    <div class="user-info">
        <strong>User ID:</strong> <?= $user['id'] ?><br>
        <strong>Status:</strong> <?= $user['is_active'] ? 'Active' : 'Inactive' ?><br>
        <strong>Created:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?><br>
        <small>Last updated: <?= $user['updated_at'] ? date('M d, Y H:i', strtotime($user['updated_at'])) : 'Never' ?></small>
    </div>

    <?php if($message): ?>
        <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required></label>
        <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></label>
        <label>Phone: <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></label>
        <label>Address: <textarea name="address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea></label>
        <label>Admin: <input type="checkbox" name="is_admin" <?= $user['is_admin'] ? 'checked' : '' ?>></label>
        <button type="submit">Update User</button>
    </form>
</div>
</body>
</html>