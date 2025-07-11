<?php include('header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Liquor Website</title>
    </head>
<body>

    <section class="nav-bar">
        <div class="logo-container">
            <img src="src/045yr3xhag3tsp47jt0r6xnk80_image.png" alt="Logo">
        </div>

        <div class="nav-options-container nav-options-font">
            <a href=""><div class="nav-option">LIQUOR</div></a>
            <a href=""><div class="nav-option">FEATURED</div></a>
            <a href=""><div class="nav-option">CATEGORIES</div></a>
            <a href=""><div class="nav-option">WINES</div></a>
        </div>
<div class="profile-search-cart">
  <div class="search-container">
    <img class="search icon" src="src/search-interface-symbol.png" alt="Search">

    <div class="search-bar-expand">
      <input type="text" placeholder="Search for products...">
      <button>Search</button>
    </div>
  </div>

  <div><a href="#"><img class="profile icon" src="src/profile-user.png" alt="Profile"></a></div>
  <div><a href="#"><img class="cart icon" src="src/grocery-store.png" alt="Cart"></a></div>
</div>

    </section>

    <section class="feedback-socials">
        <div class="social-media-links">
            <a href=""><img class="icon" src="src/facebook-app-symbol.png" alt="Facebook"></a>
            <a href=""><img class="icon" src="src/instagram.png" alt="Instagram"></a>
            <a href=""><img class="icon" src="src/twitter.png" alt="X"></a>
            <a href=""><img class="icon" src="src/tik-tok.png" alt="TikTok"></a>
        </div>
        <div class="feedback-text">
            <p>Take a look at our reviews!</p>
        </div>
        <div>
            <a href=""><div><p>News</p></div></a>
            <a href=""><div><p>Contact us</p></div></a>
            <a href=""><div><p>Delivery</p></div></a>
        </div>
    </section>

    <?php include('slider.html');  ?>

    <div class="new">
        <p class="title-text">What's New?</p>
        <div class="new-arrivals">
            </div>
            <button class="order-btn">Order</button>
    </div>

    

    <script src="product_data.js"></script> 
    <script src="main.js"></script> 
    <script src="cart.js"></script> 
</body>
</html>