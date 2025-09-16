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

// Handle POST update
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if ($name && $email) {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, address=?, is_admin=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssssii", $name, $email, $phone, $address, $is_admin, $id);
        if ($stmt->execute()) {
            $message = "User updated successfully.";
            $user = array_merge($user, ['name'=>$name,'email'=>$email,'phone'=>$phone,'address'=>$address,'is_admin'=>$is_admin]);
        } else {
            $message = "Update failed: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Name and email are required.";
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
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    padding: 20px;
    display: flex;
    justify-content: center;
}
.container {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #ddd;
}
h2 {
    color: #333;
    margin-bottom: 20px;
    text-align: center;
}
label {
    display: block;
    margin-bottom: 12px;
    font-weight: bold;
    color: #555;
}
input[type="text"], input[type="email"], textarea {
    width: 100%;
    padding: 8px 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
    box-sizing: border-box;
    background: #f5f5f5;
}
textarea { resize: vertical; min-height: 60px; }
input[type="checkbox"] { transform: scale(1.2); margin-right: 5px; }
button {
    padding: 10px 20px;
    background: #111;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
}
button:hover { background: #333; }
.back-btn {
    display: inline-block;
    margin-bottom: 15px;
    padding: 8px 15px;
    background: #666;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
}
.back-btn:hover { background: #444; }
.message {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 5px;
    text-align: center;
    color: #111;
    background: #e0ffe0;
    border: 1px solid #b0ffb0;
}
</style>
</head>
<body>
<div class="container">
    <a href="../manage-dashboard.php#users" class="back-btn">← Back to Users</a>
    <h2>Update User: <?= htmlspecialchars($user['name']) ?></h2>

    <?php if($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required></label>
        <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></label>
        <label>Phone: <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>"></label>
        <label>Address: <textarea name="address"><?= htmlspecialchars($user['address']) ?></textarea></label>
        <label>Admin: <input type="checkbox" name="is_admin" <?= $user['is_admin'] ? 'checked' : '' ?>></label>
        <button type="submit">Update User</button>
    </form>
</div>
</body>
</html>
