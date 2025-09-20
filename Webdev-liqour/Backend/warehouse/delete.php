<?php
session_start();
require_once "../sql-config.php";

if (!isset($_GET['id'], $_GET['type'])) {
    die("Invalid request");
}

$warehouse_id = intval($_GET['id']);
$type = $_GET['type'];

// Check if warehouse exists and get its status
$warehouseStmt = $conn->prepare("SELECT name, is_active FROM warehouse WHERE warehouse_id = ?");
$warehouseStmt->bind_param("i", $warehouse_id);
$warehouseStmt->execute();
$warehouseResult = $warehouseStmt->get_result();

if ($warehouseResult->num_rows == 0) {
    echo "<script>
            alert('❌ Warehouse not found.');
            window.location.href='../manage-dashboard.php#warehouse';
          </script>";
    exit();
}

$warehouse = $warehouseResult->fetch_assoc();
$warehouseName = $warehouse['name'];
$isActive = $warehouse['is_active'];

// RESTORE FUNCTIONALITY
if ($type === 'restore') {
    if ($isActive) {
        echo "<script>
                alert('⚠️ Warehouse \"$warehouseName\" is already active.');
                window.location.href='../manage-dashboard.php#warehouse';
              </script>";
        exit();
    }
    
    $conn->begin_transaction();
    
    try {
        // Restore warehouse
        $stmtRestore = $conn->prepare("UPDATE warehouse SET is_active = 1, updated_at = NOW() WHERE warehouse_id = ?");
        $stmtRestore->bind_param("i", $warehouse_id);
        $stmtRestore->execute();
        
        // Restore associated stock entries
        $stmtRestoreStock = $conn->prepare("UPDATE stock SET is_active = 1, updated_at = NOW() WHERE warehouse_id = ?");
        $stmtRestoreStock->bind_param("i", $warehouse_id);
        $stmtRestoreStock->execute();
        
        $conn->commit();
        
        echo "<script>
                alert('✅ Warehouse \"$warehouseName\" has been successfully restored and reactivated.');
                window.location.href='../manage-dashboard.php#warehouse';
              </script>";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>
                alert('❌ Failed to restore warehouse: " . addslashes($e->getMessage()) . "');
                window.location.href='../manage-dashboard.php#warehouse';
              </script>";
    }
    exit();
}

// =========================
// DELETE LOGIC
// =========================

// Stock check SQL + custom message
if ($type === 'soft') {
    // Must have 0 ACTIVE items
    $checkSql = "SELECT SUM(quantity) AS total_qty 
                 FROM stock 
                 WHERE warehouse_id = ? AND is_active = 1";
    $checkMsg = "❌ Cannot soft-delete warehouse \\\"$warehouseName\\\": it still contains active stock (%d units).\\n\\n👉 Please move or clear all active items before trying again.";
} else if ($type === 'hard') {
    // Must have 0 TOTAL items
    $checkSql = "SELECT SUM(quantity) AS total_qty 
                 FROM stock 
                 WHERE warehouse_id = ?";
    $checkMsg = "❌ Cannot hard-delete warehouse \\\"$warehouseName\\\": it still contains stock (%d units in total).\\n\\n👉 Please transfer all items out and ensure stock is completely removed before trying again.";
} else {
    echo "<script>
            alert('❌ Invalid delete type.');
            window.location.href='../manage-dashboard.php#warehouse';
          </script>";
    exit();
}

$stmt = $conn->prepare($checkSql);
$stmt->bind_param("i", $warehouse_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_qty = $row['total_qty'] ?? 0;

if ($total_qty > 0) {
    $alertMsg = sprintf($checkMsg, $total_qty);
    echo "<script>
            alert('$alertMsg');
            window.location.href='../manage-dashboard.php#warehouse';
          </script>";
    exit();
}

// =========================
// PROCEED WITH DELETE
// =========================

$conn->begin_transaction();

try {
    // SOFT DELETE
    if ($type === 'soft') {
        $stmt = $conn->prepare("UPDATE warehouse SET is_active = 0, updated_at = NOW() WHERE warehouse_id = ? AND is_active = 1");
        $stmt->bind_param("i", $warehouse_id);
        $stmt->execute();
        
        if ($stmt->affected_rows == 0) {
            $conn->rollback();
            echo "<script>
                    alert('❌ Warehouse may not exist or is already soft-deleted.');
                    window.location.href='../manage-dashboard.php#warehouse';
                  </script>";
            exit();
        }
        
        // Soft delete associated stock entries (should be 0 quantity anyway)
        $stmtStock = $conn->prepare("UPDATE stock SET is_active = 0, updated_at = NOW() WHERE warehouse_id = ?");
        $stmtStock->bind_param("i", $warehouse_id);
        $stmtStock->execute();
        
        $conn->commit();
        
        echo "<script>
                alert('✅ Warehouse \"$warehouseName\" has been soft-deleted.\\n\\nℹ️ You can restore it later if needed.');
                window.location.href='../manage-dashboard.php#warehouse';
              </script>";
    }
    
    // HARD DELETE
    else if ($type === 'hard') {
        // Delete stock entries first (foreign key constraint)
        $stmtStock = $conn->prepare("DELETE FROM stock WHERE warehouse_id = ?");
        $stmtStock->bind_param("i", $warehouse_id);
        $stmtStock->execute();
        
        // Delete warehouse
        $stmt = $conn->prepare("DELETE FROM warehouse WHERE warehouse_id = ?");
        $stmt->bind_param("i", $warehouse_id);
        $stmt->execute();
        
        if ($stmt->affected_rows == 0) {
            $conn->rollback();
            echo "<script>
                    alert('❌ Warehouse could not be deleted.');
                    window.location.href='../manage-dashboard.php#warehouse';
                  </script>";
            exit();
        }
        
        $conn->commit();
        
        echo "<script>
                alert('✅ Warehouse \"$warehouseName\" has been permanently deleted.\\n\\n⚠️ This action cannot be undone.');
                window.location.href='../manage-dashboard.php#warehouse';
              </script>";
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<script>
            alert('❌ Error deleting warehouse: " . addslashes($e->getMessage()) . "');
            window.location.href='../manage-dashboard.php#warehouse';
          </script>";
}
?>
