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
    $email = trim($_POST['email']) ?: null;
    $phone = trim($_POST['phone']) ?: null;

    if ($name) {
        // Check for duplicate name
        $stmtCheck = $conn->prepare("SELECT supplier_id FROM suppliers WHERE name=?");
        $stmtCheck->bind_param("s", $name);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows > 0) {
            $message = "Supplier name already exists. Use a different name.";
        } else {
            $stmtCheck->close();
            
            // Check for duplicate email if provided
            if ($email) {
                $stmtEmailCheck = $conn->prepare("SELECT supplier_id FROM suppliers WHERE email=?");
                $stmtEmailCheck->bind_param("s", $email);
                $stmtEmailCheck->execute();
                $stmtEmailCheck->store_result();
                if ($stmtEmailCheck->num_rows > 0) {
                    $message = "Email already exists. Use a different email.";
                    $stmtEmailCheck->close();
                } else {
                    $stmtEmailCheck->close();
                    
                    $stmt = $conn->prepare("INSERT INTO suppliers (name, email, phone, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())");
                    $stmt->bind_param("sss", $name, $email, $phone);

                    if ($stmt->execute()) {
                        echo "<script>
                            alert('Supplier added successfully!');
                            window.location.href='../manage-dashboard.php#suppliers';
                        </script>";
                        exit();
                    } else {
                        $message = "Error adding supplier: " . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO suppliers (name, email, phone, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())");
                $stmt->bind_param("sss", $name, $email, $phone);

                if ($stmt->execute()) {
                    echo "<script>
                        alert('Supplier added successfully!');
                        window.location.href='../manage-dashboard.php#suppliers';
                    </script>";
                    exit();
                } else {
                    $message = "Error adding supplier: " . $stmt->error;
                }
                $stmt->close();
            }
        }
        if (!$stmtCheck->num_rows > 0) {
            $stmtCheck->close();
        }
    } else {
        $message = "Supplier name is required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Supplier</title>
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
    padding: 1rem;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    color: var(--text);
}

.container {
    max-width: 500px;
    width: 100%;
    background: var(--bg);
    padding: 2rem;
    border-radius: var(--radius);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
    position: relative;
}

h2 {
    text-align: center;
    margin-bottom: 1.5rem;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text);
}

label {
    display: block;
    margin-bottom: 1rem;
    font-weight: 500;
    color: var(--text);
}

input[type="text"], input[type="email"] {
    width: 100%;
    padding: 0.5rem;
    margin-top: 0.3rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--accent);
    font-size: 0.9rem;
    font-family: inherit;
    transition: border-color var(--transition);
}

input[type="text"]:focus, input[type="email"]:focus {
    outline: none;
    border-color: var(--primary-dark);
}

button {
    display: block;
    width: 100%;
    padding: 0.75rem;
    background: var(--primary-dark);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    transition: var(--transition);
}

button:hover {
    background: var(--primary);
}

.back-btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: var(--primary-dark);
    color: #fff;
    text-decoration: none;
    border-radius: var(--radius);
    font-size: 0.85rem;
    font-weight: 500;
    position: absolute;
    top: 1rem;
    left: 1rem;
    transition: var(--transition);
}

.back-btn:hover {
    background: var(--primary);
}

.message {
    padding: 0.75rem;
    margin-bottom: 1rem;
    border-radius: var(--radius);
    text-align: center;
    font-size: 0.9rem;
    font-weight: 500;
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

@media (max-width: 500px) {
    .container {
        padding: 1.5rem;
        width: 90%;
    }
    .back-btn {
        position: static;
        margin-bottom: 1rem;
    }
}
</style>
</head>
<body>

<div class="container">
<a href="suppliers.php" class="back-btn">← Back to Suppliers</a>

<h2>Add New Supplier</h2>

<?php if($message): ?>
<div class="message error"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post">
    <label>Name: <input type="text" name="name" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"></label>
    <label>Email: <input type="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"></label>
    <label>Phone: <input type="text" name="phone" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>"></label>
    <button type="submit">Add Supplier</button>
</form>

</div>
</body>
</html>