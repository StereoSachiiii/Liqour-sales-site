<?php
session_start();
require_once "../sql-config.php";

if (!isset($_GET['id'], $_GET['type'])) {
    die("Invalid request");
}

$warehouse_id = intval($_GET['id']);
$type = $_GET['type'];

// Check stock before deleting (both soft & hard)
$checkSql = "SELECT SUM(quantity) AS total_qty FROM stock WHERE warehouse_id = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("i", $warehouse_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_qty = $row['total_qty'] ?? 0;

if ($total_qty > 0) {
    echo "<script>
            alert('❌ Cannot delete warehouse: it still contains stock! Please move items first.');
            window.location.href='../manage-dashboard.php#warehouse';
          </script>";
    exit;
}

// Soft delete: mark inactive
if ($type === 'soft') {
    $stmt = $conn->prepare("UPDATE warehouse SET is_active = 0, updated_at = NOW() WHERE warehouse_id = ?");
    $stmt->bind_param("i", $warehouse_id);
    if ($stmt->execute()) {
        echo "<script>
                alert('✅ Warehouse soft-deleted. It can be restored later.');
                window.location.href='../manage-dashboard.php#warehouse';
              </script>";
    } else {
        echo "<script>
                alert('❌ Error soft-deleting warehouse.');
                window.location.href='../manage-dashboard.php#warehouse';
              </script>";
    }
    exit();
}

// Hard delete: safe now because stock is 0
$deleteSql = "DELETE FROM warehouse WHERE warehouse_id = ?";
$stmt = $conn->prepare($deleteSql);
$stmt->bind_param("i", $warehouse_id);

if ($stmt->execute()) {
    echo "<script>
            alert('✅ Warehouse permanently deleted.');
            window.location.href='../manage-dashboard.php#warehouse';
          </script>";
} else {
    echo "<script>
            alert('❌ Error deleting warehouse.');
            window.location.href='../manage-dashboard.php#warehouse';
          </script>";
}
?>
