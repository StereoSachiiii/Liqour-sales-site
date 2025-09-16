<?php
session_start();
if(!isset($_SESSION['userId'], $_SESSION['username'])){
    header("Location: public/login-signup.php");
    exit();
}

include("../Backend/sql-config.php");

$userId = $_SESSION['userId'];

$purchaseSql = "
SELECT DISTINCT l.liqour_id, l.name
FROM liqours l
INNER JOIN reviews r ON l.liqour_id = r.liqour_id
ORDER BY l.name ASC
";
$stmt = $conn->prepare($purchaseSql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->execute();
$purchaseResult = $stmt->get_result();
$purchasedLiquors = $purchaseResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$filterId = isset($_GET['liqour_id']) ? (int)$_GET['liqour_id'] : null;

$sql = "SELECT r.rating, r.comment, r.created_at, u.name AS user_name, l.name AS liqour_name
        FROM reviews r
        INNER JOIN users u ON r.user_id = u.id
        INNER JOIN liqours l ON r.liqour_id = l.liqour_id";

if ($filterId) {
    $sql .= " WHERE l.liqour_id = ? ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $filterId);
} else {
    $sql .= " ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
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

  <nav class="nav-bar">
    <div class="logo-container">
      <img src="src\icons\icon.svg" alt="LiquorStore Logo">
    </div>
    <div class="nav-options-container nav-options-font">
      <div class="nav-option"><a href="index.php#wines">HOME</a></div>
      <div class="nav-option"><a href="index.php#new-arrivals">NEW ARRIVALS</a></div>
      <div class="nav-option"><a href="index.php#liquor">LIQUOR</a></div>
      
      <div class="nav-option"><a href="index.php#categories">CATEGORIES</a></div>
    </div>
    <div class="profile-search-cart">
      <div class="profile-container">
        <div class="profile">👤</div>
        <div class="profile-expand">
          <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></p>
          <p><a href="my-orders.php">My Orders</a></p>
          <p><a href="feedback.php">Feedback</a></p>
          <p><a href="logout.php">Logout</a></p>
        </div>
      </div>
      <div class="search-container">
        <div class="search-bar-expand">
          <input type="text" id="search-box" placeholder="Search products...">
          <button onclick="searchProducts()">🔍</button>
        </div>
      </div>
    </div>
  </nav>

<section class="new" id="feedback-section">
  <h2 class="title-text">📩 Customer Feedback</h2>

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

  <div id="feedback-list" style="padding: 10px; max-width: 800px; margin: auto;">
    <?php 
    if($feedbacks){
        foreach($feedbacks as $row){
            echo "
            <div style='border-bottom:1px solid #ccc; padding:8px 0;'>
              <div><strong style='color:#333;'>" . htmlspecialchars($row['user_name']) . "</strong> reviewed <em>" . htmlspecialchars($row['liqour_name']) . "</em>:</div>
              <div style='margin:4px 0; color:#555;'>" . htmlspecialchars($row['comment']) . "</div>
              <div style='font-size:0.85em; color:#888;'>Rating: " . htmlspecialchars($row['rating']) . "/5 | Date: " . htmlspecialchars($row['created_at']) . "</div>
            </div>
            ";
        }
    } else {
        echo "<p>No feedback available for this selection.</p>";
    }
    ?>
  </div>
</section>

  <footer class="feedback-socials" style="justify-content:center;">
    <p>© 2025 LiquorStore. All rights reserved.</p>
  </footer>

  <script>
    document.querySelector(".profile-container").addEventListener("click", () => {
      document.querySelector(".profile-expand").classList.toggle("profile-expand-active");
    });

    function searchProducts() {
      const query = document.getElementById("search-box").value;
      if(query.trim()) {
        window.location.href = "search.php?q=" + encodeURIComponent(query);
      } else {
        alert("Please enter a search term");
      }
    }

    document.getElementById("search-box").addEventListener("keypress", function(e) {
      if (e.key === "Enter") {
        searchProducts();
      }
    });
  </script>
</body>
</html>