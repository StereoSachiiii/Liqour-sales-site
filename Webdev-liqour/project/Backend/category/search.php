<?php
session_start();
include("../sql-config.php");

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../public/login-signup.php");
    exit();
}

$records_per_page = 10;

// Handle AJAX live search request
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $q = $_GET['q'] ?? '';
    $q = $conn->real_escape_string($q);

    $sql = "SELECT * FROM liqour_categories 
            WHERE is_active=1 AND name LIKE '%$q%' 
            ORDER BY liqour_category_id DESC
            LIMIT $records_per_page";

    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $id = htmlspecialchars($row['liqour_category_id']);
            $name = htmlspecialchars($row['name']);
            $status = $row['is_active'] ? 'Active' : 'Inactive';
            $badgeClass = $row['is_active'] ? 'badge-active' : 'badge-inactive';
            echo "<tr>
                    <td>{$id}</td>
                    <td>{$name}</td>
                    <td><span class='badge {$badgeClass}'>{$status}</span></td>
                    <td>
                        <a href='update.php?id={$id}' class='btn'>Update</a>
                        <a href='delete.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this category?');\" class='btn soft-delete'>Soft Delete</a>
                        <a href='delete.php?id={$id}&type=hard' onclick=\"return confirm('Permanently delete this category?');\" class='btn delete'>Delete Forever</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No categories found</td></tr>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Categories</title>
<link rel="stylesheet" href="../css/index.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<style>
* { box-sizing: border-box; }
body { font-family:'Inter',sans-serif; background:#f8f9fa; margin:0; padding:20px; }
.container { max-width: 900px; margin: auto; }
.section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:10px; }
.section-header h2 { margin:0; color:#212529; font-size:1.5rem; font-weight:600; }
.search-box { padding:0.75rem; border-radius:6px; border:1px solid #dee2e6; width:250px; font-size:1rem; }
.table-container { overflow-x:auto; background:white; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05); border:1px solid #dee2e6; padding:1rem; }
.table { width:100%; border-collapse:collapse; }
.table th, .table td { padding:0.75rem 0.5rem; text-align:left; border-bottom:1px solid #dee2e6; }
.badge { padding:4px 8px; border-radius:4px; font-size:0.85em; color:white; }
.badge-active { background:#4CAF50; }
.badge-inactive { background:#777; }
.btn { padding:5px 10px; border-radius:6px; margin-right:5px; text-decoration:none; background:#212529;color:#fff; font-size:0.85em; display:inline-block; transition:all 0.2s; }
.btn:hover { background:#343a40; }
.soft-delete { background:#FF9800; }
.soft-delete:hover { background:#e07b00; }
.delete { background:#E53E3E; }
.delete:hover { background:#c53030; }
</style>
</head>
<body>

<div class="container">
    <a href="../manage-dashboard.php" 
   style="
      display:inline-block;
      padding:8px 16px;
      background-color:#B0B0B0; 
      color:#fff; 
      text-decoration:none; 
      border-radius:6px; 
      font-size:0.9rem; 
      transition: background 0.2s;
   " 
   onmouseover="this.style.backgroundColor='#999999';" 
   onmouseout="this.style.backgroundColor='#B0B0B0';">
   Back to Dashboard
</a>

    <div class="section-header">
        <h2>Liquor Categories</h2>
        <input type="text" id="search-input" class="search-box" placeholder="Search categories...">
    </div>

    <div class="table-container">
        <table class="table" id="categories-table">
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Default load
                $res = $conn->query("SELECT * FROM liqour_categories WHERE is_active=1 ORDER BY liqour_category_id DESC LIMIT $records_per_page");
                if ($res && $res->num_rows>0) {
                    while ($row=$res->fetch_assoc()) {
                        $id = htmlspecialchars($row['liqour_category_id']);
                        $name = htmlspecialchars($row['name']);
                        $status = $row['is_active'] ? 'Active' : 'Inactive';
                        $badgeClass = $row['is_active'] ? 'badge-active' : 'badge-inactive';
                        echo "<tr>
                                <td>{$id}</td>
                                <td>{$name}</td>
                                <td><span class='badge {$badgeClass}'>{$status}</span></td>
                                <td>
                                    <a href='update.php?id={$id}' class='btn'>Update</a>
                                    <a href='delete.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this category?');\" class='btn soft-delete'>Soft Delete</a>
                                    <a href='delete.php?id={$id}&type=hard' onclick=\"return confirm('Permanently delete this category?');\" class='btn delete'>Delete Forever</a>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No categories found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function(){
    let ajaxTimeout = null;
    $('#search-input').on('input', function(e){
        e.preventDefault(); // prevent form submission on enter
        clearTimeout(ajaxTimeout);

        ajaxTimeout = setTimeout(() => {
            let query = $(this).val().trim();
            $.get('search.php', { ajax: 1, q: query }, function(data){
                $('#categories-table tbody').html(data);
            });
        }, 300); // debounce 300ms
    });

    // Prevent form submission on enter
    $('#search-input').on('keypress', function(e){
        if (e.which === 13) e.preventDefault();
    });
});
</script>

</body>
</html>
