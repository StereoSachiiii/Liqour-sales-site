<?php
session_start();
if(!isset($_SESSION['userId'], $_SESSION['username'])){
    header("Location: public/login-signup.php");
    exit();
}

include("../Backend/sql-config.php");

if (!isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
    die("Invalid category.");
}

$categoryId = (int)$_GET['category_id'];

$catStmt = $conn->prepare("SELECT name FROM liqour_categories WHERE liqour_category_id = ?");
$catStmt->bind_param("i", $categoryId);
$catStmt->execute();
$catResult = $catStmt->get_result();
$category = $catResult->fetch_assoc();
$catStmt->close();

if (!$category) {
    die("Category not found.");
}

$stmt = $conn->prepare("SELECT liqour_id, name, description, price, image_url
                         FROM liqours
                         WHERE category_id = ?");
$stmt->bind_param("i", $categoryId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - LiquorStore</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

  <div class="header-strip">Welcome to LiquorStore! Free delivery on orders over $50</div>

  <nav class="nav-bar">
    <div class="logo-container">
      <img src="src\icons\icon.svg" alt="LiquorStore Logo">
    </div>
    <div class="nav-option"><a href="index.php">Home</a></div>
    <div class="nav-options-container nav-options-font">
      <div class="nav-option"><a href="index.php#new-arrivals">NEW ARRIVALS</a></div>
      <div class="nav-option"><a href="index.php#liquor">LIQUOR</a></div>
      
      <div class="nav-option"><a href="index.php#categories">CATEGORIES</a></div>
    </div>
    <div class="profile-search-cart">
      <div class="profile-container">
        <div class="profile">👤</div>
        <div class="profile-expand">
          <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
          <p><a href="#" onclick="logout()">Logout</a></p>
          <p><a href="#">My Orders</a></p>
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

  <section class="new">
    <h2 class="title-text">🍷 Avalable <?php echo htmlspecialchars($category['name']); ?> products</h2>

      
        <small> found <?php echo htmlspecialchars(strval($result->num_rows))  ?> results</small>


    
    
    <div class="new-arrivals">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class='new-item'>
            <div class='image-container' style='background-image:url(<?php echo htmlspecialchars($row['image_url']); ?>)'></div>
            <div class='description'>
              <?php echo htmlspecialchars($row['name']); ?>
              <?php if (!empty($row['description'])): ?>
                <br><small style="color: #666;"><?php echo htmlspecialchars($row['description']); ?></small>
              <?php endif; ?>
            </div>
            <div class='price-add-to-cart'>
              <div class='product-price'>$<?php echo number_format($row['price'], 2); ?></div>
              <button class='add-to-cart-btn' 
                      onclick='addToCart("<?php echo $row['liqour_id']; ?>", "<?php echo addslashes($row['name']); ?>", <?php echo $row['price']; ?>, "<?php echo addslashes($row['image_url']); ?>")'>
                Add to Cart
              </button>
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

  
  <footer class="feedback-socials" style="justify-content:center;">
    <p>© 2025 LiquorStore. All rights reserved.</p>
  </footer>

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
    
    alert(`Added ${name} to cart!`);
  }

  function searchProducts(){
    const query = document.getElementById("search-box").value;
    if (query.trim()) {
      window.location.href = `search.php?q=${encodeURIComponent(query)}`;
    }
  }

  function logout() {
    if(confirm("Are you sure you want to logout?")) {
      window.location.href = "Backend/logout.php";
    }
  }
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>