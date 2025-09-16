<?php
session_start();
if(!isset($_SESSION['userId'], $_SESSION['username'])){
    header("Location: public/login-signup.php");
    exit();
}

include("../Backend/sql-config.php");


$userId = $_SESSION['userId'];


$orderRes = $conn->query("SELECT COUNT(*) AS total_orders FROM orders WHERE user_id=$userId");
$orderCount = $orderRes->fetch_assoc()['total_orders'];


$reviewRes = $conn->query("
    SELECT r.rating, r.comment, l.name AS liquor_name
    FROM reviews r
    JOIN liqours l ON r.liqour_id = l.liqour_id
    WHERE r.user_id=$userId
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - LiquorStore</title>
<link rel="stylesheet" href="css/index.css">
<style>
.profile-page {
    max-width: 900px;
    margin: 50px auto;
    padding: 30px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
}

.profile-page h2 {
    font-size: 2.5rem;
    color: var(--primary);
    margin-bottom: 30px;
    text-align: center;
    font-family: 'Montserrat', sans-serif;
}

.profile-section {
    margin-bottom: 30px;
    font-size: 1.2rem;
    color: var(--text-dark);
}

.profile-section a {
    color: var(--accent);
    text-decoration: none;
    font-weight: 600;
}

.profile-section a:hover {
    text-decoration: underline;
}

.review {
    border-top: 1px solid var(--light-gray);
    padding: 15px 0;
    font-size: 1rem;
    line-height: 1.4;
}

.review strong {
    color: var(--primary);
    display: block;
    margin-bottom: 5px;
}
</style>
</head>
<body>
      
    <div class="nav-options-container nav-options-font">
          <div class="nav-option"><a href="index.php">Home</a></div>

      <div class="nav-option"><a href="index.php#new-arrivals">NEW ARRIVALS</a></div>
      <div class="nav-option"><a href="index.php#liquor">LIQUOR</a></div>
      <div class="nav-option"><a href="index.php#categories">CATEGORIES</a></div>
    </div>
<div class="profile-page">
    <h2>👤 Your Profile</h2>

    <div class="profile-section">
        <strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?>
    </div>

    <div class="profile-section">
        <strong>Total Orders:</strong> <?php echo $orderCount; ?> 
        - <a href="my-orders.php">View Orders</a>
    </div>

    <div class="profile-section">
        <strong>Your Reviews:</strong>
        <?php if($reviewRes->num_rows > 0): ?>
            <?php while($row = $reviewRes->fetch_assoc()): ?>
                <div class="review">
                    <strong><?php echo htmlspecialchars($row['liquor_name']); ?></strong>
                    Rating: <?php echo $row['rating']; ?> / 5<br>
                    Comment: <?php echo htmlspecialchars($row['comment']); ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews yet.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
