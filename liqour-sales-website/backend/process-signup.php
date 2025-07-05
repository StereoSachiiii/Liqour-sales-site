<?php 
include('sql-config.php');

session_start();

if (isset($_POST['password']) && strlen($_POST['password']) > 8) {

    if (isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['phoneNumber'], $_POST['address'])) {

        $userName = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_SPECIAL_CHARS);
        $password_hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $phoneNumber = filter_input(INPUT_POST, 'phoneNumber', FILTER_SANITIZE_SPECIAL_CHARS);
        $isAdmin = 0; 

        try {
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, phone, address, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("sssssi", $userName, $email, $password_hashed, $phoneNumber, $address, $isAdmin);
            $stmt->execute();

            if ($stmt->error) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            if ($stmt->affected_rows === 1) {
                $confirm = $conn->prepare("SELECT * FROM users WHERE name = ?");
                if (!$confirm) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $confirm->bind_param("s", $userName);
                $confirm->execute();

                $res = $confirm->get_result();
                if ($res->num_rows === 1) {
                    $line = $res->fetch_assoc();

                    $_SESSION['username'] = $line['name'];
                    $_SESSION['userId'] = $line['id'];
                    $_SESSION['signup'] = "success";
                    $_SESSION['is_admin'] = false;

                    header("Location: ../index.php");
                    exit();
                } else {
                    $_SESSION['error'] = "Failed to confirm user after signup.";
                    header("Location: ../public/login-signup.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Invalid credentials or user not inserted.";
                header("Location: ../public/login-signup.php");
                exit();
            }

        } catch (Exception $e) {
            
            echo "Error: " . $e->getMessage();
            exit();
        }
    } else {
        $_SESSION['error'] = "Missing required fields.";
        header("Location: ../public/login-signup.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Password not strong enough or missing.";
    header("Location: ../public/login-signup.php");
    exit();
}
?>
