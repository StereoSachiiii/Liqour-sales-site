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

  <!-- Navbar -->
  <?php include('navbar.php'); ?>

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
