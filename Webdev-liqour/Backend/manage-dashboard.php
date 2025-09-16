<?php


session_start();
include("sql-config.php");

if (!isset($_SESSION['login'], $_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) ||
    $_SESSION['login'] !== 'success' || !$_SESSION['is_admin']) {
    header('Location: adminlogin.php');
    exit();
}


$records_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;

$stats = [];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
$stmt->execute();
$stats['users'] = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE is_active = 1");
$stmt->execute();
$stats['orders'] = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(total),0) as revenue FROM orders WHERE status='completed' AND is_active=1");
$stmt->execute();
$stats['revenue'] = $stmt->get_result()->fetch_assoc()['revenue'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE is_active = 1");
$stmt->execute();
$stats['reviews'] = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM liqours WHERE is_active=1");
$stmt->execute();
$stats['stock'] = $stmt->get_result()->fetch_assoc()['count'];

// Get deleted items counts for trash
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE is_active = 0");
$stmt->execute();
$stats['deleted_users'] = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM liqours WHERE is_active = 0");
$stmt->execute();
$stats['deleted_liqours'] = $stmt->get_result()->fetch_assoc()['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<style>
    * {
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        margin: 0;
        padding: 0;
        background: #f8f9fa;
        color: #333;
        line-height: 1.6;
    }
    
    .header {
        background: #1a1a1a;
        color: #fff;
        padding: 1rem 1.5rem;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .header h1 {
        margin: 0 0 1rem 0;
        font-size: 1.5rem;
        font-weight: 600;
    }
    
    .nav {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 0.5rem;
    }
    
    .nav::-webkit-scrollbar {
        height: 4px;
    }
    
    .nav::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.1);
    }
    
    .nav::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.3);
        border-radius: 2px;
    }
    
    .nav-link {
        color: white;
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        white-space: nowrap;
        transition: background-color 0.2s;
        font-size: 0.9rem;
    }
    
    .nav-link:hover {
        background: rgba(255,255,255,0.15);
    }
    
    .main {
        padding: 1.5rem;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .section {
        margin-bottom: 2rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .section-header {
        padding: 1.25rem 1.5rem;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .section-header h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #495057;
    }
    
    .section-actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .section-content {
        padding: 1.5rem;
    }
    
    .btn {
        background: #212529;
        color: #fff;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    
    .btn:hover {
        background: #343a40;
        transform: translateY(-1px);
    }
    
    .btn.search {
        background: #17a2b8;
    }
    
    .btn.search:hover {
        background: #138496;
    }
    
    .btn.soft-delete {
        background: #ffc107;
        color: #000;
    }
    
    .btn.soft-delete:hover {
        background: #e0a800;
    }
    
    .btn.delete {
        background: #dc3545;
    }
    
    .btn.delete:hover {
        background: #c82333;
    }
    
    .btn.restore {
        background: #28a745;
    }
    
    .btn.restore:hover {
        background: #218838;
    }
    
    .btn.move {
        background: #17a2b8;
    }
    
    .btn.move:hover {
        background: #138496;
    }
    
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
        min-width: 600px;
    }
    
    .table th,
    .table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
        vertical-align: middle;
    }
    
    .table th {
        background: #f8f9fa;
        color: #495057;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .table tr:hover {
        background: #f8f9fa;
    }
    
    .table td {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.25rem;
        flex-wrap: wrap;
        min-width: 200px;
    }
    
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid #dee2e6;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #212529;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 4px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        border-radius: 3px;
    }
    
    .badge-active {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .pagination-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #dee2e6;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .pagination {
        display: flex;
        gap: 0.25rem;
        align-items: center;
    }
    
    .pagination .btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.8rem;
        min-width: 40px;
        text-align: center;
    }
    
    .pagination .btn.active {
        background: #007bff;
    }
    
    .pagination .btn.active:hover {
        background: #0056b3;
    }
    
    .pagination .btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .pagination-info {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    /* M Respoive Styls */
    @media (max-width: 768px) {
        .header {
            padding: 1rem;
        }
        
        .header h1 {
            font-size: 1.25rem;
        }
        
        .main {
            padding: 1rem;
        }
        
        .section-header {
            padding: 1rem;
            flex-direction: column;
            align-items: stretch;
        }
        
        .section-actions {
            justify-content: stretch;
        }
        
        .section-actions .btn {
            flex: 1;
            text-align: center;
        }
        
        .section-content {
            padding: 1rem;
        }
        
        .stats {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .stat-card {
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 1.5rem;
        }
        
        .table th,
        .table td {
            padding: 0.5rem;
        }
        
        .action-buttons {
            flex-direction: column;
            min-width: 120px;
        }
        
        .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .nav {
            gap: 0.25rem;
        }
        
        .nav-link {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        
        .pagination-container {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }
        
        .pagination {
            justify-content: center;
            flex-wrap: wrap;
        }
    }
    
    @media (max-width: 480px) {
        .stats {
            grid-template-columns: 1fr 1fr;
        }
        
        .table {
            min-width: 500px;
        }
        
        .action-buttons .btn {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .section-actions {
            flex-direction: column;
        }
    }
    
   
    @media print {
        .header,
        .action-buttons,
        .btn,
        .pagination-container {
            display: none !important;
        }
        
        .section {
            break-inside: avoid;
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }
</style>
</head>
<body>

<header class="header">
    <h1>Admin Dashboard</h1>
    <nav class="nav">
        <a href="#stats" class="nav-link">Stats</a>
        <a href="#categories" class="nav-link">Categories</a>
        <a href="#liqours" class="nav-link">Liqours</a>
        <a href="#orders" class="nav-link">Orders</a>
        <a href="#reviews" class="nav-link">Reviews</a>
        <a href="#warehouse" class="nav-link">Warehouse</a>
        <a href="#stock" class="nav-link">Stock</a>
        <a href="#users" class="nav-link">Users</a>
        <a href="trash.php" class="nav-link">Trash</a>
    </nav>
          <a href="../public/index.php" target="_blank" class="btn" style="background: #007bff;">🌐 Visit Site</a>

</header>

<main class="main">

<?php if(isset($success_message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<?php if(isset($error_message)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<section id="stats" class="section">
  <div class="section-header"><h2>Statistics Overview</h2></div>
  <div class="section-content">
      <div class="stats">
          <div class="stat-card">
              <span class="stat-number"><?= number_format($stats['users']); ?></span>
              <div class="stat-label">Active Users</div>
          </div>
          <div class="stat-card">
              <span class="stat-number"><?= number_format($stats['orders']); ?></span>
              <div class="stat-label">Total Orders</div>
          </div>
          <div class="stat-card">
              <span class="stat-number">$<?= number_format($stats['revenue'],2); ?></span>
              <div class="stat-label">Total Revenue</div>
          </div>
          <div class="stat-card">
              <span class="stat-number"><?= number_format($stats['reviews']); ?></span>
              <div class="stat-label">Total Reviews</div>
          </div>
          <div class="stat-card">
              <span class="stat-number"><?= number_format($stats['stock']); ?></span>
              <div class="stat-label">Stock Items</div>
          </div>
          <div class="stat-card">
              <span class="stat-number"><?= number_format($stats['deleted_users'] + $stats['deleted_liqours']); ?></span>
              <div class="stat-label">Deleted Items</div>
          </div>
      </div>
  </div>
</section>

<section id="categories" class="section">
  <div class="section-header">
    <h2>Liquor Categories</h2>
    <div class="section-actions">
      <a href="category/search.php" class="btn search">🔍 Search</a>
      <a href="category/add.php" class="btn">Add Category</a>
    </div>
  </div>
  <div class="section-content">
      <div class="table-container">
          <table class="table">
              <thead><tr><th>ID</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
              <?php
              // Get total count for pagination
              $count_result = $conn->query("SELECT COUNT(*) as total FROM liqour_categories WHERE is_active=1");
              $total_records = $count_result->fetch_assoc()['total'];
              $total_pages = ceil($total_records / $records_per_page);
              
              $res = $conn->query("SELECT * FROM liqour_categories WHERE is_active=1 ORDER BY liqour_category_id DESC LIMIT $records_per_page OFFSET $offset");
              if($res && $res->num_rows>0){
                  while($row=$res->fetch_assoc()){
                      $id = htmlspecialchars($row['liqour_category_id']);
                      $name = htmlspecialchars($row['name']);
                      $status = $row['is_active'] ? 'Active' : 'Inactive';
                      echo "<tr>
                              <td>{$id}</td>
                              <td>{$name}</td>
                              <td><span class='badge badge-active'>{$status}</span></td>
                              <td>
                                  <div class='action-buttons'>
                                      <a href='category/view.php?id={$id}' class='btn'>View</a>
                                      <a href='category/update.php?id={$id}' class='btn'>Update</a>
                                      <a href='category/delete.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this category? It can be restored later.');\" class='btn soft-delete'>Soft Delete</a>
                                      <a href='category/delete.php?id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this category? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                                  </div>
                              </td>
                            </tr>";
                  }
              } else { echo "<tr><td colspan='4'>No categories found</td></tr>"; }
              ?>
              </tbody>
          </table>
      </div>
      
      <!-- ths for pagination because if too much stuff on screen at a time cant see  -->
      <?php if($total_pages > 1): ?>
      <div class="pagination-container">
          <div class="pagination-info">
              Showing <?= min($offset + 1, $total_records) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries
          </div>
          <div class="pagination">
              <?php if($page > 1): ?>
                  <a href="?page=<?= $page - 1 ?>#categories" class="btn">← Prev</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">← Prev</span>
              <?php endif; ?>
              
              <?php
              $start = max(1, $page - 2);
              $end = min($total_pages, $page + 2);
              
              if($start > 1) {
                  echo '<a href="?page=1#categories" class="btn">1</a>';
                  if($start > 2) echo '<span class="btn" style="cursor: default;">...</span>';
              }
              
              for($i = $start; $i <= $end; $i++): ?>
                  <a href="?page=<?= $i ?>#categories" class="btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
              <?php endfor;
              
              if($end < $total_pages) {
                  if($end < $total_pages - 1) echo '<span class="btn" style="cursor: default;">...</span>';
                  echo '<a href="?page=' . $total_pages . '#categories" class="btn">' . $total_pages . '</a>';
              }
              ?>
              
              <?php if($page < $total_pages): ?>
                  <a href="?page=<?= $page + 1 ?>#categories" class="btn">Next →</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">Next →</span>
              <?php endif; ?>
          </div>
      </div>
      <?php endif; ?>
  </div>
</section>

<section id="liqours" class="section">
  <div class="section-header">
    <h2>Liqours</h2>
    <div class="section-actions">
      <a href="liqour/search.php" class="btn search">🔍 Search</a>
      <a href="liqour/add.php" class="btn">Add Liqour</a>
    </div>
  </div>
  <div class="section-content">
      <div class="table-container">
          <table class="table">
              <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Category</th><th>Image</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
              <?php
              // Get total count for pagination
              $count_result = $conn->query("SELECT COUNT(*) as total FROM liqours WHERE is_active=1");
              $total_records = $count_result->fetch_assoc()['total'];
              $total_pages = ceil($total_records / $records_per_page);
              
              $sql = "SELECT l.*, c.name AS category_name FROM liqours l 
                      JOIN liqour_categories c ON l.category_id = c.liqour_category_id
                      WHERE l.is_active=1 ORDER BY l.liqour_id DESC LIMIT $records_per_page OFFSET $offset";
              $res = $conn->query($sql);
              if($res && $res->num_rows>0){
                  while($row=$res->fetch_assoc()){
                      $id=htmlspecialchars($row['liqour_id']);
                      $name=htmlspecialchars($row['name']);
                      $desc=htmlspecialchars(substr($row['description'], 0, 50) . (strlen($row['description']) > 50 ? '...' : ''));
                      $price=htmlspecialchars($row['price']);
                      $category=htmlspecialchars($row['category_name']);
                      $image=htmlspecialchars($row['image_url']);
                      $status = $row['is_active'] ? 'Active' : 'Inactive';
                      echo "<tr>
                              <td>{$id}</td>
                              <td>{$name}</td>
                              <td title='" . htmlspecialchars($row['description']) . "'>{$desc}</td>
                              <td>\${$price}</td>
                              <td>{$category}</td>
                              <td>" . (strlen($image) > 30 ? substr($image, 0, 30) . '...' : $image) . "</td>
                              <td><span class='badge badge-active'>{$status}</span></td>
                              <td>
                                <div class='action-buttons'>
                                  <a href='liqour/view.php?id={$id}' class='btn'>View</a>
                                  <a href='liqour/update.php?id={$id}' class='btn'>Update</a>
                                  <a href='liqour/delete.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this liquor? It can be restored later.');\" class='btn soft-delete'>Soft Delete</a>
                                  <a href='liqour/delete.php?id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this liquor? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                                </div>
                              </td>
                            </tr>";
                  }
              } else { echo "<tr><td colspan='8'>No liquors found</td></tr>"; }
              ?>
              </tbody>
          </table>
      </div>
      
      <!-- Pagination -->
      <?php if($total_pages > 1): ?>
      <div class="pagination-container">
          <div class="pagination-info">
              Showing <?= min($offset + 1, $total_records) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries
          </div>
          <div class="pagination">
              <?php if($page > 1): ?>
                  <a href="?page=<?= $page - 1 ?>#liqours" class="btn">← Prev</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">← Prev</span>
              <?php endif; ?>
              
              <?php
              $start = max(1, $page - 2);
              $end = min($total_pages, $page + 2);
              
              if($start > 1) {
                  echo '<a href="?page=1#liqours" class="btn">1</a>';
                  if($start > 2) echo '<span class="btn" style="cursor: default;">...</span>';
              }
              
              for($i = $start; $i <= $end; $i++): ?>
                  <a href="?page=<?= $i ?>#liqours" class="btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
              <?php endfor;
              
              if($end < $total_pages) {
                  if($end < $total_pages - 1) echo '<span class="btn" style="cursor: default;">...</span>';
                  echo '<a href="?page=' . $total_pages . '#liqours" class="btn">' . $total_pages . '</a>';
              }
              ?>
              
              <?php if($page < $total_pages): ?>
                  <a href="?page=<?= $page + 1 ?>#liqours" class="btn">Next →</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">Next →</span>
              <?php endif; ?>
          </div>
      </div>
      <?php endif; ?>
  </div>
</section>

<section id="orders" class="section">
  <div class="section-header">
    <h2>Orders</h2>
    <div class="section-actions">
      <a href="order/search.php" class="btn search">🔍 Search</a>
    </div>
  </div>
  <div class="section-content">
      <div class="table-container">
          <table class="table">
              <thead><tr><th>ID</th><th>User</th><th>Status</th><th>Total</th><th>Created</th><th>Updated</th><th>Actions</th></tr></thead>
              <tbody>
              <?php
              // Get total count for pagination
              $count_result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE is_active=1");
              $total_records = $count_result->fetch_assoc()['total'];
              $total_pages = ceil($total_records / $records_per_page);
              
              $sql = "SELECT o.*, u.name AS username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.is_active=1 ORDER BY o.order_id DESC LIMIT $records_per_page OFFSET $offset";
              $res = $conn->query($sql);
              if($res && $res->num_rows>0){
                  while($row=$res->fetch_assoc()){
                      $orderId = $row['order_id'];
                      $user = htmlspecialchars($row['username']);
                      $status = htmlspecialchars($row['status']);
                      $total = htmlspecialchars($row['total']);
                      $created = date('M d, Y', strtotime($row['created_at']));
                      $updated = date('M d, Y', strtotime($row['updated_at']));
                      echo "<tr>
                              <td>{$orderId}</td>
                              <td>{$user}</td>
                              <td><span class='badge badge-active'>{$status}</span></td>
                              <td>\${$total}</td>
                              <td>{$created}</td>
                              <td>{$updated}</td>
                              <td>
                                <div class='action-buttons'>
                                  <a href='order/view.php?order_id={$orderId}' class='btn'>View</a>
                                  <a href='order/update.php?order_id={$orderId}' class='btn'>Update</a>
                                  <a href='order/delete.php?order_id={$orderId}&type=soft' onclick=\"return confirm('Soft delete this order? It can be restored later.');\" class='btn soft-delete'>Soft Delete</a>
                                  <a href='order/delete.php?order_id={$orderId}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this order? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                                </div>
                              </td>
                            </tr>";
                  }
              } else { echo "<tr><td colspan='7'>No orders found</td></tr>"; }
              ?>
              </tbody>
          </table>
      </div>
      
      <!-- Pagination -->
      <?php if($total_pages > 1): ?>
      <div class="pagination-container">
          <div class="pagination-info">
              Showing <?= min($offset + 1, $total_records) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries
          </div>
          <div class="pagination">
              <?php if($page > 1): ?>
                  <a href="?page=<?= $page - 1 ?>#orders" class="btn">← Prev</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">← Prev</span>
              <?php endif; ?>
              
              <?php
              $start = max(1, $page - 2);
              $end = min($total_pages, $page + 2);
              
              if($start > 1) {
                  echo '<a href="?page=1#orders" class="btn">1</a>';
                  if($start > 2) echo '<span class="btn" style="cursor: default;">...</span>';
              }
              
              for($i = $start; $i <= $end; $i++): ?>
                  <a href="?page=<?= $i ?>#orders" class="btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
              <?php endfor;
              
              if($end < $total_pages) {
                  if($end < $total_pages - 1) echo '<span class="btn" style="cursor: default;">...</span>';
                  echo '<a href="?page=' . $total_pages . '#orders" class="btn">' . $total_pages . '</a>';
              }
              ?>
              
              <?php if($page < $total_pages): ?>
                  <a href="?page=<?= $page + 1 ?>#orders" class="btn">Next →</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">Next →</span>
              <?php endif; ?>
          </div>
      </div>
      <?php endif; ?>
  </div>
</section>

<section id="reviews" class="section">
  <div class="section-header">
    <h2>Reviews</h2>
    <div class="section-actions">
      <a href="review/search.php" class="btn search">🔍 Search</a>
    </div>
  </div>
  <div class="section-content">
      <div class="table-container">
          <table class="table">
              <thead><tr><th>ID</th><th>Liqour</th><th>User</th><th>Rating</th><th>Comment</th><th>Created</th><th>Actions</th></tr></thead>
              <tbody>
              <?php
              // Get total count for paging
              $count_result = $conn->query("SELECT COUNT(*) as total FROM reviews WHERE is_active=1");
              $total_records = $count_result->fetch_assoc()['total'];
              $total_pages = ceil($total_records / $records_per_page);
              
              $sql = "SELECT r.*, l.name AS liqour_name, u.name AS username 
                      FROM reviews r 
                      JOIN liqours l ON r.liqour_id=l.liqour_id 
                      JOIN users u ON r.user_id=u.id
                      WHERE r.is_active=1 ORDER BY r.review_id DESC LIMIT $records_per_page OFFSET $offset";
              $res = $conn->query($sql);
              if($res && $res->num_rows>0){
                  while($row=$res->fetch_assoc()){
                      $reviewId = $row['review_id'];
                      $liqourName = htmlspecialchars($row['liqour_name']);
                      $user = htmlspecialchars($row['username']);
                      $rating = htmlspecialchars($row['rating']);
                      $comment = htmlspecialchars(substr($row['comment'], 0, 50) . (strlen($row['comment']) > 50 ? '...' : ''));
                      $created = date('M d, Y', strtotime($row['created_at']));
                      echo "<tr>
                              <td>{$reviewId}</td>
                              <td>{$liqourName}</td>
                              <td>{$user}</td>
                              <td>{$rating}★</td>
                              <td title='" . htmlspecialchars($row['comment']) . "'>{$comment}</td>
                              <td>{$created}</td>
                              <td>
                                <div class='action-buttons'>
                                  
                                  <a href='review/delete.php?review_id={$reviewId}&type=soft' onclick=\"return confirm('Soft delete this review? It can be restored later.');\" class='btn soft-delete'>Soft Delete</a>
                                  <a href='review/delete.php?review_id={$reviewId}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this review? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                                </div>
                              </td>
                            </tr>";
                  }
              } else { echo "<tr><td colspan='7'>No reviews found</td></tr>"; }
              ?>
              </tbody>
          </table>
      </div>
      
      <!-- Pagination -->
      <?php if($total_pages > 1): ?>
      <div class="pagination-container">
          <div class="pagination-info">
              Showing <?= min($offset + 1, $total_records) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries
          </div>
          <div class="pagination">
              <?php if($page > 1): ?>
                  <a href="?page=<?= $page - 1 ?>#reviews" class="btn">← Prev</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">← Prev</span>
              <?php endif; ?>
              
              <?php
              $start = max(1, $page - 2);
              $end = min($total_pages, $page + 2);
              
              if($start > 1) {
                  echo '<a href="?page=1#reviews" class="btn">1</a>';
                  if($start > 2) echo '<span class="btn" style="cursor: default;">...</span>';
              }
              
              for($i = $start; $i <= $end; $i++): ?>
                  <a href="?page=<?= $i ?>#reviews" class="btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
              <?php endfor;
              
              if($end < $total_pages) {
                  if($end < $total_pages - 1) echo '<span class="btn" style="cursor: default;">...</span>';
                  echo '<a href="?page=' . $total_pages . '#reviews" class="btn">' . $total_pages . '</a>';
              }
              ?>
              
              <?php if($page < $total_pages): ?>
                  <a href="?page=<?= $page + 1 ?>#reviews" class="btn">Next →</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">Next →</span>
              <?php endif; ?>
          </div>
      </div>
      <?php endif; ?>
  </div>
</section>

<section id="warehouse" class="section">
  <div class="section-header">
    <h2>Warehouse</h2>
    <div class="section-actions">
      <a href="warehouse/search.php" class="btn search">🔍 Search</a>
      <a href="warehouse/add.php" class="btn">Add Warehouse</a>
    </div>
  </div>
  <div class="section-content">
      <div class="table-container">
          <table class="table">
              <thead><tr><th>ID</th><th>Name</th><th>Address</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
              <?php
              // Get total count for pagination
              $count_result = $conn->query("SELECT COUNT(*) as total FROM warehouse WHERE is_active=1");
              $total_records = $count_result->fetch_assoc()['total'];
              $total_pages = ceil($total_records / $records_per_page);
              
              $res = $conn->query("SELECT * FROM warehouse WHERE is_active=1 ORDER BY warehouse_id DESC LIMIT $records_per_page OFFSET $offset");
              if($res && $res->num_rows>0){
                  while($row=$res->fetch_assoc()){
                      $id = $row['warehouse_id'];
                      $name = htmlspecialchars($row['name']);
                      $address = htmlspecialchars(substr($row['address'], 0, 50) . (strlen($row['address']) > 50 ? '...' : ''));
                      $status = $row['is_active'] ? 'Active' : 'Inactive';
                      echo "<tr>
                              <td>{$id}</td>
                              <td>{$name}</td>
                              <td title='" . htmlspecialchars($row['address']) . "'>{$address}</td>
                              <td><span class='badge badge-active'>{$status}</span></td>
                              <td>
                                 <div class='action-buttons'>
                                   <a href='warehouse/view.php?id={$id}' class='btn'>View</a>
                                   <a href='warehouse/update.php?id={$id}' class='btn'>Update</a>
                                   <a href='warehouse/delete.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this warehouse? It can be restored later.');\" class='btn soft-delete'>Soft Delete</a>
                                   <a href='warehouse/delete.php?id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this warehouse? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                                 </div>
                              </td>
                            </tr>";
                  }
              } else { echo "<tr><td colspan='5'>No warehouses found</td></tr>"; }
              ?>
              </tbody>
          </table>
      </div>
      
      <!-- Pagination -->
      <?php if($total_pages > 1): ?>
      <div class="pagination-container">
          <div class="pagination-info">
              Showing <?= min($offset + 1, $total_records) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries
          </div>
          <div class="pagination">
              <?php if($page > 1): ?>
                  <a href="?page=<?= $page - 1 ?>#warehouse" class="btn">← Prev</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">← Prev</span>
              <?php endif; ?>
              
              <?php
              $start = max(1, $page - 2);
              $end = min($total_pages, $page + 2);
              
              if($start > 1) {
                  echo '<a href="?page=1#warehouse" class="btn">1</a>';
                  if($start > 2) echo '<span class="btn" style="cursor: default;">...</span>';
              }
              
              for($i = $start; $i <= $end; $i++): ?>
                  <a href="?page=<?= $i ?>#warehouse" class="btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
              <?php endfor;
              
              if($end < $total_pages) {
                  if($end < $total_pages - 1) echo '<span class="btn" style="cursor: default;">...</span>';
                  echo '<a href="?page=' . $total_pages . '#warehouse" class="btn">' . $total_pages . '</a>';
              }
              ?>
              
              <?php if($page < $total_pages): ?>
                  <a href="?page=<?= $page + 1 ?>#warehouse" class="btn">Next →</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">Next →</span>
              <?php endif; ?>
          </div>
      </div>
      <?php endif; ?>
  </div>
</section>

<section id="stock" class="section">
  <div class="section-header">
    <h2>Stock Levels</h2>
    <div class="section-actions">
      <a href="stock/search.php" class="btn search">🔍 Search</a>
      <a href="stock/add.php" class="btn">Add Stock Record</a>
    </div>
  </div>
  <div class="section-content">
      <div class="table-container">
          <table class="table">
              <thead>
                  <tr>
                      <th>Liqour</th>
                      <th>Warehouse</th>
                      <th>Quantity</th>
                      <th>Last Updated</th>
                      <th>Actions</th>
                  </tr>
              </thead>
              <tbody>
              <?php
              // Get total count for pagination
              $count_result = $conn->query("SELECT COUNT(*) as total FROM stock WHERE is_active=1");
              $total_records = $count_result->fetch_assoc()['total'];
              $total_pages = ceil($total_records / $records_per_page);
              
              $sql = "SELECT s.*, l.name AS liqour_name, w.name AS warehouse_name 
                      FROM stock s 
                      JOIN liqours l ON s.liqour_id=l.liqour_id 
                      JOIN warehouse w ON s.warehouse_id=w.warehouse_id
                      WHERE s.is_active=1
                      ORDER BY w.name,l.name LIMIT $records_per_page OFFSET $offset";
              $res = $conn->query($sql);
              if($res && $res->num_rows>0){
                  while($row=$res->fetch_assoc()){
                      $liqour = htmlspecialchars($row['liqour_name']);
                      $warehouse = htmlspecialchars($row['warehouse_name']);
                      $quantity = htmlspecialchars($row['quantity']);
                      $updated = date('M d, Y', strtotime($row['updated_at']));
                      $liqourId = $row['liqour_id'];
                      $warehouseId = $row['warehouse_id'];
                      echo "<tr>
                              <td>{$liqour}</td>
                              <td>{$warehouse}</td>
                              <td>{$quantity}</td>
                              <td>{$updated}</td>
                              <td>
                                <div class='action-buttons'>
                                  <a href='stock/update.php?liqour_id={$liqourId}&warehouse_id={$warehouseId}' class='btn'>Update</a>
                                  <a href='stock/move.php?liqour_id={$liqourId}&warehouse_id={$warehouseId}' class='btn move'>Move</a>
                                  <a href='stock/delete.php?liqour_id={$liqourId}&warehouse_id={$warehouseId}&type=soft' onclick=\"return confirm('Soft delete this stock record? It can be restored later.');\" class='btn soft-delete'>Soft Delete</a>
                                  <a href='stock/delete.php?liqour_id={$liqourId}&warehouse_id={$warehouseId}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this stock record? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                                </div>
                              </td>
                            </tr>";
                  }
              } else { echo "<tr><td colspan='5'>No stock records found</td></tr>"; }
              ?>
              </tbody>
          </table>
      </div>
      
      <!-- Pagination -->
      <?php if($total_pages > 1): ?>
      <div class="pagination-container">
          <div class="pagination-info">
              Showing <?= min($offset + 1, $total_records) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries
          </div>
          <div class="pagination">
              <?php if($page > 1): ?>
                  <a href="?page=<?= $page - 1 ?>#stock" class="btn">← Prev</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">← Prev</span>
              <?php endif; ?>
              
              <?php
              $start = max(1, $page - 2);
              $end = min($total_pages, $page + 2);
              
              if($start > 1) {
                  echo '<a href="?page=1#stock" class="btn">1</a>';
                  if($start > 2) echo '<span class="btn" style="cursor: default;">...</span>';
              }
              
              for($i = $start; $i <= $end; $i++): ?>
                  <a href="?page=<?= $i ?>#stock" class="btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
              <?php endfor;
              
              if($end < $total_pages) {
                  if($end < $total_pages - 1) echo '<span class="btn" style="cursor: default;">...</span>';
                  echo '<a href="?page=' . $total_pages . '#stock" class="btn">' . $total_pages . '</a>';
              }
              ?>
              
              <?php if($page < $total_pages): ?>
                  <a href="?page=<?= $page + 1 ?>#stock" class="btn">Next →</a>
              <?php else: ?>
                  <span class="btn" style="opacity: 0.5; cursor: not-allowed;">Next →</span>
              <?php endif; ?>
          </div>
      </div>
      <?php endif; ?>
  </div>
</section>

<section id="users" class="section">
  <div class="section-header">
    <h2>Users</h2>
    <div class="section-actions">
      <a href="users/search.php" class="btn search">🔍 Search</a>
      <a href="users/add.php" class="btn">Add User</a>
    </div>
  </div>
  <div class="section-content">
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Admin</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Get total count for pagination
          $count_result = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_active=1");
          $total_records = $count_result->fetch_assoc()['total'];
          $total_pages = ceil($total_records / $records_per_page);
          
          $sqlUsers = "SELECT * FROM users WHERE is_active=1 ORDER BY id DESC LIMIT $records_per_page OFFSET $offset";
          $stmt = $conn->prepare($sqlUsers);
          $stmt->execute();
          $res = $stmt->get_result();

          if($res && $res->num_rows>0){
            while($row = $res->fetch_assoc()){
              $id = $row['id'];
              $name = htmlspecialchars($row['name']);
              $email = htmlspecialchars($row['email']);
              $phone = htmlspecialchars($row['phone']);
              $address = htmlspecialchars(substr($row['address'], 0, 30) . (strlen($row['address']) > 30 ? '...' : ''));
              $is_admin = $row['is_admin'] ? 'Yes' : 'No';
              $status = $row['is_active'] ? 'Active' : 'Inactive';

              echo "
              <tr>
                <td>{$id}</td>
                <td>{$name}</td>
                <td>{$email}</td>
                <td>{$phone}</td>
                <td title='" . htmlspecialchars($row['address']) . "'>{$address}</td>
                <td><span class='badge " . ($row['is_admin'] ? 'badge-active' : 'badge-inactive') . "'>{$is_admin}</span></td>
                <td><span class='badge badge-active'>{$status}</span></td>
                <td>
                  <div class='action-buttons'>
                    <a href='users/view.php?id={$id}' class='btn'>View</a>
                    <a href='users/update.php?id={$id}' class='btn'>Update</a>
                    <a href='users/delete.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this user? They can be restored later.');\" class='btn soft-delete'>Soft Delete</a>
                    <a href='users/delete.php?id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this user? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                  </div>
                </td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='8'>No users found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="pagination-container">
        <div class="pagination-info">
            Showing <?= min($offset + 1, $total_records) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries
        </div>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>#users" class="btn">← Prev</a>
            <?php else: ?>
                <span class="btn" style="opacity: 0.5; cursor: not-allowed;">← Prev</span>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            
            if($start > 1) {
                echo '<a href="?page=1#users" class="btn">1</a>';
                if($start > 2) echo '<span class="btn" style="cursor: default;">...</span>';
            }
            
            for($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?= $i ?>#users" class="btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor;
            
            if($end < $total_pages) {
                if($end < $total_pages - 1) echo '<span class="btn" style="cursor: default;">...</span>';
                echo '<a href="?page=' . $total_pages . '#users" class="btn">' . $total_pages . '</a>';
            }
            ?>
            
            <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>#users" class="btn">Next →</a>
            <?php else: ?>
                <span class="btn" style="opacity: 0.5; cursor: not-allowed;">Next →</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
  </div>
</section>


<section class="section">
  <div class="section-header">
    <h2>Quick Actions</h2>
  </div>
  <div class="section-content">
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
      <a href="../public/index.php" target="_blank" class="btn" style="background: #007bff;">🌐 Visit Site</a>
      
    </div>
  </div>
</section>

</main>

<script>
// Add smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add loading states for buttons
document.querySelectorAll('.btn').forEach(button => {
    if (button.onclick || button.href.includes('delete')) {
        button.addEventListener('click', function() {
            if (!this.href.includes('#')) {
                this.style.opacity = '0.6';
                this.innerHTML = '⏳ Processing...';
            }
        });
    }
});

// Add table sorting functionality (optional)
document.querySelectorAll('.table th').forEach(header => {
    header.style.cursor = 'pointer';
    header.addEventListener('click', function() {
        // Add your sorting logic here if needed
        console.log('Sorting by:', this.textContent);
    });
});

// Auto-hide alerts after 5 seconds
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => {
            alert.style.display = 'none';
        }, 300);
    }, 5000);
});

// Handle pagination URL fragments
window.addEventListener('load', function() {
    if (window.location.hash) {
        setTimeout(() => {
            document.querySelector(window.location.hash).scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }, 100);
    }
});
</script>

</body>
</html>