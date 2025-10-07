<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

// Parameters
$userId = intval($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'soft';
$restore = isset($_GET['restore']) && $_GET['restore'] == 1;

if (!$userId) die("User ID not specified.");

// Fetch user
$stmt = $conn->prepare("SELECT id, name, is_active FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) die("User not found.");

// Helper: render message box
function renderBox($title, $msg, $backLink = "users.php") {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?></title>
        <style>
            :root {
                --primary: #FFD700;
                --primary-light: #FFE766;
                --primary-dark: #E6B800;
                --accent: #FFFACD;
                --accent-dark: #FFF8DC;
                --accent-light: #FFFFE0;
                --success: #28a745;
                --warning: #ffc107;
                --danger: #dc3545;
                --text: #333;
                --bg: #fff;
                --border: #ddd;
                --radius: 6px;
                --transition: 0.3s;
            }

            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: var(--accent-light);
                margin: 0;
                padding: 1rem;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                color: var(--text);
            }

            .box {
                max-width: 500px;
                width: 100%;
                background: var(--bg);
                padding: 2rem;
                border-radius: var(--radius);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                border: 1px solid var(--border);
                text-align: center;
            }

            h2 {
                margin-bottom: 1rem;
                font-size: 1.75rem;
                font-weight: 600;
                color: var(--text);
            }

            p {
                margin-bottom: 1.5rem;
                font-size: 1rem;
                color: var(--text);
            }

            a.btn {
                display: inline-block;
                padding: 0.75rem 1.25rem;
                background: var(--primary-dark);
                color: #fff;
                text-decoration: none;
                border-radius: var(--radius);
                font-weight: 500;
                transition: var(--transition);
            }

            a.btn:hover {
                background: var(--primary);
            }

            @media (max-width: 500px) {
                .box {
                    padding: 1.5rem;
                    width: 90%;
                }
                a.btn {
                    width: 100%;
                }
            }
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

// Helper: Get or create deleted user
function getOrCreateDeletedUser($conn) {
    // Check if deleted user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = 'deleted@system.local' AND name = 'Deleted User'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        return $result['id'];
    }
    
    // Create deleted user if it doesn't exist
    $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, is_admin, is_active, created_at, updated_at) VALUES ('Deleted User', 'deleted@system.local', '', 0, 0, NOW(), NOW())");
    $stmt->execute();
    $deletedUserId = $conn->insert_id;
    $stmt->close();
    
    return $deletedUserId;
}

// --- RESTORE functionality ---
if ($restore) {
    if ($user['is_active']) {
        renderBox("⚠️ Already Active", "User '{$user['name']}' is already active.");
    }
    
    // Simply reactivate the user - they lost their orders/reviews during soft delete
    $stmt = $conn->prepare("UPDATE users SET is_active=1, updated_at=NOW() WHERE id=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    
    renderBox("✅ Restored", "User '{$user['name']}' has been restored. Note: Previous orders and reviews were transferred to system during deletion and cannot be recovered.");
}

// --- Check for related data ---
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE user_id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$orderCount = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM reviews WHERE user_id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$reviewCount = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

// --- Hard delete blocked by FK constraints ---
if ($type === 'hard' && ($orderCount > 0 || $reviewCount > 0)) {
    renderBox("❌ Delete Blocked", "Cannot permanently delete user '{$user['name']}': {$orderCount} orders and {$reviewCount} reviews exist. Foreign key constraints prevent deletion.");
}

// --- Already soft-deleted ---
if (!$user['is_active'] && !$restore && $type === 'soft') {
    renderBox("⚠️ Already Soft Deleted", "User '{$user['name']}' is already inactive.");
}

// --- Handle POST deletion ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'soft';
    if ($_POST['confirm'] === 'yes') {
        if ($type === 'hard') {
            // Final safety check
            if ($orderCount > 0 || $reviewCount > 0) {
                renderBox("❌ Delete Blocked", "Cannot permanently delete: foreign key constraints prevent deletion.");
            }
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            renderBox("✅ Permanently Deleted", "User '{$user['name']}' has been permanently deleted.");
        } else {
            // --- Proper Soft Delete ---
            
            // 1️⃣ Get or create the system deleted user
            $deletedUserId = getOrCreateDeletedUser($conn);
            
            // 2️⃣ Transfer orders to deleted user (if any)
            if ($orderCount > 0) {
                $stmt = $conn->prepare("UPDATE orders SET user_id=? WHERE user_id=?");
                $stmt->bind_param("ii", $deletedUserId, $userId);
                $stmt->execute();
                $stmt->close();
            }
            
            // 3️⃣ Transfer reviews to deleted user (if any)
            if ($reviewCount > 0) {
                $stmt = $conn->prepare("UPDATE reviews SET user_id=? WHERE user_id=?");
                $stmt->bind_param("ii", $deletedUserId, $userId);
                $stmt->execute();
                $stmt->close();
            }
            
            // 4️⃣ Soft delete the user (keep name, just mark inactive)
            $stmt = $conn->prepare("UPDATE users SET is_active=0, updated_at=NOW() WHERE id=?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            
            renderBox("✅ Soft Deleted", "User '{$user['name']}' has been soft-deleted: User marked inactive, {$orderCount} orders and {$reviewCount} reviews transferred to system. User account can be restored but will lose order/review history.");
        }
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
:root {
    --primary: #FFD700;
    --primary-light: #FFE766;
    --primary-dark: #E6B800;
    --accent: #FFFACD;
    --accent-dark: #FFF8DC;
    --accent-light: #FFFFE0;
    --success: #28a745;
    --warning: #ffc107;
    --danger: #dc3545;
    --text: #333;
    --bg: #fff;
    --border: #ddd;
    --radius: 6px;
    --transition: 0.3s;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--accent-light);
    margin: 0;
    padding: 1rem;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    color: var(--text);
}

.container {
    max-width: 500px;
    width: 100%;
    background: var(--bg);
    padding: 2rem;
    border-radius: var(--radius);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
    text-align: center;
}

h2 {
    margin-bottom: 1rem;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text);
}

p {
    margin-bottom: 1rem;
    font-size: 1rem;
    color: var(--text);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}

th, td {
    border: 1px solid var(--border);
    padding: 8px;
    text-align: left;
    font-size: 0.9rem;
}

th {
    background: var(--accent);
    font-weight: 600;
}

td {
    background: var(--bg);
}

.warning {
    background: var(--warning);
    color: var(--text);
    border: 1px solid var(--primary-dark);
    padding: 10px;
    margin: 10px 0;
    border-radius: var(--radius);
    font-size: 0.875rem;
}

.hard-delete-warning {
    background: var(--danger);
    color: #fff;
    border: 1px solid var(--danger);
    padding: 10px;
    margin: 10px 0;
    border-radius: var(--radius);
    font-size: 0.875rem;
}

.actions {
    margin-top: 20px;
    display: flex;
    justify-content: center;
    gap: 10px;
}

button {
    padding: 10px 20px;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 1rem;
    font-weight: bold;
    transition: var(--transition);
}

button.confirm {
    background: var(--danger);
    color: #fff;
}

button.confirm:hover {
    background: #c82333;
}

button.cancel {
    background: var(--primary-dark);
    color: #fff;
}

button.cancel:hover {
    background: var(--primary);
}

button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

@media (max-width: 500px) {
    .container {
        padding: 1.5rem;
        width: 90%;
    }
    button {
        width: 45%;
        margin: 5px 0;
    }
}
</style>
</head>
<body>
<div class="container">
    <h2>Confirm <?= ucfirst($type) ?> Deletion</h2>
    <table>
        <tr><th>Name</th><td><?= htmlspecialchars($user['name']) ?></td></tr>
        <tr><th>Status</th><td><?= $user['is_active'] ? 'Active' : 'Inactive' ?></td></tr>
        <?php if ($orderCount > 0 || $reviewCount > 0): ?>
        <tr><th>Orders</th><td><?= $orderCount ?></td></tr>
        <tr><th>Reviews</th><td><?= $reviewCount ?></td></tr>
        <?php endif; ?>
    </table>
    
    <?php if ($type === 'hard' && ($orderCount > 0 || $reviewCount > 0)): ?>
    <div class="hard-delete-warning">
        <strong>⚠️ Hard Delete Blocked:</strong> This user has associated orders/reviews. Foreign key constraints prevent permanent deletion.
    </div>
    <?php endif; ?>
    
    <p>Are you sure you want to <strong><?= $type === 'hard' ? 'permanently delete' : 'soft delete' ?></strong> this user?</p>
    
    <?php if ($type === 'soft'): ?>
    <div class="warning">
        <strong>Soft Delete Process:</strong><br>
        • User account will be marked inactive (keeps name/email)<br>
        • All orders and reviews will be transferred to system "Deleted User"<br>
        • User can be restored later but will lose their order/review history<br>
        • This maintains database integrity while preserving business records
    </div>
    <?php endif; ?>
    
    <form method="post">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
        <div class="actions">
            <button type="submit" name="confirm" value="yes" class="confirm" <?= ($type === 'hard' && ($orderCount > 0 || $reviewCount > 0)) ? 'disabled' : '' ?>>Yes</button>
            <button type="submit" name="confirm" value="no" class="cancel">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>