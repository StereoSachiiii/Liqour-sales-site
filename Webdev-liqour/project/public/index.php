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
<div class="nav-filters-container" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; gap:10px; flex-wrap:wrap;">
    <!-- Search Input with real-time search -->
    <div style="flex:1; min-width:200px;">
        <input type="text" 
               id="liquor-search" 
               placeholder="Search liquor..." 
               oninput="searchLiquorRealTime()"
               style="padding:8px 12px; border-radius:5px; border:1px solid #ccc; width:100%; max-width:250px;">
        <button onclick="resetLiquorSearch()" style="padding:8px 12px; background:#ccc; color:#333; border:none; border-radius:5px; margin-left:5px;">Clear</button>
    </div>

    <!-- Filters with real-time change -->
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <!-- Category Filter -->
        <select id="category-filter" onchange="searchLiquorRealTime()" style="padding:8px; border-radius:5px; border:1px solid #ccc;">
            <option value="">All Categories</option>
            <?php
            $catSql = "SELECT liqour_category_id, name FROM liqour_categories WHERE is_active=1 ORDER BY name";
            $catResult = $conn->query($catSql);
            if ($catResult && $catResult->num_rows > 0) {
                while($cat = $catResult->fetch_assoc()) {
                    echo "<option value='{$cat['liqour_category_id']}'>{$cat['name']}</option>";
                }
            }
            ?>
        </select>
        
        <!-- Sort -->
        <select id="sort-select" onchange="searchLiquorRealTime()" style="padding:8px; border-radius:5px; border:1px solid #ccc;">
            <option value="">Default</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
            <option value="name_asc">Name: A-Z</option>
            <option value="name_desc">Name: Z-A</option>
            <option value="stock_desc">Most in Stock</option>
            <option value="newest">Newest First</option>
        </select>

        <!-- Price Range with real-time -->
        <div style="display:flex; align-items:center; gap:5px;">
            <label style="font-size:12px;">Price:</label>
          <input type="text" 
       id="min-price" 
       placeholder="Min" 
       oninput="searchLiquorRealTime()"
       style="width:60px; padding:6px; border-radius:4px; border:1px solid #ccc;">
<span>-</span>
<input type="text" 
       id="max-price" 
       placeholder="Max" 
       oninput="searchLiquorRealTime()"
       style="width:60px; padding:6px; border-radius:4px; border:1px solid #ccc;">
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
    <h3>pick the category that you wish to explore</h3>
    <br>
    
    <div class="new-arrivals">
    <?php 
    $sql = "SELECT * FROM liqour_categories WHERE is_active=1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($rows = $result->fetch_assoc()) {
            $catId = $rows['liqour_category_id'];
            
            // Use category's own image if available, otherwise fallback to first liquor image
            $imagePath = "src/product-images/default.jpg"; // default fallback
            
            // Check if category has its own image
            if (!empty($rows['image_url'])) {
                // Category has its own image - handle the path correctly
                $categoryImagePath = $rows['image_url'];
                
                // If it starts with 'public/', remove that prefix since we're already in the public directory
                if (strpos($categoryImagePath, 'public/') === 0) {
                    $imagePath = substr($categoryImagePath, 7); // Remove 'public/' prefix
                } else {
                    $imagePath = $categoryImagePath;
                }
            } else {
                // Fallback to first liquor image for this category
                $stmtImg = $conn->prepare("SELECT image_url FROM liqours WHERE category_id = ? AND is_active=1 ORDER BY liqour_id ASC LIMIT 1");
                $stmtImg->bind_param("i", $catId);
                $stmtImg->execute();
                $resImg = $stmtImg->get_result();
                if($resImg && $resImg->num_rows > 0){
                    $imgRow = $resImg->fetch_assoc();
                    if(!empty($imgRow['image_url'])){
                        $imagePath = $imgRow['image_url'];
                    }
                }
                $stmtImg->close();
            }
            
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

  function setCookie(name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + expires.toUTCString() + ';path=/';
}

function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) {
            const value = c.substring(nameEQ.length, c.length);
            try {
                return JSON.parse(decodeURIComponent(value));
            } catch(e) {
                return [];
            }
        }
    }
    return [];
}
  document.querySelector(".profile-container").addEventListener("click", () => {
    document.querySelector(".profile-expand").classList.toggle("profile-expand-active");
  });

  const cartCountEl = document.querySelector(".cart-count");
const userId = "<?php echo $userId ?? 'guest_' . time(); ?>";
const isGuest = <?php echo $isGuest ? 'true' : 'false'; ?>; 
 let cartItems = JSON.parse(localStorage.getItem(`cartItems_${userId}`)) || [];
// WISHLIST STORAGE FUNCTIONS
function getWishlistItems() {
    try {
        const stored = localStorage.getItem(`wishlist_${userId}`);
        if (stored) return JSON.parse(stored);
        return getCookie(`wishlist_${userId}`) || [];
    } catch (e) {
        return [];
    }
}

function saveWishlistItems(items) {
    try {
        localStorage.setItem(`wishlist_${userId}`, JSON.stringify(items));
        setCookie(`wishlist_${userId}`, JSON.stringify(items), 30);
    } catch (e) {
        setCookie(`wishlist_${userId}`, JSON.stringify(items), 30);
    }
}

let wishlistItems = getWishlistItems();

function toggleWishlist(id, name, price, img) {
    wishlistItems = getWishlistItems();
    const existingIndex = wishlistItems.findIndex(i => String(i.id) === String(id));
    const heartBtn = document.querySelector(`[data-product-id="${id}"] .wishlist-btn .heart`);
    const wishlistBtn = document.querySelector(`[data-product-id="${id}"] .wishlist-btn`);
    
    if (existingIndex > -1) {
        wishlistItems.splice(existingIndex, 1);
        if (heartBtn) {
            heartBtn.textContent = '♡';
            heartBtn.style.color = '';
        }
        if (wishlistBtn) wishlistBtn.classList.remove('active');
        showToast(`${name} removed from wishlist`);
    } else {
        wishlistItems.push({
            id: String(id),
            name: String(name),
            price: parseFloat(price),
            img: img || 'src/product-images/default.jpg'
        });
        if (heartBtn) {
            heartBtn.textContent = '♥';
            heartBtn.style.color = '#e74c3c';
        }
        if (wishlistBtn) wishlistBtn.classList.add('active');
        showToast(`${name} added to wishlist ♥`);
    }
    
    saveWishlistItems(wishlistItems);
}

function initializeWishlistHearts() {
    const currentWishlist = getWishlistItems();
    currentWishlist.forEach(item => {
        const heartBtn = document.querySelector(`[data-product-id="${item.id}"] .wishlist-btn .heart`);
        const wishlistBtn = document.querySelector(`[data-product-id="${item.id}"] .wishlist-btn`);
        
        if (heartBtn) {
            heartBtn.textContent = '♥';
            heartBtn.style.color = '#e74c3c';
        }
        if (wishlistBtn) wishlistBtn.classList.add('active');
    });
}



  // Product quantity tracking for each item
  const productQuantities = {};

  function updateCartCount() {
    const total = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    cartCountEl.textContent = total;
    cartCountEl.style.display = total > 0 ? "inline-block" : "none";
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


  updateCartCount();
  
  // Initialize wishlist hearts on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(initializeWishlistHearts, 200);
});


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

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function hideSearchPagination() {
    const pagination = document.querySelector('#liquor .pagination');
    const pageInfo = pagination?.nextElementSibling;
    
    if(pagination) pagination.style.display = 'none';
    if(pageInfo && pageInfo.textContent.includes('Showing')) {
        pageInfo.style.display = 'none';
    }
}

function showSearchInfo(data) {
    // Remove existing search info
    const existingInfo = document.querySelector('#liquor .search-results-info');
    if (existingInfo) existingInfo.remove();
    
    // Add new search info
    const liquorSection = document.querySelector('#liquor');
    const info = document.createElement('div');
    info.className = 'search-results-info';
    info.style.cssText = 'text-align: center; margin: 10px 0; padding: 10px; background: #e8f4fd; border-radius: 5px; font-size: 14px; color: #0056b3;';
    info.textContent = `Found ${data.total} products${data.has_more ? ' (showing first ' + data.products.length + ')' : ''}`;
    
    const container = document.querySelector('#liquor-results');
    container.parentNode.insertBefore(info, container);
} 
// Debounce timer
let searchTimeout = null;

// Real-time search function
function searchLiquorRealTime() {
    // Clear existing timeout
    clearTimeout(searchTimeout);
    
    // Set new timeout for 300ms delay
    searchTimeout = setTimeout(() => {
        performInstantSearch();
    }, 300);
}

function performInstantSearch() {
    const query = document.getElementById('liquor-search').value.trim();
    const sort = document.getElementById('sort-select').value;
    const minPrice = document.getElementById('min-price').value.trim();
    const maxPrice = document.getElementById('max-price').value.trim();
    const category = document.getElementById('category-filter')?.value || '';

    // Debug logging
    console.log('Search params:', {
        query, sort, minPrice, maxPrice, category
    });

    const params = new URLSearchParams();
    if(query) params.append('query', query);
    if(category) params.append('category', category);
    if(minPrice && !isNaN(parseFloat(minPrice))) params.append('minPrice', minPrice);
    if(maxPrice && !isNaN(parseFloat(maxPrice))) params.append('maxPrice', maxPrice);
    if(sort) params.append('sort', sort);
    params.append('section', 'liquor');

    const url = `search-liqour.php?${params.toString()}`;
    console.log('Full URL:', url);

    const container = document.getElementById('liquor-results');
    
    // Show subtle loading indicator
    if (!container.querySelector('.search-loading')) {
        const loading = document.createElement('div');
        loading.className = 'search-loading';
        loading.style.cssText = 'position: absolute; top: 0; right: 0; padding: 5px 10px; background: rgba(139,69,19,0.1); color: #8B4513; font-size: 12px; border-radius: 0 0 0 8px; z-index: 10;';
        loading.textContent = 'Searching...';
        container.style.position = 'relative';
        container.appendChild(loading);
    }

    fetch(url)
        .then(res => {
            console.log('Response status:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('Full search response:', data);
            
            // Remove loading indicator
            const loading = container.querySelector('.search-loading');
            if (loading) loading.remove();

            if (!data.success) {
                console.error('Search error:', data.error);
                if (data.debug) console.error('Debug info:', data.debug);
                return;
            }

            // Clear and rebuild results
            container.innerHTML = '';

            // Remove any existing sort indicator
            const existingSortIndicator = document.querySelector('.sort-indicator');
            if (existingSortIndicator) {
                existingSortIndicator.remove();
            }

            // Add sort indicator BEFORE the container, not inside it
            if (sort) {
                const sortIndicator = document.createElement('div');
                sortIndicator.className = 'sort-indicator';
                sortIndicator.style.cssText = 'background: #e8f4fd; padding: 8px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; color: #0056b3;';
                
                const sortLabels = {
                    'price_asc': 'Sorted by Price: Low to High',
                    'price_desc': 'Sorted by Price: High to Low',
                    'name_asc': 'Sorted by Name: A-Z',
                    'name_desc': 'Sorted by Name: Z-A',
                    'stock_desc': 'Sorted by Stock: Most to Least',
                    'newest': 'Sorted by Newest First'
                };
                
                sortIndicator.textContent = sortLabels[sort] || `Sorted by: ${sort}`;
                
                // Insert BEFORE the container, not inside it
                container.parentNode.insertBefore(sortIndicator, container);
            }

            if(!data.products || data.products.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:40px; color:#666;">No products found</div>';
                hideSearchPagination();
                return;
            }

            // Debug: Log products with their sort-relevant fields
            if (sort) {
                console.log('Products sorted by', sort, ':', data.products.map(p => ({
                    name: p.name,
                    price: p.price,
                    stock: p.total_stock,
                    id: p.liqour_id
                })));
            }

            // Render products
            data.products.forEach(item => {
                const stock = parseInt(item.total_stock) || 0;
                const stockClass = stock === 0 ? 'out-of-stock' : (stock <= 5 ? 'low-stock' : 'in-stock');
                const stockText = stock === 0 ? 'Out of Stock' : (stock <= 5 ? `Low Stock (${stock})` : `In Stock (${stock})`);
                const cardClass = stock === 0 ? 'new-item out-of-stock' : 'new-item';

                const div = document.createElement('div');
                div.className = cardClass;
                div.setAttribute('data-product-id', item.liqour_id);
                
                const escapedName = item.name.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
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
            hideSearchPagination();
        })
        .catch(err => {
            const loading = container.querySelector('.search-loading');
            if (loading) loading.remove();
            console.error('Fetch error:', err);
            container.innerHTML = '<div style="text-align:center; padding:40px; color:red;">Network error. Please try again.</div>';
        });
        setTimeout(initializeWishlistHearts, 100);
}
function hideSearchPagination() {
    const pagination = document.querySelector('#liquor .pagination');
    const pageInfo = pagination?.nextElementSibling;
    if(pagination) pagination.style.display = 'none';
    if(pageInfo && pageInfo.textContent.includes('Showing')) pageInfo.style.display = 'none';
}

// Store original content when page loads
let originalLiquorContent = null;

// Initialize original content storage when page loads
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('liquor-results');
    if (container) {
        originalLiquorContent = container.innerHTML;
    }
});

function resetLiquorSearch() {
    // Clear all inputs
    document.getElementById('liquor-search').value = '';
    document.getElementById('sort-select').value = '';
    document.getElementById('min-price').value = '';
    document.getElementById('max-price').value = '';
    const categoryFilter = document.getElementById('category-filter');
    if (categoryFilter) categoryFilter.value = '';
    
    // Remove sort indicator if it exists
    const existingSortIndicator = document.querySelector('.sort-indicator');
    if (existingSortIndicator) {
        existingSortIndicator.remove();
    }
    
    // Remove search results info if it exists
    const searchInfo = document.querySelector('#liquor .search-results-info');
    if (searchInfo) {
        searchInfo.remove();
    }
    
    // Clear search timeout
    clearTimeout(searchTimeout);
    
    // Show pagination again (it was hidden during search)
    const pagination = document.querySelector('#liquor .pagination');
    const pageInfo = pagination?.nextElementSibling;
    if(pagination) pagination.style.display = '';
    if(pageInfo && pageInfo.textContent.includes('Showing')) pageInfo.style.display = '';
    
    // Restore original content
    const container = document.getElementById('liquor-results');
    if (originalLiquorContent) {
        container.innerHTML = originalLiquorContent;
        // Re-initialize wishlist hearts for restored content
        setTimeout(initializeWishlistHearts, 100);
    } else {
        // Fallback: reload the page if original content wasn't stored
        const currentParams = new URLSearchParams(window.location.search);
        const liquorPage = currentParams.get('liquor_page') || '1';
        window.location.href = `?liquor_page=${liquorPage}#liquor`;
    }
    setTimeout(initializeWishlistHearts, 100);
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
if (!document.getElementById('wishlist-styles')) {
    const style = document.createElement('style');
    style.id = 'wishlist-styles';
    style.textContent = `
        .wishlist-btn.active .heart { color: #e74c3c !important; transform: scale(1.1); }
        .wishlist-btn.active { background-color: rgba(231, 76, 60, 0.1); border-color: #e74c3c; }
        .wishlist-btn:hover .heart { transform: scale(1.1); transition: transform 0.2s ease; }
    `;
    document.head.appendChild(style);
}
</script>

</body>
</html>