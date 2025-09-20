<?php
session_start();

// Check authentication
if (!isset($_SESSION['userId'])) {
    echo json_encode(["status" => "error", "message" => "User not authenticated"]);
    exit();
}

include('../Backend/sql-config.php'); // This should define $conn

// Ensure $conn is available
if (!isset($conn)) {
    echo json_encode(["status" => "error", "message" => "Database connection not found"]);
    exit();
}

// Get raw cart JSON
$rawData = file_get_contents("php://input");
error_log("Raw POST data: " . $rawData);

$data = json_decode($rawData, true);
error_log("Decoded JSON data: " . json_encode($data));

if (!$data || !isset($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(["status" => "error", "message" => "Invalid cart data"]);
    exit();
}

$userId = $_SESSION['userId'];
$cartArray = $data['cart'];

error_log("Cart array structure: " . json_encode($cartArray));

if (empty($cartArray)) {
    echo json_encode(["status" => "error", "message" => "Cart is empty"]);
    exit();
}

try {
    $conn->begin_transaction(); 
    
    // 1. CONSOLIDATE CART - Merge duplicate items by liquor ID
    $consolidatedCart = [];
    
    foreach ($cartArray as $index => $item) {
        if (!isset($item['id'], $item['quantity'], $item['price'], $item['name'])) {
            $conn->rollback();
            echo json_encode([
                "status" => "error", 
                "message" => "Missing required cart item data at index $index"
            ]);
            exit();
        }
        
        // Validate data types and ranges
        if (!is_numeric($item['id']) || !is_numeric($item['quantity']) || !is_numeric($item['price'])) {
            $conn->rollback();
            echo json_encode([
                "status" => "error", 
                "message" => "Invalid data types in cart item at index $index"
            ]);
            exit();
        }
        
        if ((int)$item['quantity'] <= 0) {
            $conn->rollback();
            echo json_encode([
                "status" => "error", 
                "message" => "Invalid quantity for item: " . $item['name']
            ]);
            exit();
        }
        
        if ((float)$item['price'] < 0) {
            $conn->rollback();
            echo json_encode([
                "status" => "error", 
                "message" => "Invalid price for item: " . $item['name']
            ]);
            exit();
        }
        
        $liqourId = (int)$item['id'];
        $quantity = (int)$item['quantity'];
        $price = (float)$item['price'];
        $name = $item['name'];
        
        // If this liquor already exists in consolidated cart, add quantities
        if (isset($consolidatedCart[$liqourId])) {
            $consolidatedCart[$liqourId]['quantity'] += $quantity;
            error_log("Consolidated duplicate liquor ID $liqourId. New quantity: " . $consolidatedCart[$liqourId]['quantity']);
        } else {
            $consolidatedCart[$liqourId] = [
                'id' => $liqourId,
                'name' => $name,
                'quantity' => $quantity,
                'price' => $price
            ];
        }
    }
    
    error_log("Consolidated cart: " . json_encode($consolidatedCart));
    
    // 2. Check stock availability AND validate liquor exists + is active
    $outOfStock = [];
    $invalidItems = [];
    
    foreach ($consolidatedCart as $item) {
        $liqourId = $item['id'];
        $quantity = $item['quantity'];

        // First check if the liquor exists and is active
        $stmt = $conn->prepare("SELECT name, price FROM liqours WHERE liqour_id=? AND is_active=1");
        $stmt->bind_param("i", $liqourId);
        $stmt->execute();
        $liqourResult = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$liqourResult) {
            $invalidItems[] = $item['name'];
            continue;
        }
        
        // Check stock
        $stmt = $conn->prepare("SELECT SUM(quantity) AS total_quantity FROM stock WHERE liqour_id=? AND is_active=1");
        $stmt->bind_param("i", $liqourId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $available = intval($res['total_quantity'] ?? 0);
        if ($quantity > $available) {
            $outOfStock[] = [
                'name' => $item['name'],
                'requested' => $quantity,
                'available' => $available
            ];
        }
    }

    // Handle validation errors
    if (!empty($invalidItems)) {
        $conn->rollback();
        echo json_encode([
            "status" => "error",
            "message" => "Some items are no longer available",
            "invalid_items" => $invalidItems
        ]);
        exit();
    }

    if (!empty($outOfStock)) {
        $conn->rollback();
        echo json_encode([
            "status" => "error",
            "message" => "Some items are out of stock",
            "items" => $outOfStock
        ]);
        exit();
    }

    // 3. Calculate total (with additional validation against DB prices)
    $total = 0;
    foreach ($consolidatedCart as $item) {
        $liqourId = $item['id'];
        $quantity = $item['quantity'];
        $cartPrice = $item['price'];
        
        // Verify price against database (prevents price manipulation)
        $stmt = $conn->prepare("SELECT price FROM liqours WHERE liqour_id=?");
        $stmt->bind_param("i", $liqourId);
        $stmt->execute();
        $dbResult = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($dbResult) {
            $dbPrice = (float)$dbResult['price'];
            // Allow small floating point differences (0.01)
            if (abs($cartPrice - $dbPrice) > 0.01) {
                $conn->rollback();
                echo json_encode([
                    "status" => "error",
                    "message" => "Price mismatch detected for item: " . $item['name'] . ". Please refresh and try again.",
                    "debug" => [
                        "cart_price" => $cartPrice,
                        "db_price" => $dbPrice,
                        "difference" => abs($cartPrice - $dbPrice)
                    ]
                ]);
                exit();
            }
            // Use database price for calculation (more secure)
            $total += ($dbPrice * $quantity);
        }
    }
    
    // 4. Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, status, total, created_at, updated_at) 
        VALUES (?, 'pending', ?, NOW(), NOW())
    ");
    $stmt->bind_param("id", $userId, $total);
    if (!$stmt->execute()) {
        $conn->rollback();
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to create order",
            "sql_error" => $stmt->error
        ]);
        exit();
    }
    $orderId = $stmt->insert_id;
    $stmt->close();
    
    if ($orderId <= 0) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Failed to get order ID"]);
        exit();
    }
    
    error_log("Created order with ID: $orderId");
    
    // 5. Insert order items AND deduct stock (using consolidated cart)
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, liqour_id, quantity, price) 
        VALUES (?, ?, ?, ?)
    ");
    
    $itemsInserted = 0;
    foreach ($consolidatedCart as $item) {
        $liqourId = $item['id'];
        $qty = $item['quantity'];
        
        // Get the verified price from database
        $priceStmt = $conn->prepare("SELECT price FROM liqours WHERE liqour_id=?");
        $priceStmt->bind_param("i", $liqourId);
        $priceStmt->execute();
        $priceResult = $priceStmt->get_result()->fetch_assoc();
        $priceStmt->close();
        
        if (!$priceResult) {
            error_log("Could not find price for liquor ID: $liqourId");
            continue;
        }
        
        $price = (float)$priceResult['price'];
        
        // Insert order item (now guaranteed to be unique per order)
        $stmt->bind_param("iiid", $orderId, $liqourId, $qty, $price);
        if ($stmt->execute()) {
            $itemsInserted++;
            error_log("Inserted order item: Order $orderId, Liquor $liqourId, Qty $qty, Price $price");
            
            // IMPORTANT: Deduct stock from inventory
            // This uses a FIFO approach - deducts from warehouses in order
            $remainingToDeduct = $qty;
            $stockStmt = $conn->prepare("
                SELECT warehouse_id, quantity 
                FROM stock 
                WHERE liqour_id=? AND is_active=1 AND quantity > 0 
                ORDER BY warehouse_id ASC
                FOR UPDATE
            ");
            $stockStmt->bind_param("i", $liqourId);
            $stockStmt->execute();
            $stockResult = $stockStmt->get_result();
            
            // Fetch all rows first, then process them
            $stockRows = [];
            while ($row = $stockResult->fetch_assoc()) {
                $stockRows[] = $row;
            }
            $stockStmt->close();
            
            // Now process the stock deductions
            foreach ($stockRows as $stockRow) {
                if ($remainingToDeduct <= 0) break;
                
                $warehouseId = $stockRow['warehouse_id'];
                $availableQty = $stockRow['quantity'];
                
                $deductQty = min($remainingToDeduct, $availableQty);
                $newQty = $availableQty - $deductQty;
                
                // Update stock
                $updateStmt = $conn->prepare("
                    UPDATE stock 
                    SET quantity=?, updated_at=NOW() 
                    WHERE liqour_id=? AND warehouse_id=?
                ");
                $updateStmt->bind_param("iii", $newQty, $liqourId, $warehouseId);
                if (!$updateStmt->execute()) {
                    error_log("Failed to update stock: " . $updateStmt->error);
                    $conn->rollback();
                    echo json_encode([
                        "status" => "error", 
                        "message" => "Stock update failed for item: " . $item['name']
                    ]);
                    exit();
                }
                $updateStmt->close();
                
                $remainingToDeduct -= $deductQty;
                
                error_log("Deducted $deductQty from warehouse $warehouseId for liquor $liqourId. New quantity: $newQty, Remaining to deduct: $remainingToDeduct");
            }
            
            // Double-check: if we couldn't deduct enough, something went wrong
            if ($remainingToDeduct > 0) {
                $conn->rollback();
                echo json_encode([
                    "status" => "error", 
                    "message" => "Stock deduction failed for item: " . $item['name'] . ". Could not deduct $remainingToDeduct units."
                ]);
                exit();
            }
        } else {
            error_log("Failed to insert order item: " . $stmt->error);
            $conn->rollback();
            echo json_encode([
                "status" => "error", 
                "message" => "Failed to insert order item for: " . $item['name']
            ]);
            exit();
        }
    }
    
    $stmt->close();
    
    if ($itemsInserted === 0) {
        $conn->rollback();
        echo json_encode([
            "status" => "error", 
            "message" => "No order items could be inserted"
        ]);
        exit();
    }
    
    // 6. Update order status to 'processing' since stock has been deducted
    $stmt = $conn->prepare("UPDATE orders SET status='processing', updated_at=NOW() WHERE order_id=?");
    $stmt->bind_param("i", $orderId);
    if (!$stmt->execute()) {
        error_log("Failed to update order status: " . $stmt->error);
        // Don't rollback here - order was successful, just status update failed
    }
    $stmt->close();
    
    $conn->commit(); 
    
    echo json_encode([
        "status" => "success",
        "message" => "Order placed successfully",
        "orderId" => $orderId,
        "total" => $total,
        "items_processed" => $itemsInserted,
        "consolidated_items" => count($consolidatedCart)
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    error_log("Order processing error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(["status" => "error", "message" => "Order processing failed: " . $e->getMessage()]);
}
?>