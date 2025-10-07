<?php
include('session.php');
include('../Backend/sql-config.php');

// Get stock data for all liquors
$stockData = [];
$stockSql = "SELECT l.liqour_id, COALESCE(SUM(s.quantity), 0) as total_stock 
            FROM liqours l
            LEFT JOIN stock s ON l.liqour_id = s.liqour_id AND s.is_active = 1
            WHERE l.is_active = 1
            GROUP BY l.liqour_id";
$stockResult = $conn->query($stockSql);
if ($stockResult && $stockResult->num_rows > 0) {
    while ($row = $stockResult->fetch_assoc()) {
        $stockData[$row['liqour_id']] = (int)$row['total_stock'];
    }
}
$conn->close();
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
  <?php include('navbar.php'); ?>

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
        <button class="btn-bulk btn-bulk-danger" onclick="showRemoveSelectedModal()">Remove Selected</button>
      </div>
    </div>

    <div id="wishlist-content">
      <!-- Content will be populated by JavaScript -->
    </div>
  </div>

  <!-- Logout Modal -->
  <div id="logoutModal" class="modal">
    <div class="modal-content">
      <h3>Confirm Logout</h3>
      <p>Are you sure you want to logout?</p>
      <button class="btn-primary" onclick="logoutNow()">Yes</button>
      <button class="btn-secondary" onclick="closeLogoutModal()">Cancel</button>
    </div>
  </div>

  <!-- Remove Selected Modal -->
  <div id="removeSelectedModal" class="modal">
    <div class="modal-content">
      <h3>Confirm Removal</h3>
      <p id="remove-selected-message">Are you sure you want to remove the selected items from your wishlist?</p>
      <button class="btn-primary" onclick="removeSelectedNow()">Yes</button>
      <button class="btn-secondary" onclick="closeRemoveSelectedModal()">Cancel</button>
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
    
    // Pass PHP stock data to JavaScript
    const stockData = <?php echo json_encode($stockData); ?>;
    
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
    
    let cartItems = JSON.parse(localStorage.getItem(`cartItems_${userId}`)) || [];
    let wishlistItems = getWishlistItems();
    const wishlistQuantities = {};

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

    function updateCartCount() {
        const total = cartItems.reduce((sum, item) => sum + item.quantity, 0);
        cartCountEl.textContent = total;
        cartCountEl.style.display = total > 0 ? "inline-block" : "none";
    }

    function updateWishlistStats() {
        const currentWishlist = getWishlistItems();
        const totalItems = currentWishlist.length;
        const totalValue = currentWishlist.reduce((sum, item) => sum + parseFloat(item.price || 0), 0);
        
        const totalItemsEl = document.getElementById('total-items');
        const totalValueEl = document.getElementById('total-value');
        
        if (totalItemsEl) totalItemsEl.textContent = totalItems;
        if (totalValueEl) totalValueEl.textContent = `$${totalValue.toFixed(2)}`;
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
        wishlistItems = getWishlistItems(); 
        
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
            const itemId = parseInt(item.id);
            const stock = stockData[itemId] || stockData[String(itemId)] || 0;
            console.log(`Item ${item.name} (ID: ${itemId}): Stock = ${stock}`);
            
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

          container.innerHTML = `<div class="wishlist-items">${itemsHTML}`;
    }

    function removeFromWishlist(itemId) {
        wishlistItems = getWishlistItems();
        const index = wishlistItems.findIndex(item => String(item.id) === String(itemId));
        
        if (index > -1) {
            const removedItem = wishlistItems[index];
            wishlistItems.splice(index, 1);
            saveWishlistItems(wishlistItems);
            
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
        let hasStockIssues = false;
        
        checkboxes.forEach(checkbox => {
            const itemId = checkbox.dataset.itemId;
            const item = wishlistItems.find(w => w.id === itemId);
            const stock = stockData[itemId] || stockData[String(itemId)] || 0;
            
            if (item && stock > 0) {
                const quantity = wishlistQuantities[itemId] || 1;
                const existing = cartItems.find(i => i.id === itemId);
                const currentInCart = existing ? existing.quantity : 0;
                
                if (currentInCart + quantity <= stock) {
                    if (existing) {
                        existing.quantity += quantity;
                    } else {
                        cartItems.push({id: itemId, name: item.name, price: item.price, img: item.img, quantity});
                    }
                    addedCount++;
                } else {
                    hasStockIssues = true;
                }
            } else if (stock === 0) {
                hasStockIssues = true;
            }
        });
        
        if (addedCount > 0) {
            localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
            updateCartCount();
            showToast(`${addedCount} items added to cart`);
            
            checkboxes.forEach(cb => cb.checked = false);
            updateSelectedCount();
        }
        
        if (hasStockIssues) {
            showToast('Some items were skipped due to insufficient stock');
        }
    }

    function showRemoveSelectedModal() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        if (checkboxes.length === 0) {
            showToast('Please select items to remove');
            return;
        }
        document.getElementById('remove-selected-message').textContent = `Are you sure you want to remove ${checkboxes.length} item${checkboxes.length > 1 ? 's' : ''} from your wishlist?`;
        document.getElementById('removeSelectedModal').style.display = 'flex';
    }

    function closeRemoveSelectedModal() {
        document.getElementById('removeSelectedModal').style.display = 'none';
    }

    function removeSelectedNow() {
        const checkboxes = document.querySelectorAll('.item-checkbox:checked');
        let currentWishlist = getWishlistItems();
        
        checkboxes.forEach(checkbox => {
            const itemId = checkbox.dataset.itemId;
            const index = currentWishlist.findIndex(item => String(item.id) === String(itemId));
            if (index > -1) {
                currentWishlist.splice(index, 1);
            }
        });
        
        saveWishlistItems(currentWishlist);
        renderWishlist();
        updateWishlistStats();
        showToast(`Selected items removed from wishlist`);
        closeRemoveSelectedModal();
    }

    function updateSelectedCount() {
        const selected = document.querySelectorAll('.item-checkbox:checked').length;
        document.getElementById('selected-count').textContent = `(${selected} selected)`;
    }

    function viewProduct(itemId) {
        window.location.href = `product.php?liqour_id=${itemId}`;
    }

    function showToast(message) {
        const toastContainer = document.getElementById('toast-container');
        
        const toast = document.createElement('div');
        toast.textContent = message;
        toast.className = 'toast';
        toastContainer.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.opacity = '1';
        });

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.addEventListener('transitionend', () => toast.remove());
        }, 3000);
    }

    function showLogoutModal() {
        if (isGuest) {
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
        ['logoutModal', 'removeSelectedModal'].forEach(id => {
            if (e.target === document.getElementById(id)) {
                document.getElementById(id).style.display = 'none';
            }
        });
    });

    // Add wishlist button styles
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

    // Initialize
    updateCartCount();
    renderWishlist();
    updateWishlistStats();
  </script>

</body>
</html>