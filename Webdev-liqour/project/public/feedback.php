<?php
include('session.php');
include("../Backend/sql-config.php");

// Get purchased liquors (for filter dropdown only if logged in)
$purchasedLiquors = [];
if (!$isGuest) {
    $purchaseSql = "
        SELECT DISTINCT l.liqour_id, l.name
        FROM liqours l
        INNER JOIN reviews r ON l.liqour_id = r.liqour_id
        WHERE r.user_id = ?
        ORDER BY l.name ASC
    ";
    $stmt = $conn->prepare($purchaseSql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $purchaseResult = $stmt->get_result();
    $purchasedLiquors = $purchaseResult->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Filter setup
$filterId = isset($_GET['liqour_id']) ? (int)$_GET['liqour_id'] : null;

// Base feedback query (public feedback for everyone)
$sql = "SELECT r.rating, r.comment, r.created_at, u.name AS user_name, l.name AS liqour_name
        FROM reviews r
        INNER JOIN users u ON r.user_id = u.id
        INNER JOIN liqours l ON r.liqour_id = l.liqour_id";

if ($filterId && !$isGuest) {
    $sql .= " WHERE l.liqour_id = ? ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $filterId);
} else {
    $sql .= " ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
$feedbacks = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feedback - LiquorStore</title>
  <link rel="stylesheet" href="css/index.css">
</head>
<body>

  <div class="header-strip">Welcome to LiquorStore! Free delivery on orders over $50</div>

  <!-- Navbar -->
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

  <!-- Feedback Section -->
  <section class="new" id="feedback-section">
    <h2 class="title-text">📩 Customer Feedback</h2>

    <?php if (!$isGuest && $purchasedLiquors): ?>
      <!-- Product filter -->
      <div style="text-align:center; margin-bottom: 20px;">
        <form method="GET" id="filterForm">
          <label for="liqourFilter">Filter by product:</label>
          <select name="liqour_id" id="liqourFilter" onchange="document.getElementById('filterForm').submit()">
            <option value="">-- All Products --</option>
            <?php foreach ($purchasedLiquors as $liqour): ?>
              <option value="<?= htmlspecialchars($liqour['liqour_id']) ?>" <?= ($filterId == $liqour['liqour_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($liqour['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    <?php elseif($isGuest): ?>
      <p style="text-align:center; color:#777;">Login to filter feedback by your purchases.</p>
    <?php endif; ?>

    <!-- Feedback list -->
    <div id="feedback-list" style="padding: 10px; max-width: 800px; margin: auto;">
      <?php if($feedbacks): ?>
        <?php foreach($feedbacks as $row): ?>
          <div style='border-bottom:1px solid #ccc; padding:8px 0;'>
            <div><strong style='color:#333;'><?= htmlspecialchars($row['user_name']) ?></strong> reviewed <em><?= htmlspecialchars($row['liqour_name']) ?></em>:</div>
            <div style='margin:4px 0; color:#555;'><?= htmlspecialchars($row['comment']) ?></div>
            <div style='font-size:0.85em; color:#888;'>Rating: <?= htmlspecialchars($row['rating']) ?>/5 | Date: <?= htmlspecialchars($row['created_at']) ?></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No feedback available.</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- Logout Modal -->
  <div id="logoutModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:#fff; padding:20px; border-radius:10px; text-align:center; min-width:300px;">
      <h3>Confirm Logout</h3>
      <p>Are you sure you want to logout?</p>
      <button onclick="logoutNow()" style="padding:8px 16px; background:#8B4513; color:white; border:none; border-radius:5px; margin-right:10px;">Yes</button>
      <button onclick="closeLogoutModal()" style="padding:8px 16px; background:#ccc; color:#333; border:none; border-radius:5px;">Cancel</button>
    </div>
  </div>

  <div id="toast-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"></div>

  <?php include('footer.php'); ?>

  <script>
    document.querySelector(".profile-container").addEventListener("click", () => {
      document.querySelector(".profile-expand").classList.toggle("profile-expand-active");
    });

    // Cart functionality - same as index.php and wishlist.php
    const cartCountEl = document.querySelector(".cart-count");
const userId = "<?php echo $userId; ?>";
    const isGuest = <?php echo $isGuest ? 'true' : 'false'; ?>;
    
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

    function updateCartCount() {
        const total = cartItems.reduce((sum, item) => sum + item.quantity, 0);
        cartCountEl.textContent = total;
        cartCountEl.style.display = total > 0 ? "inline-block" : "none";
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

    // Close modal if clicked outside
    window.addEventListener('click', (e) => {
        const modal = document.getElementById('logoutModal');
        if(e.target === modal) modal.style.display = 'none';
    });

    function searchProducts() {
        const query = document.getElementById("search-box").value;
        if(query.trim()) {
            window.location.href = "index.php?search=" + encodeURIComponent(query);
        } else {
            alert("Please enter a search term");
        }
    }

  const searchBox = document.getElementById("search-box");
if (searchBox) {
  searchBox.addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
      searchProducts();
    }
  });
}


    // Initialize cart count on page load
    updateCartCount();
  </script>
</body>
</html>