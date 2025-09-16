<?php
session_start();
include('../Backend/sql-config.php');

if(!isset($_GET['liqour_id'])) die('No product specified');
$id = intval($_GET['liqour_id']);

$sql = "SELECT l.*, c.name AS category_name 
        FROM liqours l 
        JOIN liqour_categories c ON l.category_id = c.liqour_category_id
        WHERE l.liqour_id=? AND l.is_active=1";
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
/* ===== SINGLE PRODUCT PAGE STYLING ===== */
.single-product-wrapper {
    max-width: 1000px;
    margin: 50px auto;
    padding: 20px;
    display: flex;
    gap: 50px;
    flex-wrap: wrap;
    background: #f8fafc;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.single-product-wrapper .product-image {
    flex: 1 1 350px;
    max-width: 400px;
}
.single-product-wrapper .product-image img {
    width: 100%;
    border-radius: 10px;
    object-fit: cover;
}
.single-product-wrapper .product-details {
    flex: 1 1 350px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}
.single-product-wrapper .product-details h1 {
    margin-bottom: 15px;
    font-size: 2rem;
}
.single-product-wrapper .product-details p {
    margin: 8px 0;
    font-size: 1rem;
}
.single-product-wrapper .product-details .price {
    font-size: 1.3rem;
    font-weight: bold;
    margin-top: 10px;
}
.single-product-wrapper .add-to-cart-btn {
    padding: 12px 25px;
    background: #8B4513;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1rem;
    margin-top: 20px;
    transition: all 0.2s ease-in-out;
}
.single-product-wrapper .add-to-cart-btn:hover {
    background: #a05c2c;
}

/* Responsive */
@media screen and (max-width: 768px){
    .single-product-wrapper {
        flex-direction: column;
        gap: 30px;
    }
    .single-product-wrapper .product-image,
    .single-product-wrapper .product-details {
        flex: 1 1 100%;
    }
}
</style>
</head>
<body>
    
  <nav class="nav-bar">
          <a href="index.php"><div class="logo-container"><img src="src\icons\icon.svg" alt="LiquorStore Logo">    </div></a>

    <div class="nav-options-container nav-options-font">
      <div class="nav-option"><a href="index.php#new-arrivals">NEW ARRIVALS</a></div>
      <div class="nav-option"><a href="index.php#liquor">LIQUOR</a></div>
      <div class="nav-option"><a href="index.php#categories">CATEGORIES</a></div>
    </div>
<!-- profile cart -->
    <div class="profile-search-cart">
      <div class="profile-container">
        <div class="profile">👤</div>
        <div class="profile-expand">
            <p><a href="profile.php">Profile</a></p>
<p><a href="#" onclick="showLogoutModal()">Logout</a></p>
            <p><a href="my-orders.php">My Orders</a></p>
        </div>
      </div>

           <div class="nav-search-icon" style="cursor:pointer; margin-left:15px;" onclick="scrollToLiquorSearch()">
  🔍
</div>




      <div class="cart-container cart-link">
        <a href="cart.php"><div class="cart">🛒</div></a>
        <div class="cartLengthDisplay cart-count">0</div>
      </div>
    </div>
  </nav>

<div class="single-product-wrapper">
    <div class="product-image">
        <img src="<?= htmlspecialchars($row['image_url'] ?: 'src/product-images/default.jpg') ?>" alt="<?= htmlspecialchars($row['name']) ?>">
    </div>
    <div class="product-details">
        <h1><?= htmlspecialchars($row['name']) ?></h1>
        <p><strong>Category:</strong> <?= htmlspecialchars($row['category_name']) ?></p>
        <p class="price"><strong>Price:</strong> $<?= number_format($row['price'], 2) ?></p>
        <p><strong>Description:</strong> <?= htmlspecialchars($row['description'] ?: 'No description available.') ?></p>
        <button class="add-to-cart-btn" onclick="addToCart('<?= $row['liqour_id'] ?>','<?= htmlspecialchars($row['name']) ?>',<?= $row['price'] ?>,'<?= htmlspecialchars($row['image_url']) ?>')">
            Add to Cart
        </button>
    </div>
</div>



<script>
const cartCountEl = document.querySelector(".cart-count");
const userId = "<?= $_SESSION['userId'] ?>";
let cartItems = JSON.parse(localStorage.getItem(`cartItems_${userId}`)) || [];

// Update the cart count display
function updateCartCount() {
    const total = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    cartCountEl.textContent = total;
    cartCountEl.style.display = total > 0 ? "inline-block" : "none";
}

// Initial update on page load
updateCartCount();

// Add to cart function
function addToCart(id, name, price, img){
    const existing = cartItems.find(i => i.id === id);
    if(existing){
        existing.quantity++;
    } else {
        cartItems.push({id, name, price, img, quantity:1});
    }
    localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
    updateCartCount(); // <-- update count in nav-bar
    showToast(`${name} added to cart 🛒`);
}

// Optional: small toast notification (like index.php)
function showToast(message) {
    let toastContainer = document.getElementById('toast-container');
    if(!toastContainer){
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.position = 'fixed';
        toastContainer.style.bottom = '20px';
        toastContainer.style.right = '20px';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.background = "#8B4513";
    toast.style.color = "#fff";
    toast.style.padding = "12px 20px";
    toast.style.marginTop = "10px";
    toast.style.borderRadius = "8px";
    toast.style.boxShadow = "0 2px 6px rgba(0,0,0,0.3)";
    toast.style.opacity = "0";
    toast.style.transition = "opacity 0.3s ease";

    toastContainer.appendChild(toast);

    requestAnimationFrame(() => { toast.style.opacity = "1"; });

    setTimeout(() => {
        toast.style.opacity = "0";
        toast.addEventListener("transitionend", () => toast.remove());
    }, 2000);
}

// Optional: Logout modal functions
function showLogoutModal() {
    document.getElementById('logoutModal').style.display = 'flex';
}
function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}
function logoutNow() {
    window.location.href = "../Backend/auth/logout.php";
}

// Optional: Scroll to liquor search input
function scrollToLiquorSearch() {
    const searchInput = document.getElementById('liquor-search');
    if(searchInput){
        searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        searchInput.style.transition = 'all 0.5s';
        searchInput.style.backgroundColor = '#fff3cd';
        searchInput.style.borderColor = '#8B4513';
        setTimeout(() => {
            searchInput.style.backgroundColor = '';
            searchInput.style.borderColor = '';
        }, 1500);
        searchInput.focus();
    }
}
</script>
}


</body>
</html>
