<?php
session_start();
if(!isset($_SESSION['userId'], $_SESSION['username'])){
    header("Location: public/login-signup.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shopping Cart - LiquorStore</title>
  <link rel="stylesheet" href="css/index.css">
</head>
<body>
       


  <div class="header-strip">
 
  
  Welcome to LiquorStore! Free delivery on orders over $50</div>

  <nav class="nav-bar">
    <div class="logo-container">
 <img src="src\icons\icon.svg" alt="LiquorStore Logo">    </div>
    <div class="nav-options-container nav-options-font">
      <div class="nav-option"><a href="index.php#new-arrivals">NEW ARRIVALS</a></div>     
      <div class="nav-option"><a href="index.php#wines">WINES</a></div>
      <div class="nav-option"><a href="index.php#categories">CATEGORIES</a></div>
    </div>

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

  <section class="new">
    <h2 class="title-text">🛒 Your Shopping Cart</h2>
    
    <div id="cart-items-container" class="new-arrivals"></div>
    
    <div id="cart-footer" style="min-height: 200px; text-align: center; padding: 30px; margin-top: 20px;">
      
      <div id="cart-summary" class="new-item" style="display: none;">
        <h3 style="margin-bottom: 20px;">Order Summary</h3>
        <div style="font-size: 24px; font-weight: bold; margin-bottom: 20px; color: #8B4513;">
          Total: <span id="cart-total">$0.00</span>
        </div>
        <button onclick="checkout()" style="padding: 15px 30px; background: #8B4513; color: white; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; font-weight: bold;">Proceed to Checkout</button>
      </div>

      <div id="empty-cart" class="new-item" style="display: none;">
        <h3 style="margin-bottom: 15px; color: #666;">Your cart is empty</h3>
        <p style="margin-bottom: 20px; color: #999;">Add some products from our store to get started!</p>
        <a href="index.php" style="padding: 12px 25px; background: #8B4513; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">Continue Shopping</a>
      </div>

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

    function renderCartItems() {
      const container = document.getElementById("cart-items-container");
      const cartSummary = document.getElementById("cart-summary");
      const emptyCart = document.getElementById("empty-cart");
      
      container.innerHTML = "";
      
      if (cartItems.length === 0) {
        cartSummary.style.display = "none";
        emptyCart.style.display = "block";
        return;
      }
      
      emptyCart.style.display = "none";
      cartSummary.style.display = "block";
      
      let totalPrice = 0;
      
      cartItems.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        totalPrice += itemTotal;
        
        const cartItemDiv = document.createElement("div");
        cartItemDiv.className = "new-item";
        cartItemDiv.style.position = "relative";
        cartItemDiv.style.padding = "20px";
        
        cartItemDiv.innerHTML = `
          <div class="image-container" style="background-image: url('${item.img}'); height: 200px; margin-bottom: 15px;"></div>
          <div class="description" style="font-size: 18px; font-weight: bold; margin-bottom: 10px;">${item.name}</div>
          <div class="product-price" style="font-size: 16px; color: #8B4513; font-weight: bold; margin-bottom: 15px;">$${item.price} each</div>
          
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <button onclick="updateQuantity(${index}, -1)" style="background: #8B4513; color: white; border: none; width: 35px; height: 35px; border-radius: 5px; cursor: pointer; font-size: 16px;">-</button>
              <span style="font-size: 18px; font-weight: bold; min-width: 40px; text-align: center;">${item.quantity}</span>
              <button onclick="updateQuantity(${index}, 1)" style="background: #8B4513; color: white; border: none; width: 35px; height: 35px; border-radius: 5px; cursor: pointer; font-size: 16px;">+</button>
            </div>
            <button onclick="removeItem(${index})" style="background: #ff4444; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 14px;">Remove</button>
          </div>
          
          <div style="font-size: 16px; color: #8B4513; font-weight: bold; text-align: center; padding: 10px; background: #f8f8f8; border-radius: 5px;">
            Subtotal: $${itemTotal.toFixed(2)}
          </div>
        `;
        
        container.appendChild(cartItemDiv);
      });
      
      document.getElementById("cart-total").textContent = `$${totalPrice.toFixed(2)}`;
    }
    
    function updateQuantity(index, change) {
      if (cartItems[index]) {
        cartItems[index].quantity += change;
        if (cartItems[index].quantity <= 0) {
          cartItems.splice(index, 1);
        }
        localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
        updateCartCount();
        renderCartItems();
      }
    }
    
    function removeItem(index) {
      cartItems.splice(index, 1);
      localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
      updateCartCount();
      renderCartItems();
    }
    
   function checkout() {
  if (cartItems.length === 0) {
    alert("Your cart is empty!");
    return;
  }

  fetch("saveCart.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ cart: cartItems })
  })
  .then(res => res.json())
  .then(result => {
    if (result.status === "success") {
      alert(`Order received!\n\n${cartItems.map(item => 
        `${item.name} x${item.quantity} = $${(item.price * item.quantity).toFixed(2)}`
      ).join('\n')}\n\nTotal: $${cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0).toFixed(2)}`);
      
      cartItems = [];
      localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
      updateCartCount();
      renderCartItems();
    } else if (result.status === "error" && result.items) {
      let msg = result.items.map(it => 
        `${it.name}: Requested ${it.requested}, Available ${it.available}`
      ).join("\n");
      alert("Cannot place order, insufficient stock:\n" + msg);
    } else {
      alert("Error: " + result.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert("Something went wrong while sending your cart.");
  });
}


    function searchProducts(){
      const query = document.getElementById("search-box").value;
      alert("Search for: " + query);
    }
    function logout() {
      if(confirm("Are you sure you want to logout?")) {
        window.location.href = "Backend/auth/logout.php";
      }
    }
    
    updateCartCount();
    renderCartItems();
  </script>

</body>
</html>
