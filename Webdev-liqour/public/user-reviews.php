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

    $stmt = $conn->prepare("SELECT COUNT(*) FROM reviews WHERE user_id=? AND liqour_id=?");
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
    WHERE r.liqour_id = $liqourId
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
<style>
body {
     background: #fff; 
     color: #000; 
     font-family: sans-serif; 
     padding: 20px; 
    }
h2, h3 {
     margin: 20px 0 10px; 
     font-weight: normal; 
    }
form { display: flex;
     flex-direction: column;
     }
label { margin: 10px 0 5px; 
    font-size: 0.9em; 
}
input, textarea { padding: 6px;
     margin-bottom: 10px;
      font-size: 0.9em; 
    }
button { padding: 8px; 
    background: #000; 
    color: #fff; 
    border: none; 
    cursor: pointer;
     font-size: 0.9em;
     }
.success, .error { margin: 10px 0;
     font-size: 0.9em;
     }
.review-item {
     margin-bottom: 15px; 
}
.reviewer { 
    font-weight: bold; 

    font-size: 0.9em; 
}
</style>
</head>
<body>
      <img src="src\icons\icon.svg" alt="LiquorStore Logo">

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

</body>
</html>
