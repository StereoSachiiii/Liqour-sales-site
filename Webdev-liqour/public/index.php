<?php
session_start();
if(!isset($_SESSION['userId'], $_SESSION['username'])){
    header("Location: login-signup.php");
    exit();
}

include('../Backend/sql-config.php');

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

  <nav class="nav-bar">
          <a href="index.php"><div class="logo-container"><img src="src\icons\icon.svg" alt="LiquorStore Logo">    </div></a>

    <div class="nav-options-container nav-options-font">
      <div class="nav-option"><a href="#new-arrivals">NEW ARRIVALS</a></div>
      <div class="nav-option"><a href="#liquor">LIQUOR</a></div>
      <div class="nav-option"><a href="#categories">CATEGORIES</a></div>
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

  <section class="feedback-socials">
    <div>
      <a href="feedback.php">📩 Feedback</a>
    </div>    
    <div class="social-media-links">
      <p>🌐 Follow us:</p>
      <a href="#">FB</a>
      <a href="#">IG</a>
      <a href="#">X</a>
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
    <div class="new-arrivals">
    <?php 
    $sql = "SELECT * FROM liqours WHERE is_active = 1 ORDER BY liqour_id DESC LIMIT 6";

    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          echo "
          <div class='new-item'>
              <div class='image-container' style='background-image:url({$row['image_url']})'></div>
              <div class='description'>{$row['name']}</div>
             <div class='price-add-to-cart'>
    <div class='product-price'>$".$row['price']."</div>
    <button class='add-to-cart-btn' onclick='addToCart(\"{$row['liqour_id']}\", \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\")'>Add to Cart</button>
    <button class='view-product-btn' onclick='viewProduct(".$row['liqour_id'].")'>View Product</button>
</div>
                              <button class='add-to-cart-btn' onclick='viewReviews(\"{$row['liqour_id']}\")' style='background:#666; margin-top:5px;'>View Reviews</button>

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
            <option value="price_asc">Price: Low → High</option>
            <option value="price_desc">Price: High → Low</option>
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
    <div class="new-arrivals">
    <?php
    $sql = "SELECT l.liqour_id, l.name, l.price, l.image_url, c.name AS category_name 
        FROM liqours l
        JOIN liqour_categories c ON l.category_id = c.liqour_category_id
        WHERE l.is_active = 1 AND c.is_active = 1";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "
            <div class='new-item'>
                <div class='image-container' style='background-image:url({$row['image_url']})'></div>
                <div class='description'>{$row['name']} ({$row['category_name']})</div>
                <div class='price-add-to-cart'>
    <div class='product-price'>$".$row['price']."</div>
    <button class='add-to-cart-btn' onclick='addToCart(\"{$row['liqour_id']}\", \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\")'>Add to Cart</button>
    <button class='view-product-btn' onclick='viewProduct(".$row['liqour_id'].")'>View Product</button>
</div>
                                  <button class='add-to-cart-btn' onclick='viewReviews(\"{$row['liqour_id']}\")' style='background:#666; margin-top:5px;'>View Reviews</button>

            </div>";
        }
    } else {
        echo "<p>No liquors available.</p>";
    }
    ?>
    </div>
  </section>

 
  <section class="new" id="featured">
    <h2 class="title-text">⭐ Featured (High Stock)</h2>
    <div class="new-arrivals">
    <?php
    $sql = "SELECT l.liqour_id, l.name, l.price, l.image_url, c.name AS category_name, SUM(s.quantity) AS total_stock
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
            echo "
            <div class='new-item'>
                <div class='image-container' style='background-image:url({$row['image_url']})'></div>
                <div class='description'>{$row['name']} ({$row['category_name']})</div>
                <div class='price-add-to-cart'>
    <div class='product-price'>$".$row['price']."</div>
    <button class='add-to-cart-btn' onclick='addToCart(\"{$row['liqour_id']}\", \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\")'>Add to Cart</button>
    <button class='view-product-btn' onclick='viewProduct(".$row['liqour_id'].")'>View Product</button>
</div>
                <div style='width: 100%; margin-top: 8px;'>
                    <button class='add-to-cart-btn' onclick='viewReviews(\"{$row['liqour_id']}\")' style='background:#666; width: 100%;'>View Reviews</button>
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
    <h2 class="title-text">📦 Categories</h2>
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

  <footer class="feedback-socials" style="justify-content:center;">
    <p>© 2025 LiquorStore. All rights reserved.</p>
  </footer>

<script src="js/main.js"></script>
<script>
  document.querySelector(".profile-container").addEventListener("click", () => {
    document.querySelector(".profile-expand").classList.toggle("profile-expand-active");
  });

  const cartCountEl = document.querySelector(".cart-count");
  const userId = "<?php echo $_SESSION['userId']; ?>";
  let cartItems = JSON.parse(localStorage.getItem(`cartItems_${userId}`)) || [];

  function updateCartCount() {
    const total = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    cartCountEl.textContent = total;
    cartCountEl.style.display = total > 0 ? "inline-block" : "none";
  }
  updateCartCount();

  function addToCart(id, name, price, img) {
    const existing = cartItems.find(i => i.name === name);
    if(existing) {
        existing.quantity++;
    } else {
        cartItems.push({id, name, price, img, quantity: 1});
    }
    
    localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
    updateCartCount();
  }

  function searchProducts(){
    const query = document.getElementById("search-box").value;
    alert("Search for: " + query);
  }

  function showLogoutModal() {
    document.getElementById('logoutModal').style.display = 'flex';
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
function addToCart(id, name, price, img) {
    const existing = cartItems.find(i => i.name === name);
    if(existing) {
        existing.quantity++;
    } else {
        cartItems.push({id, name, price, img, quantity: 1});
    }
    
    localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
    updateCartCount();

    showToast(`${name} added to cart 🛒`);
}function searchLiquorAJAX() {
    const query = document.getElementById('liquor-search').value.trim();
    const sort = document.getElementById('sort-select').value;
    const minPrice = document.getElementById('min-price').value.trim();
    const maxPrice = document.getElementById('max-price').value.trim();

    const params = new URLSearchParams();
    if(query) params.append('query', query);
    if(minPrice) params.append('minPrice', minPrice);
    if(maxPrice) params.append('maxPrice', maxPrice);
    if(sort) params.append('sort', sort);

    fetch(`search-liqour.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            const container = document.querySelector('#liquor .new-arrivals');
            container.innerHTML = ''; // clear old results

            if(data.length === 0) {
                container.innerHTML = '<p>No products found.</p>';
                return;
            }

            // Optional: Sort client-side if not handled server-side
            if(sort === 'price_asc') data.sort((a,b) => a.price - b.price);
            if(sort === 'price_desc') data.sort((a,b) => b.price - a.price);

            data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'new-item';
                div.innerHTML = `
    <div class='image-container' style='background-image:url(${item.image_url})'></div>
    <div class='description'>${item.name} (${item.category_name})</div>
    <div class='price-add-to-cart'>
        <div class='product-price'>$${item.price}</div>
        <button class='add-to-cart-btn' onclick='addToCart("${item.liqour_id}", "${item.name}", ${item.price}, "${item.image_url}")'>Add to Cart</button>
        <button class='view-product-btn' onclick='viewProduct(${item.liqour_id})'>View Product</button>
    </div>
`;
                container.appendChild(div);
            });
        })
        .catch(err => console.error(err));
}

function resetLiquorSearch() {
    document.getElementById('liquor-search').value = '';
    document.getElementById('sort-select').value = '';
    document.getElementById('min-price').value = '';
    document.getElementById('max-price').value = '';
    searchLiquorAJAX();
}


function resetLiquorSearch() {
    document.getElementById('liquor-search').value = ''; // clear input
    searchLiquorAJAX(); // call the same function as search
}


</script>

</body>
</html>
