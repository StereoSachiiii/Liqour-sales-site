<?php
session_start();

if (!isset($_SESSION['userId'])) {
    header("Location: public/login-signup.php");
    exit();
}

include("../Backend/sql-config.php");

if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die("Invalid order ID.");
}

$orderId = (int)$_GET['order_id'];
$userId = $_SESSION['userId'];

try {
    $conn->begin_transaction();
    
    // First, check if order exists and belongs to user
    $checkStmt = $conn->prepare("SELECT order_id, status FROM orders WHERE order_id = ? AND user_id = ? AND is_active = 1");
    $checkStmt->bind_param("ii", $orderId, $userId);
    $checkStmt->execute();
    $orderResult = $checkStmt->get_result();
    
    if ($orderResult->num_rows === 0) {
        $checkStmt->close();
        $conn->rollback();
        die("Order not found or already cancelled.");
    }
    
    $order = $orderResult->fetch_assoc();
    $checkStmt->close();
    
    // Check if order can be cancelled (only pending or processing orders)
    if (!in_array($order['status'], ['pending', 'processing'])) {
        $conn->rollback();
        die("Cannot cancel order with status: " . $order['status']);
    }
    
    // Get all order items to restore stock
    $itemsStmt = $conn->prepare("
        SELECT oi.liqour_id, oi.quantity, l.name 
        FROM order_items oi
        JOIN liqours l ON oi.liqour_id = l.liqour_id
        WHERE oi.order_id = ?
    ");
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    
    if ($itemsResult->num_rows === 0) {
        $itemsStmt->close();
        $conn->rollback();
        die("No order items found.");
    }
    
    // Restore stock for each item
    $restoredItems = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $liqourId = $item['liqour_id'];
        $quantityToRestore = $item['quantity'];
        
        // Find warehouses to restore stock to (restore to first available warehouse)
        $warehouseStmt = $conn->prepare("
            SELECT warehouse_id 
            FROM stock 
            WHERE liqour_id = ? AND is_active = 1 
            ORDER BY warehouse_id ASC 
            LIMIT 1
        ");
        $warehouseStmt->bind_param("i", $liqourId);
        $warehouseStmt->execute();
        $warehouseResult = $warehouseStmt->get_result();
        
        if ($warehouseResult->num_rows > 0) {
            $warehouse = $warehouseResult->fetch_assoc();
            $warehouseId = $warehouse['warehouse_id'];
            
            // Restore stock to this warehouse
            $restoreStmt = $conn->prepare("
                UPDATE stock 
                SET quantity = quantity + ?, updated_at = NOW() 
                WHERE liqour_id = ? AND warehouse_id = ?
            ");
            $restoreStmt->bind_param("iii", $quantityToRestore, $liqourId, $warehouseId);
            
            if (!$restoreStmt->execute()) {
                $warehouseStmt->close();
                $restoreStmt->close();
                $conn->rollback();
                die("Failed to restore stock for item: " . $item['name']);
            }
            
            $restoreStmt->close();
            $restoredItems[] = $item['name'] . " (+" . $quantityToRestore . ")";
            
            error_log("Restored $quantityToRestore units to warehouse $warehouseId for liquor $liqourId");
            
        } else {
            // If no warehouse found, create a new stock entry in the first available warehouse
            $defaultWarehouseStmt = $conn->prepare("SELECT warehouse_id FROM warehouse WHERE is_active = 1 ORDER BY warehouse_id ASC LIMIT 1");
            $defaultWarehouseStmt->execute();
            $defaultWarehouseResult = $defaultWarehouseStmt->get_result();
            
            if ($defaultWarehouseResult->num_rows > 0) {
                $defaultWarehouse = $defaultWarehouseResult->fetch_assoc();
                $defaultWarehouseId = $defaultWarehouse['warehouse_id'];
                
                $insertStockStmt = $conn->prepare("
                    INSERT INTO stock (liqour_id, warehouse_id, quantity, is_active, updated_at) 
                    VALUES (?, ?, ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE quantity = quantity + ?, updated_at = NOW()
                ");
                $insertStockStmt->bind_param("iiii", $liqourId, $defaultWarehouseId, $quantityToRestore, $quantityToRestore);
                
                if (!$insertStockStmt->execute()) {
                    $defaultWarehouseStmt->close();
                    $insertStockStmt->close();
                    $conn->rollback();
                    die("Failed to create stock entry for item: " . $item['name']);
                }
                
                $insertStockStmt->close();
                $restoredItems[] = $item['name'] . " (+" . $quantityToRestore . " - new stock entry)";
            }
            $defaultWarehouseStmt->close();
        }
        $warehouseStmt->close();
    }
    $itemsStmt->close();
    
    // Now soft delete the order (set is_active = 0 and status = 'cancelled')
    $cancelStmt = $conn->prepare("
        UPDATE orders 
        SET is_active = 0, status = 'cancelled', updated_at = NOW() 
        WHERE order_id = ? AND user_id = ? AND is_active = 1
    ");
    $cancelStmt->bind_param("ii", $orderId, $userId);
    $cancelStmt->execute();
    
    if ($cancelStmt->affected_rows === 0) {
        $cancelStmt->close();
        $conn->rollback();
        die("Failed to cancel order.");
    }
    
    $cancelStmt->close();
    
    // Commit all changes
    $conn->commit();
    
    // Log successful cancellation
    error_log("Order $orderId cancelled successfully. Restored stock for: " . implode(", ", $restoredItems));
    
    $conn->close();
    
    // Redirect back to My Orders page with success message
    header("Location: my-orders.php?msg=Order cancelled successfully and stock restored");
    exit();

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Order cancellation error: " . $e->getMessage());
    die("Error cancelling order: " . $e->getMessage());
}
?>