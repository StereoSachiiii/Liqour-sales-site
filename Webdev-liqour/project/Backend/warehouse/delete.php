<?php
session_start();
require_once "../sql-config.php";

// ========================
// VALIDATION
// ========================
if (!isset($_GET['id'], $_GET['type'])) {
    showModal("Error", "Invalid request: missing parameters.", "../manage-dashboard.php#warehouse");
    exit();
}

$warehouse_id = intval($_GET['id']);
$type = $_GET['type'];

// ========================
// FETCH WAREHOUSE
// ========================
$warehouseStmt = $conn->prepare("SELECT name, is_active FROM warehouse WHERE warehouse_id = ?");
$warehouseStmt->bind_param("i", $warehouse_id);
$warehouseStmt->execute();
$warehouseResult = $warehouseStmt->get_result();

if ($warehouseResult->num_rows === 0) {
    showModal("Not Found", "Warehouse not found.", "../manage-dashboard.php#warehouse");
    exit();
}

$warehouse = $warehouseResult->fetch_assoc();
$warehouseName = htmlspecialchars($warehouse['name']);
$isActive = $warehouse['is_active'];

// ========================
// MODAL FUNCTION
// ========================
function showModal($title, $message, $redirectUrl = null) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?></title>
        <style>:root {
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
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background: var(--accent-light);
    color: var(--text);
}

.modal {
    border: 1px solid var(--border);
    padding: 30px;
    text-align: center;
    max-width: 500px;
    width: 90%;
    background: var(--bg);
    border-radius: var(--radius);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

h2 {
    margin-bottom: 20px;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text);
}

p {
    margin-bottom: 20px;
    font-size: 1rem;
}

a {
    display: inline-block;
    padding: 10px 20px;
    background: var(--success);
    text-decoration: none;
    color: var(--text);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-weight: 500;
    transition: var(--transition);
}

a:hover {
    background: var(--primary-light);
}
        </style>
    </head>
    <body>
        <div class="modal">
            <h2><?= htmlspecialchars($title) ?></h2>
            <p><?= nl2br(htmlspecialchars($message)) ?></p>
            <?php if ($redirectUrl): ?>
                <a href="<?= htmlspecialchars($redirectUrl) ?>">Back to dashboard</a>
            <?php endif; ?>
        </div>
        <script>
            <?php if ($redirectUrl): ?>
                setTimeout(() => {
                    window.location.href = '<?= $redirectUrl ?>';
                }, 5000);
            <?php endif; ?>
        </script>
    </body>
    </html>
    <?php
}

// ========================
// RESTORE FUNCTIONALITY
// ========================
if ($type === 'restore') {
    if ($isActive) {
        showModal("Already Active", "Warehouse \"$warehouseName\" is already active.", "../manage-dashboard.php#warehouse");
        exit();
    }

    $conn->begin_transaction();
    try {
        $stmtRestore = $conn->prepare("UPDATE warehouse SET is_active = 1, updated_at = NOW() WHERE warehouse_id = ?");
        $stmtRestore->bind_param("i", $warehouse_id);
        $stmtRestore->execute();

        $stmtRestoreStock = $conn->prepare("UPDATE stock SET is_active = 1, updated_at = NOW() WHERE warehouse_id = ?");
        $stmtRestoreStock->bind_param("i", $warehouse_id);
        $stmtRestoreStock->execute();

        $conn->commit();
        showModal("Restored", "Warehouse \"$warehouseName\" has been successfully restored.", "../manage-dashboard.php#warehouse");
    } catch (Exception $e) {
        $conn->rollback();
        showModal("Restore Failed", "Failed to restore warehouse: " . $e->getMessage(), "../manage-dashboard.php#warehouse");
    }
    exit();
}

// ========================
// DELETE LOGIC
// ========================
if (!in_array($type, ['soft', 'hard'])) {
    showModal("Invalid Request", "Invalid delete type specified.", "../manage-dashboard.php#warehouse");
    exit();
}

// Stock check
$totalSql = $type === 'soft' 
    ? "SELECT SUM(quantity) AS total_qty FROM stock WHERE warehouse_id = ? AND is_active = 1" 
    : "SELECT SUM(quantity) AS total_qty FROM stock WHERE warehouse_id = ?";

$stmtCheck = $conn->prepare($totalSql);
$stmtCheck->bind_param("i", $warehouse_id);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
$rowCheck = $resultCheck->fetch_assoc();
$total_qty = $rowCheck['total_qty'] ?? 0;

if ($total_qty > 0) {
    $deleteType = ($type === 'soft') ? 'soft-delete' : 'permanently delete';
    showModal("Cannot Delete Warehouse", "Cannot $deleteType warehouse \"$warehouseName\" because it still contains $total_qty units of stock.\nPlease clear stock first.", "../manage-dashboard.php#warehouse");
    exit();
}

// Proceed with delete
$conn->begin_transaction();

try {
    if ($type === 'soft') {
        $stmt = $conn->prepare("UPDATE warehouse SET is_active = 0, updated_at = NOW() WHERE warehouse_id = ? AND is_active = 1");
        $stmt->bind_param("i", $warehouse_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $conn->rollback();
            showModal("Delete Failed", "Warehouse may not exist or is already soft-deleted.", "../manage-dashboard.php#warehouse");
            exit();
        }

        $stmtStock = $conn->prepare("UPDATE stock SET is_active = 0, updated_at = NOW() WHERE warehouse_id = ?");
        $stmtStock->bind_param("i", $warehouse_id);
        $stmtStock->execute();

        $conn->commit();
        showModal("Soft Deleted", "Warehouse \"$warehouseName\" has been soft-deleted successfully.", "../manage-dashboard.php#warehouse");
    } else { // hard delete
        $stmtStock = $conn->prepare("DELETE FROM stock WHERE warehouse_id = ?");
        $stmtStock->bind_param("i", $warehouse_id);
        $stmtStock->execute();

        $stmt = $conn->prepare("DELETE FROM warehouse WHERE warehouse_id = ?");
        $stmt->bind_param("i", $warehouse_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $conn->rollback();
            showModal("Delete Failed", "Warehouse could not be deleted.", "../manage-dashboard.php#warehouse");
            exit();
        }

        $conn->commit();
        showModal("Permanently Deleted", "Warehouse \"$warehouseName\" has been permanently deleted.", "../manage-dashboard.php#warehouse");
    }
} catch (Exception $e) {
    $conn->rollback();
    showModal("Delete Error", "Error deleting warehouse: " . $e->getMessage(), "../manage-dashboard.php#warehouse");
}
?>
