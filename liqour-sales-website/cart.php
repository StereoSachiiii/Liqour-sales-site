<?php 
header("Content-Type: application/json");

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!isset($data['cart']) || !is_array($data['cart'])) {    echo json_encode(["status" => "Error", "message" => "cart wasn't received"]);
    exit();
} else {
    $cartArray = $data['cart'];
    echo json_encode(["status" => "success", "received" => $cartArray]);
}
?>
