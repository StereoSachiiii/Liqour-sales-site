<?php
include("../sql-config.php");

if(!isset($_GET['id'])){
    die("User ID not specified.");
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows === 0){
    die("User not found.");
}

$user = $res->fetch_assoc();
$stmt->close();

// Get order statistics
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_orders,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
    SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as total_spent,
    MAX(created_at) as last_order_date
FROM orders WHERE user_id=? AND is_active=1");
$stmt->bind_param("i", $id);
$stmt->execute();
$order_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get review statistics
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as avg_rating,
    MAX(created_at) as last_review_date
FROM reviews WHERE user_id=? AND is_active=1");
$stmt->bind_param("i", $id);
$stmt->execute();
$review_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent orders (last 5)
$stmt = $conn->prepare("SELECT order_id, status, total, created_at 
FROM orders WHERE user_id=? AND is_active=1 
ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent reviews (last 5)
$stmt = $conn->prepare("SELECT r.rating, r.comment, r.created_at, l.name as liquor_name 
FROM reviews r 
JOIN liqours l ON r.liqour_id = l.liqour_id 
WHERE r.user_id=? AND r.is_active=1 
ORDER BY r.created_at DESC LIMIT 5");
$stmt->bind_param("i", $id);
$stmt->execute();
$recent_reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View User</title>
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
    margin: 0;
    padding: 20px;
    color: var(--text);
}

.container {
    max-width: 900px;
    margin: 0 auto;
    background: var(--bg);
    border-radius: var(--radius);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
    overflow: hidden;
}

.header {
    background: var(--accent);
    padding: 20px 25px;
    border-bottom: 1px solid var(--border);
}

.header h2 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text);
}

.back-btn {
    display: inline-block;
    margin-bottom: 15px;
    padding: 8px 15px;
    background: var(--primary-dark);
    color: #fff;
    text-decoration: none;
    border-radius: var(--radius);
    font-weight: 500;
    font-size: 0.9rem;
    transition: var(--transition);
}

.back-btn:hover {
    background: var(--primary);
}

.content {
    padding: 25px;
}

.section {
    margin-bottom: 30px;
}

.section h3 {
    margin: 0 0 15px 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text);
    border-bottom: 2px solid var(--border);
    padding-bottom: 5px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.info-item {
    padding: 12px;
    background: var(--accent-dark);
    border-radius: var(--radius);
    border-left: 3px solid var(--primary);
}

.info-item label {
    font-weight: bold;
    color: var(--text);
    display: block;
    margin-bottom: 5px;
    font-size: 0.875rem;
}

.info-item .value {
    font-size: 1rem;
    color: var(--text);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.stat-card {
    text-align: center;
    padding: 15px;
    background: var(--accent-dark);
    border-radius: var(--radius);
    border: 1px solid var(--border);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--text);
    display: block;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--text);
    text-transform: uppercase;
    margin-top: 5px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.table th,
.table td {
    padding: 8px 12px;
    text-align: left;
    border-bottom: 1px solid var(--border);
    font-size: 0.9rem;
}

.table th {
    background: var(--accent);
    font-weight: 600;
    color: var(--text);
}

.table td {
    background: var(--bg);
}

.status-badge {
    padding: 2px 8px;
    border-radius: var(--radius);
    font-size: 0.75rem;
    font-weight: bold;
    text-transform: uppercase;
}

.status-active {
    background: var(--success);
    color: #fff;
}

.status-inactive {
    background: var(--danger);
    color: #fff;
}

.status-completed {
    background: var(--success);
    color: #fff;
}

.status-pending {
    background: var(--warning);
    color: #fff;
}

.status-processing {
    background: var(--primary-light);
    color: var(--text);
}

.status-cancelled {
    background: var(--danger);
    color: #fff;
}

.no-data {
    text-align: center;
    color: var(--text);
    font-style: italic;
    padding: 20px;
    font-size: 0.9rem;
}

@media (max-width: 600px) {
    .container {
        padding: 15px;
        width: 90%;
    }
    .info-grid, .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="users.php" class="back-btn">← Back to Dashboard</a>
        <h2>User Details: <?= htmlspecialchars($user['name']) ?></h2>
    </div>
    
    <div class="content">
        <!-- Basic Information -->
        <div class="section">
            <h3>Basic Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>User ID</label>
                    <div class="value"><?= $user['id'] ?></div>
                </div>
                <div class="info-item">
                    <label>Name</label>
                    <div class="value"><?= htmlspecialchars($user['name']) ?></div>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <div class="value"><?= htmlspecialchars($user['email']) ?></div>
                </div>
                <div class="info-item">
                    <label>Phone</label>
                    <div class="value"><?= $user['phone'] ? htmlspecialchars($user['phone']) : 'Not provided' ?></div>
                </div>
                <div class="info-item">
                    <label>Address</label>
                    <div class="value"><?= $user['address'] ? htmlspecialchars($user['address']) : 'Not provided' ?></div>
                </div>
                <div class="info-item">
                    <label>Account Type</label>
                    <div class="value"><?= $user['is_admin'] ? 'Administrator' : 'Regular User' ?></div>
                </div>
                <div class="info-item">
                    <label>Status</label>
                    <div class="value">
                        <span class="status-badge <?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <label>Member Since</label>
                    <div class="value"><?= date('M d, Y', strtotime($user['created_at'])) ?></div>
                </div>
                <div class="info-item">
                    <label>Last Updated</label>
                    <div class="value"><?= $user['updated_at'] ? date('M d, Y H:i', strtotime($user['updated_at'])) : 'Never' ?></div>
                </div>
                <div class="info-item">
                    <label>Last Login</label>
                    <div class="value"><?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never logged in' ?></div>
                </div>
            </div>
        </div>

        <!-- Order Statistics -->
        <div class="section">
            <h3>Order Statistics</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $order_stats['total_orders'] ?: 0 ?></span>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $order_stats['completed_orders'] ?: 0 ?></span>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $order_stats['pending_orders'] ?: 0 ?></span>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $order_stats['processing_orders'] ?: 0 ?></span>
                    <div class="stat-label">Processing</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number">$<?= number_format($order_stats['total_spent'] ?: 0, 2) ?></span>
                    <div class="stat-label">Total Spent</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $order_stats['last_order_date'] ? date('M d, Y', strtotime($order_stats['last_order_date'])) : 'Never' ?></span>
                    <div class="stat-label">Last Order</div>
                </div>
            </div>
        </div>

        <!-- Review Statistics -->
        <div class="section">
            <h3>Review Statistics</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $review_stats['total_reviews'] ?: 0 ?></span>
                    <div class="stat-label">Total Reviews</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $review_stats['avg_rating'] ? number_format($review_stats['avg_rating'], 1) : 'N/A' ?></span>
                    <div class="stat-label">Avg Rating</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $review_stats['last_review_date'] ? date('M d, Y', strtotime($review_stats['last_review_date'])) : 'Never' ?></span>
                    <div class="stat-label">Last Review</div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="section">
            <h3>Recent Orders</h3>
            <?php if (!empty($recent_orders)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?= $order['order_id'] ?></td>
                                <td>
                                    <span class="status-badge status-<?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td>$<?= number_format($order['total'], 2) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No orders found</div>
            <?php endif; ?>
        </div>

        <!-- Recent Reviews -->
        <div class="section">
            <h3>Recent Reviews</h3>
            <?php if (!empty($recent_reviews)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_reviews as $review): ?>
                            <tr>
                                <td><?= htmlspecialchars($review['liquor_name']) ?></td>
                                <td><?= $review['rating'] ?>/5</td>
                                <td><?= $review['comment'] ? htmlspecialchars(substr($review['comment'], 0, 50)) . (strlen($review['comment']) > 50 ? '...' : '') : 'No comment' ?></td>
                                <td><?= date('M d, Y', strtotime($review['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No reviews found</div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>