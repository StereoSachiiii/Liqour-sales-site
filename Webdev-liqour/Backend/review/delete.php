<?php
session_start();
include('../sql-config.php');

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

$review_id = $_GET['review_id'] ?? null;
$type = $_GET['type'] ?? 'soft'; // default to soft delete
$restore = isset($_GET['restore']) && $_GET['restore'] == 1;

if (!$review_id || !is_numeric($review_id)) {
    echo "<script>
        alert('Invalid review ID.');
        window.location.href='../manage-dashboard.php#reviews';
    </script>";
    exit();
}

// Helper function to render message box
function renderBox($title, $msg, $backLink = "../manage-dashboard.php#reviews") {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:1rem; }
        .box { background:white; padding:2rem; border-radius:12px; border:1px solid #ccc; max-width:600px; width:100%; text-align:center; }
        h2 { margin-bottom:1rem; }
        p { margin-bottom:1.5rem; }
        a.btn { display:inline-block; padding:0.75rem 1.25rem; background:#212529; color:white; text-decoration:none; border-radius:6px; margin:0.25rem; }
        a.btn:hover { background:#343a40; }
        a.btn-danger { background:#dc3545; }
        a.btn-danger:hover { background:#c82333; }
        a.btn-success { background:#28a745; }
        a.btn-success:hover { background:#218838; }
    </style>
    </head>
    <body>
        <div class="box">
            <h2><?= htmlspecialchars($title) ?></h2>
            <p><?= htmlspecialchars($msg) ?></p>
            <a href="<?= $backLink ?>" class="btn">Back to Reviews</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Fetch review details
$stmt = $conn->prepare("SELECT review_id, comment, rating, liqour_id, user_id, is_active FROM reviews WHERE review_id = ?");
$stmt->bind_param("i", $review_id);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$review) {
    renderBox("❌ Not Found", "Review not found.");
}

$reviewComment = $review['comment'];
$reviewRating = $review['rating'];
$isActive = $review['is_active'];

// RESTORE functionality
if ($restore) {
    if ($isActive) {
        renderBox("⚠️ Already Active", "This review is already active and cannot be restored.");
    }

    $stmtRestore = $conn->prepare("UPDATE reviews SET is_active=1, updated_at=NOW() WHERE review_id=?");
    $stmtRestore->bind_param("i", $review_id);
    $stmtRestore->execute();
    $stmtRestore->close();

    renderBox("✅ Restored", "Review has been successfully restored.");
}

// Already soft-deleted confirmation
if (!$isActive && !$restore) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Already Soft Deleted</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:1rem; }
        .box { background:white; padding:2rem; border-radius:12px; border:1px solid #ccc; max-width:700px; width:100%; text-align:center; }
        a.btn { display:inline-block; padding:0.75rem 1.25rem; background:#212529; color:white; text-decoration:none; border-radius:6px; margin:0.25rem; }
        a.btn:hover { background:#343a40; }
        a.btn-success { background:#28a745; }
        a.btn-success:hover { background:#218838; }
        a.btn-danger { background:#dc3545; }
        a.btn-danger:hover { background:#c82333; }
        table { width:100%; border-collapse: collapse; margin-bottom:15px; }
        th, td { border:1px solid #ccc; padding:8px; text-align:left; }
        th { background:#eee; }
    </style>
    </head>
    <body>
        <div class="box">
            <h2>⚠️ Already Soft Deleted</h2>
            <p>Review "<strong><?= htmlspecialchars($reviewComment) ?></strong>" (Rating: <?= $reviewRating ?>) is already inactive.</p>

            <p>Actions you can take:</p>
            <a class="btn btn-success" href="?review_id=<?= $review_id ?>&restore=1">🔄 Restore Review</a>
            <a class="btn btn-danger" href="?review_id=<?= $review_id ?>&type=hard" 
               onclick="return confirm('⚠️ PERMANENT DELETE\\nThis will permanently delete the review and cannot be undone!\\nAre you sure?')">
               🗑️ Delete Permanently
            </a>
            <a class="btn" href="../manage-dashboard.php#reviews">← Back to Reviews</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Perform delete
if ($type === 'soft') {
    $stmtDel = $conn->prepare("UPDATE reviews SET is_active=0, updated_at=NOW() WHERE review_id=?");
    $stmtDel->bind_param("i", $review_id);
    $stmtDel->execute();
    $stmtDel->close();
    renderBox("✅ Soft Deleted", "Review has been soft-deleted. It can be restored later.");
} elseif ($type === 'hard') {
    $stmtDel = $conn->prepare("DELETE FROM reviews WHERE review_id=?");
    $stmtDel->bind_param("i", $review_id);
    $stmtDel->execute();
    $stmtDel->close();
    renderBox("✅ Permanently Deleted", "Review has been permanently deleted.");
} else {
    renderBox("❌ Invalid Action", "Unknown delete type specified.");
}

$conn->close();
?>
