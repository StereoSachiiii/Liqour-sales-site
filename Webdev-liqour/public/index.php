<?php
include('session.php');
include('../Backend/sql-config.php');

// Pagination for liquor section
$liquorPage = isset($_GET['liquor_page']) ? (int)$_GET['liquor_page'] : 1;
$liquorItemsPerPage = 8; // Show 8 items per page
$liquorOffset = ($liquorPage - 1) * $liquorItemsPerPage;

// Count total liquor items for pagination
$countSql = "SELECT COUNT(*) as total FROM liqours l
             JOIN liqour_categories c ON l.category_id = c.liqour_category_id
             WHERE l.is_active = 1 AND c.is_active = 1";
$countResult = $conn->query($countSql);
$totalLiquorItems = $countResult->fetch_assoc()['total'];
$totalLiquorPages = ceil($totalLiquorItems / $liquorItemsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Liquor Store</title>
  <link rel="stylesheet" href="css/index.css">
 
</head>
<body>

  <div class="header-strip">Welcome to LiquorStore! Free delivery on orders over $50</div>

<?php include('navbar.php') ;?>


  <section class="feedback-socials">
    <div>
      <a href="feedback.php">📩 Take a look at our feedback!</a>
    </div>    
    <div class="social-media-links">
      <p>🌐 Follow us:</p>
      <a href="#">Facebook</a>
      <a href="#">Instagram</a>
      <a href="#">twitter</a>
    </div>
  </section>

  <section class="slider-container">
    <div class="slider">
      <div class="slide"><img src="src/slider/slide1.jpg" alt="Banner 1"></div>
      <div class="slide"><img src="src/slider/slide2.jpg" alt="Banner 2"></div>
      <div class="slide"><img src="src/slider/slide3.jpg" alt="Banner 3"></div>
    </div>
    <div class="slider-arrow prev">❮</div>
    <div class="slider-arrow next">❯</div>
    <div class="slider-nav">
      <div class="nav-dot"></div>
      <div class="nav-dot"></div>
      <div class="nav-dot"></div>
    </div>
    <div id="toast-container" style="
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
"></div>

  </section>

 
  <section class="new" id="new-arrivals">
    <h2 class="title-text">✨ New Arrivals</h2>
    <h3> take a look at our freshly arrived items .</h3>
    <br>
    <div class="new-arrivals">
    <?php 
    $sql = "SELECT l.*, COALESCE(SUM(s.quantity), 0) as total_stock 
            FROM liqours l
            LEFT JOIN stock s ON l.liqour_id = s.liqour_id AND s.is_active = 1
            WHERE l.is_active = 1 
            GROUP BY l.liqour_id
            ORDER BY l.liqour_id DESC LIMIT 6";

    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          $stock = (int)$row['total_stock'];
          $stockClass = $stock === 0 ? 'out-of-stock' : ($stock <= 5 ? 'low-stock' : 'in-stock');
          $stockText = $stock === 0 ? 'Out of Stock' : ($stock <= 5 ? "Low Stock ($stock)" : "In Stock ($stock)");
          $cardClass = $stock === 0 ? 'new-item out-of-stock' : 'new-item';
          
          echo "
          <div class='$cardClass' data-product-id='{$row['liqour_id']}'>
              <button class='wishlist-btn' onclick='toggleWishlist({$row['liqour_id']}, \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\")'>
                  <span class='heart'>♡</span>
              </button>
              <div class='stock-status $stockClass'>$stockText</div>
              <div class='image-container' style='background-image:url({$row['image_url']})'></div>
              <div class='description'>{$row['name']}</div>
              <div class='product-price'>\${$row['price']}</div>
              
              <div class='quantity-controls'>
                  <button class='quantity-btn' onclick='updateProductQuantity({$row['liqour_id']}, -1)' " . ($stock === 0 ? 'disabled' : '') . ">-</button>
                  <div class='quantity-display' id='qty-{$row['liqour_id']}'>1</div>
                  <button class='quantity-btn' onclick='updateProductQuantity({$row['liqour_id']}, 1)' " . ($stock === 0 ? 'disabled' : '') . ">+</button>
              </div>
              
              <div class='action-buttons'>
                  <button class='btn-primary' onclick='addToCartWithQuantity({$row['liqour_id']}, \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\", $stock)' " . ($stock === 0 ? 'disabled' : '') . ">
                      " . ($stock === 0 ? 'Out of Stock' : 'Add to Cart') . "
                  </button>
                  <button class='btn-secondary' onclick='viewProduct({$row['liqour_id']})'>View Details</button>
                  <button class='btn-wishlist' onclick='toggleWishlist({$row['liqour_id']}, \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\")'>
                      ♡ Add to Wishlist
                  </button>
                  <button class='btn-secondary' onclick='viewReviews({$row['liqour_id']})'>Reviews</button>
              </div>
          </div>";
      }
    } else {
      echo "<p>No new arrivals yet.</p>";
    }
    ?>
    </div>
  </section>


  <section class="new" id="liquor">
    <h2 class="title-text">🥃 Liquor</h2>
    <h3> checkout all the products that we have to offer !</h3>
    <br>
<div class="nav-filters-container" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
    <!-- Left: Search Box -->
    <div style="flex:1;">
        <input type="text" id="liquor-search" placeholder="Search liquor..." style="padding:5px 10px; border-radius:5px; border:1px solid #ccc; width:180px;">
        <button onclick="searchLiquorAJAX()" style="padding:5px 10px; background:#8B4513; color:white; border:none; border-radius:5px; margin-left:5px;">Search</button>
        <button onclick="resetLiquorSearch()" style="padding:5px 10px; background:#ccc; color:#333; border:none; border-radius:5px; margin-left:5px;">Reset</button>
    </div>

    <!-- Right: Filters -->
    <div style="flex:1; display:flex; justify-content:flex-end; align-items:center; gap:10px;">
        <!-- Sort -->
        <select id="sort-select" onchange="searchLiquorAJAX()" style="padding:5px; border-radius:5px; border:1px solid #ccc;">
            <option value="">Sort By</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
        </select>

        <!-- Price slider -->
        <div style="display:flex; align-items:center; gap:5px;">
            <label>Price:</label>
            <input type="number" id="min-price" placeholder="Min" style="width:60px; padding:3px 5px; border-radius:5px; border:1px solid #ccc;">
            <span>-</span>
            <input type="number" id="max-price" placeholder="Max" style="width:60px; padding:3px 5px; border-radius:5px; border:1px solid #ccc;">
            <button onclick="searchLiquorAJAX()" style="padding:3px 8px; background:#8B4513; color:white; border:none; border-radius:5px;">Apply</button>
        </div>
    </div>
</div>

    <br>
    <div class="new-arrivals" id="liquor-results">
    <?php
    $sql = "SELECT l.liqour_id, l.name, l.price, l.image_url, c.name AS category_name,
            COALESCE(SUM(s.quantity), 0) as total_stock
            FROM liqours l
            JOIN liqour_categories c ON l.category_id = c.liqour_category_id
            LEFT JOIN stock s ON l.liqour_id = s.liqour_id AND s.is_active = 1
            WHERE l.is_active = 1 AND c.is_active = 1
            GROUP BY l.liqour_id, l.name, l.price, l.image_url, c.name
            ORDER BY l.liqour_id DESC
            LIMIT $liquorItemsPerPage OFFSET $liquorOffset";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stock = (int)$row['total_stock'];
            $stockClass = $stock === 0 ? 'out-of-stock' : ($stock <= 5 ? 'low-stock' : 'in-stock');
            $stockText = $stock === 0 ? 'Out of Stock' : ($stock <= 5 ? "Low Stock ($stock)" : "In Stock ($stock)");
            $cardClass = $stock === 0 ? 'new-item out-of-stock' : 'new-item';
            
            echo "
            <div class='$cardClass' data-product-id='{$row['liqour_id']}'>
                <button class='wishlist-btn' onclick='toggleWishlist({$row['liqour_id']}, \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\")'>
                    <span class='heart'>♡</span>
                </button>
                <div class='stock-status $stockClass'>$stockText</div>
                <div class='image-container' style='background-image:url({$row['image_url']})'></div>
                <div class='description'>{$row['name']} ({$row['category_name']})</div>
                <div class='product-price'>\${$row['price']}</div>
                
                <div class='quantity-controls'>
                    <button class='quantity-btn' onclick='updateProductQuantity({$row['liqour_id']}, -1)' " . ($stock === 0 ? 'disabled' : '') . ">-</button>
                    <div class='quantity-display' id='qty-{$row['liqour_id']}'>1</div>
                    <button class='quantity-btn' onclick='updateProductQuantity({$row['liqour_id']}, 1)' " . ($stock === 0 ? 'disabled' : '') . ">+</button>
                </div>
                
                <div class='action-buttons'>
                    <button class='btn-primary' onclick='addToCartWithQuantity({$row['liqour_id']}, \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\", $stock)' " . ($stock === 0 ? 'disabled' : '') . ">
                        " . ($stock === 0 ? 'Out of Stock' : 'Add to Cart') . "
                    </button>
                    <button class='btn-secondary' onclick='viewProduct({$row['liqour_id']})'>View Details</button>
                    <button class='btn-wishlist' onclick='toggleWishlist({$row['liqour_id']}, \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\")'>
                        ♡ Wishlist
                    </button>
                    <button class='btn-secondary' onclick='viewReviews({$row['liqour_id']})'>Reviews</button>
                </div>
            </div>";
        }
    } else {
        echo "<p>No liquors available.</p>";
    }
    ?>
    </div>
    
    <!-- Pagination for Liquor Section -->
    <?php if ($totalLiquorPages > 1): ?>
    <div class="pagination">
        <!-- Previous Button -->
        <?php if ($liquorPage > 1): ?>
            <a href="?liquor_page=<?php echo $liquorPage - 1; ?>#liquor" onclick="scrollToLiquor()">« Previous</a>
        <?php else: ?>
            <span class="disabled">« Previous</span>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php 
        $startPage = max(1, $liquorPage - 2);
        $endPage = min($totalLiquorPages, $liquorPage + 2);
        
        // Show first page if we're not starting from 1
        if ($startPage > 1): ?>
            <a href="?liquor_page=1#liquor" onclick="scrollToLiquor()">1</a>
            <?php if ($startPage > 2): ?>
                <span>...</span>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Page number links -->
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <?php if ($i == $liquorPage): ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?liquor_page=<?php echo $i; ?>#liquor" onclick="scrollToLiquor()"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <!-- Show last page if we're not ending at last page -->
        <?php if ($endPage < $totalLiquorPages): ?>
            <?php if ($endPage < $totalLiquorPages - 1): ?>
                <span>...</span>
            <?php endif; ?>
            <a href="?liquor_page=<?php echo $totalLiquorPages; ?>#liquor" onclick="scrollToLiquor()"><?php echo $totalLiquorPages; ?></a>
        <?php endif; ?>

        <!-- Next Button -->
        <?php if ($liquorPage < $totalLiquorPages): ?>
            <a href="?liquor_page=<?php echo $liquorPage + 1; ?>#liquor" onclick="scrollToLiquor()">Next »</a>
        <?php else: ?>
            <span class="disabled">Next »</span>
        <?php endif; ?>
    </div>

    <!-- Page Info -->
    <div style="text-align: center; margin-top: 10px; color: #666; font-size: 14px;">
        Showing <?php echo ($liquorOffset + 1); ?> to <?php echo min($liquorOffset + $liquorItemsPerPage, $totalLiquorItems); ?> of <?php echo $totalLiquorItems; ?> products
    </div>
    <?php endif; ?>
  </section>

 
  <section class="new" id="featured">
    <h2 class="title-text">⭐ Featured </h2>
    <h3> check out our hot sales items! (high in stocks)</h3>
    <br>
    <div class="new-arrivals">
    <?php
    $sql = "SELECT l.liqour_id, l.name, l.price, l.image_url, c.name AS category_name, 
            COALESCE(SUM(s.quantity), 0) AS total_stock
            FROM liqours l
            JOIN liqour_categories c ON l.category_id = c.liqour_category_id
            LEFT JOIN stock s ON l.liqour_id = s.liqour_id AND s.is_active = 1
            WHERE l.is_active = 1 AND c.is_active = 1
            GROUP BY l.liqour_id, l.name, l.price, l.image_url, c.name
            ORDER BY total_stock DESC
            LIMIT 6";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stock = (int)$row['total_stock'];
            $stockClass = $stock === 0 ? 'out-of-stock' : ($stock <= 5 ? 'low-stock' : 'in-stock');
            $stockText = $stock === 0 ? 'Out of Stock' : ($stock <= 5 ? "Low Stock ($stock)" : "In Stock ($stock)");
            $cardClass = $stock === 0 ? 'new-item out-of-stock' : 'new-item';
            
            echo "
            <div class='$cardClass' data-product-id='{$row['liqour_id']}'>
                <button class='wishlist-btn' onclick='toggleWishlist({$row['liqour_id']}, \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\")'>
                    <span class='heart'>♡</span>
                </button>
                <div class='stock-status $stockClass'>$stockText</div>
                <div class='image-container' style='background-image:url({$row['image_url']})'></div>
                <div class='description'>{$row['name']} ({$row['category_name']})</div>
                <div class='product-price'>\${$row['price']}</div>
                
                <div class='quantity-controls'>
                    <button class='quantity-btn' onclick='updateProductQuantity({$row['liqour_id']}, -1)' " . ($stock === 0 ? 'disabled' : '') . ">-</button>
                    <div class='quantity-display' id='qty-{$row['liqour_id']}'>1</div>
                    <button class='quantity-btn' onclick='updateProductQuantity({$row['liqour_id']}, 1)' " . ($stock === 0 ? 'disabled' : '') . ">+</button>
                </div>
                
                <div class='action-buttons'>
                    <button class='btn-primary' onclick='addToCartWithQuantity({$row['liqour_id']}, \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\", $stock)' " . ($stock === 0 ? 'disabled' : '') . ">
                        " . ($stock === 0 ? 'Out of Stock' : 'Add to Cart') . "
                    </button>
                    <button class='btn-secondary' onclick='viewProduct({$row['liqour_id']})'>View Details</button>
                    <button class='btn-wishlist' onclick='toggleWishlist({$row['liqour_id']}, \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\")'>
                        ♡ Wishlist
                    </button>
                    <button class='btn-secondary' onclick='viewReviews({$row['liqour_id']})'>Reviews</button>
                </div>
            </div>";
        }
    } else {
        echo "<p>No featured products available.</p>";
    }
    ?>
    </div>
  </section>

<!-- Categories Section -->
<section class="new" id="categories">
    <h2 class="title-text">📦 Available Categories</h2>
    <h3> pick the category that you wish to explore</h3>
    <br>
    
    <div class="new-arrivals">
    <?php 
    $sql = "SELECT * FROM liqour_categories WHERE is_active=1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($rows = $result->fetch_assoc()) {
            $catId = $rows['liqour_category_id'];

            // Get first liquor image for this category
            $stmtImg = $conn->prepare("SELECT image_url FROM liqours WHERE category_id = ? AND is_active=1 ORDER BY liqour_id ASC LIMIT 1");
            $stmtImg->bind_param("i", $catId);
            $stmtImg->execute();
            $resImg = $stmtImg->get_result();
            $imagePath = "src/product-images/default.jpg"; // fallback image
            if($resImg && $resImg->num_rows > 0){
                $imgRow = $resImg->fetch_assoc();
                if(!empty($imgRow['image_url'])){
                    $imagePath = $imgRow['image_url'];
                }
            }
            $stmtImg->close();

            echo "
            <a href='category.php?category_id={$catId}' style='text-decoration:none; color:inherit;'>
                <div class='new-item'>
                    <div class='image-container' style='background-image:url({$imagePath}); height:150px; border-radius:8px;'></div>
                    <div class='description'>{$rows['name']}</div>
                </div>
            </a>";
        }
    } else {
        echo "No categories available.";
    }
    ?>
    </div>
</section>
        <div id="logoutModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
  <div style="background:#fff; padding:20px; border-radius:10px; text-align:center; min-width:300px;">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to logout?</p>
    <button onclick="logoutNow()" style="padding:8px 16px; background:#8B4513; color:white; border:none; border-radius:5px; margin-right:10px;">Yes</button>
    <button onclick="closeLogoutModal()" style="padding:8px 16px; background:#ccc; color:#333; border:none; border-radius:5px;">Cancel</button>
  </div>
</div>

<?php include('footer.php')?>

<script src="js/main.js"></script>
<script>
  document.querySelector(".profile-container").addEventListener("click", () => {
    document.querySelector(".profile-expand").classList.toggle("profile-expand-active");
  });

  const cartCountEl = document.querySelector(".cart-count");
const userId = "<?php echo $userId ?? 'guest_' . time(); ?>";
const isGuest = <?php echo $isGuest ? 'true' : 'false'; ?>; 
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

  updateCartCount();
  
  // Initialize wishlist hearts on page load
  setTimeout(initializeWishlistHearts, 100);

  function scrollToLiquor() {
    document.getElementById('liquor').scrollIntoView({ behavior: 'smooth' });
  }

  function searchProducts(){
    const query = document.getElementById("search-box").value;
    alert("Search for: " + query);
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
function scrollToLiquorSearch() {
    const searchInput = document.getElementById('liquor-search');
    searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Highlight the input box briefly
    searchInput.style.transition = 'all 0.5s';
    searchInput.style.backgroundColor = '#fff3cd';
    searchInput.style.borderColor = '#8B4513';
    setTimeout(() => {
        searchInput.style.backgroundColor = '';
        searchInput.style.borderColor = '';
    }, 1500);
    
    searchInput.focus();
}
function viewProduct(liquorId) {
    // Redirect to single product page with ID
    window.location.href = 'product.php?liqour_id=' + liquorId;
}


function logoutNow() {
    window.location.href = "../Backend/auth/logout.php";
}

// Optional: close modal if clicked outside
window.addEventListener('click', (e) => {
    const modal = document.getElementById('logoutModal');
    if(e.target === modal) modal.style.display = 'none';
});

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

// Legacy function for backward compatibility
function addToCart(id, name, price, img) {
    addToCartWithQuantity(id, name, price, img, 999); // Assume high stock for legacy calls
}

function searchLiquorAJAX() {
    const query = document.getElementById('liquor-search').value.trim();
    const sort = document.getElementById('sort-select').value;
    const minPrice = document.getElementById('min-price').value.trim();
    const maxPrice = document.getElementById('max-price').value.trim();

    const params = new URLSearchParams();
    if(query) params.append('query', query);
    if(minPrice) params.append('minPrice', minPrice);
    if(maxPrice) params.append('maxPrice', maxPrice);
    if(sort) params.append('sort', sort);

    // Add loading indicator
    const container = document.getElementById('liquor-results');
    container.innerHTML = '<div style="text-align:center; padding:20px;">🔍 Searching...</div>';

    fetch(`search-liqour.php?${params.toString()}`)
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            container.innerHTML = ''; // clear loading message

            if(!Array.isArray(data) || data.length === 0) {
                container.innerHTML = '<p>No products found.</p>';
                // Hide pagination when searching
                const pagination = document.querySelector('#liquor .pagination');
                const pageInfo = pagination ? pagination.nextElementSibling : null;
                if(pagination) pagination.style.display = 'none';
                if(pageInfo) pageInfo.style.display = 'none';
                return;
            }

            data.forEach(item => {
                // Better stock handling with debugging
                const stock = item.total_stock !== undefined ? parseInt(item.total_stock, 10) : 0;
                
                // Debug logging (remove in production)
                console.log(`Product: ${item.name}, Stock raw: ${item.total_stock}, Stock parsed: ${stock}`);
                
                const stockClass = stock === 0 ? 'out-of-stock' : (stock <= 5 ? 'low-stock' : 'in-stock');
                const stockText = stock === 0 ? 'Out of Stock' : (stock <= 5 ? `Low Stock (${stock})` : `In Stock (${stock})`);
                const cardClass = stock === 0 ? 'new-item out-of-stock' : 'new-item';

                const div = document.createElement('div');
                div.className = cardClass;
                div.setAttribute('data-product-id', item.liqour_id);
                
                // Better string escaping for onclick handlers
                const escapedName = (item.name || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                const escapedImageUrl = (item.image_url || '').replace(/"/g, '&quot;');
                const categoryDisplay = item.category_name ? ` (${item.category_name})` : '';
                
                div.innerHTML = `
                    <button class='wishlist-btn' onclick='toggleWishlist(${item.liqour_id}, "${escapedName}", ${item.price}, "${escapedImageUrl}")'>
                        <span class='heart'>♡</span>
                    </button>
                    <div class='stock-status ${stockClass}'>${stockText}</div>
                    <div class='image-container' style='background-image:url(${item.image_url || 'src/product-images/default.jpg'})'></div>
                    <div class='description'>${item.name}${categoryDisplay}</div>
                    <div class='product-price'>$${parseFloat(item.price || 0).toFixed(2)}</div>
                    
                    <div class='quantity-controls'>
                        <button class='quantity-btn' onclick='updateProductQuantity(${item.liqour_id}, -1)' ${stock === 0 ? 'disabled' : ''}>-</button>
                        <div class='quantity-display' id='qty-${item.liqour_id}'>1</div>
                        <button class='quantity-btn' onclick='updateProductQuantity(${item.liqour_id}, 1)' ${stock === 0 ? 'disabled' : ''}>+</button>
                    </div>
                    
                    <div class='action-buttons'>
                        <button class='btn-primary' onclick='addToCartWithQuantity(${item.liqour_id}, "${escapedName}", ${item.price}, "${escapedImageUrl}", ${stock})' ${stock === 0 ? 'disabled' : ''}>
                            ${stock === 0 ? 'Out of Stock' : 'Add to Cart'}
                        </button>
                        <button class='btn-secondary' onclick='viewProduct(${item.liqour_id})'>View Details</button>
                        <button class='btn-wishlist' onclick='toggleWishlist(${item.liqour_id}, "${escapedName}", ${item.price}, "${escapedImageUrl}")'>
                            ♡ Wishlist
                        </button>
                        <button class='btn-secondary' onclick='viewReviews(${item.liqour_id})'>Reviews</button>
                    </div>
                `;
                container.appendChild(div);
            });

            // Re-initialize wishlist hearts 
            setTimeout(initializeWishlistHearts, 100);

            // Hide pagination when searching
            const pagination = document.querySelector('#liquor .pagination');
            const pageInfo = pagination ? pagination.nextElementSibling : null;
            if(pagination) pagination.style.display = 'none';
            if(pageInfo) pageInfo.style.display = 'none';
        })
        .catch(err => {
            console.error('Search error:', err);
            container.innerHTML = '<div style="text-align:center; padding:20px; color:red;">❌ Error loading search results. Please try again.</div>';
        });
}
function resetLiquorSearch() {
    document.getElementById('liquor-search').value = '';
    document.getElementById('sort-select').value = '';
    document.getElementById('min-price').value = '';
    document.getElementById('max-price').value = '';
    
    // Reload the page to show original paginated results
    const currentParams = new URLSearchParams(window.location.search);
    const liquorPage = currentParams.get('liquor_page') || '1';
    window.location.href = `?liquor_page=${liquorPage}#liquor`;
}

 // Add this to index.php script section
function restoreCheckoutCart() {
  // Check if there's a pending cart and user just logged in
  const pendingCart = localStorage.getItem('pendingCheckoutCart');
  const timestamp = localStorage.getItem('pendingCheckoutTimestamp');
  
  if (pendingCart && timestamp) {
    const cartAge = Date.now() - parseInt(timestamp);
    // If cart is less than 1 hour old, restore it
    if (cartAge < 3600000) { // 1 hour in milliseconds
      const pendingItems = JSON.parse(pendingCart);
      
      // Merge with existing cart (avoid duplicates)
      pendingItems.forEach(pendingItem => {
        const existing = cartItems.find(item => item.id === pendingItem.id);
        if (existing) {
          existing.quantity += pendingItem.quantity;
        } else {
          cartItems.push(pendingItem);
        }
      });
      
      localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
      updateCartCount();
      
      // Clean up pending cart
      localStorage.removeItem('pendingCheckoutCart');
      localStorage.removeItem('pendingCheckoutTimestamp');
      
      // Show notification
      showToast('Welcome back! Your cart has been restored.');
      
      // Check if they should be redirected to checkout
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('checkout') === '1') {
        setTimeout(() => {
          window.location.href = 'cart.php';
        }, 1000);
      }
    } else {
      // Cart is too old, clean up
      localStorage.removeItem('pendingCheckoutCart');
      localStorage.removeItem('pendingCheckoutTimestamp');
    }
  }
}

// Call this when the page loads on index.php (add at the end)
if (!isGuest) {
  restoreCheckoutCart();
}

// Auto-scroll to liquor section if coming from pagination
window.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#liquor') {
        setTimeout(() => {
            scrollToLiquor();
        }, 100);
    }
});
</script>

</body>
</html>