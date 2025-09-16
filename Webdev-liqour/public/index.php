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
    <div class="logo-container">
      <img src="src\icons\icon.svg" alt="LiquorStore Logo">
    </div>
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
            <p><a href="#" onclick="logout()">Logout</a></p>
            <p><a href="my-orders.php">My Orders</a></p>
        </div>
      </div>

      <div class="search-container">
        <div class="search-bar-expand">
          <input type="text" id="search-box" placeholder="Search products...">
          <button onclick="searchProducts()">Search</button>
        </div>
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
    $sql = "SELECT * FROM liqours ORDER BY liqour_id DESC LIMIT 6";
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
    <div class="new-arrivals">
    <?php
    $sql = "SELECT l.liqour_id, l.name, l.price, l.image_url, c.name AS category_name 
            FROM liqours l
            JOIN liqour_categories c ON l.category_id = c.liqour_category_id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "
            <div class='new-item'>
                <div class='image-container' style='background-image:url({$row['image_url']})'></div>
                <div class='description'>{$row['name']} ({$row['category_name']})</div>
                <div class='price-add-to-cart'>
                  <div class='product-price'>\${$row['price']}</div>
                  <button class='add-to-cart-btn' 
                          onclick='addToCart(\"{$row['liqour_id']}\", \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\")'>
                      Add to Cart
                  </button>
                   
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
            LEFT JOIN stock s ON l.liqour_id = s.liqour_id
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
                    <div class='product-price'>\${$row['price']}</div>
                    <button class='add-to-cart-btn' onclick='addToCart(\"{$row['liqour_id']}\", \"{$row['name']}\", {$row['price']}, \"{$row['image_url']}\")'>Add to Cart</button>
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

  

  function logout() {
    if(confirm("Are you sure you want to logout?")) {
window.location.href = "../Backend/auth/logout.php";
    }
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
}


</script>

</body>
</html>
