<?php 
// Force HTTPS
if(empty($_SERVER['HTTPS']) || $_SERVER["HTTPS"] == 'off'){
    $secureUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $secureUrl");
    exit();
}

session_start();
include("sql-config.php");

// Check authentication
if (
    !isset($_SESSION['login'], $_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) ||
    $_SESSION['login'] !== 'success'
) {
    header('Location: /process-login.php');
    exit();
}

// Get statistics (using your existing table structure)
$stats = [];

// Total Users
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
$stmt->execute();
$stats['users'] = $stmt->get_result()->fetch_assoc()['count'];

// Total Orders (assuming you have an orders table)
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders");
    $stmt->execute();
    $stats['orders'] = $stmt->get_result()->fetch_assoc()['count'];
} catch (Exception $e) {
    $stats['orders'] = 0;
}

// Total Revenue (assuming you have an orders table with total_amount field)
try {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status = 'completed'");
    $stmt->execute();
    $stats['revenue'] = $stmt->get_result()->fetch_assoc()['revenue'];
} catch (Exception $e) {
    $stats['revenue'] = 0;
}

// Total Reviews (assuming you have a reviews table)
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews");
    $stmt->execute();
    $stats['reviews'] = $stmt->get_result()->fetch_assoc()['count'];
} catch (Exception $e) {
    $stats['reviews'] = 0;
}

// Total Stock Items
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM liqours");
$stmt->execute();
$stats['stock'] = $stmt->get_result()->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <style>
    body {
      font-family: 'Inter', system-ui, sans-serif;
      line-height: 1.5;
      color: #333;
      background: white;
      margin: 0;
      padding: 0;
    }
    
    .header {
      background: black;
      color: white;
      padding: 20px;
      position: sticky;
      top: 0;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .main {
      padding: 20px;
      max-width: 1400px;
      margin: 0 auto;
    }
    
    .nav {
      display: flex;
      gap: 15px;
      padding: 5px 0;
      overflow-x: auto;
    }
    
    .nav-link {
      color: white;
      text-decoration: none;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 14px;
      white-space: nowrap;
    }
    
    .nav-link:hover {
      background: rgba(255,255,255,0.1);
    }
    
    .section {
      margin-bottom: 20px;
      border: 1px solid #eee;
      border-radius: 8px;
      overflow: hidden;
    }
    
    .section-header {
      padding: 15px;
      background: #f8f8f8;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .section-content {
      padding: 15px;
    }
    
    .btn {
      background: black;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
      text-decoration: none;
      display: inline-block;
    }
    
    .btn:hover {
      opacity: 0.9;
    }
    
    .table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }
    
    .table th, .table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    
    .table th {
      background: #f8f8f8;
      color: #666;
    }
    
    .table tr:hover {
      background: #f9f9f9;
    }
    
    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 15px;
    }
    
    .stat-card {
      background: #f8f8f8;
      padding: 15px;
      border-radius: 6px;
      text-align: center;
    }
    
    .stat-number {
      font-size: 24px;
      font-weight: bold;
      color: #333;
    }
    
    .delete {
      background: #dc3545;
    }
    
    .delete:hover {
      background: #c82333;
    }
    
    @media (max-width: 768px) {
      .stats {
        grid-template-columns: 1fr 1fr;
      }
      
      .table {
        display: block;
        overflow-x: auto;
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
    </nav>
  </header>

  <main class="main">
    <section id="stats" class="section">
      <div class="section-header">
        <h2>Statistics Overview</h2>
      </div>
      <div class="section-content stats">
        <div class="stat-card">
          <div class="stat-number"><?php echo number_format($stats['users']); ?></div>
          <div>Total Users</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo number_format($stats['orders']); ?></div>
          <div>Total Orders</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">$<?php echo number_format($stats['revenue'], 2); ?></div>
          <div>Total Revenue</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo number_format($stats['reviews']); ?></div>
          <div>Total Reviews</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo number_format($stats['stock']); ?></div>
          <div>Total Stock Items</div>
        </div>
      </div>
    </section>

    <section id="categories" class="section">
      <div class="section-header">
        <h2>Liqour Categories</h2>
        <a href="add-liqour.php"><button class="btn">Add Category</button></a>
      </div>
      <div class="section-content">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php 
              $sqlCategories = "SELECT * FROM liqour_categories";
              $stmt = $conn->prepare($sqlCategories);
              $stmt->execute();
              $res = $stmt->get_result();
              
              if($res->num_rows > 0){
                while($row = $res->fetch_assoc()){
                   $category_id = htmlspecialchars($row['liqour_category_id']);
                   $name = htmlspecialchars($row['name']);
                  
                   echo "
                   <tr>
                    <td>{$category_id}</td>
                    <td>{$name}</td>
                    <td>
                    <a href='update-category.php?id={$category_id}'><button class='btn'>Update</button></a>
                    <button class='btn delete'>Delete</button>
                    </td>
                  </tr>";                                   
                }
              }
            ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="liqours" class="section">
      <div class="section-header">
        <h2>Liqours</h2>
       <a href="add-liqour.php"> <button class="btn">Add Liqour</button></a>
      </div>
      <div class="section-content">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Description</th>
              <th>Price</th>
              <th>Category</th>
              <th>Image</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $sqlLiqours = "SELECT * FROM liqours";
            $stmt = $conn->prepare($sqlLiqours);
            $stmt->execute();
            $res = $stmt->get_result();

            if($res->num_rows > 0){
              while($row = $res->fetch_assoc()){
               
                $id = htmlspecialchars($row['liqour_id']);
                $name = htmlspecialchars($row['name']);
                $desc = htmlspecialchars($row['description']);
                $price = htmlspecialchars($row['price']);
                $category = htmlspecialchars($row['category_id']); 
                $image = htmlspecialchars($row['image_url']);
                echo "<tr>
                        <td>{$id}</td>
                        <td>{$name}</td>
                        <td>{$desc}</td>
                        <td>\${$price}</td>
                        <td>{$category}</td>
                        <td>{$image}</td>
                        <td>
                           <a href='update-liqour.php?id={$id}'><button class='btn'>Update</button></a>
                         <button class='btn delete'>Delete</button>
                        </td>
                      </tr>";
              }
            } else {
              echo "<tr><td colspan='7'>No liquors found.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </section>

    <section id="orders" class="section">
      <div class="section-header">
        <h2>Orders</h2>
      </div>
      <div class="section-content">
        <table class="table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>User</th>
              <th>Status</th>
              <th>Total</th>
              <th>Created At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <section id="reviews" class="section">
      <div class="section-header">
        <h2>Reviews</h2>
      </div>
      <div class="section-content">
        <table class="table">
          <thead>
            <tr>
              <th>Review ID</th>
              <th>Liqour</th>
              <th>User</th>
              <th>Rating</th>
              <th>Comment</th>
              <th>Created At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>

    <section id="warehouse" class="section">
      <div class="section-header">
        <h2>Warehouse & Stock</h2>
        <button class="btn">Add Warehouse</button>
      </div>
      <div class="section-content">
        <table class="table">
          <thead>
            <tr>
              <th>Warehouse ID</th>
              <th>Name</th>
              <th>Address</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>

        <div class="section-header">
          <h3>Stock Levels</h3>
        </div>
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
          <tbody></tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>