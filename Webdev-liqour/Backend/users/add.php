<?php
session_start();
include('../sql-config.php');

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']) ?: null;
    $address = trim($_POST['address']) ?: null;
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if ($name && $email && $password) {
        // Check for duplicate email
        $stmtCheck = $conn->prepare("SELECT userId FROM users WHERE email=?");
        $stmtCheck->bind_param("s", $email);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows > 0) {
            $message = "Email already exists. Use a different email.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, phone, address, is_admin, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $stmt->bind_param("sssssi", $name, $email, $password_hash, $phone, $address, $is_admin);

            if ($stmt->execute()) {
                echo "<script>
                    alert('User added successfully!');
                    window.location.href='../manage-dashboard.php#users';
                </script>";
                exit();
            } else {
                $message = "Error adding user: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmtCheck->close();
    } else {
        $message = "Name, email, and password are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add User</title>
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    padding: 30px 0;
    display: flex;
    justify-content: center;
}
.container {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    max-width: 500px;
    width: 100%;
    position: relative;
}
h2 { text-align:center; margin-bottom:20px; }
label { display: block; margin-bottom: 15px; font-weight: bold; color: #555; }
input[type="text"], input[type="email"], input[type="password"], textarea {
    width: 100%;
    padding: 8px 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
    background: #f5f5f5;
    box-sizing: border-box;
    font-size: 14px;
}
textarea { min-height: 60px; resize: vertical; }
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
    display:inline-block;
    margin-bottom: 20px;
    padding: 8px 16px;
    background: #666;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    position: absolute;
    top: 15px;
    left: 15px;
}
.back-btn:hover { background:#444; }
.message {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    text-align:center;
    font-weight: bold;
}
.error { background:#ffe0e0; border:1px solid #ffb0b0; color:#900; }
.success { background:#e0ffe0; border:1px solid #b0ffb0; color:#090; }
</style>
</head>
<body>

<div class="container">
<a href="../manage-dashboard.php#users" class="back-btn">← Back to Users</a>

<h2>Add New User</h2>

<?php if($message): ?>
<div class="message error"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post">
    <label>Name: <input type="text" name="name" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"></label>
    <label>Email: <input type="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"></label>
    <label>Password: <input type="password" name="password" required></label>
    <label>Phone: <input type="text" name="phone" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"></label>
    <label>Address: <textarea name="address"><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea></label>
    <label>Admin: <input type="checkbox" name="is_admin" <?= isset($_POST['is_admin']) ? 'checked' : '' ?>></label>
    <button type="submit">Add User</button>
</form>

</div>
</body>
</html>
