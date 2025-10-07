<?php
session_start();
include("../sql-config.php");

// --- ADMIN CHECK ---
if (!isset($_SESSION['login'], $_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) ||
    $_SESSION['login'] !== 'success' || $_SESSION['is_admin'] != 1) {
    header('Location: ../process-login.php');
    exit();
}

$liqourId = $_GET['liqour_id'] ?? null;
$warehouseId = $_GET['warehouse_id'] ?? null;
$type = $_GET['type'] ?? 'soft'; // default soft delete
$restore = isset($_GET['restore']) && $_GET['restore'] == 1;

if (!$liqourId || !$warehouseId) {
    die("Missing liquor or warehouse ID.");
}

// --- FETCH STOCK ---
$stmt = $conn->prepare("
    SELECT s.quantity, s.is_active, l.name AS liqour_name, w.name AS warehouse_name
    FROM stock s
    JOIN liqours l ON s.liqour_id = l.liqour_id
    JOIN warehouse w ON s.warehouse_id = w.warehouse_id
    WHERE s.liqour_id=? AND s.warehouse_id=?
");
$stmt->bind_param("ii",$liqourId,$warehouseId);
$stmt->execute();
$stock = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$stock) die("Stock record not found.");

// --- HELPER TO RENDER MESSAGE BOX ---
function renderBox($title, $msg, $links = []) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?=htmlspecialchars($title)?></title>
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

.container, .box {
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

th, td {
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

.cancel {
    background: #666;
    color: #fff;
}

.cancel:hover {
    background: #444;
}

a.btn {
    display: inline-block;
    padding: 0.6rem 1.2rem;
    margin: 0.25rem;
    text-decoration: none;
    border-radius: var(--radius);
    color: #fff;
    background: var(--primary);
    font-weight: 500;
    transition: var(--transition);
}

a.btn:hover {
    background: var(--primary-dark);
}

a.btn-success {
    background: var(--success);
}

a.btn-success:hover {
    background: #218838;
}

a.btn-danger {
    background: var(--danger);
}

a.btn-danger:hover {
    background: #c82333;
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
        <div class="box">
            <h2><?=htmlspecialchars($title)?></h2>
            <p><?=htmlspecialchars($msg)?></p>
            <?php foreach($links as $link): ?>
                <a href="<?= $link['href'] ?>" class="btn <?= $link['class'] ?? '' ?>"><?= htmlspecialchars($link['text']) ?></a>
            <?php endforeach; ?>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// --- RESTORE FUNCTIONALITY ---
if ($restore) {
    if ($stock['is_active']) {
        renderBox("⚠️ Already Active", "This stock record is already active.");
    }
    $stmtRestore = $conn->prepare("UPDATE stock SET is_active=1, updated_at=NOW() WHERE liqour_id=? AND warehouse_id=?");
    $stmtRestore->bind_param("ii",$liqourId,$warehouseId);
    $stmtRestore->execute();
    $stmtRestore->close();

    renderBox("✅ Restored", "Stock for '{$stock['liqour_name']}' in '{$stock['warehouse_name']}' has been restored.",
        [['href'=>'../manage-dashboard.php#stock','text'=>'Back to Stock']]);
}

// --- DELETION RULE: ONLY IF STOCK = 0 ---

if ($stock['quantity'] > 0) {
    $moveLink = "move.php?liqour_id={$liqourId}&warehouse_id={$warehouseId}";
    renderBox(
        " Cannot Delete",
        "This stock still has {$stock['quantity']} units. Only empty stock can be soft or hard deleted.",
        [
            ['href'=>$moveLink,'text'=>'Move Stock','class'=>'btn-success'],
            ['href'=>'../manage-dashboard.php#stock','text'=>'Back to Stock']
        ]
    );
}


// --- PROCESS DELETION ---
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if ($_POST['confirm']==='yes') {
        if ($type==='hard') {
            $stmtDel = $conn->prepare("DELETE FROM stock WHERE liqour_id=? AND warehouse_id=?");
        } else {
            $stmtDel = $conn->prepare("UPDATE stock SET is_active=0, updated_at=NOW() WHERE liqour_id=? AND warehouse_id=?");
        }
        $stmtDel->bind_param("ii",$liqourId,$warehouseId);
        $stmtDel->execute();
        $stmtDel->close();

        renderBox($type==='hard'?"✅ Permanently Deleted":"✅ Soft Deleted",
                  $type==='hard'?"Stock deleted permanently.":"Stock soft-deleted. It can be restored later.",
                  [['href'=>'../manage-dashboard.php#stock','text'=>'Back to Stock']]);
    } else {
        renderBox("❌ Cancelled","Stock deletion cancelled.",
                  [['href'=>'../manage-dashboard.php#stock','text'=>'Back to Stock']]);
    }
}

// --- SHOW CONFIRMATION FORM ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm <?= ucfirst($type) ?> Deletion</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f4f4;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;padding:1rem;}
.container{background:#fff;padding:2rem;border-radius:12px;max-width:500px;width:100%;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h2{margin-bottom:1rem;}
table{width:100%;border-collapse:collapse;margin-bottom:15px;}
th,td{border:1px solid #ccc;padding:8px;text-align:left;}
th{background:#eee;}
.actions{margin-top:20px;}
button{padding:10px 20px;margin:5px;border:none;border-radius:6px;cursor:pointer;font-weight:bold;transition:all 0.2s;}
.confirm{background:#c0392b;color:#fff;}
.confirm:hover{background:#a93226;}
.cancel{background:#666;color:#fff;}
.cancel:hover{background:#444;}
@media(max-width:500px){button{width:45%;margin:5px 0;}}
</style>
</head>
<body>
<div class="container">
<h2>Confirm <?= ucfirst($type) ?> Deletion</h2>
<table>
    <tr><th>Liquor</th><td><?= htmlspecialchars($stock['liqour_name']) ?></td></tr>
    <tr><th>Warehouse</th><td><?= htmlspecialchars($stock['warehouse_name']) ?></td></tr>
    <tr><th>Quantity</th><td><?= $stock['quantity'] ?></td></tr>
    <tr><th>Status</th><td><?= $stock['is_active']?'Active':'Inactive' ?></td></tr>
</table>
<p>Are you sure you want to <strong><?= $type==='hard'?'permanently delete':'soft delete' ?></strong> this stock record?</p>
<form method="post">
    <div class="actions">
        <button type="submit" name="confirm" value="yes" class="confirm">Yes</button>
        <button type="submit" name="confirm" value="no" class="cancel">Cancel</button>
    </div>
</form>
</div>
</body>
</html>
