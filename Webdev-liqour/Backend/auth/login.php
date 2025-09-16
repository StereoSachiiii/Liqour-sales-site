<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
    $secureUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location:$secureUrl");
    exit();
}

session_start();
include('../sql-config.php');

$role = isset($_POST['role']) && $_POST['role'] === 'admin' ? 'admin' : 'user';

if (empty($_POST['username']) || empty($_POST['password'])) {
    $_SESSION['error'] = "Missing username or password.";
    $redirect = $role === 'admin' ? '../adminlogin.php' : '../../public/login-signup.php';
    header("Location: $redirect");
    exit();
}

$username = trim($_POST['username']);
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE name=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();

$redirect = $role === 'admin' ? '../adminlogin.php' : '../../public/login-signup.php';

if ($res && $res->num_rows === 1) {
    $user = $res->fetch_assoc();
    if (password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true); 
        $_SESSION['login'] = "success";
        $_SESSION['userId'] = $user['id'];
        $_SESSION['username'] = $user['name'];

        if ((int)$user['is_admin'] === 1) {
            $_SESSION['is_admin'] = true;
            $_SESSION['Admin'] = "Admin login successful.";
            header("Location: ../manage-dashboard.php");
            exit();
        } else {
            $_SESSION['is_admin'] = false;
            header("Location: ../../public/index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Incorrect password.";
        header("Location: $redirect");
        exit();
    }
} else {
    $_SESSION['error'] = "User not found.";
    header("Location: $redirect");
    exit();
}
?>
