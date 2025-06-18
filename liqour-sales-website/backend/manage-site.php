

<?php /*
session_start();

if (
    !isset($_SESSION['login'], $_SESSION['user_id'], $_SESSION['username'], $_SESSION['is_admin']) ||
    $_SESSION['login'] !== 'success'
) {
    header('Location: /process-login.php');
    exit();
}
    */
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
        <div class="stat-card">Total Users</div>
        <div class="stat-card">Total Orders</div>
        <div class="stat-card">Total Revenue</div>
        <div class="stat-card">Total Reviews</div>
        <div class="stat-card">Total Stock Items</div>
      </div>
    </section>

    <section id="categories" class="section">
      <div class="section-header">
        <h2>Liqour Categories</h2>
        <button class="btn">Add Category</button>
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
          <tbody></tbody>
        </table>
      </div>
    </section>

    <section id="liqours" class="section">
      <div class="section-header">
        <h2>Liqours</h2>
        <button class="btn">Add Liqour</button>
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
          <tbody></tbody>
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