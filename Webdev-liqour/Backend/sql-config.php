<?php
$dbServer   = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName     = 'liqourstore';

$conn = new mysqli($dbServer, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection error. Please try again later.");
}

$conn->set_charset("utf8mb4");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
