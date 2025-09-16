<?php
session_start();
if(!isset($_SESSION['userId'], $_SESSION['username'])){
    header("Location: public/login-signup.php");
    exit();
}

include("../Backend/sql-config.php");

$userId = $_SESSION['userId'];

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
  <link rel="stylesheet" href="css/index.css">
</head>
<body>
<!-- navbar  -->
<nav class="nav-bar">
       <a href="index.php"><div class="logo-container"><img src="src\icons\icon.svg" alt="LiquorStore Logo">    </div></a>

  <div class="nav-options-container nav-options-font">
        <div class="nav-option"><a href="index.php">HOME</a></div>

    <div class="nav-option"><a href="index.php#new-arrivals">NEW ARRIVALS</a></div>
    <div class="nav-option"><a href="index.php#liquor">LIQUOR</a></div>
    <div class="nav-option"><a href="index.php#categories">CATEGORIES</a></div>
  </div>
  <div class="profile-search-cart">
    <div class="profile-container">
      <div class="profile">👤</div>
      <div class="profile-expand">
        <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></p>
        
        <p><a href="profile.php">Profile</a></p>
        <p><a href="Backend/logout.php">Logout</a></p>
      </div>
    </div>
  </div>
</nav>


  <section class="new" id="orders-section">
    <h2 class="title-text">📦 My Orders</h2>
    
    <div style="max-width: 1000px; margin: 0 auto; padding: 20px;">
      <?php if (empty($orders)): ?>
        <p style="text-align: center; color: #666;">You haven't placed any orders yet.</p>
      <?php else: ?>
        <?php foreach ($orders as $order): ?>
          <div style="border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; padding: 20px; background: #f9f9f9;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
              <div>
                <h3 style="margin: 0; color: #333;">Order #<?= $order['order_id'] ?></h3>
                <p style="margin: 5px 0; color: #666; font-size: 0.9em;">
                  Placed on: <?= date('M d, Y', strtotime($order['created_at'])) ?>
                </p>
              </div>
              <div style="text-align: right;">
                <div style="font-size: 1.2em; font-weight: bold; color: #333;">
                  Total: $<?= number_format($order['total'], 2) ?>
                </div>
                <div style="margin-top: 5px;">
                  <?php if ($order['status'] === 'fulfilled'): ?>
                    <span style="background: #4CAF50; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.85em;">
                      ✓ FULFILLED
                    </span>
                  <?php elseif ($order['status'] === 'pending'): ?>
                    <span style="background: #FF9800; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.85em;">
                      ⏳ PENDING
                    </span>
                  <?php else: ?>
                    <span style="background: #666; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.85em;">
                      <?= strtoupper($order['status']) ?>
                    </span>
                  <?php endif; ?>                  
                </div>
                 <?php if ($order['status'] !== 'completed'): ?>
              <div style="margin-top: 10px; text-align: right;">
                <a href="remove-order.php?order_id=<?= $order['order_id'] ?>"
   onclick="if(confirm('Are you sure you want to remove this order?')) { window.location=this.href; return false; } else { return false; }"
   style="background: #ff4444; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 0.85em;">
   Remove Order
</a>

        </div>
    <?php endif; ?>
              </div>
            </div>

            <div>
              <?php foreach ($order['items'] as $item): ?>
                <div style="display: flex; align-items: center; margin-bottom: 15px; padding: 10px; background: white; border-radius: 6px;">
                  <div style="width: 60px; height: 60px; background-image: url('<?= $item['image_url'] ?>'); background-size: cover; background-position: center; border-radius: 4px; margin-right: 15px;"></div>
                  
                  <div style="flex: 1;">
                    <h4 style="margin: 0 0 5px 0; color: #333;"><?= htmlspecialchars($item['product_name']) ?></h4>
                    <p style="margin: 0; color: #666; font-size: 0.9em;">
                      Quantity: <?= $item['quantity'] ?> × $<?= number_format($item['price'], 2) ?> = $<?= number_format($item['quantity'] * $item['price'], 2) ?>
                    </p>
                  </div>

                  <?php if ($order['status'] === 'completed'): ?>
                    <div>
                      <a href="user-reviews.php?liqour_id=<?= $item['liqour_id'] ?>&order_id=<?= $order['order_id'] ?>&product_name=<?= urlencode($item['product_name']) ?>" 
                         style="background: #333; color: white; text-decoration: none; padding: 8px 12px; border-radius: 4px; font-size: 0.9em; display: inline-block;">
                        + Review
                      </a>
                    </div>
                  <?php endif; ?>

                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
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
  </script>
</body>
</html>
