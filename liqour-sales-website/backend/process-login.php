<?php
include('sql-config.php');
session_start();

if (isset($_POST['username'], $_POST['password'])) {

    if (strlen($_POST['password']) < 8) {
        $_SESSION['error'] = "Password not strong enough";
        header("Location: ../public/login-signup.php");
        exit();
    }

    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password']; 

    $sql = "SELECT * FROM users WHERE name = '$username'";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) === 1) {
        $line = mysqli_fetch_assoc($res);

        $passwordCheck = password_verify($password, $line['password_hash']);
        $isAdmin = $line['is_admin'];

        if ($line['name'] == $username && $passwordCheck && $isAdmin) {
            $_SESSION["login"] = "success";
            $_SESSION["user_id"] = $line['id'];
            $_SESSION["username"] = $line['name'];
            $_SESSION["is_admin"] = true;

            header("Location: /admin-dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid credentials or not an admin.";
            header("Location: ../public/login-signup.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "User not found.";
        header("Location: ../public/login-signup.php");
        exit();
    }

} else {
    $_SESSION['error'] = "Please provide all the details.";
    header("Location: ../public/login-signup.php");
    exit();
}
?>
