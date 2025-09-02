<?php
session_start();
include("sql-config.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['login']) || $_SESSION['login'] !== 'success') {
    header('Location: /process-login.php');
    exit();
}

if (isset($_GET['id'])) {
    $cid = $_GET['id'];
    
    // Delete from database
    $sql = "DELETE FROM liqour_categories WHERE liqour_category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $cid);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        header("Location: ../Backend/manage-site.php");
        exit();
    } else {
        ?>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: white; color: black; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
                .error { border: 1px solid black; padding: 30px; background: white; text-align: center; }
                a { color: black; text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class="error">
                <p>Delete failed</p>
                <a href="../Backend/manage-site.php">Back to Dashboard</a>
            </div>
        </body>
        </html>
        <?php
    }
} else {
    header("Location: ../Backend/manage-site.php");
    exit();
}
?>