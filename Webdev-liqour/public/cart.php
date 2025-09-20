<?php
include('session.php');
// Fetch all cart items from localStorage via JavaScript (we'll handle this differently)
// For now, we'll fetch stock for all liquors to ensure we have complete data
include('../Backend/sql-config.php'); 
$allStock = [];
$stmt = $conn->prepare("
    SELECT liqour_id, SUM(quantity) as stock_quantity
    FROM stock
    WHERE is_active=1
    GROUP BY liqour_id
");
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()){
    $allStock[$row['liqour_id']] = intval($row['stock_quantity']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shopping Cart - LiquorStore</title>
<link rel="stylesheet" href="css/cart.css">


<style>
/* Unified modal styling */
.modal {
  display: none;
  position: fixed;
  top:0; left:0;
  width:100%; height:100%;
  background: rgba(0,0,0,0.6);
  justify-content: center;
  align-items: center;
  z-index: 2000;
}
.modal-content {
  background: #fff;
  padding: 30px;
  border-radius: 12px;
  max-width: 400px;
  width: 90%;
  text-align: center;
}
.modal h2, .modal h3 { color:#8B4513; margin-bottom: 15px; }
.modal button {
  padding:12px 25px;
  border:none;
  border-radius:6px;
  font-weight:bold;
  cursor:pointer;
}
.modal .btn-primary { background:#8B4513; color:white; }
.modal .btn-secondary { background:#ccc; color:#333; margin-left:10px; }
.modal .close { position:absolute; top:10px; right:15px; font-size:20px; cursor:pointer; }

/* Stock warning styles */
.stock-warning {
  color: #ff6b6b;
  font-weight: bold;
  font-size: 12px;
}
.stock-ok {
  color: #51cf66;
  font-weight: bold;
  font-size: 12px;
}
.out-of-stock {
  color: #ff4757;
  font-weight: bold;
  font-size: 12px;
}
</style>
</head>
<body>

<?php include('navbar.php');?>

<section class="new">
  <h2 class="title-text">🛒 Your Shopping Cart</h2>
  <div id="cart-items-container" class="new-arrivals"></div>

  <div id="cart-footer">
    <div id="cart-summary" style="display:none;">
      <h3>Order Summary</h3>
      <div>Total: <span id="cart-total">$0.00</span></div>
      <button onclick="checkout()">Proceed to Checkout</button>
    </div>
    <div id="empty-cart" style="display:none;">
      <h3>Your cart is empty</h3>
      <p>Add some products from our store to get started!</p>
      <a href="index.php" style="padding:12px 25px;background:#8B4513;color:white;text-decoration:none;border-radius:5px;font-weight:bold;">Continue Shopping</a>
    </div>
  </div>
</section>

<!-- Guest login modal -->
<div id="guest-login-modal" class="modal">
  <div class="modal-content">
    <h2>Login Required</h2>
    <p>You need to login or sign up to proceed with checkout. Your cart will be saved!</p>
    <button class="btn-primary" onclick="proceedToLogin()">Login / Sign Up</button>
    <button class="btn-secondary" onclick="closeGuestModal()">Cancel</button>
  </div>
</div>

<!-- Payment modal -->
<div id="payment-modal" class="modal">
  <div class="modal-content">
    <h2>💳 Credit Payment</h2>
    <input type="text" id="mock-card-number" placeholder="Card Number" style="width:80%;padding:10px;margin:5px 0;border:1px solid #ccc;border-radius:6px;"><br>
    <input type="text" id="mock-expiry" placeholder="MM/YY" style="width:80%;padding:10px;margin:5px 0;border:1px solid #ccc;border-radius:6px;"><br>
    <input type="text" id="mock-cvv" placeholder="CVV" style="width:80%;padding:10px;margin:5px 0;border:1px solid #ccc;border-radius:6px;"><br>
    <button class="btn-primary" onclick="confirmPayment()">Pay</button>
    <button class="btn-secondary" onclick="closePaymentModal()">Cancel</button>
  </div>
</div>

<!-- Cart Info / Notifications -->
<div id="cart-info-modal" class="modal">
  <div class="modal-content">
    <h3 id="cart-info-title"></h3>
    <p id="cart-info-msg"></p>
    <button class="btn-primary" onclick="closeCartInfoModal()">OK</button>
  </div>
</div>

<!-- Order success modal -->
<div id="order-success-modal" class="modal">
  <div class="modal-content">
    <h2>✅ Order Received</h2>
    <div id="order-success-details" style="text-align:left;margin-top:10px;"></div>
    <button class="btn-primary" onclick="closeOrderSuccessModal()">OK</button>
  </div>
</div>

<!-- Order failure modal -->
<div id="order-fail-modal" class="modal">
  <div class="modal-content">
    <h2>⚠️ Order Failed</h2>
    <div id="order-fail-msg" style="text-align:left;margin-top:10px;"></div>
    <button class="btn-primary" onclick="closeOrderFailModal()">OK</button>
  </div>
</div>

<!-- Logout modal -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to logout?</p>
    <button class="btn-primary" onclick="logoutNow()">Yes</button>
    <button class="btn-secondary" onclick="closeLogoutModal()">Cancel</button>
  </div>
</div>

<?php include('footer.php')?>

<script>
// Variables and initialization
const cartCountEl = document.querySelector(".cart-count");
const userId = "<?php echo $userId; ?>";
const isGuest = <?php echo $isGuest ? 'true' : 'false'; ?>;
let cartItems = JSON.parse(localStorage.getItem(`cartItems_${userId}`)) || [];
const allStock = <?php echo json_encode($allStock); ?>;

console.log('Stock data loaded:', allStock);

// Profile dropdown - Click only functionality
document.addEventListener('DOMContentLoaded', function() {
    const profileContainer = document.querySelector(".profile-container");
    const profileDropdown = document.querySelector(".profile-expand");
    
    if (profileContainer && profileDropdown) {
        // Toggle dropdown on profile container click
        profileContainer.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            profileDropdown.classList.toggle("profile-expand-active");
        });
        
        // Prevent dropdown from closing when clicking inside it
        profileDropdown.addEventListener("click", function(e) {
            e.stopPropagation();
        });
        
        // Close dropdown when clicking outside
        document.addEventListener("click", function(e) {
            if (!profileContainer.contains(e.target)) {
                profileDropdown.classList.remove("profile-expand-active");
            }
        });
        
        // Close dropdown on escape key
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                profileDropdown.classList.remove("profile-expand-active");
            }
        });
    }
});

// Modal helpers
function showCartInfo(title,msg){ document.getElementById('cart-info-title').textContent=title; document.getElementById('cart-info-msg').textContent=msg; document.getElementById('cart-info-modal').style.display='flex'; }
function closeCartInfoModal(){ document.getElementById('cart-info-modal').style.display='none'; }
function showOrderSuccess(details){ document.getElementById('order-success-details').innerHTML=details; document.getElementById('order-success-modal').style.display='flex'; }
function closeOrderSuccessModal(){ cartItems=[]; localStorage.setItem(`cartItems_${userId}`,JSON.stringify(cartItems)); updateCartCount(); renderCartItems(); document.getElementById('order-success-modal').style.display='none'; }
function showOrderFail(msg){ document.getElementById('order-fail-msg').innerHTML=msg; document.getElementById('order-fail-modal').style.display='flex'; }
function closeOrderFailModal(){ document.getElementById('order-fail-modal').style.display='none'; }

function restoreCheckoutCart() {
  const pendingCart = localStorage.getItem('pendingCheckoutCart');
  const timestamp = localStorage.getItem('pendingCheckoutTimestamp');
  if (pendingCart && timestamp) {
    const cartAge = Date.now() - parseInt(timestamp);
    if (cartAge < 3600000) {
      const pendingItems = JSON.parse(pendingCart);
      pendingItems.forEach(pendingItem => {
        const existing = cartItems.find(item => item.id === pendingItem.id);
        if (existing) existing.quantity += pendingItem.quantity; 
        else cartItems.push(pendingItem);
      });
      localStorage.setItem(`cartItems_${userId}`, JSON.stringify(cartItems));
      localStorage.removeItem('pendingCheckoutCart');
      localStorage.removeItem('pendingCheckoutTimestamp');
      showCartInfo("Welcome Back!","Your previous cart has been restored.");
    } else {
      localStorage.removeItem('pendingCheckoutCart');
      localStorage.removeItem('pendingCheckoutTimestamp');
    }
  }
}
if (!isGuest) restoreCheckoutCart();

function updateCartCount(){
  const total = cartItems.reduce((sum,item)=>sum+item.quantity,0);
  cartCountEl.textContent = total;
  cartCountEl.style.display = total>0?"inline-block":"none";
}

function getStockInfo(itemId) {
  const stockQty = allStock[itemId] || 0;
  console.log(`Stock for item ${itemId}:`, stockQty);
  return stockQty;
}

function renderCartItems(){
  const container = document.getElementById("cart-items-container");
  const cartSummary = document.getElementById("cart-summary");
  const emptyCart = document.getElementById("empty-cart");
  container.innerHTML="";
  
  if(cartItems.length===0){ 
    cartSummary.style.display="none"; 
    emptyCart.style.display="block"; 
    return; 
  }
  
  emptyCart.style.display="none"; 
  cartSummary.style.display="block";
  let totalPrice=0;
  
  cartItems.forEach((item,index)=>{
    const stockQty = getStockInfo(item.id);
    totalPrice += item.price * item.quantity;
    
    let stockClass = "stock-ok";
    let stockText = `Available: ${stockQty}`;
    
    if (stockQty === 0) {
      stockClass = "out-of-stock";
      stockText = "Out of Stock";
    } else if (stockQty < item.quantity) {
      stockClass = "stock-warning";
      stockText = `Available: ${stockQty} (Not enough in stock!)`;
    } else if (stockQty <= 5) {
      stockClass = "stock-warning";
      stockText = `Available: ${stockQty} (Low stock)`;
    }
    
    const div=document.createElement("div");
    div.className="new-item";
    div.style.padding="20px"; 
    div.style.marginBottom="20px";
    
    if (stockQty === 0) {
      div.style.opacity = "0.7";
      div.style.border = "2px solid #ff4757";
    }
    
    div.innerHTML=`
      <div class="image-container" style="background-image:url('${item.img}');height:200px;margin-bottom:15px;background-size:cover;border-radius:8px;"></div>
      <div style="font-size:18px;font-weight:bold;margin-bottom:5px;">${item.name}</div>
      <div style="font-size:16px;color:#8B4513;font-weight:bold;margin-bottom:2px;">$${item.price} each</div>
      <div class="${stockClass}" style="margin-bottom:10px;">${stockText}</div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:15px;">
        <div style="display:flex;align-items:center;gap:10px;">
          <button onclick="updateQuantity(${index},-1)" style="background:#8B4513;color:white;border:none;width:35px;height:35px;border-radius:5px;cursor:pointer;">-</button>
          <span style="font-size:18px;font-weight:bold;min-width:40px;text-align:center;">${item.quantity}</span>
          <button onclick="updateQuantity(${index},1)" 
            ${item.quantity >= stockQty || stockQty === 0 ? 
              'disabled style="background:#ccc;color:#666;cursor:not-allowed;border:none;width:35px;height:35px;border-radius:5px;"' : 
              'style="background:#8B4513;color:white;border:none;width:35px;height:35px;border-radius:5px;cursor:pointer;"'
            }>+</button>
        </div>
        <button onclick="removeItem(${index})" style="background:#ff4444;color:white;border:none;padding:8px 15px;border-radius:5px;cursor:pointer;">Remove</button>
      </div>
      <div style="font-size:16px;color:#8B4513;font-weight:bold;text-align:center;padding:10px;background:#f2c94c;border-radius:5px;">
        Subtotal: $${(item.price*item.quantity).toFixed(2)}
      </div>
      ${stockQty < item.quantity ? 
        '<div style="background:#fff3cd;border:1px solid #ffeaa7;color:#856404;padding:8px;border-radius:5px;font-size:12px;text-align:center;margin-top:10px;"><strong>Warning:</strong> Not enough stock available. Please reduce quantity.</div>' : 
        ''
      }`;
    container.appendChild(div);
  });
  
  document.getElementById("cart-total").textContent=`$${totalPrice.toFixed(2)}`;
  document.getElementById("cart-summary").querySelector("h3").textContent=`Order Summary (${cartItems.reduce((s,i)=>s+i.quantity,0)} item${cartItems.reduce((s,i)=>s+i.quantity,0)!==1?'s':''})`;
}

function updateQuantity(index, change){
  if(cartItems[index]){
    const currentItem = cartItems[index];
    const stockQty = getStockInfo(currentItem.id);
    const newQuantity = currentItem.quantity + change;
    
    if(newQuantity <= 0) {
      cartItems.splice(index,1);
    } else {
      if(newQuantity > stockQty) {
        if(stockQty === 0) {
          showCartInfo("Out of Stock", `${currentItem.name} is currently out of stock.`);
          return;
        } else {
          showCartInfo("Stock Limit", `Only ${stockQty} units of ${currentItem.name} are available.`);
          currentItem.quantity = stockQty;
        }
      } else {
        currentItem.quantity = newQuantity;
      }
    }
    
    localStorage.setItem(`cartItems_${userId}`,JSON.stringify(cartItems));
    updateCartCount();
    renderCartItems();
  }
}

function removeItem(index){
  cartItems.splice(index,1);
  localStorage.setItem(`cartItems_${userId}`,JSON.stringify(cartItems));
  updateCartCount();
  renderCartItems();
}

function checkout(){
  if(cartItems.length===0){ 
    showCartInfo("Empty Cart","Your cart is empty! Add products before checkout."); 
    return; 
  }
  
  const stockIssues = [];
  cartItems.forEach(item => {
    const stockQty = getStockInfo(item.id);
    if (stockQty === 0) {
      stockIssues.push(`${item.name} is out of stock`);
    } else if (stockQty < item.quantity) {
      stockIssues.push(`${item.name}: only ${stockQty} available (you have ${item.quantity} in cart)`);
    }
  });
  
  if (stockIssues.length > 0) {
    showCartInfo("Stock Issues", "Please fix these stock issues before checkout:\n" + stockIssues.join('\n'));
    return;
  }
  
  if(isGuest){
    localStorage.setItem('pendingCheckoutCart', JSON.stringify(cartItems));
    localStorage.setItem('pendingCheckoutTimestamp', Date.now().toString());
    document.getElementById("guest-login-modal").style.display="flex";
    return;
  }
  document.getElementById("payment-modal").style.display="flex";
}

function closeGuestModal(){ document.getElementById("guest-login-modal").style.display="none"; }
function proceedToLogin(){
  document.getElementById("guest-login-modal").style.display="none";
  localStorage.setItem('pendingCheckoutCart', JSON.stringify(cartItems));
  localStorage.setItem('pendingCheckoutTimestamp', Date.now().toString());
  window.location.href='login-signup.php?checkout=1';
}
function closePaymentModal(){ document.getElementById("payment-modal").style.display="none"; }

function confirmPayment(){
  const card=document.getElementById("mock-card-number").value.trim();
  const expiry=document.getElementById("mock-expiry").value.trim();
  const cvv=document.getElementById("mock-cvv").value.trim();
  if(!card||!expiry||!cvv){ showCartInfo("Payment Error","Please fill all mock payment fields."); return; }
  closePaymentModal();
  fetch("saveCart.php",{ method:"POST", headers:{"Content-Type":"application/json"}, body:JSON.stringify({cart:cartItems}) })
  .then(res=>res.json())
  .then(result=>{ if(result.status==="success"){ const details=cartItems.map(i=>`${i.name} x${i.quantity} = $${(i.price*i.quantity).toFixed(2)}`).join("<br>"); showOrderSuccess(details); } else showOrderFail(result.message || "Error placing order"); })
  .catch(err=>{ console.error(err); showOrderFail("Something went wrong"); });
}

function showLogoutModal(){ if(isGuest){ window.location.href='login-signup.php'; } else { document.getElementById('logoutModal').style.display='flex'; } }
function closeLogoutModal(){ document.getElementById('logoutModal').style.display='none'; }
function logoutNow(){ window.location.href="../Backend/auth/logout.php"; }

window.addEventListener('click',(e)=>{ ['logoutModal','guest-login-modal','payment-modal'].forEach(id=>{ if(e.target===document.getElementById(id)) document.getElementById(id).style.display='none'; }); });

// Initialize
updateCartCount();
renderCartItems();
</script>

</body>
</html>