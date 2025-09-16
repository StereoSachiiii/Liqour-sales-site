<?php
session_start();
$_SESSION = [];
session_destroy();
header("Location: ../../public/login-signup.php");
exit();
?>
