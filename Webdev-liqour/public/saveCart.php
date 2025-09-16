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
error_log("First cart item keys: " . (isset($cartArray[0]) ? json_encode(array_keys($cartArray[0])) : "No items"));

if (empty($cartArray)) {
    echo json_encode(["status" => "error", "message" => "Cart is empty"]);
    exit();
}

try {
    $conn->begin_transaction(); 
    
    $outOfStock = [];
    foreach ($cartArray as $item) {
        $liqourId = (int)$item['id'];
        $quantity = (int)$item['quantity'];

        $stmt = $conn->prepare("SELECT SUM(quantity) AS total_quantity FROM stock WHERE liqour_id=? AND is_active=1");
        $stmt->bind_param("i", $liqourId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        $available = intval($res['total_quantity'] ?? 0);
        if ($quantity > $available) {
            $outOfStock[] = [
                'name' => $item['name'],
                'requested' => $quantity,
                'available' => $available
            ];
        }
        $stmt->close();
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


    $total = 0;
    foreach ($cartArray as $item) {
        $total += ((float)$item['price'] * (int)$item['quantity']);
    }
    
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, status, total, created_at, updated_at) 
        VALUES (?, 'pending', ?, NOW(), NOW())
    ");
    $stmt->bind_param("id", $userId, $total);
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();
    
    if ($orderId <= 0) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Failed to create order"]);
        exit();
    }
    
    $liqourIds = array_map(fn($item) => (int)$item['id'], $cartArray);
    $placeholders = implode(',', array_fill(0, count($liqourIds), '?'));
    $types = str_repeat('i', count($liqourIds));
    $stmt = $conn->prepare("SELECT liqour_id FROM liqours WHERE liqour_id IN ($placeholders)");
    $stmt->bind_param($types, ...$liqourIds);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $validIds = [];
    while ($row = $result->fetch_assoc()) {
        $validIds[] = (int)$row['liqour_id'];
    }
    $stmt->close();
    
    if (empty($validIds)) {
        $conn->rollback();
        echo json_encode([
            "status" => "error", 
            "message" => "No valid liquor IDs found",
            "debug" => [
                "requested_ids" => $liqourIds,
                "valid_ids" => $validIds
            ]
        ]);
        exit();
    }
    
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, liqour_id, quantity, price) 
        VALUES (?, ?, ?, ?)
    ");
    
    $itemsInserted = 0;
    foreach ($cartArray as $item) {
        $liqourId = (int)$item['id'];
        $qty = (int)$item['quantity'];
        $price = (float)$item['price'];
        
        if (!in_array($liqourId, $validIds)) {
            continue; 
        }
        
        $stmt->bind_param("iiid", $orderId, $liqourId, $qty, $price);
        if ($stmt->execute()) {
            $itemsInserted++;
        } else {
            error_log("Failed to insert order item: " . $stmt->error);
        }
    }
    
    $stmt->close();
    
    if ($itemsInserted === 0) {
        $conn->rollback();
        echo json_encode([
            "status" => "error", 
            "message" => "No order items could be inserted",
            "debug" => [
                "cart_names" => array_column($cartArray, 'name')
            ]
        ]);
        exit();
    }
    
    $conn->commit(); 
    
    echo json_encode([
        "status" => "success",
        "message" => "Cart saved successfully",
        "orderId" => $orderId,
        "total" => $total
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
