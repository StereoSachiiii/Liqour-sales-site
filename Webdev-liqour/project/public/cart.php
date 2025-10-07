<?php
include('session.php');
include('../Backend/sql-config.php'); 

// Fetch user's saved addresses
$userAddresses = [];
if (!$isGuest) {
    $stmt = $conn->prepare("SELECT address_id, address FROM user_addresses WHERE user_id = ? AND is_active = 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $userAddresses[] = $row;
    }
}

// Fetch stock
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
/* Previous styles remain the same */
#fixed-checkout-btn {
  position: fixed;
  bottom: 20px;
  left: 20px;
  background: #8B4513;
  color: white;
  border: none;
  padding: 15px 25px;
  border-radius: 50px;
  font-weight: bold;
  font-size: 16px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
  z-index: 1000;
  transition: all 0.3s ease;
  display: none;
}

#fixed-checkout-btn:hover {
  background: #A0522D;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(139, 69, 19, 0.4);
}

#fixed-checkout-btn.visible {
  display: block;
}

#cart-items-container {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  overflow-x: auto;
  gap: 15px;
  padding: 10px 0;
}

.new-item {
  flex: 0 0 auto;
}

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
  max-width: 500px;
  width: 90%;
  text-align: center;
  color:black;
  max-height: 80vh;
  overflow-y: auto;
}
.modal h2, .modal h3 { color:black; margin-bottom: 15px; }
.modal button {
  padding:12px 25px;
  border:none;
  border-radius:6px;
  font-weight:bold;
  cursor:pointer;
}
.modal .btn-primary {
   background:#8B4513;
   color:white; }
.modal .btn-secondary { background:#ccc; color:black; margin-left:10px; }

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

/* Address selection styles */
.address-option {
  background: #f8f9fa;
  border: 2px solid #dee2e6;
  border-radius: 8px;
  padding: 15px;
  margin: 10px 0;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: left;
}
.address-option:hover {
  border-color: #8B4513;
  background: #fff;
}
.address-option.selected {
  border-color: #8B4513;
  background: #fff3e0;
}
.address-option input[type="radio"] {
  margin-right: 10px;
}
.new-address-form {
  text-align: left;
  margin-top: 15px;
}
.new-address-form textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 6px;
  margin-top: 10px;
  min-height: 80px;
  font-family: inherit;
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
    <p style="color:black">You need to login or sign up to proceed with checkout. Your cart will be saved!</p>
    <button class="btn-primary" onclick="proceedToLogin()">Login / Sign Up</button>
    <button class="btn-secondary" onclick="closeGuestModal()">Cancel</button>
  </div>
</div>

<!-- Address Selection Modal (NEW) -->
<div id="address-modal" class="modal">
  <div class="modal-content">
    <h2>📍 Select Delivery Address</h2>
    
    <div id="saved-addresses">
      <?php if (count($userAddresses) > 0): ?>
        <?php foreach($userAddresses as $addr): ?>
          <div class="address-option" onclick="selectAddress(<?php echo $addr['address_id']; ?>)">
            <input type="radio" name="selected-address" value="<?php echo $addr['address_id']; ?>">
            <span><?php echo htmlspecialchars($addr['address']); ?></span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color:#666;font-size:14px;">No saved addresses. Please add a new one below.</p>
      <?php endif; ?>
    </div>
    
    <div class="new-address-form">
      <h3 style="font-size:16px;margin-bottom:10px;">Or Add New Address</h3>
      <textarea id="new-address-input" placeholder="Enter your complete delivery address..."></textarea>
      <button class="btn-primary" onclick="useNewAddress()" style="margin-top:10px;width:100%;">Use This Address</button>
    </div>
    
    <button class="btn-secondary" onclick="closeAddressModal()" style="margin-top:15px;">Cancel</button>
  </div>
</div>

<!-- Payment Modal -->
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

<!-- Other modals remain the same -->
<div id="cart-info-modal" class="modal">
  <div class="modal-content">
    <h3 id="cart-info-title"></h3>
    <p id="cart-info-msg"></p>
    <button class="btn-primary" onclick="closeCartInfoModal()">OK</button>
  </div>
</div>

<div id="order-success-modal" class="modal">
  <div class="modal-content">
    <h2>✅ Order Received</h2>
    <div id="order-success-details" style="text-align:left;margin-top:10px;"></div>
    <button class="btn-primary" onclick="closeOrderSuccessModal()">OK</button>
  </div>
</div>

<div id="order-fail-modal" class="modal">
  <div class="modal-content">
    <h2>⚠️ Order Failed</h2>
    <div id="order-fail-msg" style="text-align:left;margin-top:10px;"></div>
    <button class="btn-primary" onclick="closeOrderFailModal()">OK</button>
  </div>
</div>

<div id="logoutModal" class="modal">
  <div class="modal-content">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to logout?</p>
    <button class="btn-primary" onclick="logoutNow()">Yes</button>
    <button class="btn-secondary" onclick="closeLogoutModal()">Cancel</button>
  </div>
</div>

<div id="logoutModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
  <div style="background:#fff; padding:20px; border-radius:10px; text-align:center; min-width:300px;">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to logout?</p>
    <button onclick="logoutNow()" style="padding:8px 16px; background:#8B4513; color:white; border:none; border-radius:5px; margin-right:10px;">Yes</button>
    <button onclick="closeLogoutModal()" style="padding:8px 16px; background:#ccc; color:#333; border:none; border-radius:5px;">Cancel</button>
  </div>
</div>

<?php include('footer.php')?>
<button id="fixed-checkout-btn" onclick="scrollToCheckout()">
  🛒 Checkout
</button>

<script>
const cartCountEl = document.querySelector(".cart-count");
const userId = "<?php echo $userId; ?>";
const isGuest = <?php echo $isGuest ? 'true' : 'false'; ?>;
let cartItems = JSON.parse(localStorage.getItem(`cartItems_${userId}`)) || [];
const allStock = <?php echo json_encode($allStock); ?>;
let selectedAddressId = null;
let newAddressText = null;

// Previous functions remain the same (updateCartCount, renderCartItems, etc.)
// ... [Keep all your existing functions]

function showCartInfo(title,msg){ document.getElementById('cart-info-title').textContent=title; document.getElementById('cart-info-msg').textContent=msg; document.getElementById('cart-info-modal').style.display='flex'; }
function closeCartInfoModal(){ document.getElementById('cart-info-modal').style.display='none'; }
function showOrderSuccess(details){ document.getElementById('order-success-details').innerHTML=details; document.getElementById('order-success-modal').style.display='flex'; }
function closeOrderSuccessModal(){ cartItems=[]; localStorage.setItem(`cartItems_${userId}`,JSON.stringify(cartItems)); updateCartCount(); renderCartItems(); document.getElementById('order-success-modal').style.display='none'; }
function showOrderFail(msg){ document.getElementById('order-fail-msg').innerHTML=msg; document.getElementById('order-fail-modal').style.display='flex'; }
function closeOrderFailModal(){ document.getElementById('order-fail-modal').style.display='none'; }

function updateCartCount(){
  const total = cartItems.reduce((sum,item)=>sum+item.quantity,0);
  cartCountEl.textContent = total;
  cartCountEl.style.display = total>0?"inline-block":"none";
}

function getStockInfo(itemId) {
  const stockQty = allStock[itemId] || 0;
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
    }
    
    const div=document.createElement("div");
    div.className="new-item";
    div.style.padding="20px"; 
    div.style.marginBottom="20px";
    
    if (stockQty === 0) div.style.opacity = "0.7";
    
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
        <button onclick="removeItem(${index})" style="background:#ff4444;color:white;border:none;padding:8px 15px;border-radius:5px;cursor:pointer;">X</button>
      </div>
      <div style="font-size:16px;color:#8B4513;font-weight:bold;text-align:center;padding:10px;background:#f2c94c;border-radius:5px;">
        Subtotal: $${(item.price*item.quantity).toFixed(2)}
      </div>`;
    container.appendChild(div);
  });
  
  document.getElementById("cart-total").textContent=`$${totalPrice.toFixed(2)}`;
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
        showCartInfo("Stock Limit", `Only ${stockQty} units available.`);
        currentItem.quantity = stockQty;
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

// MODIFIED: Checkout now opens address modal
function checkout(){
  if(cartItems.length===0){ 
    showCartInfo("Empty Cart","Your cart is empty!"); 
    return; 
  }
  
  const stockIssues = [];
  cartItems.forEach(item => {
    const stockQty = getStockInfo(item.id);
    if (stockQty === 0) {
      stockIssues.push(`${item.name} is out of stock`);
    } else if (stockQty < item.quantity) {
      stockIssues.push(`${item.name}: only ${stockQty} available`);
    }
  });
  
  if (stockIssues.length > 0) {
    showCartInfo("Stock Issues", stockIssues.join('\n'));
    return;
  }
  
  if(isGuest){
    localStorage.setItem('pendingCheckoutCart', JSON.stringify(cartItems));
    localStorage.setItem('pendingCheckoutTimestamp', Date.now().toString());
    document.getElementById("guest-login-modal").style.display="flex";
    return;
  }
  
  // Show address selection modal
  document.getElementById("address-modal").style.display="flex";
}

// NEW: Address selection functions
function selectAddress(addressId) {
  selectedAddressId = addressId;
  newAddressText = null;
  
  // Visual feedback
  document.querySelectorAll('.address-option').forEach(opt => {
    opt.classList.remove('selected');
  });
  event.currentTarget.classList.add('selected');
  
  // Check the radio button
  event.currentTarget.querySelector('input[type="radio"]').checked = true;
  
  // Clear new address input
  document.getElementById('new-address-input').value = '';
  
  // Proceed to payment
  closeAddressModal();
  document.getElementById("payment-modal").style.display="flex";
}

function useNewAddress() {
  const addressInput = document.getElementById('new-address-input').value.trim();
  
  if (!addressInput) {
    showCartInfo("Address Required", "Please enter a delivery address.");
    return;
  }
  
  newAddressText = addressInput;
  selectedAddressId = null;
  
  closeAddressModal();
  document.getElementById("payment-modal").style.display="flex";
}

function closeAddressModal() {
  document.getElementById("address-modal").style.display="none";
}

function closeGuestModal(){ document.getElementById("guest-login-modal").style.display="none"; }
function proceedToLogin(){
  localStorage.setItem('pendingCheckoutCart', JSON.stringify(cartItems));
  localStorage.setItem('pendingCheckoutTimestamp', Date.now().toString());
  window.location.href='login-signup.php?checkout=1';
}
function closePaymentModal(){ document.getElementById("payment-modal").style.display="none"; }

// MODIFIED: Send address info with order
function confirmPayment(){
  const card=document.getElementById("mock-card-number").value.trim();
  const expiry=document.getElementById("mock-expiry").value.trim();
  const cvv=document.getElementById("mock-cvv").value.trim();
  
  if(!card||!expiry||!cvv){ 
    showCartInfo("Payment Error","Please fill all payment fields."); 
    return; 
  }
  
  if (!selectedAddressId && !newAddressText) {
    showCartInfo("Address Required", "Please select or add a delivery address.");
    closePaymentModal();
    document.getElementById("address-modal").style.display="flex";
    return;
  }
  
  closePaymentModal();
  
  fetch("saveCart.php",{ 
    method:"POST", 
    headers:{"Content-Type":"application/json"}, 
    body:JSON.stringify({
      cart: cartItems,
      addressId: selectedAddressId,
      newAddress: newAddressText
    }) 
  })
  .then(res=>res.json())
  .then(result=>{ 
    if(result.status==="success"){ 
      const details=cartItems.map(i=>`${i.name} x${i.quantity} = $${(i.price*i.quantity).toFixed(2)}`).join("<br>"); 
      showOrderSuccess(details); 
    } else {
      showOrderFail(result.message || "Error placing order"); 
    }
  })
  .catch(err=>{ 
    console.error(err); 
    showOrderFail("Something went wrong"); 
  });
}

function scrollToCheckout() {
  const checkoutButton = document.querySelector('#cart-summary button');
  if (checkoutButton) {
    checkoutButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}
// Profile dropdown toggle
document.querySelector(".profile-container")?.addEventListener("click", () => {
  document.querySelector(".profile-expand").classList.toggle("profile-expand-active");
});

// Logout modal functions
function showLogoutModal() {
  const isGuest = <?php echo $isGuest ? 'true' : 'false'; ?>;
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

// Close modal if clicked outside
window.addEventListener('click', (e) => {
  const modal = document.getElementById('logoutModal');
  if(e.target === modal) modal.style.display = 'none';
});

// Scroll to liquor search (redirects to index if on cart page)
function scrollToLiquorSearch() {
  window.location.href = 'index.php#liquor';
}
updateCartCount();
renderCartItems();
</script>

</body>
</html>