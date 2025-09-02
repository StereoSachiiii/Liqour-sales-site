<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Liquor Store</title>
  <link rel="stylesheet" href="index.css">
</head>
<body>

  <!-- HEADER NAVBAR -->
  <div class="header-strip">Welcome to LiquorStore! Free delivery on orders over $50</div>

  <nav class="nav-bar">
    <div class="logo-container">
      <img src="logo.png" alt="LiquorStore Logo">
    </div>
    <div class="nav-options-container nav-options-font">
      <div class="nav-option"><a href="#new-arrivals">NEW ARRIVALS</a></div>
      <div class="nav-option"><a href="#liquor">LIQUOR</a></div>
      <div class="nav-option"><a href="#wines">WINES</a></div>
      <div class="nav-option"><a href="#categories">CATEGORIES</a></div>
    </div>
    <div class="profile-search-cart">
      <div class="profile-container">
        <div class="profile">👤</div>
        <div class="profile-expand">
          <p><a href="#">Login</a></p>
          <p><a href="#">Register</a></p>
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
        <a href="cart.html"><div class="cart">🛒</div></a>
        <div class="cartLengthDisplay cart-count">0</div>
      </div>
    </div>
  </nav>

  <!-- FEEDBACK + SOCIALS -->
  <section class="feedback-socials">
<div>
    <a href="feedback.html">📩 Feedback</a>
  </div>    
  <div class="social-media-links">
      <p>🌐 Follow us:</p>
      <a href="#">FB</a>
      <a href="#">IG</a>
      <a href="#">X</a>
    </div>
  </section>

  <!-- SLIDER -->
  <section class="slider-container">
    <div class="slider">
      <div class="slide"><img src="banner1.jpg" alt="Banner 1"></div>
      <div class="slide"><img src="banner2.jpg" alt="Banner 2"></div>
      <div class="slide"><img src="banner3.jpg" alt="Banner 3"></div>
    </div>
    <div class="slider-arrow prev">❮</div>
    <div class="slider-arrow next">❯</div>
    <div class="slider-nav">
      <div class="nav-dot"></div>
      <div class="nav-dot"></div>
      <div class="nav-dot"></div>
    </div>
  </section>

  <!-- NEW ARRIVALS -->
  <section class="new" id="new-arrivals">
    <h2 class="title-text">✨ New Arrivals</h2>
    <div class="new-arrivals">
      <div class='new-item'>
        <div class='image-container' style='background-image:url(product1.jpg)'></div>
        <div class='description'>Product 1</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$10</div>
          <button class='add-to-cart-btn' onclick='addToCart("Product 1", 10, "product1.jpg")'>Add to Cart</button>
        </div>
      </div>
      <div class='new-item'>
        <div class='image-container' style='background-image:url(product2.jpg)'></div>
        <div class='description'>Product 2</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$20</div>
          <button class='add-to-cart-btn' onclick='addToCart("Product 2", 20, "product2.jpg")'>Add to Cart</button>
        </div>
      </div>
      <div class='new-item'>
        <div class='image-container' style='background-image:url(product3.jpg)'></div>
        <div class='description'>Product 3</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$30</div>
          <button class='add-to-cart-btn' onclick='addToCart("Product 3", 30, "product3.jpg")'>Add to Cart</button>
        </div>
      </div>
      <div class='new-item'>
        <div class='image-container' style='background-image:url(product4.jpg)'></div>
        <div class='description'>Product 4</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$40</div>
          <button class='add-to-cart-btn' onclick='addToCart("Product 4", 40, "product4.jpg")'>Add to Cart</button>
        </div>
      </div>
    </div>
  </section>

  <!-- LIQUOR -->
  <section class="new" id="liquor">
    <h2 class="title-text">🥃 Liquor</h2>
    <div class="new-arrivals">
      <div class='new-item'>
        <div class='image-container' style='background-image:url(liquor5.jpg)'></div>
        <div class='description'>Liquor 5</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$60</div>
          <button class='add-to-cart-btn' onclick='addToCart("Liquor 5", 60, "liquor5.jpg")'>Add to Cart</button>
        </div>
      </div>
      <div class='new-item'>
        <div class='image-container' style='background-image:url(liquor6.jpg)'></div>
        <div class='description'>Liquor 6</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$72</div>
          <button class='add-to-cart-btn' onclick='addToCart("Liquor 6", 72, "liquor6.jpg")'>Add to Cart</button>
        </div>
      </div>
      <div class='new-item'>
        <div class='image-container' style='background-image:url(liquor7.jpg)'></div>
        <div class='description'>Liquor 7</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$84</div>
          <button class='add-to-cart-btn' onclick='addToCart("Liquor 7", 84, "liquor7.jpg")'>Add to Cart</button>
        </div>
      </div>
      <div class='new-item'>
        <div class='image-container' style='background-image:url(liquor8.jpg)'></div>
        <div class='description'>Liquor 8</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$96</div>
          <button class='add-to-cart-btn' onclick='addToCart("Liquor 8", 96, "liquor8.jpg")'>Add to Cart</button>
        </div>
      </div>
    </div>
  </section>

  <!-- FEATURED -->
  <section class="new" id="featured">
    <h2 class="title-text">⭐ Featured</h2>
    <div class="new-arrivals">
      <div class="new-item">
        <div class="image-container" style="background-image:url('featured1.jpg')"></div>
        <div class="description">Whiskey Premium</div>
        <div class="price-add-to-cart">
          <div class="product-price">$59</div>
          <button class="add-to-cart-btn" onclick="addToCart('Whiskey Premium', 59, 'featured1.jpg')">Add to Cart</button>
        </div>
      </div>
      <div class="new-item">
        <div class="image-container" style="background-image:url('featured2.jpg')"></div>
        <div class="description">Vintage Wine</div>
        <div class="price-add-to-cart">
          <div class="product-price">$89</div>
          <button class="add-to-cart-btn" onclick="addToCart('Vintage Wine', 89, 'featured2.jpg')">Add to Cart</button>
        </div>
      </div>
    </div>
  </section>

  <!-- CATEGORIES -->
  <section class="new" id="categories">
    <h2 class="title-text">📦 Categories</h2>
    <div class="new-arrivals">
      <div class="new-item"><div class="description">Beer</div></div>
      <div class="new-item"><div class="description">Wine</div></div>
      <div class="new-item"><div class="description">Whiskey</div></div>
      <div class="new-item"><div class="description">Vodka</div></div>
      <div class="new-item"><div class="description">Rum</div></div>
      <div class="new-item"><div class="description">Gin</div></div>
    </div>
  </section>

  <!-- WINES -->
  <section class="new" id="wines">
    <h2 class="title-text">🍇 Wines</h2>
    <div class="new-arrivals">
      <div class='new-item'>
        <div class='image-container' style='background-image:url(wine9.jpg)'></div>
        <div class='description'>Wine 9</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$135</div>
          <button class='add-to-cart-btn' onclick='addToCart("Wine 9", 135, "wine9.jpg")'>Add to Cart</button>
        </div>
      </div>
      <div class='new-item'>
        <div class='image-container' style='background-image:url(wine10.jpg)'></div>
        <div class='description'>Wine 10</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$150</div>
          <button class='add-to-cart-btn' onclick='addToCart("Wine 10", 150, "wine10.jpg")'>Add to Cart</button>
        </div>
      </div>
      <div class='new-item'>
        <div class='image-container' style='background-image:url(wine11.jpg)'></div>
        <div class='description'>Wine 11</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$165</div>
          <button class='add-to-cart-btn' onclick='addToCart("Wine 11", 165, "wine11.jpg")'>Add to Cart</button>
        </div>
      </div>
      <div class='new-item'>
        <div class='image-container' style='background-image:url(wine12.jpg)'></div>
        <div class='description'>Wine 12</div>
        <div class='price-add-to-cart'>
          <div class='product-price'>$180</div>
          <button class='add-to-cart-btn' onclick='addToCart("Wine 12", 180, "wine12.jpg")'>Add to Cart</button>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="feedback-socials" style="justify-content:center;">
    <p>© 2025 LiquorStore. All rights reserved.</p>
  </footer>

<script>
  // PROFILE DROPDOWN
  document.querySelector(".profile-container").addEventListener("click", () => {
    document.querySelector(".profile-expand").classList.toggle("profile-expand-active");
  });

  // CART LOGIC
  const cartCountEl = document.querySelector(".cart-count");
  let cartItems = JSON.parse(localStorage.getItem("cartItems")) || [];

  function updateCartCount() {
    const total = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    cartCountEl.textContent = total;
    cartCountEl.style.display = total > 0 ? "inline-block" : "none";
  }

  updateCartCount();

  function addToCart(name, price, img) {
    const id = "item_" + Math.random().toString(36).substr(2,9);
    
    const existing = cartItems.find(i => i.name === name);
    if(existing) {
      existing.quantity++;
    } else {
      cartItems.push({id, name, price, img, quantity: 1});
    }
    
    localStorage.setItem("cartItems", JSON.stringify(cartItems));
    updateCartCount();
  }

  // SEARCH
  function searchProducts(){
    const query = document.getElementById("search-box").value;
    alert("Search for: " + query);
  }

  // SLIDER
  let slideIndex = 0;
  const slides = document.querySelectorAll(".slide");
  document.querySelector(".prev").addEventListener("click", ()=>changeSlide(-1));
  document.querySelector(".next").addEventListener("click", ()=>changeSlide(1));

  function showSlide(n){
    slides.forEach((s,i)=>s.style.display=(i===n)?"block":"none");
  }

  function changeSlide(step){
    slideIndex=(slideIndex+step+slides.length)%slides.length; 
    showSlide(slideIndex);
  }

  showSlide(slideIndex);
</script>

</body>
</html>