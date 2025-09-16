<?php
session_start();
include("../sql-config.php");

if (!isset($_SESSION['login']) || $_SESSION['login'] !== 'success') {
    header('Location: ../adminlogin.php');
    exit();
}

if (isset($_GET['id'])) {
    $cid = $_GET['id'];

    // Check if category has products
    $sqlCheck = "SELECT COUNT(*) FROM liqours WHERE category_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param('i', $cid);
    $stmtCheck->execute();
    $stmtCheck->bind_result($count);
    $stmtCheck->fetch();
    $stmtCheck->close();

    // Determine if hard delete is requested
    $hardDelete = isset($_GET['hard']) && $_GET['hard'] == 1;

    if ($count > 0 && !$hardDelete) {
        // Cannot delete if products exist, unless hard delete
        ?>
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: white;
                    color: black;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .error {
                    border: 1px solid black;
                    padding: 30px;
                    background: white;
                    text-align: center;
                }
                a {
                    color: black;
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class="error">
                <p>Cannot delete category: it has products assigned.</p>
                <a href="../manage-dashboard.php">Back to Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }

    if ($hardDelete) {
        // Hard delete
        $sqlDel = "DELETE FROM liqour_categories WHERE liqour_category_id = ?";
    } else {
        // Soft delete
        $sqlDel = "UPDATE liqour_categories SET is_active = 0 WHERE liqour_category_id = ?";
    }

    $stmtDel = $conn->prepare($sqlDel);
    $stmtDel->bind_param('i', $cid);
    $stmtDel->execute();

    if ($stmtDel->affected_rows > 0) {
        header("Location: ../manage-dashboard.php");
        exit();
    } else {
        echo "<p>Delete failed. <a href='../manage-dashboard.php'>Back to Dashboard</a></p>";
    }
    $stmtDel->close();

} else {
    header("Location: ../manage-dashboard.php");
    exit();
}
?>
