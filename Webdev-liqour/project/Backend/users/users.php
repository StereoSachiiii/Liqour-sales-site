<?php
session_start();
include(__DIR__ . "/../sql-config.php");

// Check admin login
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: /public/login-signup.php");
    exit();
}

$records_per_page = 10;

// Handle AJAX live search request
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $query = trim($_GET['q'] ?? '');
    $search_param = "%{$query}%";

    $stmt = $conn->prepare("SELECT * FROM users 
                            WHERE is_active=1 
                            AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR address LIKE ?)
                            ORDER BY id DESC
                            LIMIT ?");
    $stmt->bind_param("ssssi", $search_param, $search_param, $search_param, $search_param, $records_per_page);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $id = htmlspecialchars($row['id']);
            $name = htmlspecialchars($row['name']);
            $email = htmlspecialchars($row['email']);
            $phone = htmlspecialchars($row['phone'] ?? 'N/A');
            $address = htmlspecialchars(substr($row['address'] ?? '', 0, 30) . (strlen($row['address'] ?? '') > 30 ? '...' : ''));
            $is_admin = $row['is_admin'] ? 'Yes' : 'No';
            $adminBadge = $row['is_admin'] ? 'badge-active' : 'badge-inactive';
            $status = $row['is_active'] ? 'Active' : 'Inactive';
            echo "<tr>
                <td>{$id}</td>
                <td>{$name}</td>
                <td>{$email}</td>
                <td>{$phone}</td>
                <td title='" . htmlspecialchars($row['address'] ?? '') . "'>{$address}</td>
                <td><span class='badge {$adminBadge}'>{$is_admin}</span></td>
                <td><span class='badge badge-active'>{$status}</span></td>
                <td>
                    <div class='action-buttons'>
                        <a href='view.php?id={$id}' class='btn view'>View</a>
                        <a href='update.php?id={$id}' class='btn update'>Update</a>
                        <a href='delete.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this user?');\" class='btn soft-delete'>Soft Delete</a>
                        <a href='delete.php?id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE?');\" class='btn delete'>Delete Forever</a>
                    </div>
                </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='8' class='no-data'>No users found</td></tr>";
    }
    $stmt->close();
    exit;
}

// Non-AJAX: initial load
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE is_active=1");
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();
$total_pages = ceil($total_records / $records_per_page);

$stmt = $conn->prepare("SELECT * FROM users WHERE is_active=1 ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$res = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users Management</title>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
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
    margin: 0;
    padding: 0;
    background: var(--accent-light);
    color: var(--text);
}

h1, h2, h3, h4 {
    margin: 0;
}

a {
    text-decoration: none;
    color: var(--text);
}

table {
    border-collapse: collapse;
    width: 100%;
}

th, td {
    text-align: left;
    padding: 12px;
    font-size: 0.9rem;
}

th {
    background: var(--primary);
    color: #fff;
    font-weight: 600;
}

td {
    background: var(--bg);
}

tr:nth-child(even) td {
    background: var(--accent-dark);
}

.header {
    padding: 1rem 2rem;
    background: var(--primary-dark);
    color: #fff;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.header h1 {
    font-size: 1.8rem;
}

.nav a.btn {
    padding: 0.5rem 1rem;
    background: var(--accent);
    border-radius: var(--radius);
    font-weight: 500;
    transition: var(--transition);
}

.nav a.btn:hover {
    background: var(--primary-light);
}

.table-container {
    overflow-x: auto;
    margin: 1rem 2rem;
    background: var(--bg);
    border-radius: var(--radius);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border);
}

.table th, .table td {
    border-bottom: 1px solid var(--border);
}

.badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: var(--radius);
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-active {
    background: var(--success);
    color: #fff;
}

.badge-inactive {
    background: var(--danger);
    color: #fff;
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.action-buttons .btn {
    padding: 0.4rem 0.8rem;
    border-radius: var(--radius);
    font-size: 0.85rem;
    font-weight: 500;
    transition: var(--transition);
    cursor: pointer;
}

.action-buttons .btn:hover {
    opacity: 0.9;
}

.btn.view {
    background: var(--primary-light);
    color: var(--text);
}

.btn.update {
    background: var(--primary);
    color: #000;
}

.btn.soft-delete {
    background: var(--warning);
    color: #000;
}

.btn.delete {
    background: var(--danger);
    color: #000;
}

.pagination-container {
    margin: 1rem 2rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.pagination-info {
    font-size: 0.9rem;
    color: var(--text);
}

.pagination {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.pagination .btn {
    padding: 0.4rem 0.8rem;
    border-radius: var(--radius);
    background: var(--accent);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
}

.pagination .btn:hover {
    background: var(--primary);
    color: #fff;
}

.pagination .btn.active {
    background: var(--primary-dark);
    color: #fff;
}

.pagination .btn.disabled {
    background: #eee;
    color: #aaa;
    cursor: not-allowed;
}

.search-container {
    position: relative;
    margin: 1rem 2rem;
}

.search-box {
    width: 100%;
    padding: 0.6rem 2.5rem 0.6rem 1rem;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    font-size: 1rem;
    font-family: inherit;
    background: var(--accent);
    transition: border-color var(--transition);
}

.search-box:focus {
    outline: none;
    border-color: var(--primary-dark);
}

.search-icon {
    position: absolute;
    right: 0.8rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1rem;
    color: var(--text);
    opacity: 0.6;
}

.loading-spinner {
    position: absolute;
    right: 2.5rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1.2rem;
    height: 1.2rem;
    border: 2px solid var(--primary-dark);
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    display: none;
}

.search-container.loading .loading-spinner {
    display: block;
}

.no-data {
    text-align: center;
    padding: 2rem;
    color: var(--text);
    font-style: italic;
    font-size: 0.9rem;
}

@keyframes spin {
    0% { transform: translateY(-50%) rotate(0deg); }
    100% { transform: translateY(-50%) rotate(360deg); }
}

.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@media (max-width: 768px) {
    .table-container, .search-container, .pagination-container {
        margin: 1rem;
    }
    .header {
        flex-direction: column;
        align-items: flex-start;
    }
    .nav {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .action-buttons {
        flex-direction: column;
    }
}
</style>
</head>
<body>

<header class="header">
    <h1>Users Management</h1>
    <nav class="nav">
        <a href="../manage-dashboard.php" class="btn">← Back to Dashboard</a>
        <a href="add.php" class="btn">Add New User</a>
    </nav>
</header>

<main class="main">
<section class="section">
    <div class="section-header"><h2>Active Users</h2></div>
    <div class="section-content">
        <div class="search-container">
            <input type="text" id="search-input" class="search-box" placeholder="Search by name, email, phone, or address..." autocomplete="off">
            <span class="search-icon">🔍</span>
            <div class="loading-spinner"></div>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Admin</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <?php
                    if ($res && $res->num_rows > 0) {
                        while ($row = $res->fetch_assoc()) {
                            $id = htmlspecialchars($row['id']);
                            $name = htmlspecialchars($row['name']);
                            $email = htmlspecialchars($row['email']);
                            $phone = htmlspecialchars($row['phone'] ?? 'N/A');
                            $address = htmlspecialchars(substr($row['address'] ?? '', 0, 30) . (strlen($row['address'] ?? '') > 30 ? '...' : ''));
                            $is_admin = $row['is_admin'] ? 'Yes' : 'No';
                            $adminBadge = $row['is_admin'] ? 'badge-active' : 'badge-inactive';
                            $status = $row['is_active'] ? 'Active' : 'Inactive';
                            echo "<tr>
                                <td>{$id}</td><td>{$name}</td><td>{$email}</td><td>{$phone}</td>
                                <td title='".htmlspecialchars($row['address'] ?? '')."'>{$address}</td>
                                <td><span class='badge {$adminBadge}'>{$is_admin}</span></td>
                                <td><span class='badge badge-active'>{$status}</span></td>
                                <td>
                                    <div class='action-buttons'>
                                        <a href='view.php?id={$id}' class='btn view'>View</a>
                                        <a href='update.php?id={$id}' class='btn update'>Update</a>
                                        <a href='delete.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this user?');\" class='btn soft-delete'>Soft Delete</a>
                                        <a href='delete.php?id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE?');\" class='btn delete'>Delete Forever</a>
                                    </div>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='no-data'>No users found</td></tr>";
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>

        <?php
        // Initial pagination
        $pagination = '';
        if ($total_pages > 1) {
            $pagination = '<div class="pagination-container">';
            $pagination .= '<div class="pagination-info">Showing ' . min($offset + 1, $total_records) . ' to ' . min($offset + $records_per_page, $total_records) . ' of ' . $total_records . ' entries</div>';
            $pagination .= '<div class="pagination">';

            if ($page > 1) {
                $pagination .= '<a href="#" class="btn page-btn" data-page="' . ($page - 1) . '">← Prev</a>';
            } else {
                $pagination .= '<span class="btn disabled">← Prev</span>';
            }

            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);

            if ($start > 1) {
                $pagination .= '<a href="#" class="btn page-btn" data-page="1">1</a>';
                if ($start > 2) $pagination .= '<span class="btn disabled">...</span>';
            }
            for ($i = $start; $i <= $end; $i++) {
                $active = ($i == $page) ? 'active' : '';
                $pagination .= '<a href="#" class="btn page-btn ' . $active . '" data-page="' . $i . '">' . $i . '</a>';
            }
            if ($end < $total_pages) {
                if ($end < $total_pages - 1) $pagination .= '<span class="btn disabled">...</span>';
                $pagination .= '<a href="#" class="btn page-btn" data-page="' . $total_pages . '">' . $total_pages . '</a>';
            }

            if ($page < $total_pages) {
                $pagination .= '<a href="#" class="btn page-btn" data-page="' . ($page + 1) . '">Next →</a>';
            } else {
                $pagination .= '<span class="btn disabled">Next →</span>';
            }

            $pagination .= '</div></div>';
        }
        ?>
        <div id="pagination-container"><?= $pagination ?></div>
    </div>
</section>
</main>

<script>
$(document).ready(function(){
    let ajaxTimeout = null;
    $('#search-input').on('input', function(e){
        e.preventDefault();
        clearTimeout(ajaxTimeout);

        ajaxTimeout = setTimeout(() => {
            let query = $(this).val().trim();
            $('.search-container').addClass('loading');
            $.get('users.php', { ajax: 1, q: query }, function(data){
                $('#users-table-body').html(data).addClass('fade-in');
                $('.search-container').removeClass('loading');
                setTimeout(() => $('#users-table-body').removeClass('fade-in'), 300);
            }).fail(function(xhr, status, error) {
                console.error('AJAX Error:', status, error, xhr.responseText);
                $('#users-table-body').html('<tr><td colspan="8" class="no-data">Error loading users. Please try again.</td></tr>');
                $('.search-container').removeClass('loading');
            });
        }, 300);
    });

    $('#search-input').on('keypress', function(e){
        if (e.which === 13) e.preventDefault();
    });

    $(document).on('click', '.page-btn', function(e) {
        e.preventDefault();
        let page = $(this).data('page');
        if (page && !$(this).hasClass('disabled')) {
            window.location.href = '?page=' + page;
        }
    });
});
</script>

</body>
</html>