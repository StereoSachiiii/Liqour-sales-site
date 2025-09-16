<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
    $secureUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $secureUrl");
    exit();
}

session_start([
    'cookie_lifetime' => 0,
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true,
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<style>
    body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f5f5f5;
        color: #111;
    }
    .main-box {
        background-color: #fff;
        padding: 40px;
        border-radius: 10px;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: 1px solid #ccc;
    }
    h1 {
        margin-bottom: 10px;
        text-align: center;
        color: #111;
    }
    p {
        text-align: center;
        color: #555;
        margin-bottom: 20px;
    }
    input {
        width: 100%;
        padding: 10px 12px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #f5f5f5;
        color: #111;
        font-size: 14px;
    }
    input::placeholder { color: #888; }
    .sign-in-button {
        width: 100%;
        padding: 10px;
        background-color: #111;
        color: #fff;
        font-weight: bold;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    .sign-in-button:hover {
        background-color: #333;
    }
    form {
        display: flex;
        flex-direction: column;
    }
</style>
</head>
<body>

<div class="main-box">
    <h1>Admin Login</h1>
    <p>Log in to manage your site!</p>
    <form action="auth/login.php" method="POST">
        <input type="text" name="username" placeholder="Username" required autocomplete="username">
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
        <input type="hidden" name="role" value="admin">
        <?php
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        ?>
        <input type="hidden" name="csrf_token" value="<?=$_SESSION['csrf_token']?>">
        <button class="sign-in-button">Sign In</button>
    </form>
</div>

</body>
</html>
