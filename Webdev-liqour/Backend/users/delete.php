<?php
session_start();
include("../sql-config.php");

// --- Admin check ---
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

// --- Validate ID and type ---
$userId = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'soft';
$restore = isset($_GET['restore']) && $_GET['restore'] == 1;
if (!$userId) die("User ID not specified.");
$userId = intval($userId);

// --- Fetch user ---
$stmt = $conn->prepare("SELECT id, name, is_active FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) die("User not found.");

// --- Helper: render consistent message box ---
function renderBox($title, $msg, $backLink = "../manage-dashboard.php#users") {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?></title>
        <style>
            body { font-family: Arial,sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:1rem; }
            .box { background:white; padding:2rem; border-radius:12px; border:1px solid #ccc; max-width:500px; width:100%; text-align:center; }
            h2 { margin-bottom:1rem; }
            p { margin-bottom:1.5rem; }
            a.btn { display:inline-block; padding:0.75rem 1.25rem; background:#212529; color:white; text-decoration:none; border-radius:6px; margin:0.25rem; }
            a.btn:hover { background:#343a40; }
            a.btn-danger { background:#dc3545; }
            a.btn-danger:hover { background:#c82333; }
            a.btn-success { background:#28a745; }
            a.btn-success:hover { background:#218838; }
            table { width:100%; border-collapse: collapse; margin-bottom:15px; }
            th, td { border:1px solid #ccc; padding:8px; text-align:left; }
            th { background:#eee; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2><?= htmlspecialchars($title) ?></h2>
            <p><?= htmlspecialchars($msg) ?></p>
            <a href="<?= $backLink ?>" class="btn">Back to Users</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// --- RESTORE functionality ---
if ($restore) {
    if ($user['is_active']) {
        renderBox("⚠️ Already Active", "User '{$user['name']}' is already active.");
    }
    $stmtRestore = $conn->prepare("UPDATE users SET is_active=1, updated_at=NOW() WHERE id=?");
    $stmtRestore->bind_param("i", $userId);
    $stmtRestore->execute();
    $stmtRestore->close();
    renderBox("✅ Restored", "User '{$user['name']}' has been successfully restored.");
}

// --- Check foreign key constraints before deletion ---
$checkOrders = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE user_id=?");
$checkOrders->bind_param("i", $userId);
$checkOrders->execute();
$orderCount = $checkOrders->get_result()->fetch_assoc()['cnt'] ?? 0;
$checkOrders->close();

$checkReviews = $conn->prepare("SELECT COUNT(*) AS cnt FROM reviews WHERE user_id=?");
$checkReviews->bind_param("i", $userId);
$checkReviews->execute();
$reviewCount = $checkReviews->get_result()->fetch_assoc()['cnt'] ?? 0;
$checkReviews->close();

if ($orderCount > 0 || $reviewCount > 0) {
    renderBox("❌ Delete Blocked", "Cannot delete user '{$user['name']}': associated orders ($orderCount) or reviews ($reviewCount) exist.");
}

// --- Already soft-deleted confirmation ---
if (!$user['is_active'] && !$restore && $type === 'soft') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Already Soft Deleted</title>
        <style>
            body { font-family: Arial,sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:1rem; }
            .box { background:white; padding:2rem; border-radius:12px; border:1px solid #ccc; max-width:500px; width:100%; text-align:center; }
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
            <p>User '<strong><?= htmlspecialchars($user['name']) ?></strong>' is already inactive.</p>
            <a class="btn btn-success" href="?id=<?= $userId ?>&restore=1">🔄 Restore User</a>
            <a class="btn btn-danger" href="?id=<?= $userId ?>&type=hard" 
               onclick="return confirm('⚠️ PERMANENT DELETE\\nThis will permanently delete the user and cannot be undone!\\nAre you sure?')">🗑️ Delete Permanently</a>
            <a class="btn" href="../manage-dashboard.php#users">← Back to Users</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// --- Perform deletion ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['confirm'] === 'yes') {
        if ($type === 'hard') {
            $stmtDel = $conn->prepare("DELETE FROM users WHERE id=?");
        } else {
            $stmtDel = $conn->prepare("UPDATE users SET is_active=0, updated_at=NOW() WHERE id=?");
        }
        $stmtDel->bind_param("i", $userId);
        $stmtDel->execute();
        $stmtDel->close();

        renderBox($type === 'hard' ? "✅ Permanently Deleted" : "✅ Soft Deleted",
                  $type === 'hard' ? "User '{$user['name']}' has been permanently deleted." : "User '{$user['name']}' has been soft-deleted. It can be restored later.");
    } else {
        renderBox("❌ Cancelled", "User deletion cancelled.");
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm <?= ucfirst($type) ?> Deletion</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:1rem; }
.container { background:#fff; padding:2rem; border-radius:12px; max-width:500px; width:100%; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
h2 { margin-bottom:1rem; }
table { width:100%; border-collapse:collapse; margin-bottom:15px; }
th, td { border:1px solid #ccc; padding:8px; text-align:left; }
th { background:#eee; }
.actions {margin-top:20px;}
button { padding:10px 20px; margin:5px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; transition:all 0.2s;}
.confirm {background:#c0392b; color:#fff;}
.confirm:hover {background:#a93226;}
.cancel {background:#666; color:#fff;}
.cancel:hover {background:#444;}
@media(max-width:500px){ button { width:45%; margin:5px 0; } }
</style>
</head>
<body>
<div class="container">
    <h2>Confirm <?= ucfirst($type) ?> Deletion</h2>
    <table>
        <tr><th>Name</th><td><?= htmlspecialchars($user['name']) ?></td></tr>
        <tr><th>Status</th><td><?= $user['is_active'] ? 'Active' : 'Inactive' ?></td></tr>
    </table>
    <p>Are you sure you want to <strong><?= $type === 'hard' ? 'permanently delete' : 'soft delete' ?></strong> this user?</p>
    <form method="post">
        <div class="actions">
            <button type="submit" name="confirm" value="yes" class="confirm">Yes</button>
            <button type="submit" name="confirm" value="no" class="cancel">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>
