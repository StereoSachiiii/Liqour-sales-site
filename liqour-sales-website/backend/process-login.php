<?php
session_start();

if (isset($_POST['username'], $_POST['password'])) {

    if (strlen($_POST['password']) < 8) {
        $_SESSION['error'] = "Password not strong enough";
        echo "{$_SESSION['error']}";
        header("Location: ../public/login-signup.php");
        exit();
    } else {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $password = $_POST['password']; 
        echo "{$username} + {$password}";
    }

} else {
    echo 'Please provide all the details';
}
?>
