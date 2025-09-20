<?php
session_start();

// Default: guest
$isGuest = true;
$username = 'Guest';
$userId = null;

// Check if user is logged in (not guest)
if (isset($_SESSION['userId']) && isset($_SESSION['username']) && 
    isset($_SESSION['isGuest']) && $_SESSION['isGuest'] === false) {
    $isGuest = false;
    $username = $_SESSION['username'];
    $userId = $_SESSION['userId'];
}

// Handle guest users
if ($isGuest) {
    if (!isset($_SESSION['guestId'])) {
        $_SESSION['isGuest'] = true;
        $_SESSION['guestId'] = 'guest_' . time() . '_' . rand(1000, 9999);
        $_SESSION['username'] = 'Guest';
    }
    $userId = $_SESSION['guestId'];
}

include("../Backend/sql-config.php");

// Validate category ID
if (!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    die("Invalid category.");
}
$categoryId = (int)$_GET['category_id'];

// Fetch category info
$catStmt = $conn->prepare("SELECT name FROM liqour_categories WHERE liqour_category_id = ? AND is_active = 1");
$catStmt->bind_param("i", $categoryId);
$catStmt->execute();
$catResult = $catStmt->get_result();
$category = $catResult->fetch_assoc();
$catStmt->close();

if (!$category) {
    die("Category not found.");
}

// Fetch products with stock information
$stmt = $conn->prepare("SELECT l.liqour_id, l.name, l.description, l.price, l.image_url,
                        COALESCE(SUM(s.quantity), 0) as total_stock
                        FROM liqours l
                        LEFT JOIN stock s ON l.liqour_id = s.liqour_id AND s.is_active = 1
                        WHERE l.category_id = ? AND l.is_active = 1
                        GROUP BY l.liqour_id, l.name, l.description, l.price, l.image_url
                        ORDER BY l.liqour_id DESC");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($category['name']); ?> - LiquorStore</title>
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="css/category.css">
 
</head>
<body>

  <!-- Header strip -->
  <div class="header-strip">Welcome to LiquorStore! Free delivery on orders over $50</div>

  <!-- Navbar -->
 <?php include('navbar.php'); ?>
  <!-- Feedback + socials -->
  <section class="feedback-socials">
    <div><a href="feedback.php">📩 Take a look at our feedback!</a></div>
    <div class="social-media-links">
      <p>🌐 Follow us:</p>
      <a href="#">Facebook</a>
      <a href="#">Instagram</a>
      <a href="#">twitter</a>
    </div>
  </section>

  <!-- Category products -->
  <section class="new">
    <h2 class="title-text">🍷 <?= htmlspecialchars($category['name']); ?> Collection</h2>
    <h3>Discover our premium selection of <?= htmlspecialchars($category['name']); ?> products</h3>
    <small>Found <?= htmlspecialchars((string)$result->num_rows); ?> results</small>
    <br><br>

    <div class="new-arrivals">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php 
          $stock = (int)$row['total_stock'];
          $stockClass = $stock === 0 ? 'out-of-stock' : ($stock <= 5 ? 'low-stock' : 'in-stock');
          $stockText = $stock === 0 ? 'Out of Stock' : ($stock <= 5 ? "Low Stock ($stock)" : "In Stock ($stock)");
          $cardClass = $stock === 0 ? 'new-item out-of-stock' : 'new-item';
          ?>
          <div class='<?= $cardClass ?>' data-product-id='<?= $row['liqour_id'] ?>'>
              <button class='wishlist-btn' onclick='toggleWishlist(<?= $row['liqour_id'] ?>, "<?= addslashes($row['name']) ?>", <?= $row['price'] ?>, "<?= addslashes($row['image_url']) ?>")'>
                  <span class='heart'>♡</span>
              </button>
              <div class='stock-status <?= $stockClass ?>'><?= $stockText ?></div>
              <div class='image-container' style='background-image:url(<?= htmlspecialchars($row['image_url']) ?>)'></div>
              <div class='description'>
                <?= htmlspecialchars($row['name']) ?>
                <?php if (!empty($row['description'])): ?>
                  <br><small style="color: #666;"><?= htmlspecialchars($row['description']) ?></small>
                <?php endif; ?>
              </div>
              <div class='product-price'>$<?= number_format($row['price'], 2) ?></div>
              
              <div class='quantity-controls'>
                  <button class='quantity-btn' onclick='updateProductQuantity(<?= $row['liqour_id'] ?>, -1)' <?= $stock === 0 ? 'disabled' : '' ?>>-</button>
                  <div class='quantity-display' id='qty-<?= $row['liqour_id'] ?>'>1</div>
                  <button class='quantity-btn' onclick='updateProductQuantity(<?= $row['liqour_id'] ?>, 1)' <?= $stock === 0 ? 'disabled' : '' ?>>+</button>
              </div>
              
              <div class='action-buttons'>
                  <button class='btn-primary' onclick='addToCartWithQuantity(<?= $row['liqour_id'] ?>, "<?= addslashes($row['name']) ?>", <?= $row['price'] ?>, "<?= addslashes($row['image_url']) ?>", <?= $stock ?>)' <?= $stock === 0 ? 'disabled' : '' ?>>
                      <?= $stock === 0 ? 'Out of Stock' : 'Add to Cart' ?>
                  </button>
                  <button class='btn-secondary' onclick='viewProduct(<?= $row['liqour_id'] ?>)'>View Details</button>
                  <button class='btn-wishlist' onclick='toggleWishlist(<?= $row['liqour_id'] ?>, "<?= addslashes($row['name']) ?>", <?= $row['price'] ?>, "<?= addslashes($row['image_url']) ?>")'>
                      ♡ Add to Wishlist
                  </button>
                  <button class='btn-secondary' onclick='viewReviews(<?= $row['liqour_id'] ?>)'>Reviews</button>
              </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="new-item" style="text-align: center; padding: 50px 20px;">
          <h3 style="margin-bottom: 15px; color: #666;">No products in this category yet</h3>
          <p style="margin-bottom: 20px; color: #999;">Check back later for new arrivals!</p>
          <a href="index.php" style="padding: 12px 25px; background: #8B4513; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Browse All Products</a>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Logout Modal -->
  <div id="logoutModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:#fff; padding:20px; border-radius:10px; text-align:center; min-width:300px;">
      <h3>Confirm Logout</h3>
      <p>Are you sure you want to logout?</p>
      <button onclick="logoutNow()" style="padding:8px 16px; background:#8B4513; color:white; border:none; border-radius:5px; margin-right:10px;">Yes</button>
      <button onclick="closeLogoutModal()" style="padding:8px 16px; background:#ccc; color:#333; border:none; border-radius:5px;">Cancel</button>
    </div>
  </div>

  <!-- Toast container -->
  <div id="toast-container"></div>

  <!-- Footer -->
  <footer class="feedback-socials" style="justify-content:center;">
    <p>© 2025 LiquorStore. All rights reserved.</p>
  </footer>

  <!-- Scripts -->
  <script>
    // Profile expand toggle
    document.querySelector(".profile-container").addEventListener("click", () => {
      document.querySelector(".profile-expand").classList.toggle("profile-expand-active");
    });

    // Cart handling
    const cartCountEl = document.querySelector(".cart-count");
    const userId = "<?= $userId ?? 'guest_' . time(); ?>";
    const isGuest = <?= $isGuest ? 'true' : 'false'; ?>;
    let cartItems = JSON.parse(localStorage.getItem(`cartItems_${userId}`)) || [];
    let wishlistItems = JSON.parse(localStorage.getItem(`wishlist_${userId}`)) || [];

    // Product quantity tracking for each item
    const productQuantities = {};

    function updateCartCount() {
      const total = cartItems.reduce((sum, item) => sum + item.quantity, 0);
      cartCountEl.textContent = total;
      cartCountEl.style.display = total > 0 ? "inline-block" : "none";
    }

    function initializeWishlistHearts() {
      wishlistItems.forEach(item => {
        const heartBtn = document.querySelector(`[data-product-id="${item.id}"] .wishlist-btn .heart`);
        const wishlistBtn = document.querySelector(`[data-product-id="${item.id}"] .wishlist-btn`);
        if (heartBtn) {
          heartBtn.textContent = '♥';
          wishlistBtn.classList.add('active');
        }
      });
    }

    function updateProductQuantity(productId, change) {
      const currentQty = productQuantities[productId] || 1;
      const newQty = Math.max(1, currentQty + change);
      productQuantities[productId] = newQty;
      
      const displayEl = document.getElementById(`qty-${productId}`);
      if (displayEl) {
        displayEl.textContent = newQty;
      }
    }

    function addToCartWithQuantity(id, name, price, img, maxStock) {
      const quantity = productQuantities[id] || 1;
      
      if (maxStock === 0) {
        showToast(`${name} is out of stock!`);
        return;
      }

      const existing = cartItems.find(i => i.id === id);
      const currentInCart = existing ? existing.quantity : 0;
      
      if (currentInCart + quantity > maxStock) {
        showToast(`Cannot add ${quantity} items. Only ${maxStock - currentInCart} more available.`);
        return;
      }

      if (existing) {
        existing.quantity += quantity;
      } else {
        cartItems.push({id, name, price, img, quantity});
      }
      
      localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
      updateCartCount();

      showToast(`${quantity}x ${name} added to cart 🛒`);
      
      // Reset quantity display to 1
      productQuantities[id] = 1;
      document.getElementById(`qty-${id}`).textContent = '1';
    }

    function toggleWishlist(id, name, price, img) {
      const existingIndex = wishlistItems.findIndex(i => i.id === id);
      const heartBtn = document.querySelector(`[data-product-id="${id}"] .wishlist-btn .heart`);
      const wishlistBtn = document.querySelector(`[data-product-id="${id}"] .wishlist-btn`);
      
      if (existingIndex > -1) {
        // Remove from wishlist
        wishlistItems.splice(existingIndex, 1);
        if (heartBtn) {
          heartBtn.textContent = '♡';
          wishlistBtn.classList.remove('active');
        }
        showToast(`${name} removed from wishlist`);
      } else {
        // Add to wishlist
        wishlistItems.push({id, name, price, img});
        if (heartBtn) {
          heartBtn.textContent = '♥';
          wishlistBtn.classList.add('active');
        }
        showToast(`${name} added to wishlist ♥`);
      }
      
      localStorage.setItem(`wishlist_${userId}`, JSON.stringify(wishlistItems));
    }

    function viewProduct(liquorId) {
      window.location.href = 'product.php?liqour_id=' + liquorId;
    }

    function viewReviews(liquorId) {
      window.location.href = 'feedback.php?liqour_id=' + liquorId;
    }

    function showToast(message) {
      const toastContainer = document.getElementById('toast-container');
      
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

      // fade in
      requestAnimationFrame(() => {
        toast.style.opacity = "1";
      });

      // remove after 3 seconds
      setTimeout(() => {
        toast.style.opacity = "0";
        toast.addEventListener("transitionend", () => toast.remove());
      }, 3000);
    }

    function showLogoutModal() {
      if(isGuest) {
        window.location.href = 'login-signup.php';
      } else {
        document.getElementById('logoutModal').style.display = 'flex';
      }
    }

    function closeLogoutModal() {
      document.getElementById('logoutModal').style.display = 'none';
    }

    function logoutNow() {
      window.location.href = "../Backend/auth/logout.php";
    }

    // Legacy function for backward compatibility
    function addToCart(id, name, price, img) {
      addToCartWithQuantity(id, name, price, img, 999); // Assume high stock for legacy calls
    }

    // Close modal if clicked outside
    window.addEventListener('click', (e) => {
      const modal = document.getElementById('logoutModal');
      if(e.target === modal) modal.style.display = 'none';
    });

    updateCartCount();
    
    // Initialize wishlist hearts on page load
    setTimeout(initializeWishlistHearts, 100);
  </script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>