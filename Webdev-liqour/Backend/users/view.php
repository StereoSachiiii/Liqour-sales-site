<?php
include("../sql-config.php");

if(!isset($_GET['id'])){
    die("User ID not specified.");
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows === 0){
    die("User not found.");
}

$user = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View User</title>
<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    background: #f5f5f5;
    color: #111;
    display: flex;
    justify-content: center;
}
.container {
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #ddd;
}
h2 {
    margin-bottom: 20px;
    color: #333;
    text-align: center;
}
p {
    margin: 8px 0;
    line-height: 1.4;
}
strong {
    color: #555;
}
.back-btn {
    display: inline-block;
    margin-bottom: 20px;
    padding: 8px 15px;
    border-radius: 5px;
    background-color: #666;
    color: white;
    text-decoration: none;
}
.back-btn:hover {
    background-color: #444;
}
.status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 13px;
    color: white;
}
.active { background-color: #27ae60; }
.inactive { background-color: #c0392b; }
</style>
</head>
<body>
<div class="container">
    <a href="../manage-dashboard.php#users" class="back-btn">← Back to Users</a>
    <h2>View User: <?= htmlspecialchars($user['name']) ?></h2>

    <p><strong>ID:</strong> <?= $user['id'] ?></p>
    <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($user['address']) ?></p>
    <p><strong>Admin:</strong> <?= $user['is_admin'] ? 'Yes' : 'No' ?></p>
    <p><strong>Status:</strong> 
        <span class="status <?= $user['is_active'] ? 'active' : 'inactive' ?>">
            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
        </span>
    </p>
    <p><strong>Created At:</strong> <?= $user['created_at'] ?></p>
    <p><strong>Updated At:</strong> <?= $user['updated_at'] ?></p>
</div>
</body>
</html>
