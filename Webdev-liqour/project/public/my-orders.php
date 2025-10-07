<?php
include('session.php');
include("../Backend/sql-config.php");

$isGuest = !isset($_SESSION['userId']) || $_SESSION['isGuest'];

$sql = "SELECT o.order_id, o.status, o.total, o.created_at,
               oi.liqour_id, oi.quantity, oi.price,
               l.name AS product_name, l.image_url
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN liqours l ON oi.liqour_id = l.liqour_id
        WHERE o.user_id = ? AND o.is_active = 1
        ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orderId = $row['order_id'];
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'order_id' => $row['order_id'],
            'status' => $row['status'],
            'total' => $row['total'],
            'created_at' => $row['created_at'],
            'items' => []
        ];
    }
    $orders[$orderId]['items'][] = [
        'liqour_id' => $row['liqour_id'],
        'product_name' => $row['product_name'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
        'image_url' => $row['image_url']
    ];
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders - LiquorStore</title>
  <link rel="stylesheet" href="css/my-orders.css">
  <link rel="stylesheet" href="css/index.css">
</head>
<body>

<!-- NAVBAR -->
<?php include('navbar.php'); ?>

<!-- ORDERS SECTION -->
<section class="new" id="orders-section">
  <h2 class="title-text">📦 My Orders</h2>

  <div class="orders-list">
    <?php if (empty($orders)): ?>
      <div class="no-orders">You haven't placed any orders yet.</div>
    <?php else: ?>
      <?php foreach ($orders as $order): ?>
        <div class="order-row">
          <div class="order-header">
            <div>
              <h3>Order </h3>
              <p>Placed on: <?= date('M d, Y', strtotime($order['created_at'])) ?></p>
            </div>
            <div class="order-meta">
              <div class="order-total">Total: $<?= number_format($order['total'],2) ?></div>
              <?php
                $statusClass = $order['status'] === 'fulfilled' ? 'fulfilled' :
                               ($order['status'] === 'pending' ? 'pending' : 'other');
              ?>
              <span class="order-status <?= $statusClass ?>"><?= strtoupper($order['status']) ?></span>
              <?php if ($order['status'] !== 'completed'): ?>
                <a href="#" class="remove-order-btn" data-order-id="<?= $order['order_id'] ?>" onclick="showRemoveOrderModal(<?= $order['order_id'] ?>)">
                  Remove Order
                </a>
              <?php endif; ?>
            </div>
          </div>

          <div class="order-items">
            <?php foreach ($order['items'] as $item): ?>
              <div class="order-item">
                <div class="order-item-img" style="background-image: url('<?= $item['image_url'] ?>');"></div>
                <div class="order-item-details">
                  <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                  <p>Quantity: <?= $item['quantity'] ?> × $<?= number_format($item['price'],2) ?> = $<?= number_format($item['quantity'] * $item['price'],2) ?></p>
                </div>
                <?php if ($order['status'] === 'completed'): ?>
                  <a href="user-reviews.php?liqour_id=<?= $item['liqour_id'] ?>&order_id=<?= $order['order_id'] ?>&product_name=<?= urlencode($item['product_name']) ?>" class="review-btn">
                    + Review
                  </a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<?php include('footer.php') ?>

<!-- LOGOUT MODAL -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to logout?</p>
    <button class="btn-primary" onclick="logoutNow()">Yes</button>
    <button class="btn-secondary" onclick="closeLogoutModal()">Cancel</button>
  </div>
</div>

<!-- REMOVE ORDER MODAL -->
<div id="removeOrderModal" class="modal">
  <div class="modal-content">
    <h3>Confirm Order Removal</h3>
    <p>Are you sure you want to remove this order? This action cannot be undone.</p>
    <button class="btn-primary" onclick="removeOrderNow()">Yes</button>
    <button class="btn-secondary" onclick="closeRemoveOrderModal()">Cancel</button>
  </div>
</div>

<script>
const isGuest = <?= $isGuest ? 'true' : 'false' ?>;
let currentOrderId = null;

// Profile dropdown
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, setting up profile dropdown...');
    
    const profileContainer = document.querySelector(".profile-container");
    const profileDropdown = document.querySelector(".profile-expand");
    
    console.log('Profile container found:', profileContainer);
    console.log('Profile dropdown found:', profileDropdown);
    
    if (!profileContainer || !profileDropdown) {
        console.error('Profile elements not found!');
        return;
    }
    
    let isOpen = false;
    
    profileContainer.addEventListener("click", function(e) {
        console.log('Profile container clicked');
        e.preventDefault();
        e.stopPropagation();
        
        isOpen = !isOpen;
        
        if (isOpen) {
            profileDropdown.classList.add("profile-expand-active");
            console.log('Dropdown opened');
        } else {
            profileDropdown.classList.remove("profile-expand-active");
            console.log('Dropdown closed');
        }
    });
    
    profileDropdown.addEventListener("click", function(e) {
        console.log('Clicked inside dropdown');
        e.stopPropagation();
    });
    
    document.addEventListener("click", function(e) {
        if (isOpen && !profileContainer.contains(e.target)) {
            console.log('Clicked outside, closing dropdown');
            isOpen = false;
            profileDropdown.classList.remove("profile-expand-active");
        }
    });
    
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape" && isOpen) {
            console.log('Escape pressed, closing dropdown');
            isOpen = false;
            profileDropdown.classList.remove("profile-expand-active");
        }
    });
    
    console.log('Profile dropdown setup complete');
});

// Logout modal
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

// Remove order modal
function showRemoveOrderModal(orderId) {
    currentOrderId = orderId;
    document.getElementById('removeOrderModal').style.display = 'flex';
}
function closeRemoveOrderModal() {
    document.getElementById('removeOrderModal').style.display = 'none';
    currentOrderId = null;
}
function removeOrderNow() {
    if (currentOrderId) {
        window.location.href = `remove-order.php?order_id=${currentOrderId}`;
    }
    closeRemoveOrderModal();
}

// Close modals on backdrop click
window.addEventListener('click', (e) => {
    ['logoutModal', 'removeOrderModal', 'guest-login-modal', 'payment-modal'].forEach(id => {
        if (e.target === document.getElementById(id)) {
            document.getElementById(id).style.display = 'none';
            if (id === 'removeOrderModal') {
                currentOrderId = null;
            }
        }
    });
});

// Cart count
const cartCountEl = document.querySelector(".cart-count");
const userId = "<?php echo $userId; ?>";

let cartItems = JSON.parse(localStorage.getItem(`cartItems_${userId}`)) || [];

function updateCartCount() {
    const total = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    cartCountEl.textContent = total;
    cartCountEl.style.display = total > 0 ? "inline-block" : "none";
}

updateCartCount();
</script>

</body>
</html>