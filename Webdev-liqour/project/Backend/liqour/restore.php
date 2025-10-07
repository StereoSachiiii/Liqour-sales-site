<?php
session_start();
include("../sql-config.php");

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../adminlogin.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../manage-dashboard.php");
    exit();
}

$lid = intval($_GET['id']);

// Helper function
function renderBox($title, $msg, $backLink = "../manage-dashboard.php") {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?></title>
        <style>
            body { font-family: Arial, sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; padding:1rem; }
            .box { background:white; padding:2rem; border-radius:12px; border:1px solid #ccc; max-width:700px; width:100%; text-align:center; }
            h2 { margin-bottom:1rem; }
            p { margin-bottom:1.5rem; }
            a.btn { display:inline-block; padding:0.75rem 1.25rem; background:#212529; color:white; text-decoration:none; border-radius:6px; margin:0.25rem; }
            a.btn:hover { background:#343a40; }
            a.btn-success { background:#28a745; }
            a.btn-success:hover { background:#218838; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2><?= htmlspecialchars($title) ?></h2>
            <p><?= htmlspecialchars($msg) ?></p>
            <a href="<?= $backLink ?>" class="btn btn-success">← Back to Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Fetch liquor
$stmt = $conn->prepare("SELECT name, is_active FROM liqours WHERE liqour_id=?");
$stmt->bind_param("i", $lid);
$stmt->execute();
$liquor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$liquor) {
    renderBox("❌ Not Found", "Liquor not found.");
}

$liquorName = $liquor['name'];
$isActive = $liquor['is_active'];

if ($isActive) {
    renderBox("⚠️ Already Active", "Liquor '$liquorName' is already active.");
}

// Restore liquor + stock
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE liqours SET is_active=1, updated_at=NOW() WHERE liqour_id=?");
    $stmt->bind_param("i", $lid);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE stock SET is_active=1, updated_at=NOW() WHERE liqour_id=?");
    $stmt->bind_param("i", $lid);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    renderBox("✅ Restored", "Liquor '$liquorName' and its stock have been successfully restored.");
} catch (Exception $e) {
    $conn->rollback();
    renderBox("❌ Restore Failed", $e->getMessage());
}
?>
