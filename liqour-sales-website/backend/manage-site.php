

<?php
session_start();

if (
    !isset($_SESSION['login'], $_SESSION['user_id'], $_SESSION['username'], $_SESSION['is_admin']) ||
    $_SESSION['login'] !== 'success'
) {
    header('Location: /process-login.php');
    exit();
}
?>





<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
</head>
<body>
  <header>
    <h1>Admin Dashboard</h1>
    <nav>
      <ul>
        <li><a href="#stats">Stats</a></li>
        <li><a href="#categories">Categories</a></li>
        <li><a href="#liqours">Liqours</a></li>
        <li><a href="#orders">Orders</a></li>
        <li><a href="#reviews">Reviews</a></li>
        <li><a href="#warehouse">Warehouse</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <!-- Stats -->
    <section id="stats">
      <h2>Statistics Overview</h2>
      <div>
        <p>Total Users: [Number]</p>
        <p>Total Orders: [Number]</p>
        <p>Total Revenue: [Amount]</p>
        <p>Total Reviews: [Number]</p>
        <p>Total Stock Items: [Number]</p>
      </div>
    </section>

    <!-- Categories -->
    <section id="categories">
      <h2>Liqour Categories</h2>
      <button>Add Category</button>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Category rows -->
        </tbody>
      </table>
    </section>

    <!-- Liqours -->
    <section id="liqours">
      <h2>Liqours</h2>
      <button>Add Liqour</button>
      <table>
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
          <!-- Liqour rows -->
        </tbody>
      </table>
    </section>

    <!-- Orders -->
    <section id="orders">
      <h2>Orders</h2>
      <table>
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
        <tbody>
          <!-- Order rows -->
        </tbody>
      </table>
    </section>

    <!-- Reviews -->
    <section id="reviews">
      <h2>Reviews</h2>
      <table>
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
        <tbody>
          <!-- Review rows -->
        </tbody>
      </table>
    </section>

    <!-- Warehouse -->
    <section id="warehouse">
      <h2>Warehouse & Stock</h2>
      <button>Add Warehouse</button>
      <table>
        <thead>
          <tr>
            <th>Warehouse ID</th>
            <th>Name</th>
            <th>Address</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Warehouse rows -->
        </tbody>
      </table>

      <h3>Stock Levels</h3>
      <table>
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
          <!-- Stock rows -->
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
