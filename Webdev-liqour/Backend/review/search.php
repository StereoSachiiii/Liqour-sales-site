<?php
session_start();
include("../sql-config.php");

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../public/login-signup.php");
    exit();
}

$records_per_page = 10;

// Handle AJAX live search
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $q = $_GET['q'] ?? '';
    $q = $conn->real_escape_string($q);

    $sql = "SELECT r.*, u.name AS user_name, u.email AS user_email, l.name AS liquor_name
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN liqours l ON r.liqour_id = l.liqour_id
            WHERE r.is_active=1 AND (
                l.name LIKE '%$q%' OR 
                u.name LIKE '%$q%' OR 
                u.email LIKE '%$q%'
            )
            ORDER BY r.review_id DESC
            LIMIT $records_per_page";

    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $id = htmlspecialchars($row['review_id']);
            $liquor = htmlspecialchars($row['liquor_name']);
            $user = htmlspecialchars($row['user_name']) . " (" . htmlspecialchars($row['user_email']) . ")";
            $rating = htmlspecialchars($row['rating']);
            $comment = htmlspecialchars($row['comment']);
            $status = $row['is_active'] ? 'Active' : 'Inactive';
            $badgeClass = $row['is_active'] ? 'badge-active' : 'badge-inactive';
            $created = htmlspecialchars($row['created_at']);

            echo "<tr>
                    <td>{$id}</td>
                    <td>{$liquor}</td>
                    <td>{$user}</td>
                    <td>{$rating}/5</td>
                    <td>{$comment}</td>
                    <td><span class='badge {$badgeClass}'>{$status}</span></td>
                    <td>{$created}</td>
                    <td>
                        <a href='delete-review.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this review?');\" class='btn soft-delete'>Soft Delete</a>
                        <a href='delete-review.php?id={$id}&type=hard' onclick=\"return confirm('Permanently delete this review?');\" class='btn delete'>Delete Forever</a>
                    </td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='8'>No reviews found</td></tr>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Reviews</title>
<link rel="stylesheet" href="../css/index.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<style>
* { box-sizing: border-box; }
body { font-family:'Inter',sans-serif; background:#f8f9fa; margin:0; padding:20px; }
.container { max-width: 1200px; margin:auto; }
.section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:10px; }
.section-header h2 { margin:0; color:#212529; font-size:1.5rem; font-weight:600; }
.search-box { padding:0.75rem; border-radius:6px; border:1px solid #dee2e6; width:300px; font-size:1rem; }
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
    <div class="section-header">
        <h2>Reviews</h2>
        <input type="text" id="search-input" class="search-box" placeholder="Search by liquor or user...">
    </div>

    <div class="table-container">
        <table class="table" id="reviews-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Liquor</th>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT r.*, u.name AS user_name, u.email AS user_email, l.name AS liquor_name
                                     FROM reviews r
                                     JOIN users u ON r.user_id = u.id
                                     JOIN liqours l ON r.liqour_id = l.liqour_id
                                     WHERE r.is_active=1
                                     ORDER BY r.review_id DESC
                                     LIMIT $records_per_page");
                if ($res && $res->num_rows>0) {
                    while ($row = $res->fetch_assoc()) {
                        $id = htmlspecialchars($row['review_id']);
                        $liquor = htmlspecialchars($row['liquor_name']);
                        $user = htmlspecialchars($row['user_name']) . " (" . htmlspecialchars($row['user_email']) . ")";
                        $rating = htmlspecialchars($row['rating']);
                        $comment = htmlspecialchars($row['comment']);
                        $status = $row['is_active'] ? 'Active' : 'Inactive';
                        $badgeClass = $row['is_active'] ? 'badge-active' : 'badge-inactive';
                        $created = htmlspecialchars($row['created_at']);
                        echo "<tr>
                                <td>{$id}</td>
                                <td>{$liquor}</td>
                                <td>{$user}</td>
                                <td>{$rating}/5</td>
                                <td>{$comment}</td>
                                <td><span class='badge {$badgeClass}'>{$status}</span></td>
                                <td>{$created}</td>
                                <td>
                                    <a href='delete-review.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this review?');\" class='btn soft-delete'>Soft Delete</a>
                                    <a href='delete-review.php?id={$id}&type=hard' onclick=\"return confirm('Permanently delete this review?');\" class='btn delete'>Delete Forever</a>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No reviews found</td></tr>";
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
        e.preventDefault();
        clearTimeout(ajaxTimeout);
        ajaxTimeout = setTimeout(() => {
            let query = $(this).val().trim();
            $.get('search.php', { ajax: 1, q: query }, function(data){
                $('#reviews-table tbody').html(data);
            });
        }, 300);
    });

    $('#search-input').on('keypress', function(e){
        if (e.which === 13) e.preventDefault();
    });
});
</script>

</body>
</html>
