<?php
include('session.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Wishlist - LiquorStore</title>
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="css/wishlist.css">

</head>
<body>

  <div class="header-strip">Welcome to LiquorStore! Free delivery on orders over $50</div>
  <?php  include('navbar.php'); ?>

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

  <div class="wishlist-container">
    <div class="wishlist-header">
      <h1>💖 My Wishlist</h1>
      <p>Save your favorite items for later</p>
      <div class="wishlist-stats">
        <div class="stat-item">
          <span class="stat-number" id="total-items">0</span>
          <span class="stat-label">Items</span>
        </div>
        <div class="stat-item">
          <span class="stat-number" id="total-value">$0.00</span>
          <span class="stat-label">Total Value</span>
        </div>
      </div>
    </div>

    <div id="bulk-actions" class="bulk-actions" style="display: none;">
      <div class="bulk-select">
        <input type="checkbox" id="select-all">
        <label for="select-all">Select All</label>
        <span id="selected-count">(0 selected)</span>
      </div>
      <div class="bulk-buttons">
        <button class="btn-bulk btn-bulk-primary" onclick="addSelectedToCart()">Add Selected to Cart</button>
        <button class="btn-bulk btn-bulk-danger" onclick="removeSelected()">Remove Selected</button>
      </div>
    </div>

    <div id="wishlist-content">
      <!-- Content will be populated by JavaScript -->
    </div>
  </div>

  <!-- Logout Modal -->
  <div id="logoutModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:#fff; padding:20px; border-radius:10px; text-align:center; min-width:300px;">
      <h3>Confirm Logout</h3>
      <p>Are you sure you want to logout?</p>
      <button onclick="logoutNow()" style="padding:8px 16px; background:#8B4513; color:white; border:none; border-radius:5px; margin-right:10px;">Yes</button>
      <button onclick="closeLogoutModal()" style="padding:8px 16px; background:#ccc; color:#333; border:none; border-radius:5px;">Cancel</button>
    </div>
  </div>

  <div id="toast-container"></div>

  <?php include('footer.php') ?>

  <script>
    document.querySelector(".profile-container").addEventListener("click", () => {
      document.querySelector(".profile-expand").classList.toggle("profile-expand-active");
    });

    const cartCountEl = document.querySelector(".cart-count");
    const userId = "<?php echo $userId ?? 'guest_' . time(); ?>";
    const isGuest = <?php echo $isGuest ? 'true' : 'false'; ?>;
    let cartItems = JSON.parse(localStorage.getItem(`cartItems_${userId}`)) || [];
    let wishlistItems = JSON.parse(localStorage.getItem(`wishlist_${userId}`)) || [];

    // Product quantity tracking for wishlist items
    const wishlistQuantities = {};

    function updateCartCount() {
      const total = cartItems.reduce((sum, item) => sum + item.quantity, 0);
      cartCountEl.textContent = total;
      cartCountEl.style.display = total > 0 ? "inline-block" : "none";
    }

    function updateWishlistStats() {
      const totalItems = wishlistItems.length;
      const totalValue = wishlistItems.reduce((sum, item) => sum + parseFloat(item.price), 0);
      
      document.getElementById('total-items').textContent = totalItems;
      document.getElementById('total-value').textContent = `$${totalValue.toFixed(2)}`;
    }

    function updateWishlistQuantity(itemId, change) {
      const currentQty = wishlistQuantities[itemId] || 1;
      const newQty = Math.max(1, currentQty + change);
      wishlistQuantities[itemId] = newQty;
      
      const displayEl = document.getElementById(`wishlist-qty-${itemId}`);
      if (displayEl) {
        displayEl.textContent = newQty;
      }
    }

    function renderWishlist() {
      const container = document.getElementById('wishlist-content');
      
      if (wishlistItems.length === 0) {
        container.innerHTML = `
          <div class="empty-wishlist">
            <div class="empty-icon">💔</div>
            <h3>Your wishlist is empty</h3>
            <p>Start adding products you love to your wishlist.<br>They'll appear here for easy access later!</p>
            <a href="index.php" class="shop-now-btn">Start Shopping</a>
          </div>
        `;
        document.getElementById('bulk-actions').style.display = 'none';
        return;
      }

      document.getElementById('bulk-actions').style.display = 'block';
      
      const itemsHTML = wishlistItems.map((item, index) => {
        // Simulate stock (you can replace this with real stock data)
        const stock = Math.floor(Math.random() * 20);
        const stockClass = stock === 0 ? 'out-of-stock' : (stock <= 5 ? 'low-stock' : 'in-stock');
        const stockText = stock === 0 ? 'Out of Stock' : (stock <= 5 ? `Low Stock (${stock})` : `In Stock (${stock})`);
        const cardClass = stock === 0 ? 'wishlist-item out-of-stock' : 'wishlist-item';
        
        return `
          <div class="${cardClass}" data-item-id="${item.id}">
            <button class="remove-btn" onclick="removeFromWishlist('${item.id}')" title="Remove from wishlist">×</button>
            <div class="stock-status ${stockClass}">${stockText}</div>
            <input type="checkbox" class="item-checkbox" data-item-id="${item.id}" style="position: absolute; top: 15px; left: 50px; z-index: 10;">
            
            <div class="wishlist-image" style="background-image: url('${item.img}')"></div>
            
            <div class="item-details">
              <div class="item-name">${item.name}</div>
              <div class="item-price">$${parseFloat(item.price).toFixed(2)}</div>
              <div class="item-added">Added to wishlist</div>
              
              <div class="quantity-controls">
                <button class="quantity-btn" onclick="updateWishlistQuantity('${item.id}', -1)" ${stock === 0 ? 'disabled' : ''}>-</button>
                <div class="quantity-display" id="wishlist-qty-${item.id}">1</div>
                <button class="quantity-btn" onclick="updateWishlistQuantity('${item.id}', 1)" ${stock === 0 ? 'disabled' : ''}>+</button>
              </div>
              
              <div class="action-buttons">
                <button class="btn-primary" onclick="addToCartFromWishlist('${item.id}', '${item.name}', ${item.price}, '${item.img}', ${stock})" ${stock === 0 ? 'disabled' : ''}>
                  ${stock === 0 ? 'Out of Stock' : 'Add to Cart'}
                </button>
                <button class="btn-secondary" onclick="viewProduct('${item.id}')">View Details</button>
              </div>
            </div>
          </div>
        `;
      }).join('');

      container.innerHTML = `<div class="wishlist-items">${itemsHTML}</div>`;
    }

    function removeFromWishlist(itemId) {
      const index = wishlistItems.findIndex(item => item.id === itemId);
      if (index > -1) {
        const removedItem = wishlistItems[index];
        wishlistItems.splice(index, 1);
        localStorage.setItem(`wishlist_${userId}`, JSON.stringify(wishlistItems));
        showToast(`${removedItem.name} removed from wishlist`);
        renderWishlist();
        updateWishlistStats();
      }
    }

    function addToCartFromWishlist(id, name, price, img, maxStock) {
      const quantity = wishlistQuantities[id] || 1;
      
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
      
      // Reset quantity
      wishlistQuantities[id] = 1;
      const displayEl = document.getElementById(`wishlist-qty-${id}`);
      if (displayEl) displayEl.textContent = '1';
    }

    function addSelectedToCart() {
      const checkboxes = document.querySelectorAll('.item-checkbox:checked');
      if (checkboxes.length === 0) {
        showToast('Please select items to add to cart');
        return;
      }
      
      let addedCount = 0;
      checkboxes.forEach(checkbox => {
        const itemId = checkbox.dataset.itemId;
        const item = wishlistItems.find(w => w.id === itemId);
        if (item) {
          const quantity = wishlistQuantities[itemId] || 1;
          const existing = cartItems.find(i => i.id === itemId);
          
          if (existing) {
            existing.quantity += quantity;
          } else {
            cartItems.push({id: itemId, name: item.name, price: item.price, img: item.img, quantity});
          }
          addedCount++;
        }
      });
      
      if (addedCount > 0) {
        localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
        updateCartCount();
        showToast(`${addedCount} items added to cart`);
        
        // Uncheck all checkboxes
        checkboxes.forEach(cb => cb.checked = false);
        updateSelectedCount();
      }
    }

    function removeSelected() {
      const checkboxes = document.querySelectorAll('.item-checkbox:checked');
      if (checkboxes.length === 0) {
        showToast('Please select items to remove');
        return;
      }
      
      if (confirm(`Remove ${checkboxes.length} items from wishlist?`)) {
        checkboxes.forEach(checkbox => {
          const itemId = checkbox.dataset.itemId;
          const index = wishlistItems.findIndex(item => item.id === itemId);
          if (index > -1) {
            wishlistItems.splice(index, 1);
          }
        });
        
        localStorage.setItem(`wishlist_${userId}`, JSON.stringify(wishlistItems));
        renderWishlist();
        updateWishlistStats();
        showToast('Selected items removed from wishlist');
      }
    }

    function updateSelectedCount() {
      const selected = document.querySelectorAll('.item-checkbox:checked').length;
      document.getElementById('selected-count').textContent = `(${selected} selected)`;
    }

    function viewProduct(itemId) {
      window.location.href = `product.php?liqour_id=${itemId}`;
    }

    function goToSearch() {
      window.location.href = 'index.php#liquor';
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

      requestAnimationFrame(() => {
        toast.style.opacity = "1";
      });

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

    // Event listeners
    document.addEventListener('change', function(e) {
      if (e.target.classList.contains('item-checkbox') || e.target.id === 'select-all') {
        if (e.target.id === 'select-all') {
          const checkboxes = document.querySelectorAll('.item-checkbox');
          checkboxes.forEach(cb => cb.checked = e.target.checked);
        }
        updateSelectedCount();
      }
    });

    window.addEventListener('click', (e) => {
      const modal = document.getElementById('logoutModal');
      if(e.target === modal) modal.style.display = 'none';
    });

    // Initialize
    updateCartCount();
    renderWishlist();
    updateWishlistStats();
  </script>

</body>
</html>