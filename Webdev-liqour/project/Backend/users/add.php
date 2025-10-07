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
        // Check for duplicate email - using correct column name 'id'
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmtCheck->bind_param("s", $email);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows > 0) {
            $message = "Email already exists. Use a different email.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Fixed INSERT statement - using correct column name 'id' instead of 'userId'
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add User</title>
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

label {
    display: block;
    margin-top: 10px;
    font-weight: bold;
    font-size: 0.875rem;
    color: var(--text);
}

input[type="text"],
input[type="email"],
input[type="password"],
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
input[type="password"]:focus,
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

.error {
    background: var(--danger);
    color: #fff;
    border: 1px solid var(--danger);
}

.success {
    background: var(--success);
    color: #fff;
    border: 1px solid var(--success);
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