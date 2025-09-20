 
 
 
 
 <nav class="nav-bar">
    <a href="index.php">
      <div class="logo-container">
        <img src="src/icons/icon.svg" alt="LiquorStore Logo">
      </div>
    </a>

    <div class="nav-options-container nav-options-font">
      <div class="nav-option"><a href="index.php">HOME</a></div>
      <div class="nav-option"><a href="index.php#new-arrivals">NEW ARRIVALS</a></div>
      <div class="nav-option"><a href="index.php#liquor">LIQUOR</a></div>
      <div class="nav-option"><a href="index.php#categories">CATEGORIES</a></div>
    </div>

    <!-- Profile / Search / Cart -->
   <!-- Profile / Search / Cart -->
<div class="profile-search-cart">
  <!-- Profile -->
  <div class="profile-container">
  <div class="profile">👤</div>
  <div class="profile-expand">
    <?php if ($isGuest): ?>
      <p class="username">Guest</p>
      <a href="login-signup.php">Login / Sign Up</a>
      <a href="wishlist.php">My Wishlist</a>
    <?php else: ?>
      <p class="username"><?php echo htmlspecialchars($username); ?></p>
      <a href="profile.php">Profile</a>
      
      <a href="#" onclick="showLogoutModal()">Logout</a>
      <a href="my-orders.php">My Orders</a>
      <a href="wishlist.php">My Wishlist</a>
    <?php endif; ?>
  </div>
</div>


<?php if (basename($_SERVER['PHP_SELF']) !== 'cart.php' &&
          basename($_SERVER['PHP_SELF']) !== 'category.php' &&
          basename($_SERVER['PHP_SELF']) !== 'feedback.php' &&
          basename($_SERVER['PHP_SELF']) !== 'my-orders.php' &&
          basename($_SERVER['PHP_SELF']) !== 'profile.php' &&
          basename($_SERVER['PHP_SELF']) !== 'wishlist.php' &&
          basename($_SERVER['PHP_SELF']) !== 'product.php'
          
          ): ?>
  <div class="nav-search-icon" onclick="scrollToLiquorSearch()">🔍</div>
<?php endif; ?>


  <!-- Cart -->
  <div class="cart-container cart-link">
    <a href="cart.php">
      <div class="cart">🛒</div>
      <div class="cartLengthDisplay cart-count">0</div>
    </a>
  </div>
</div>

  </nav>