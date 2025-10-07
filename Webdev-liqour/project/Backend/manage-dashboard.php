<?php
session_start();
include("sql-config.php");

if (!isset($_SESSION['login'], $_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) ||
    $_SESSION['login'] !== 'success' || !$_SESSION['is_admin']) {
    header('Location: adminlogin.php');
    exit();
}

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

// Additional stats for other sections
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM liqour_categories WHERE is_active=1");
$stmt->execute();
$stats['categories'] = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM warehouse WHERE is_active=1");
$stmt->execute();
$stats['warehouses'] = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM stock WHERE is_active=1");
$stmt->execute();
$stats['stock_records'] = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM suppliers WHERE is_active=1");
$stmt->execute();
$stats['suppliers'] = $stmt->get_result()->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --primary:rgb(255, 191, 0);
    --primary-dark:rgb(230, 146, 0);
    --accent-light: #FFFACD;
    --success: #28a745;
    --warning:rgb(255, 119, 7);
    --danger: #dc3545;
    --text: #000;
    --bg: #fff;
    --border: #ddd;
    --radius: 6px;
    --transition: 0.3s;
}

body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 10px; background: var(--accent-light); color: var(--text); line-height: 1.5; }
h1, h2, h3, h4 { margin: 0; font-weight: 600; color: var(--text); }
a { color: black; text-decoration: none;padding:4px;background-color:var(--primary) ;border-radius: 5px; }
.btn { padding: 0.4rem 0.8rem; border-radius: var(--radius); font-size: 0.9rem; font-weight: 600; transition: var(--transition); cursor: pointer; text-decoration: none; color: #000; background: var(--primary); display: inline-block; }
.btn:hover { background: var(--primary-dark); }
.header {  justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; background: wheat; color: var(--text); padding: 0.5rem 1rem; border-radius: var(--radius); }
.nav { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.nav-link { color: var(--text); font-weight: 600; transition: var(--transition); }
.nav-link:hover { color: var(--primary-dark); }
.section { margin-bottom: 1rem; background: var(--bg); border-radius: var(--radius); padding: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.section-actions { display: flex; gap: 0.3rem; }
.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.5rem; }
.stat-card { background: var(--bg); border-radius: var(--radius); padding: 0.8rem; text-align: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
.stat-number { font-size: 1.8rem; font-weight: bold; color: var(--primary-dark); }
.stat-label { font-size: 0.9rem; color: var(--text); }
.stat-graph { height: 60px; margin-top: 0.3rem; }
.alert { padding: 0.8rem; border-radius: var(--radius); margin-bottom: 0.8rem; }
.alert-success { background: var(--success); color: #fff; }
.alert-error { background: var(--danger); color: #fff; }
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
        <a href="#suppliers" class="nav-link">Suppliers</a>
        <a href="trash.php" class="nav-link">Trash</a>
        <a href="../public/index.php" target="_blank" class="btn">🌐 Visit Site</a>
        <a href="auth/logout.php" class="btn btn-logout" onclick="return confirm('Are you sure you want to sign out?');">🚪 Sign Out</a>
    </nav>
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
                <canvas class="stat-graph" id="usersChart"></canvas>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= number_format($stats['orders']); ?></span>
                <div class="stat-label">Total Orders</div>
                <canvas class="stat-graph" id="ordersChart"></canvas>
            </div>
            <div class="stat-card">
                <span class="stat-number">$<?= number_format($stats['revenue'],2); ?></span>
                <div class="stat-label">Total Revenue</div>
                <canvas class="stat-graph" id="revenueChart"></canvas>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= number_format($stats['reviews']); ?></span>
                <div class="stat-label">Total Reviews</div>
                <canvas class="stat-graph" id="reviewsChart"></canvas>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= number_format($stats['stock']); ?></span>
                <div class="stat-label">Stock Items</div>
                <canvas class="stat-graph" id="stockChart"></canvas>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= number_format($stats['deleted_users'] + $stats['deleted_liqours']); ?></span>
                <div class="stat-label">Deleted Items</div>
                <canvas class="stat-graph" id="deletedChart"></canvas>
            </div>
        </div>
    </div>
</section>

<section id="categories" class="section">
    <div class="section-header">
        <h2>Liquor Categories (<?= number_format($stats['categories']); ?> Active)</h2>
        <div class="section-actions">
            <a href="category/category.php" class="btn">Manage Categories</a>
            <a href="category/add.php" class="btn">Add Category</a>
        </div>
    </div>
</section>

<section id="liqours" class="section">
    <div class="section-header">
        <h2>Liqours (<?= number_format($stats['stock']); ?> Active)</h2>
        <div class="section-actions">
            <a href="liqour/liqour.php" class="btn">Manage Liqours</a>
            <a href="liqour/add.php" class="btn">Add Liqour</a>
        </div>
    </div>
</section>

<section id="orders" class="section">
    <div class="section-header">
        <h2>Orders (<?= number_format($stats['orders']); ?> Active)</h2>
        <div class="section-actions">
            <a href="order/order.php" class="btn">Manage Orders</a>
        </div>
    </div>
</section>

<section id="reviews" class="section">
    <div class="section-header">
        <h2>Reviews (<?= number_format($stats['reviews']); ?> Active)</h2>
        <div class="section-actions">
            <a href="review/review.php" class="btn">Manage Reviews</a>
        </div>
    </div>
</section>

<section id="warehouse" class="section">
    <div class="section-header">
        <h2>Warehouse (<?= number_format($stats['warehouses']); ?> Active)</h2>
        <div class="section-actions">
            <a href="warehouse/warehouse.php" class="btn">Manage Warehouses</a>
            <a href="warehouse/add.php" class="btn">Add Warehouse</a>
        </div>
    </div>
</section>

<section id="stock" class="section">
    <div class="section-header">
        <h2>Stock Levels (<?= number_format($stats['stock_records']); ?> Records)</h2>
        <div class="section-actions">
            <a href="stock/stock.php" class="btn">Manage Stock</a>
            <a href="stock/add.php" class="btn">Add Stock Record</a>
        </div>
    </div>
</section>

<section id="users" class="section">
    <div class="section-header">
        <h2>Users (<?= number_format($stats['users']); ?> Active)</h2>
        <div class="section-actions">
            <a href="users/users.php" class="btn">Manage Users</a>
            <a href="users/add.php" class="btn">Add User</a>
        </div>
    </div>
</section>

<section id="suppliers" class="section">
    <div class="section-header">
        <h2>Suppliers (<?= number_format($stats['suppliers']); ?> Active)</h2>
        <div class="section-actions">
            <a href="suppliers/suppliers.php" class="btn">Manage Suppliers</a>
            <a href="suppliers/add.php" class="btn">Add Supplier</a>
        </div>
    </div>
</section>

<section id="addresses" class="section">
    <div class="section-header">
        <h2>Addresses</h2>
        <div class="section-actions">
            <a href="addresses/addresses.php" class="btn">Manage Liqours</a>
           
        </div>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Quick Actions</h2>
    </div>
    <div class="section-content">
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="../public/index.php" target="_blank" class="btn">🌐 Visit Site</a>
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

// Simple charts for stats (placeholder data)
const chartConfig = {
    type: 'line',
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { x: { display: false }, y: { display: false } },
        elements: { line: { tension: 0.4 }, point: { radius: 0 } },
        plugins: { legend: { display: false } }
    }
};

new Chart(document.getElementById('usersChart'), {
    ...chartConfig,
    data: { labels: ['1', '2', '3', '4', '5'], datasets: [{ data: [50, 60, 55, 70, <?= $stats['users'] ?>], borderColor: var('--primary') }] }
});

new Chart(document.getElementById('ordersChart'), {
    ...chartConfig,
    data: { labels: ['1', '2', '3', '4', '5'], datasets: [{ data: [10, 20, 15, 25, <?= $stats['orders'] ?>], borderColor: var('--primary') }] }
});

new Chart(document.getElementById('revenueChart'), {
    ...chartConfig,
    data: { labels: ['1', '2', '3', '4', '5'], datasets: [{ data: [1000, 1500, 1200, 1800, <?= $stats['revenue'] ?>], borderColor: var('--primary') }] }
});

new Chart(document.getElementById('reviewsChart'), {
    ...chartConfig,
    data: { labels: ['1', '2', '3', '4', '5'], datasets: [{ data: [5, 10, 8, 12, <?= $stats['reviews'] ?>], borderColor: var('--primary') }] }
});

new Chart(document.getElementById('stockChart'), {
    ...chartConfig,
    data: { labels: ['1', '2', '3', '4', '5'], datasets: [{ data: [100, 120, 110, 130, <?= $stats['stock'] ?>], borderColor: var('--primary') }] }
});

new Chart(document.getElementById('deletedChart'), {
    ...chartConfig,
    data: { labels: ['1', '2', '3', '4', '5'], datasets: [{ data: [2, 4, 3, 5, <?= $stats['deleted_users'] + $stats['deleted_liqours'] ?>], borderColor: var('--primary') }] }
});
</script>

</body>
</html>