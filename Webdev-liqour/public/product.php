<?php
session_start();
include('../Backend/sql-config.php');

if(!isset($_GET['liqour_id'])) die('No product specified');
$id = intval($_GET['liqour_id']);

// Fetch product info with stock data
$sql = "SELECT l.*, c.name AS category_name, COALESCE(SUM(s.quantity), 0) as total_stock
        FROM liqours l 
        JOIN liqour_categories c ON l.category_id = c.liqour_category_id
        LEFT JOIN stock s ON l.liqour_id = s.liqour_id AND s.is_active = 1
        WHERE l.liqour_id=? AND l.is_active=1
        GROUP BY l.liqour_id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows === 0) die('Product not found');

$row = $result->fetch_assoc();
$stock = (int)$row['total_stock'];

// ===== USER SESSION LOGIC =====
$isGuest = true;
$username = 'Guest';
$userId = null;

if(isset($_SESSION['userId'], $_SESSION['username']) && ($_SESSION['isGuest'] ?? true) === false){
    $isGuest = false;
    $username = $_SESSION['username'];
    $userId = $_SESSION['userId'];
}

if($isGuest){
    if(!isset($_SESSION['guestId'])){
        $_SESSION['isGuest'] = true;
        $_SESSION['guestId'] = 'guest_'.time().'_'.rand(1000,9999);
        $_SESSION['username'] = 'Guest';
    }
    $userId = $_SESSION['guestId'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($row['name']) ?> | LiquorStore</title>
<link rel="stylesheet" href="css/index.css">
<link rel="stylesheet" href="css/product.css">

</head>
<body>

<?php include('navbar.php') ?>
<!-- SINGLE PRODUCT -->
<div class="single-product-wrapper">
  <div class="product-image">
    <?php
    $stockClass = $stock === 0 ? 'out-of-stock' : ($stock <= 5 ? 'low-stock' : 'in-stock');
    $stockText = $stock === 0 ? 'Out of Stock' : ($stock <= 5 ? "Low Stock ($stock)" : "In Stock ($stock)");
    $imageClass = $stock === 0 ? 'out-of-stock-overlay' : '';
    ?>
    <button class="wishlist-heart" onclick="toggleWishlist('<?= $row['liqour_id'] ?>','<?= addslashes($row['name']) ?>',<?= $row['price'] ?>,'<?= addslashes($row['image_url']) ?>')">
      <span class="heart">♡</span>
    </button>
    <div class="stock-badge <?= $stockClass ?>"><?= $stockText ?></div>
    <div class="<?= $imageClass ?>">
      <img src="<?= htmlspecialchars($row['image_url'] ?: 'src/product-images/default.jpg') ?>" alt="<?= htmlspecialchars($row['name']) ?>">
    </div>
  </div>
  
  <div class="product-details">
    <h1><?= htmlspecialchars($row['name']) ?></h1>
    
    <div class="product-info">
      <div class="info-item">
        <span class="info-label">Category:</span>
        <span class="info-value"><?= htmlspecialchars($row['category_name']) ?></span>
      </div>
      <div class="info-item">
        <span class="info-label">Product ID:</span>
        <span class="info-value">#<?= $row['liqour_id'] ?></span>
      </div>
    </div>

    <div class="price-display">$<?= number_format($row['price'], 2) ?></div>

    <div class="stock-info <?= $stockClass ?>">
      <span>📦 <?= $stockText ?></span>
      <?php if($stock > 0 && $stock <= 5): ?>
        <span style="margin-left: 10px;">⚠️ Limited quantity available!</span>
      <?php endif; ?>
    </div>

    <?php if($stock > 0): ?>
    <div class="quantity-section">
      <h4>Select Quantity:</h4>
      <div class="quantity-controls">
        <button class="quantity-btn" onclick="updateQuantity(-1)">-</button>
        <div class="quantity-display" id="quantity-display">1</div>
        <button class="quantity-btn" onclick="updateQuantity(1)">+</button>
        <span style="margin-left: 15px; color: #666; font-size: 0.9em;">
          Max: <?= $stock ?> available
        </span>
      </div>
    </div>
    <?php endif; ?>

    <div class="action-buttons">
      <button class="btn-primary" 
              onclick="addToCartWithQuantity('<?= $row['liqour_id'] ?>','<?= addslashes($row['name']) ?>',<?= $row['price'] ?>,'<?= addslashes($row['image_url']) ?>',<?= $stock ?>)"
              <?= $stock === 0 ? 'disabled' : '' ?>>
        <?= $stock === 0 ? 'Out of Stock' : 'Add to Cart' ?>
      </button>
      <button class="btn-wishlist" onclick="toggleWishlist('<?= $row['liqour_id'] ?>','<?= addslashes($row['name']) ?>',<?= $row['price'] ?>,'<?= addslashes($row['image_url']) ?>')">
        <span class="heart-btn">♡</span> Wishlist
      </button>
    </div>

    <?php if(!empty($row['description'])): ?>
    <div class="description-section">
      <h3>Product Description</h3>
      <p><?= nl2br(htmlspecialchars($row['description'])) ?></p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Logout Modal -->
<div id="logoutModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
  <div style="background:#fff; padding:20px; border-radius:10px; text-align:center; min-width:300px;">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to logout?</p>
    <button onclick="logoutNow()" style="padding:8px 16px; background:#8B4513; color:white; border:none; border-radius:5px; margin-right:10px;">Yes</button>
    <button onclick="closeLogoutModal()" style="padding:8px 16px; background:#ccc; color:#333; border:none; border-radius:5px;">Cancel</button>
  </div>
</div>

<!-- Toast -->
<div id="toast-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"></div>

<script>
// ===== Profile toggle =====
const profileContainer = document.querySelector(".profile-container");
profileContainer.addEventListener("click", ()=> {
  document.querySelector(".profile-expand").classList.toggle("profile-expand-active");
});
document.addEventListener("click", (e)=>{
  if(!profileContainer.contains(e.target)){
    document.querySelector(".profile-expand").classList.remove("profile-expand-active");
  }
});

// ===== Cart and wishlist logic =====
const cartCountEl = document.querySelector(".cart-count");
const userId = "<?= $userId ?>";
const isGuest = <?= $isGuest ? 'true' : 'false' ?>;
const maxStock = <?= $stock ?>;

let cartItems = JSON.parse(localStorage.getItem(`cartItems_${userId}`)) || [];
let wishlistItems = JSON.parse(localStorage.getItem(`wishlist_${userId}`)) || [];
let selectedQuantity = 1;

function updateCartCount(){
  const total = cartItems.reduce((sum,i)=>sum+i.quantity,0);
  cartCountEl.textContent = total;
  cartCountEl.style.display = total>0?"inline-block":"none";
}

function updateQuantity(change) {
  selectedQuantity = Math.max(1, Math.min(maxStock, selectedQuantity + change));
  document.getElementById('quantity-display').textContent = selectedQuantity;
  
  // Update button states
  const minusBtn = document.querySelector('.quantity-btn');
  const plusBtn = document.querySelectorAll('.quantity-btn')[1];
  minusBtn.disabled = selectedQuantity <= 1;
  plusBtn.disabled = selectedQuantity >= maxStock;
}

function addToCartWithQuantity(id,name,price,img,maxStock) {
  if (maxStock === 0) {
    showToast(`${name} is out of stock!`);
    return;
  }

  const existing = cartItems.find(i=>i.id===id);
  const currentInCart = existing ? existing.quantity : 0;
  
  if (currentInCart + selectedQuantity > maxStock) {
    showToast(`Cannot add ${selectedQuantity} items. Only ${maxStock - currentInCart} more available.`);
    return;
  }

  if(existing) { 
    existing.quantity += selectedQuantity; 
  } else { 
    cartItems.push({id,name,price,img,quantity:selectedQuantity}); 
  }
  
  localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
  updateCartCount();
  showToast(`${selectedQuantity}x ${name} added to cart 🛒`);
  
  // Reset quantity to 1
  selectedQuantity = 1;
  document.getElementById('quantity-display').textContent = '1';
  updateQuantity(0);
}

function toggleWishlist(id, name, price, img) {
  const existingIndex = wishlistItems.findIndex(i => i.id === id);
  const heartBtn = document.querySelector('.wishlist-heart .heart');
  const heartBtnSmall = document.querySelector('.heart-btn');
  const wishlistBtn = document.querySelector('.wishlist-heart');
  
  if (existingIndex > -1) {
    // Remove from wishlist
    wishlistItems.splice(existingIndex, 1);
    if (heartBtn) {
      heartBtn.textContent = '♡';
      wishlistBtn.classList.remove('active');
    }
    if (heartBtnSmall) heartBtnSmall.textContent = '♡';
    showToast(`${name} removed from wishlist`);
  } else {
    // Add to wishlist
    wishlistItems.push({id, name, price, img});
    if (heartBtn) {
      heartBtn.textContent = '♥';
      wishlistBtn.classList.add('active');
    }
    if (heartBtnSmall) heartBtnSmall.textContent = '♥';
    showToast(`${name} added to wishlist ♥`);
  }
  
  localStorage.setItem(`wishlist_${userId}`, JSON.stringify(wishlistItems));
}

function initializeWishlist() {
  const productId = '<?= $row['liqour_id'] ?>';
  const isInWishlist = wishlistItems.some(item => item.id === productId);
  
  if (isInWishlist) {
    const heartBtn = document.querySelector('.wishlist-heart .heart');
    const heartBtnSmall = document.querySelector('.heart-btn');
    const wishlistBtn = document.querySelector('.wishlist-heart');
    
    if (heartBtn) {
      heartBtn.textContent = '♥';
      wishlistBtn.classList.add('active');
    }
    if (heartBtnSmall) heartBtnSmall.textContent = '♥';
  }
}

function showToast(msg){
  const toastContainer = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.style.background = "#8B4513";
  toast.style.color = "#fff";
  toast.style.padding = "12px 20px";
  toast.style.marginTop = "10px";
  toast.style.borderRadius = "8px";
  toast.style.boxShadow = "0 2px 6px rgba(0,0,0,0.3)";
  toast.style.opacity = "0";
  toast.style.transition = "opacity 0.3s ease";
  toast.textContent = msg;
  
  toastContainer.appendChild(toast);
  requestAnimationFrame(()=>toast.style.opacity='1');
  setTimeout(()=>{ 
    toast.style.opacity='0'; 
    toast.addEventListener('transitionend',()=>toast.remove()); 
  }, 3000);
}

function goToSearch() {
  window.location.href = 'index.php#liquor';
}

// ===== Logout modal =====
function showLogoutModal() { 
  if(isGuest) {
    window.location.href = 'login-signup.php';
  } else {
    document.getElementById('logoutModal').style.display='flex'; 
  }
}
function closeLogoutModal() { document.getElementById('logoutModal').style.display='none'; }
function logoutNow() { window.location.href="../Backend/auth/logout.php"; }

// Close modal when clicking outside
window.addEventListener('click',(e)=>{
  const modal = document.getElementById('logoutModal');
  if(e.target===modal) modal.style.display='none';
});

// Legacy function for compatibility
function addToCart(id,name,price,img) {
  addToCartWithQuantity(id,name,price,img,maxStock);
}

// Initialize
updateCartCount();
initializeWishlist();
updateQuantity(0); // Initialize button states
</script>

</body>
</html>