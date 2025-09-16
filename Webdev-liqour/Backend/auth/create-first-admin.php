<?php
include('../sql-config.php');
session_start();

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? "Unknown error"));
}

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
    $secureUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $secureUrl");
    exit();
}

if (isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['phone'], $_POST['address'])) {
    $name     = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $email    = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'];
    $phone    = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_NUMBER_INT));
    $address  = trim(filter_input(INPUT_POST, 'address', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: create-first-admin.php");
        exit();
    }

    if (!$name || !$email || !$password || !$phone || !$address) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: create-first-admin.php");
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, phone, address, is_admin, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())");
    $stmt->bind_param("sssss", $name, $email, $password_hash, $phone, $address);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Admin created successfully.";
        header("Location: ../adminlogin.php");
        exit();
    } else {
        $_SESSION['error'] = "Error creating admin: " . $stmt->error;
        header("Location: create-first-admin.php");
        exit();
    }
}
?>

<?php if (isset($_SESSION['error'])): ?>
    <div style="color: red; margin-bottom: 1rem;">
        <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div style="color: green; margin-bottom: 1rem;">
        <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<form method="POST" action="create-first-admin.php">
  <input name="name" placeholder="Name" required><br><br>
  <input name="email" type="email" placeholder="Email" required><br><br>
  <input name="password" type="password" placeholder="Password" required><br><br>
  <input name="phone" placeholder="Phone" required><br><br>
  <input name="address" placeholder="Address" required><br><br>
  <button type="submit">Create Admin</button>
</form>
