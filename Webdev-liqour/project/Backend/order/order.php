<?php
session_start();
include(__DIR__ . "/../sql-config.php"); // Adjust relative path

// Check admin login
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: /public/login-signup.php");
    exit();
}

$records_per_page = 10;

// AJAX handler
if (isset($_GET['ajax']) && $_GET['ajax']==1) {
    $query = $_GET['q'] ?? '';
    $page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
    $offset = ($page-1) * $records_per_page;
    $search_param = "%{$query}%";

    // Total count
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total
                                  FROM orders o
                                  JOIN users u ON o.user_id = u.id
                                  WHERE o.is_active=1 AND (o.order_id LIKE ? OR u.name LIKE ? OR o.status LIKE ?)");
    $count_stmt->bind_param("sss",$search_param,$search_param,$search_param);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch paginated results
    $stmt = $conn->prepare("SELECT o.*, u.name AS user_name
                            FROM orders o
                            JOIN users u ON o.user_id = u.id
                            WHERE o.is_active=1 AND (o.order_id LIKE ? OR u.name LIKE ? OR o.status LIKE ?)
                            ORDER BY o.order_id DESC
                            LIMIT ? OFFSET ?");
    $stmt->bind_param("sssii",$search_param,$search_param,$search_param,$records_per_page,$offset);
    $stmt->execute();
    $res = $stmt->get_result();

    $response = ['html'=>'','pagination'=>'','total'=>$total_records];

    if($res && $res->num_rows>0){
        while($row=$res->fetch_assoc()){
            $id = htmlspecialchars($row['order_id']);
            $user = htmlspecialchars($row['user_name']);
            $total = number_format($row['total'], 2);
            $status = htmlspecialchars(ucfirst($row['status']));
            $created = htmlspecialchars($row['created_at']);
            $is_active = $row['is_active'];
            $badgeClass = $is_active ? 'badge-active' : 'badge-inactive';

            $response['html'] .= "<tr>
                <td>{$id}</td>
                <td>{$user}</td>
                <td>\${$total}</td>
                <td><span class='badge {$badgeClass}'>{$status}</span></td>
                <td>{$created}</td>
                <td>
                    <div class='action-buttons'>
                        <a href='view.php?order_id={$id}' class='btn'>View</a>
                        <a href='update.php?order_id={$id}' class='btn update'>Update</a>
                        <a href='delete.php?order_id={$id}&type=soft' onclick=\"return confirm('Soft delete this order?');\" class='btn soft-delete'>Soft Delete</a>
                        <a href='delete.php?order_id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE?');\" class='btn delete'>Delete Forever</a>
                    </div>
                </td>
            </tr>";
        }
    } else {
        $response['html'] = "<tr><td colspan='6' style='text-align:center; padding:2rem; color:#666;'>No orders found</td></tr>";
    }

    // Pagination
    if($total_pages>1){
        $pagination = '<div class="pagination-container">';
        $pagination .= '<div class="pagination-info">Showing '.min($offset+1,$total_records).' to '.min($offset+$records_per_page,$total_records).' of '.$total_records.' entries</div>';
        $pagination .= '<div class="pagination">';
        if($page>1) $pagination .= '<a href="#" class="btn page-btn" data-page="'.($page-1).'">← Prev</a>';
        else $pagination .= '<span class="btn disabled">← Prev</span>';

        $start = max(1,$page-2);
        $end = min($total_pages,$page+2);
        if($start>1){ $pagination .= '<a href="#" class="btn page-btn" data-page="1">1</a>'; if($start>2) $pagination .= '<span class="btn disabled">...</span>'; }
        for($i=$start;$i<=$end;$i++){ $active = ($i==$page)?'active':''; $pagination .= '<a href="#" class="btn page-btn '.$active.'" data-page="'.$i.'">'.$i.'</a>'; }
        if($end<$total_pages){ if($end<$total_pages-1) $pagination .= '<span class="btn disabled">...</span>'; $pagination .= '<a href="#" class="btn page-btn" data-page="'.$total_pages.'">'.$total_pages.'</a>'; }
        if($page<$total_pages) $pagination .= '<a href="#" class="btn page-btn" data-page="'.($page+1).'">Next →</a>';
        else $pagination .= '<span class="btn disabled">Next →</span>';

        $pagination .= '</div></div>';
        $response['pagination'] = $pagination;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Initial page load
$page = isset($_GET['page'])? max(1,intval($_GET['page'])) : 1;
$offset = ($page-1)*$records_per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE is_active=1");
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records/$records_per_page);

$stmt = $conn->prepare("SELECT o.*, u.name AS user_name
                        FROM orders o
                        JOIN users u ON o.user_id = u.id
                        WHERE o.is_active=1
                        ORDER BY o.order_id DESC
                        LIMIT ? OFFSET ?");
$stmt->bind_param("ii",$records_per_page,$offset);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders Management</title>
<link rel="stylesheet" href="../css/index.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<style>
:root {
    --primary: #FFD700;
    --primary-dark: #E6B800;
    --accent-light: #FFFACD;
    --success: #28a745;
    --warning: #ffc107;
    --danger: #dc3545;
    --text:#333;
    --bg:#fff;
    --border:#ddd;
    --radius:6px;
    --transition:0.3s;
    --update: #2196F3;
    --update-dark: #1976D2;
}

body{font-family:'Segoe UI',sans-serif; margin:0; padding:20px; background:var(--accent-light); color:var(--text);}
h1,h2,h3,h4{margin:0;}
a{color:inherit;text-decoration:none;}
.btn{padding:0.3rem 0.5rem;border-radius:var(--radius);font-size:0.8rem;font-weight:600;transition:var(--transition);cursor:pointer;text-decoration:none;color:#fff;background:var(--primary);}
.btn:hover{background:var(--primary-dark);}
.table-container{overflow-x:auto;margin-top:1rem;}
.table{width:100%;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;border-collapse:collapse;}
.table th, .table td{padding:0.75rem 0.5rem; border-bottom:1px solid var(--border);}
.table th{background:var(--primary);color:#fff;font-weight:600;}
.badge{display:inline-block;padding:0.2rem 0.5rem;border-radius:var(--radius);font-size:0.85rem;font-weight:600;}
.badge-active{background:var(--success);color:#fff;}
.badge-inactive{background:var(--danger);color:#fff;}
.action-buttons{display:flex;flex-wrap:wrap;gap:0.3rem;}
.action-buttons .btn{padding:0.3rem 0.5rem;border-radius:var(--radius);font-size:0.8rem;font-weight:600;transition:var(--transition);cursor:pointer;}
.action-buttons .btn:hover{opacity:0.85;}
.btn.soft-delete{background:var(--warning);color:#fff;}
.btn.delete{background:var(--danger);color:#fff;}
.btn.update{background:var(--update);color:#fff;}
.btn.update:hover{background:var(--update-dark);}
.search-container{position:relative;margin-bottom:1rem;}
.search-box{width:100%;padding:0.5rem 2.5rem 0.5rem 1rem;border-radius:var(--radius);border:1px solid var(--border);outline:none;transition:var(--transition);}
.search-box:focus{border-color:var(--primary-dark);}
.search-icon{position:absolute;right:0.8rem;top:50%;transform:translateY(-50%);font-size:1rem;color:#666;}
.loading-spinner{position:absolute;right:2rem;top:50%;width:1rem;height:1rem;border:2px solid var(--primary);border-top:2px solid transparent;border-radius:50%;animation:spin 0.7s linear infinite;display:none;}
.search-container.loading .loading-spinner{display:block;}
@keyframes spin{0%{transform:translateY(-50%) rotate(0deg);}100%{transform:translateY(-50%) rotate(360deg);}}
.fade-in{animation:fadeIn 0.3s ease-in-out;}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
.pagination-container{margin-top:1rem;display:flex;flex-direction:column;gap:0.5rem;}
.pagination-info{font-size:0.9rem;color:var(--text);}
.pagination{display:flex;flex-wrap:wrap;gap:0.3rem;}
.pagination .btn{padding:0.3rem 0.6rem;border-radius:var(--radius);background:var(--accent-light);font-weight:600;cursor:pointer;transition:var(--transition);}
.pagination .btn:hover{background:var(--primary);color:#fff;}
.pagination .btn.active{background:var(--primary-dark);color:#fff;}
.pagination .btn.disabled{background:#eee;color:#aaa;cursor:default;}
@media(max-width:768px){.action-buttons{flex-direction:column;}}
</style>
</head>
<body>

<header style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
<h1>Orders Management</h1>
<nav style="display:flex;gap:0.5rem;flex-wrap:wrap;">
<a href="../manage-dashboard.php" class="btn">← Back to Dashboard</a>
</nav>
</header>

<section>
<div class="search-container">
<input type="text" id="search-input" class="search-box" placeholder="Search by order ID, user or status..." autocomplete="off">
<span class="search-icon">🔍</span>
<div class="loading-spinner"></div>
</div>

<div class="table-container">
<table class="table">
<thead>
<tr><th>Order ID</th><th>User</th><th>Total</th><th>Status</th><th>Created At</th><th>Actions</th></tr>
</thead>
<tbody id="orders-table-body">
<?php
if($res && $res->num_rows>0){
    while($row=$res->fetch_assoc()){
        $id = htmlspecialchars($row['order_id']);
        $user = htmlspecialchars($row['user_name']);
        $total = number_format($row['total'], 2);
        $status = htmlspecialchars(ucfirst($row['status']));
        $created = htmlspecialchars($row['created_at']);
        $is_active = $row['is_active'];
        $badgeClass = $is_active ? 'badge-active' : 'badge-inactive';
        echo "<tr>
            <td>{$id}</td>
            <td>{$user}</td>
            <td>\${$total}</td>
            <td><span class='badge {$badgeClass}'>{$status}</span></td>
            <td>{$created}</td>
            <td>
                <div class='action-buttons'>
                    <a href='view.php?order_id={$id}' class='btn'>View</a>
                    <a href='update.php?order_id={$id}' class='btn update'>Update</a>
                    <a href='delete.php?order_id={$id}&type=soft' onclick=\"return confirm('Soft delete this order?');\" class='btn soft-delete'>Soft Delete</a>
                    <a href='delete.php?order_id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE?');\" class='btn delete'>Delete Forever</a>
                </div>
            </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='6' style='text-align:center;padding:2rem;color:#666;'>No orders found</td></tr>";
}
?>
</tbody>
</table>
</div>

<div id="pagination-container">
<?php
if($total_pages>1){
    $pagination = '<div class="pagination-container">';
    $pagination .= '<div class="pagination-info">Showing '.min($offset+1,$total_records).' to '.min($offset+$records_per_page,$total_records).' of '.$total_records.' entries</div>';
    $pagination .= '<div class="pagination">';
    if($page>1) $pagination .= '<a href="#" class="btn page-btn" data-page="'.($page-1).'">← Prev</a>';
    else $pagination .= '<span class="btn disabled">← Prev</span>';

    $start = max(1,$page-2);
    $end = min($total_pages,$page+2);
    if($start>1){ $pagination .= '<a href="#" class="btn page-btn" data-page="1">1</a>'; if($start>2) $pagination .= '<span class="btn disabled">...</span>'; }
    for($i=$start;$i<=$end;$i++){ $active = ($i==$page)?'active':''; $pagination .= '<a href="#" class="btn page-btn '.$active.'" data-page="'.$i.'">'.$i.'</a>'; }
    if($end<$total_pages){ if($end<$total_pages-1) $pagination .= '<span class="btn disabled">...</span>'; $pagination .= '<a href="#" class="btn page-btn" data-page="'.$total_pages.'">'.$total_pages.'</a>'; }
    if($page<$total_pages) $pagination .= '<a href="#" class="btn page-btn" data-page="'.($page+1).'">Next →</a>';
    else $pagination .= '<span class="btn disabled">Next →</span>';

    $pagination .= '</div></div>';
    echo $pagination;
}
?>
</div>
</section>

<script>
$(document).ready(function(){
    let ajaxTimeout=null;
    let currentPage=<?= $page ?>;
    let currentQuery='';

    function loadOrders(query,page){
        $('.search-container').addClass('loading');
        $.ajax({
            url:'order.php',
            method:'GET',
            data:{ajax:1,q:query,page:page},
            dataType:'json',
            success:function(resp){
                $('#orders-table-body').html(resp.html).addClass('fade-in');
                $('#pagination-container').html(resp.pagination);
                setTimeout(()=>{$('#orders-table-body').removeClass('fade-in');},300);
            },
            error:function(){alert('Failed to fetch data');},
            complete:function(){ $('.search-container').removeClass('loading'); }
        });
    }

    $('#search-input').on('input',function(){
        currentQuery=$(this).val();
        clearTimeout(ajaxTimeout);
        ajaxTimeout=setTimeout(()=>{loadOrders(currentQuery,1);},300);
    });

    $(document).on('click','.page-btn',function(e){
        e.preventDefault();
        let page=$(this).data('page');
        if(page){currentPage=page;loadOrders(currentQuery,page);}
    });
});
</script>

</body>
</html>