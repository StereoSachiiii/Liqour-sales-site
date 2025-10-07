<?php 

if(empty($_SERVER['HTTPS']) || $_SERVER["HTTPS"] == 'off'){
    $secureUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $secureUrl");
    exit();
}

session_start();
include("sql-config.php");

if (!isset($_SESSION['login'], $_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) ||
    $_SESSION['login'] !== 'success' || !$_SESSION['is_admin']) {
    header('Location: adminlogin.php');
    exit();
}?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
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

.section-content {
    padding: 1.5rem;
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

.btn.restore {
    background: #28a745;
}

.btn.restore:hover {
    background: #218838;
}

.btn.delete {
    background: #dc3545;
}

.btn.delete:hover {
    background: #c82333;
}

.btn.soft-delete {
    background: #ffc107;
    color: #000;
}

.btn.soft-delete:hover {
    background: #e0a800;
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

/* Mobile responsive for Trash */
@media (max-width: 768px) {
    .section-header {
        padding: 1rem;
        flex-direction: column;
        align-items: stretch;
    }

    .section-content {
        padding: 1rem;
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
}

@media (max-width: 480px) {
    .table {
        min-width: 500px;
    }

    .action-buttons .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}

    </style>
</head>
<body >
<a class="btn restore" style="position: fixed;" href="manage-dashboard.php">
<div>
      Back to management
</div>
</a>



<section id="trash" class="section">
  <div class="section-header">
    <h2>Trash (Soft Deleted Items)</h2>
  </div>
  <div class="section-content">
    
    <!-- Deleted Users -->
<h3>Deleted Users</h3>
<div class="table-container">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Deleted At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Fetch soft-deleted users
      $sqlDeletedUsers = "SELECT id, name, email, updated_at FROM users WHERE is_active = 0 ORDER BY updated_at DESC";
      $res = $conn->query($sqlDeletedUsers);

      if ($res && $res->num_rows > 0) {
          while ($row = $res->fetch_assoc()) {
              $id = (int)$row['id'];
              $name = htmlspecialchars($row['name']);
              $email = htmlspecialchars($row['email']);
              $deleted = !empty($row['updated_at']) ? date('M d, Y', strtotime($row['updated_at'])) : 'Unknown';

              echo "<tr>
                      <td>{$id}</td>
                      <td>{$name}</td>
                      <td>{$email}</td>
                      <td>{$deleted}</td>
                      <td>
                        <div class='action-buttons'>
                          <a href='users/restore.php?id={$id}' onclick=\"return confirm('Restore this user?');\" class='btn restore'>Restore</a>
                          <a href='users/delete.php?id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this user? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                        </div>
                      </td>
                    </tr>";
          }
      } else {
          echo "<tr><td colspan='5'>No deleted users found.</td></tr>";
      }
      ?>
    </tbody>
  </table>
</div>



    <!-- Deleted Liquors -->
    <h3>Deleted Liquors</h3>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Price</th>
            <th>Deleted At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sqlDeletedLiquors = "SELECT * FROM liqours WHERE is_active=0 ORDER BY updated_at DESC";
          $res = $conn->query($sqlDeletedLiquors);
          if($res && $res->num_rows>0){
            while($row = $res->fetch_assoc()){
              $id = $row['liqour_id'];
              $name = htmlspecialchars($row['name']);
              $price = htmlspecialchars($row['price']);
              $deleted = date('M d, Y', strtotime($row['updated_at']));
              
              echo "
              <tr>
                <td>{$id}</td>
                <td>{$name}</td>
                <td>\${$price}</td>
                <td>{$deleted}</td>
                <td>
                  <div class='action-buttons'>
                    <a href='liqour/restore.php?id={$id}' onclick=\"return confirm('Restore this liquor?');\" class='btn restore'>Restore</a>
                    <a href='liqour/delete.php?id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this liquor? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                  </div>
                </td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='5'>No deleted liquors found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>

    <h3>Deleted Categories</h3>
<div class="table-container">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Deleted At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $sqlDeletedCategories = "SELECT * FROM liqour_categories WHERE is_active=0 ORDER BY updated_at DESC";
      $res = $conn->query($sqlDeletedCategories);
      if($res && $res->num_rows>0){
        while($row = $res->fetch_assoc()){
          $id = $row['liqour_category_id'];
          $name = htmlspecialchars($row['name']);
          $deleted = date('M d, Y', strtotime($row['updated_at']));
          
          echo "<tr>
                  <td>{$id}</td>
                  <td>{$name}</td>
                  <td>{$deleted}</td>
                  <td>
                    <div class='action-buttons'>
                      <a href='category/restore.php?category_id={$id}' 
                         onclick=\"return confirm('Restore this category?');\" 
                         class='btn restore'>Restore</a>
                      <a href='category/delete.php?id={$id}&type=hard' 
                         onclick=\"return confirm('⚠️ PERMANENTLY DELETE this category? This action cannot be undone!');\" 
                         class='btn delete'>Delete Forever</a>
                    </div>
                  </td>
                </tr>";
        }
      } else {
        echo "<tr><td colspan='4'>No deleted categories found.</td></tr>";
      }
      ?>
    </tbody>
  </table>
</div>


    <!-- Deleted Orders -->
    <h3>Deleted Orders</h3>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Total</th>
            <th>Deleted At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sqlDeletedOrders = "SELECT o.*, u.name AS username FROM orders o 
                               LEFT JOIN users u ON o.user_id = u.id 
                               WHERE o.is_active=0 ORDER BY o.updated_at DESC";
          $res = $conn->query($sqlDeletedOrders);
          if($res && $res->num_rows>0){
            while($row = $res->fetch_assoc()){
              $id = $row['order_id'];
              $user = htmlspecialchars($row['username'] ?? 'Unknown User');
              $total = htmlspecialchars($row['total']);
              $deleted = date('M d, Y', strtotime($row['updated_at']));
              
              echo "
              <tr>
                <td>{$id}</td>
                <td>{$user}</td>
                <td>\${$total}</td>
                <td>{$deleted}</td>
                <td>
                  <div class='action-buttons'>
                    <a href='order/restore.php?order_id={$id}' onclick=\"return confirm('Restore this order?');\" class='btn restore'>Restore</a>
                    <a href='order/delete.php?order_id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this order? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                  </div>
                </td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='5'>No deleted orders found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>

    <!-- Deleted Reviews -->
    <h3>Deleted Reviews</h3>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Liquor</th>
            <th>User</th>
            <th>Rating</th>
            <th>Deleted At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sqlDeletedReviews = "SELECT r.*, l.name AS liqour_name, u.name AS username 
                                FROM reviews r 
                                LEFT JOIN liqours l ON r.liqour_id=l.liqour_id 
                                LEFT JOIN users u ON r.user_id=u.id
                                WHERE r.is_active=0 ORDER BY r.updated_at DESC";
          $res = $conn->query($sqlDeletedReviews);
          if($res && $res->num_rows>0){
            while($row = $res->fetch_assoc()){
              $id = $row['review_id'];
              $liqour = htmlspecialchars($row['liqour_name'] ?? 'Unknown Liquor');
              $user = htmlspecialchars($row['username'] ?? 'Unknown User');
              $rating = htmlspecialchars($row['rating']);
              $deleted = date('M d, Y', strtotime($row['updated_at']));
              
              echo "
              <tr>
                <td>{$id}</td>
                <td>{$liqour}</td>
                <td>{$user}</td>
                <td>{$rating}★</td>
                <td>{$deleted}</td>
                <td>
                  <div class='action-buttons'>
                    <a href='review/restore.php?review_id={$id}' onclick=\"return confirm('Restore this review?');\" class='btn restore'>Restore</a>
                    <a href='review/delete.php?review_id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this review? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                  </div>
                </td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='6'>No deleted reviews found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>

    <!-- Deleted Warehouses -->
    <h3>Deleted Warehouses</h3>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Address</th>
            <th>Deleted At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sqlDeletedWarehouses = "SELECT * FROM warehouse WHERE is_active=0 ORDER BY updated_at DESC";
          $res = $conn->query($sqlDeletedWarehouses);
          if($res && $res->num_rows>0){
            while($row = $res->fetch_assoc()){
              $id = $row['warehouse_id'];
              $name = htmlspecialchars($row['name']);
              $address = htmlspecialchars(substr($row['address'], 0, 30) . (strlen($row['address']) > 30 ? '...' : ''));
              $deleted = date('M d, Y', strtotime($row['updated_at']));
              
              echo "
              <tr>
                <td>{$id}</td>
                <td>{$name}</td>
                <td title='" . htmlspecialchars($row['address']) . "'>{$address}</td>
                <td>{$deleted}</td>
                <td>
                  <div class='action-buttons'>
                    <a href='warehouse/restore.php?id={$id}' onclick=\"return confirm('Restore this warehouse?');\" class='btn restore'>Restore</a>
                    <a href='warehouse/delete.php?id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this warehouse? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
                  </div>
                </td>
              </tr>";
            }
          } else {
            echo "<tr><td colspan='5'>No deleted warehouses found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>


 <!-- Deleted Stock Records -->
<h3>Deleted Stock Records</h3>
<div class="table-container">
  <table class="table">
    <thead>
      <tr>
        <th>Liquor</th>
        <th>Warehouse</th>
        <th>Quantity</th>
        <th>Deleted At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $sqlDeletedStock = "SELECT s.*, l.name AS liqour_name, w.name AS warehouse_name
                          FROM stock s
                          JOIN liqours l ON s.liqour_id = l.liqour_id
                          JOIN warehouse w ON s.warehouse_id = w.warehouse_id
                          WHERE s.is_active=0
                          ORDER BY s.updated_at DESC";
      $res = $conn->query($sqlDeletedStock);
      if ($res && $res->num_rows > 0) {
          while($row = $res->fetch_assoc()){
              $liqour_id = $row['liqour_id'];
              $warehouse_id = $row['warehouse_id'];
              $liqour = htmlspecialchars($row['liqour_name']);
              $warehouse = htmlspecialchars($row['warehouse_name']);
              $quantity = htmlspecialchars($row['quantity']);
              $deleted = date('M d, Y', strtotime($row['updated_at']));

              echo "
              <tr>
                  <td>{$liqour}</td>
                  <td>{$warehouse}</td>
                  <td>{$quantity}</td>
                  <td>{$deleted}</td>
                  <td>
                      <div class='action-buttons'>
                          <a href='stock/restore.php?liqour_id={$liqour_id}&warehouse_id={$warehouse_id}'
                             onclick=\"return confirm('Restore this stock record?');\" 
                             class='btn restore'>Restore</a>
                          <a href='stock/delete.php?liqour_id={$liqour_id}&warehouse_id={$warehouse_id}&type=hard'
                             onclick=\"return confirm('⚠️ PERMANENTLY DELETE this stock record? This action cannot be undone!');\" 
                             class='btn delete'>Delete Forever</a>
                      </div>
                  </td>
              </tr>";
          }
      } else {
          echo "<tr><td colspan='5'>No deleted stock records found.</td></tr>";
      }
      ?>




    </tbody>
  </table>



</div>

<!-- Deleted Suppliers -->
<h3>Deleted Suppliers</h3>
<div class="table-container">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Deleted At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $sqlDeletedSuppliers = "SELECT * FROM suppliers WHERE is_active=0 ORDER BY updated_at DESC";
      $res = $conn->query($sqlDeletedSuppliers);
      if($res && $res->num_rows>0){
        while($row = $res->fetch_assoc()){
          $id = $row['supplier_id'];
          $name = htmlspecialchars($row['name']);
          $email = htmlspecialchars($row['email'] ?? 'N/A');
          $phone = htmlspecialchars($row['phone'] ?? 'N/A');
          $deleted = date('M d, Y', strtotime($row['updated_at']));
          
          echo "
          <tr>
            <td>{$id}</td>
            <td>{$name}</td>
            <td>{$email}</td>
            <td>{$phone}</td>
            <td>{$deleted}</td>
            <td>
              <div class='action-buttons'>
                <a href='suppliers/restore.php?supplier_id={$id}' onclick=\"return confirm('Restore this supplier?');\" class='btn restore'>Restore</a>
                <a href='suppliers/delete.php?supplier_id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE this supplier? This action cannot be undone!');\" class='btn delete'>Delete Forever</a>
              </div>
            </td>
          </tr>";
        }
      } else {
        echo "<tr><td colspan='6'>No deleted suppliers found.</td></tr>";
      }
      ?>
    </tbody>
  </table>
</div>



  </div>
</section>

    
</body>
</html>