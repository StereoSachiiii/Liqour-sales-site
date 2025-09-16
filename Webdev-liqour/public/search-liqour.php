<?php
session_start();
include('../Backend/sql-config.php');

if(!isset($_GET['liqour_id'])) die('No product specified');
$id = intval($_GET['liqour_id']);

$sql = "SELECT l.*, c.name AS category_name 
        FROM liqours l 
        JOIN liqour_categories c ON l.category_id = c.liqour_category_id
        WHERE l.liqour_id=? AND l.is_active=1 AND c.is_active=1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows === 0) die('Product not found');

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($row['name']) ?> | LiquorStore</title>
    <link rel="stylesheet" href="css/index.css">
    <style>
        .product-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
        }
        .product-image {
            flex: 1 1 300px;
            max-width: 400px;
        }
        .product-image img {
            width: 100%;
            border-radius: 10px;
        }
        .product-details {
            flex: 1 1 300px;
        }
        .product-details h1 {
            margin-bottom: 10px;
        }
        .product-details p {
            margin: 8px 0;
        }
        .add-to-cart-btn {
            padding: 10px 20px;
            background: #8B4513;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="product-container">
    <div class="product-image">
        <img src="<?= htmlspecialchars($row['image_url'] ?: 'src/product-images/default.jpg') ?>" alt="<?= htmlspecialchars($row['name']) ?>">
    </div>
    <div class="product-details">
        <h1><?= htmlspecialchars($row['name']) ?></h1>
        <p><strong>Category:</strong> <?= htmlspecialchars($row['category_name']) ?></p>
        <p><strong>Price:</strong> $<?= number_format($row['price'], 2) ?></p>
        <p><strong>Description:</strong> <?= htmlspecialchars($row['description'] ?: 'No description available.') ?></p>
        <button class="add-to-cart-btn" onclick="addToCart('<?= $row['liqour_id'] ?>','<?= htmlspecialchars($row['name']) ?>',<?= $row['price'] ?>,'<?= htmlspecialchars($row['image_url']) ?>')">
            Add to Cart
        </button>
    </div>
</div>

<script>
const userId = "<?= $_SESSION['userId'] ?>";
let cartItems = JSON.parse(localStorage.getItem(`cartItems_${userId}`)) || [];

function addToCart(id, name, price, img) {
    const existing = cartItems.find(i => i.id === id);
    if(existing) {
        existing.quantity++;
    } else {
        cartItems.push({id, name, price, img, quantity: 1});
    }
    localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
    alert(`${name} added to cart 🛒`);
}
</script>

</body>
</html>
