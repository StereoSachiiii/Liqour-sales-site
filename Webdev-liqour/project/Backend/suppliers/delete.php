<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

// Parameters
$supplierId = intval($_GET['supplier_id'] ?? 0);
$type = $_GET['type'] ?? 'soft';
$restore = isset($_GET['restore']) && $_GET['restore'] == 1;

if (!$supplierId) die("Supplier ID not specified.");

// Fetch supplier
$stmt = $conn->prepare("SELECT supplier_id, name, is_active FROM suppliers WHERE supplier_id=?");
$stmt->bind_param("i", $supplierId);
$stmt->execute();
$supplier = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$supplier) die("Supplier not found.");

// Helper: render message box
function renderBox($title, $msg, $backLink = "suppliers.php") {
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
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 1rem;
    color: var(--text);
}

.box {
    background: var(--bg);
    padding: 2rem;
    border-radius: var(--radius);
    max-width: 500px;
    width: 100%;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
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
}

a.btn {
    display: inline-block;
    padding: 0.75rem 1.25rem;
    background: var(--primary);
    color: #fff;
    text-decoration: none;
    border-radius: var(--radius);
    margin: 0.25rem;
    font-weight: 500;
    transition: var(--transition);
}

a.btn:hover {
    background: var(--primary-dark);
}
        </style>
    </head>
    <body>
        <div class="box">
            <h2><?= htmlspecialchars($title) ?></h2>
            <p><?= htmlspecialchars($msg) ?></p>
            <a href="<?= $backLink ?>" class="btn">Back to Suppliers</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// --- RESTORE functionality ---
if ($restore) {
    if ($supplier['is_active']) {
        renderBox("⚠️ Already Active", "Supplier '{$supplier['name']}' is already active.");
    }
    
    // Simply reactivate the supplier
    $stmt = $conn->prepare("UPDATE suppliers SET is_active=1, updated_at=NOW() WHERE supplier_id=?");
    $stmt->bind_param("i", $supplierId);
    $stmt->execute();
    $stmt->close();
    
    renderBox("✅ Restored", "Supplier '{$supplier['name']}' has been restored successfully.");
}

// --- Check for related data ---
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM liqours WHERE supplier_id=?");
$stmt->bind_param("i", $supplierId);
$stmt->execute();
$liqourCount = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

// --- Hard delete blocked by FK constraints ---
if ($type === 'hard' && $liqourCount > 0) {
    renderBox("❌ Delete Blocked", "Cannot permanently delete supplier '{$supplier['name']}': {$liqourCount} liquor products are linked to this supplier. Foreign key constraints prevent deletion.");
}

// --- Already soft-deleted ---
if (!$supplier['is_active'] && !$restore && $type === 'soft') {
    renderBox("⚠️ Already Soft Deleted", "Supplier '{$supplier['name']}' is already inactive.");
}

// --- Handle POST deletion ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'soft';
    if ($_POST['confirm'] === 'yes') {
        if ($type === 'hard') {
            // Final safety check
            if ($liqourCount > 0) {
                renderBox("❌ Delete Blocked", "Cannot permanently delete: foreign key constraints prevent deletion.");
            }
            
            $stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id=?");
            $stmt->bind_param("i", $supplierId);
            $stmt->execute();
            $stmt->close();
            renderBox("✅ Permanently Deleted", "Supplier '{$supplier['name']}' has been permanently deleted.");
        } else {
            // --- Soft Delete ---
            // For suppliers, we'll keep them in the database but mark as inactive
            // Linked liquors will show supplier as inactive but maintain the relationship
            
            $stmt = $conn->prepare("UPDATE suppliers SET is_active=0, updated_at=NOW() WHERE supplier_id=?");
            $stmt->bind_param("i", $supplierId);
            $stmt->execute();
            $stmt->close();
            
            $linkedMsg = $liqourCount > 0 ? " {$liqourCount} linked liquor products remain but supplier shows as inactive." : "";
            renderBox("✅ Soft Deleted", "Supplier '{$supplier['name']}' has been soft-deleted: Supplier marked inactive.{$linkedMsg} Supplier can be restored later.");
        }
    } else {
        renderBox("❌ Cancelled", "Supplier deletion cancelled.");
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
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 1rem;
    color: var(--text);
}

.container {
    background: var(--bg);
    padding: 2rem;
    border-radius: var(--radius);
    max-width: 500px;
    width: 100%;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
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
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
    border: 1px solid var(--border);
}

th,
td {
    border: 1px solid var(--border);
    padding: 8px;
    text-align: left;
}

th {
    background: var(--accent);
    font-weight: 600;
}

.actions {
    margin-top: 20px;
}

button {
    padding: 10px 20px;
    margin: 5px;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-weight: bold;
    transition: var(--transition);
}

.confirm {
    background: var(--danger);
    color: #fff;
}

.confirm:hover {
    background: #c0392b;
}

.confirm:disabled {
    background: #ccc;
    cursor: not-allowed;
    opacity: 0.6;
}

.cancel {
    background: #666;
    color: #fff;
}

.cancel:hover {
    background: #444;
}

.warning {
    background: var(--accent);
    border: 1px solid var(--warning);
    padding: 10px;
    margin: 10px 0;
    border-radius: var(--radius);
    text-align: left;
}

@media (max-width: 500px) {
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
        <tr><th>Name</th><td><?= htmlspecialchars($supplier['name']) ?></td></tr>
        <tr><th>Status</th><td><?= $supplier['is_active'] ? 'Active' : 'Inactive' ?></td></tr>
        <?php if ($liqourCount > 0): ?>
        <tr><th>Linked Products</th><td><?= $liqourCount ?> liquor products</td></tr>
        <?php endif; ?>
    </table>
    
    <?php if ($type === 'hard' && $liqourCount > 0): ?>
    <div style="background:#fee; border:1px solid #fcc; padding:10px; margin:10px 0; border-radius:5px;">
        <strong>⚠️ Hard Delete Blocked:</strong> This supplier has linked liquor products. Foreign key constraints prevent permanent deletion.
    </div>
    <?php endif; ?>
    
    <p>Are you sure you want to <strong><?= $type === 'hard' ? 'permanently delete' : 'soft delete' ?></strong> this supplier?</p>
    
    <?php if ($type === 'soft'): ?>
    <div class="warning">
        <strong>Soft Delete Process:</strong><br>
        • Supplier will be marked as inactive<br>
        • Linked liquor products will remain but show supplier as inactive<br>
        • Supplier can be restored later maintaining all relationships<br>
        • This preserves data integrity while hiding the supplier from active lists
    </div>
    <?php endif; ?>
    
    <form method="post">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
        <div class="actions">
            <button type="submit" name="confirm" value="yes" class="confirm" <?= ($type === 'hard' && $liqourCount > 0) ? 'disabled' : '' ?>>Yes</button>
            <button type="submit" name="confirm" value="no" class="cancel">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>