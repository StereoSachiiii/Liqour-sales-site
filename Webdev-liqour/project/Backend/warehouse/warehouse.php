<?php
session_start();
include(__DIR__ . "/../sql-config.php"); // Adjust path

// Admin check
if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: /public/login-signup.php");
    exit();
}

$records_per_page = 10;

// AJAX handler
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $query = $_GET['q'] ?? '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $records_per_page;
    $search_param = "%{$query}%";

    // Count
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM warehouse WHERE is_active=1 AND (name LIKE ? OR address LIKE ?)");
    $count_stmt->bind_param("ss", $search_param, $search_param);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch
    $stmt = $conn->prepare("SELECT * FROM warehouse WHERE is_active=1 AND (name LIKE ? OR address LIKE ?) ORDER BY warehouse_id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ssii", $search_param, $search_param, $records_per_page, $offset);
    $stmt->execute();
    $res = $stmt->get_result();

    $response = ['html' => '', 'pagination' => '', 'total' => $total_records];

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $id = htmlspecialchars($row['warehouse_id']);
            $name = htmlspecialchars($row['name']);
            $address = htmlspecialchars(substr($row['address'], 0, 30) . (strlen($row['address']) > 30 ? '...' : ''));
            $status = $row['is_active'] ? 'Active' : 'Inactive';
            $badgeClass = $row['is_active'] ? 'badge-active' : 'badge-inactive';

            $response['html'] .= "<tr>
                <td>{$id}</td>
                <td>{$name}</td>
                <td title='" . htmlspecialchars($row['address']) . "'>{$address}</td>
                <td><span class='badge {$badgeClass}'>{$status}</span></td>
                <td>{$row['created_at']}</td>
                <td>
                    <div class='action-buttons'>
                        <a href='update.php?id={$id}' class='btn'>Update</a>
                        <a href='delete.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this warehouse?');\" class='btn soft-delete'>Soft Delete</a>
                        <a href='delete.php?id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE?');\" class='btn delete'>Delete Forever</a>
                    </div>
                </td>
            </tr>";
        }
    } else {
        $response['html'] = "<tr><td colspan='6' style='text-align:center;padding:2rem;color:#666;'>No warehouses found</td></tr>";
    }

    // Pagination
    if ($total_pages > 1) {
        $pagination = '<div class="pagination-container">';
        $pagination .= '<div class="pagination-info">Showing ' . min($offset + 1, $total_records) . ' to ' . min($offset + $records_per_page, $total_records) . ' of ' . $total_records . ' entries</div>';
        $pagination .= '<div class="pagination">';
        if ($page > 1) $pagination .= '<a href="#" class="btn page-btn" data-page="' . ($page - 1) . '">← Prev</a>';
        else $pagination .= '<span class="btn disabled">← Prev</span>';

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
        if ($page < $total_pages) $pagination .= '<a href="#" class="btn page-btn" data-page="' . ($page + 1) . '">Next →</a>';
        else $pagination .= '<span class="btn disabled">Next →</span>';

        $pagination .= '</div></div>';
        $response['pagination'] = $pagination;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Initial load
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM warehouse WHERE is_active=1");
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$stmt = $conn->prepare("SELECT * FROM warehouse WHERE is_active=1 ORDER BY warehouse_id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$res = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Warehouse Management</title>
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
body { font-family:'Segoe UI',sans-serif; margin:0; padding:0; background:var(--accent-light); color:var(--text); }
h1,h2,h3,h4{margin:0;}
a{text-decoration:none;color:black;}
.table{border-collapse:collapse;width:100%;}
th,td{text-align:left;padding:12px;}
th{background:var(--primary); color:#fff;}
td{background:var(--bg);}
tr:nth-child(even) td{background:var(--accent);}
.header{padding:1rem 2rem;background:var(--primary-dark);color:#fff;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:1rem;}
.header h1{font-size:1.8rem;}
.nav a.btn{padding:0.4rem 1rem;background:var(--accent);border-radius:var(--radius);font-weight:bold;transition:var(--transition);}
.nav a.btn:hover{background:var(--accent-dark);}
.table-container{overflow-x:auto;margin-top:1rem;}
.table th,.table td{border-bottom:1px solid var(--border);}
.table th{font-weight:600;}
.badge{display:inline-block;padding:0.2rem 0.5rem;border-radius:var(--radius);font-size:0.85rem;font-weight:600;}
.badge-active{background:var(--success);color:#fff;}
.badge-inactive{background:var(--danger);color:#fff;}
.action-buttons{display:flex;flex-wrap:wrap;gap:0.3rem;}
.action-buttons .btn{padding:0.3rem 0.5rem;border-radius:var(--radius);font-size:0.8rem;font-weight:600;transition:var(--transition);cursor:pointer;}
.action-buttons .btn:hover{opacity:0.85;}
.btn.soft-delete{background:var(--warning);color:#fff;}
.btn.delete{background:var(--danger);color:#fff;}
.btn.update{background:var(--primary-light);color:#333;}
.btn.view{background:var(--accent);color:#333;}
.pagination-container{margin-top:1rem;display:flex;flex-direction:column;gap:0.5rem;}
.pagination-info{font-size:0.9rem;color:var(--text);}
.pagination{display:flex;flex-wrap:wrap;gap:0.3rem;}
.pagination .btn{padding:0.3rem 0.6rem;border-radius:var(--radius);background:var(--primary-light);font-weight:600;cursor:pointer;transition:var(--transition);}
.pagination .btn:hover{background:var(--primary);color:#fff;}
.pagination .btn.active{background:var(--primary-dark);color:#fff;}
.pagination .btn.disabled{background:#eee;color:#aaa;cursor:default;}
.search-container{position:relative;margin-bottom:1rem;}
.search-box{width:100%;padding:0.5rem 2.5rem 0.5rem 1rem;border-radius:var(--radius);border:1px solid var(--border);font-size:1rem;outline:none;transition:var(--transition);}
.search-box:focus{border-color:var(--primary-dark);}
.search-icon{position:absolute;right:0.8rem;top:50%;transform:translateY(-50%);font-size:1rem;color:#666;}
.loading-spinner{position:absolute;right:2rem;top:50%;width:1rem;height:1rem;border:2px solid var(--primary);border-top:2px solid transparent;border-radius:50%;animation:spin 0.7s linear infinite;display:none;}
.search-container.loading .loading-spinner{display:block;}
@keyframes spin{0%{transform:translateY(-50%) rotate(0deg);}100%{transform:translateY(-50%) rotate(360deg);}}
.fade-in{animation:fadeIn 0.3s ease-in-out;}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
@media(max-width:768px){.action-buttons{flex-direction:column;}.header{flex-direction:column;align-items:flex-start;}.nav{display:flex;gap:0.5rem;flex-wrap:wrap;}}
</style>
</head>
<body>
<header class="header">
    <h1>Warehouse Management</h1>
    <nav class="nav">
        <a href="../manage-dashboard.php" class="btn">← Back to Dashboard</a>
        <a href="add.php" class="btn">Add New Warehouse</a>
    </nav>
</header>

<main class="main">
<section class="section">
    <div class="section-header"><h2>Active Warehouses</h2></div>
    <div class="section-content">
        <div class="search-container">
            <input type="text" id="search-input" class="search-box" placeholder="Search by name or address..." autocomplete="off">
            <span class="search-icon">🔍</span>
            <div class="loading-spinner"></div>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Address</th><th>Status</th><th>Created At</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody id="warehouse-table-body">
                    <?php
                    if($res && $res->num_rows>0){
                        while($row=$res->fetch_assoc()){
                            $id = htmlspecialchars($row['warehouse_id']);
                            $name = htmlspecialchars($row['name']);
                            $address = htmlspecialchars(substr($row['address'],0,30) . (strlen($row['address'])>30?'...':''));
                            $status = $row['is_active']?'Active':'Inactive';
                            $badgeClass = $row['is_active']?'badge-active':'badge-inactive';
                            echo "<tr>
                                <td>{$id}</td><td>{$name}</td><td title='".htmlspecialchars($row['address'])."'>{$address}</td>
                                <td><span class='badge {$badgeClass}'>{$status}</span></td>
                                <td>{$row['created_at']}</td>
                                <td>
                                    <div class='action-buttons'>
                                        <a href='update.php?id={$id}' class='btn'>Update</a>
                                        <a href='delete.php?id={$id}&type=soft' onclick=\"return confirm('Soft delete this warehouse?');\" class='btn soft-delete'>Soft Delete</a>
                                        <a href='delete.php?id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE?');\" class='btn delete'>Delete Forever</a>
                                    </div>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;padding:2rem;color:#666;'>No warehouses found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div id="pagination-container">
            <?php
            if ($total_pages > 1) {
                $pagination = '<div class="pagination-container">';
                $pagination .= '<div class="pagination-info">Showing ' . min($offset + 1, $total_records) . ' to ' . min($offset + $records_per_page, $total_records) . ' of ' . $total_records . ' entries</div>';
                $pagination .= '<div class="pagination">';
                if ($page > 1) $pagination .= '<a href="#" class="btn page-btn" data-page="' . ($page - 1) . '">← Prev</a>';
                else $pagination .= '<span class="btn disabled">← Prev</span>';

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
                if ($page < $total_pages) $pagination .= '<a href="#" class="btn page-btn" data-page="' . ($page + 1) . '">Next →</a>';
                else $pagination .= '<span class="btn disabled">Next →</span>';

                $pagination .= '</div></div>';
                echo $pagination;
            }
            ?>
        </div>
    </div>
</section>
</main>

<script>
$(document).ready(function(){
    let ajaxTimeout = null;
    let currentPage = <?= $page ?>;
    let currentQuery = '';

    function loadWarehouses(query, page) {
        $('.search-container').addClass('loading');
        $.ajax({
            url: 'warehouse.php', // Ensure this is the correct path to the script
            method: 'GET',
            data: { ajax: 1, q: query, page: page },
            dataType: 'json',
            success: function(resp) {
                $('#warehouse-table-body').html(resp.html).addClass('fade-in');
                $('#pagination-container').html(resp.pagination);
                setTimeout(() => { $('#warehouse-table-body').removeClass('fade-in'); }, 300);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('Failed to fetch data. Please try again.');
            },
            complete: function() {
                $('.search-container').removeClass('loading');
            }
        });
    }

    $('#search-input').on('input', function() {
        currentQuery = $(this).val();
        clearTimeout(ajaxTimeout);
        ajaxTimeout = setTimeout(() => { loadWarehouses(currentQuery, 1); }, 300);
    });

    $(document).on('click', '.page-btn', function(e) {
        e.preventDefault();
        let page = $(this).data('page');
        if (page) {
            currentPage = page;
            loadWarehouses(currentQuery, page);
        }
    });

    $('#search-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            currentQuery = $(this).val();
            loadWarehouses(currentQuery, 1);
        }
    });
});
</script>

</body>
</html>