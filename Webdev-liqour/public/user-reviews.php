<?php
session_start();
if(!isset($_SESSION['userId'], $_SESSION['username'])){
    header("Location: public/login-signup.php");
    exit();
}

include("../Backend/sql-config.php");

$userId = $_SESSION['userId'];

if(!isset($_GET['liqour_id']) || empty($_GET['liqour_id'])){
    die("No product selected.");
}

$liqourId = intval($_GET['liqour_id']);
$productName = htmlspecialchars($_GET['product_name'] ?? '');

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = ? AND oi.liqour_id = ? AND o.status = 'completed'
");
$stmt->bind_param("ii", $userId, $liqourId);
$stmt->execute();
$stmt->bind_result($fulfilledCount);
$stmt->fetch();
$stmt->close();

if($fulfilledCount == 0){
    die("You can only review products from fulfilled orders.");
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND liqour_id = ? AND is_active = 1");

    $stmt->bind_param("ii", $userId, $liqourId);
    $stmt->execute();
    $stmt->bind_result($existingCount);
    $stmt->fetch();
    $stmt->close();

    if($existingCount > 0){
        $error = "You have already reviewed this product.";
    } else {
        if($rating < 1 || $rating > 5) $rating = 5;

        $stmt = $conn->prepare("INSERT INTO reviews (user_id, liqour_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiis", $userId, $liqourId, $rating, $comment);
        $stmt->execute();
        $stmt->close();

        $success = "Review submitted successfully!";
    }
}

$reviews = [];
$result = $conn->query("
    SELECT r.rating, r.comment, u.name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id
    WHERE r.liqour_id = $liqourId AND r.is_active = 1
    ORDER BY r.created_at DESC
");
if($result){
    while($row = $result->fetch_assoc()){
        $reviews[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Review <?= $productName ?></title>
<link rel="stylesheet" href="css/user-reviews.css">
<link rel="stylesheet" href="css/index.css">
<style>

</style>
</head>
<body> 
<!-- Navbar Start -->
<nav class="nav-bar">
    <a href="index.php" class="logo-container">
        <img src="src/icons/icon.svg" alt="LiquorStore Logo">
    </a>

    <div class="nav-options-container">
        <div class="nav-option"><a href="index.php">HOME</a></div>
        <div class="nav-option"><a href="new-arrivals.php">NEW ARRIVALS</a></div>
        <div class="nav-option"><a href="categories.php">CATEGORIES</a></div>
        <div class="nav-option"><a href="my-orders.php">MY ORDERS</a></div>
        <div class="nav-option"><a href="contact.php">CONTACT</a></div>
    </div>

    <div class="profile-search-cart">
        <div class="profile-container">
            <div class="profile">👤</div>
            <div class="profile-expand">
                <p>Hello, <?= htmlspecialchars($_SESSION['username']) ?></p>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
                <p><a href="wishlist.php">My Wishlist</a></p>
            </div>
        </div>

        <div class="cart-container">
            <div class="cart">🛒</div>
            <div class="cart-count">
                <?php 
                    echo isset($_SESSION['cart_count']) ? intval($_SESSION['cart_count']) : 0;
                ?>
            </div>
        </div>
    </div>
</nav>
<!-- Navbar End -->


<h2>Review: <?= $productName ?></h2>

<?php if(isset($success)): ?>
    <p class="success"><?= $success ?></p>
    <p><a href="my-orders.php">← Back to Orders</a></p>
<?php elseif(isset($error)): ?>
    <p class="error"><?= $error ?></p>
<?php else: ?>
<form method="post">
    <label for="rating">Rating (1-5)</label>
    <input type="number" name="rating" id="rating" min="1" max="5" value="5" required>

    <label for="comment">Comment</label>
    <textarea name="comment" id="comment" rows="4" placeholder="Write your review..." required></textarea>

    <button type="submit">Submit Review</button>
</form>
<?php endif; ?>

<?php if(count($reviews) > 0): ?>
<h3>Previous Reviews:</h3>
<?php foreach($reviews as $r): ?>
    <div class="review-item">
        <div class="reviewer"><?= htmlspecialchars($r['name']) ?> rated: <?= $r['rating'] ?>/5</div>
        <div><?= htmlspecialchars($r['comment']) ?></div>
    </div>
<?php endforeach; ?>
<?php endif; ?>
<script>
    
// PROFILE DROPDOWN TOGGLE
const profileContainer = document.querySelector('.profile-container');
const profileExpand = document.querySelector('.profile-expand');

profileContainer.addEventListener('click', () => {
    profileExpand.classList.toggle('profile-expand-active');
});

// CLOSE PROFILE DROPDOWN WHEN CLICK OUTSIDE
document.addEventListener('click', (e) => {
    if (!profileContainer.contains(e.target)) {
        profileExpand.classList.remove('profile-expand-active');
    }
});

// CART CLICK NAVIGATION
const cartContainer = document.querySelector('.cart-container');
cartContainer.addEventListener('click', () => {
    window.location.href = 'cart.php'; // Replace with your cart page
});

// LOGOUT LINK
const logoutLink = profileExpand.querySelector('a[href="logout.php"]');
logoutLink.addEventListener('click', (e) => {
    // Optional: confirm logout
    if(!confirm('Are you sure you want to logout?')) e.preventDefault();
});

// MY ORDERS LINK
const myOrdersLink = document.querySelector('.nav-option a[href="my-orders.php"]');
myOrdersLink.addEventListener('click', () => {
    window.location.href = 'my-orders.php';
});
</script>

</body>
</html>
