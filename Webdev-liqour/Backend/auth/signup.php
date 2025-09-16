<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
    $secureUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location:$secureUrl");
    exit();
}

session_start();
include('../sql-config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Invalid CSRF token.";
    header("Location: ../../public/login-signup.php");
    exit();
}

$required = ['name','email','password','phoneNumber','address'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = "Missing required fields.";
        header("Location: ../../public/login-signup.php");
        exit();
    }
}

$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS);
$phone = preg_replace('/\D/', '', $_POST['phoneNumber']); // digits only
$password = $_POST['password'];
$isAdmin = 0;

if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $password)) {
    $_SESSION['error'] = "Password must be 8+ chars with uppercase, lowercase, number, special char.";
    header("Location: ../../public/login-signup.php");
    exit();
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $_SESSION['error'] = "Email already registered.";
    header("Location: ../../public/login-signup.php");
    exit();
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (name,email,password_hash,phone,address,is_admin) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("sssssi", $name,$email,$hash,$phone,$address,$isAdmin);
$stmt->execute();

if ($stmt->affected_rows === 1) {
    session_regenerate_id(true); // secure session

    $_SESSION['username'] = $name;
    $_SESSION['userId'] = $conn->insert_id;
    $_SESSION['signup'] = "success";
    $_SESSION['is_admin'] = false;

    header("Location: ../../public/index.php");
    exit();
} else {
    $_SESSION['error'] = "Signup failed. Please try again.";
    header("Location: ../../public/login-signup.php");
    exit();
}
?>
