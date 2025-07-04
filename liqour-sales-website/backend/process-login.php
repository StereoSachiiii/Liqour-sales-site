<?php
include('sql-config.php');
session_start();

if (isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $user = $res->fetch_assoc();

        if (password_verify($password, $user['password_hash'])) {
            if ((int)$user['is_admin'] === 0) {
                $_SESSION["login"] = "success";
                $_SESSION["user_id"] = $user['id'];
                $_SESSION["username"] = $user['name'];
                $_SESSION["is_admin"] = false;

                header("Location: ../index.php");
                exit();
            } else {
                $_SESSION['error'] = "Admins must log in through the admin portal.";
            }
        } else {
            $_SESSION['error'] = "Incorrect password.";
        }
    } else {
        $_SESSION['error'] = "User not found.";
    }

    header("Location: ../public/login-signup.php");
    exit();
}
?>
