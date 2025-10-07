<?php
error_reporting(0);
session_start();
include("../sql-config.php");

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../public/login-signup.php");
    exit();
}

$records_per_page = 10;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    if ($action == 'add') {
        $user_id = intval($_POST['user_id']);
        $address = trim($_POST['address']);
        if ($user_id > 0 && !empty($address)) {
            $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, address) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $address);
            $response['success'] = $stmt->execute();
            $response['message'] = $response['success'] ? 'Address added successfully' : 'Failed to add address';
        } else {
            $response['message'] = 'Invalid user or address';
        }
    } elseif ($action == 'update') {
        $address_id = intval($_POST['address_id']);
        $user_id = intval($_POST['user_id']);
        $address = trim($_POST['address']);
        if ($address_id > 0 && $user_id > 0 && !empty($address)) {
            $stmt = $conn->prepare("UPDATE user_addresses SET user_id = ?, address = ? WHERE address_id = ?");
            $stmt->bind_param("isi", $user_id, $address, $address_id);
            $response['success'] = $stmt->execute();
            $response['message'] = $response['success'] ? 'Address updated successfully' : 'Failed to update address';
        } else {
            $response['message'] = 'Invalid data';
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (isset($_GET['delete_id']) && isset($_GET['type'])) {
    $id = intval($_GET['delete_id']);
    $type = $_GET['type'];
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $q = isset($_GET['q']) ? urlencode($_GET['q']) : '';
    if ($id > 0) {
        if ($type == 'soft') {
            $stmt = $conn->prepare("UPDATE user_addresses SET is_active = 0 WHERE address_id = ?");
            $stmt->bind_param("i", $id);
        } elseif ($type == 'hard') {
            $stmt = $conn->prepare("DELETE FROM user_addresses WHERE address_id = ?");
            $stmt->bind_param("i", $id);
        }
        $success = $stmt->execute();
        $params = "success=" . ($success ? 1 : 0);
        if ($page > 1) $params .= "&page=$page";
        if (!empty($q)) $params .= "&q=$q";
        header("Location: addresses.php?$params");
        exit;
    }
}


if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $query = $_GET['q'] ?? '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $records_per_page;
    $search_param = "%{$query}%";

    $count_stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM user_addresses a
        JOIN users u ON a.user_id = u.id
        WHERE a.is_active=1
        AND (a.address LIKE ? OR u.name LIKE ?)
    ");
    $count_stmt->bind_param("ss", $search_param, $search_param);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $records_per_page);


    $stmt = $conn->prepare("
        SELECT a.*, u.name AS user_name
        FROM user_addresses a
        JOIN users u ON a.user_id = u.id
        WHERE a.is_active=1
        AND (a.address LIKE ? OR u.name LIKE ?)
        ORDER BY a.address_id DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ssii", $search_param, $search_param, $records_per_page, $offset);
    $stmt->execute();
    $res = $stmt->get_result();

    $response = ['html' => '', 'pagination' => '', 'total' => $total_records];

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $id = htmlspecialchars($row['address_id']);
            $user_name = htmlspecialchars($row['user_name']);
            $address = htmlspecialchars($row['address'], ENT_QUOTES);
            $status = $row['is_active'] ? 'Active' : 'Inactive';
            $badgeClass = $row['is_active'] ? 'badge-active' : 'badge-inactive';

            $response['html'] .= "
                <tr>
                    <td>{$id}</td>
                    <td>{$user_name}</td>
                    <td>{$address}</td>
                    <td><span class='badge {$badgeClass}'>{$status}</span></td>
                    <td>
                        <div class='action-buttons'>
                            <button class='btn update' data-id='{$id}' data-user_id='{$row['user_id']}' data-address='{$address}'>Update</button>
                            <a href='?delete_id={$id}&type=soft' onclick=\"return confirm('Soft delete this address?');\" class='btn soft-delete'>Soft Delete</a>
                            <a href='?delete_id={$id}&type=hard' onclick=\"return confirm('⚠️ PERMANENTLY DELETE?');\" class='btn delete'>Delete Forever</a>
                        </div>
                    </td>
                </tr>
            ";
        }
    } else {
        $response['html'] = "<tr><td colspan='5' style='text-align:center; padding:2rem; color:#666;'>No addresses found</td></tr>";
    }

   
    if ($total_pages > 1) {
        $pagination = '<div class="pagination-container">';
        $pagination .= '<div class="pagination-info">Showing ' . min($offset + 1, $total_records) . ' to ' . min($offset + $records_per_page, $total_records) . ' of ' . $total_records . ' entries</div>';
        $pagination .= '<div class="pagination">';
        if ($page > 1) $pagination .= '<a href="#" class="btn page-btn" data-page="' . ($page-1) . '">← Prev</a>';
        else $pagination .= '<span class="btn disabled">← Prev</span>';
        $start = max(1, $page-2);
        $end = min($total_pages, $page+2);
        if ($start > 1) { $pagination .= '<a href="#" class="btn page-btn" data-page="1">1</a>'; if ($start>2) $pagination.='<span class="btn disabled">...</span>'; }
        for ($i=$start;$i<=$end;$i++) { $active = $i==$page?'active':''; $pagination.='<a href="#" class="btn page-btn '.$active.'" data-page="'.$i.'">'.$i.'</a>'; }
        if ($end<$total_pages) { if($end<$total_pages-1) $pagination.='<span class="btn disabled">...</span>'; $pagination.='<a href="#" class="btn page-btn" data-page="'.$total_pages.'">'.$total_pages.'</a>'; }
        if ($page<$total_pages) $pagination.='<a href="#" class="btn page-btn" data-page="'.($page+1).'">Next →</a>'; else $pagination.='<span class="btn disabled">Next →</span>';
        $pagination .= '</div></div>';
        $response['pagination']=$pagination;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}


$users_stmt = $conn->prepare("SELECT id, name FROM users WHERE is_active=1 ORDER BY name");
$users_stmt->execute();
$users_res = $users_stmt->get_result();
$users = [];
while ($user = $users_res->fetch_assoc()) {
    $users[] = $user;
}


$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$q = $_GET['q'] ?? '';
$offset = ($page-1)*$records_per_page;
$search_param = "%{$q}%";

$count_stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM user_addresses a
    JOIN users u ON a.user_id = u.id
    WHERE a.is_active=1
    AND (a.address LIKE ? OR u.name LIKE ?)
");
$count_stmt->bind_param("ss", $search_param, $search_param);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$stmt = $conn->prepare("
    SELECT a.*, u.name AS user_name
    FROM user_addresses a
    JOIN users u ON a.user_id = u.id
    WHERE a.is_active=1
    AND (a.address LIKE ? OR u.name LIKE ?)
    ORDER BY a.address_id DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ssii", $search_param, $search_param, $records_per_page, $offset);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Addresses Management</title>
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

h1,h2,h3,h4 { margin: 0; }
a { text-decoration: none; color: inherit; }
table { border-collapse: collapse; width: 100%; }
th,td { text-align: left; padding: 12px; }
th { background: var(--primary); color: #fff; }
td { background: var(--bg); }
tr:nth-child(even) td { background: var(--accent); }

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

.header h1 { font-size: 1.8rem; }

.nav {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.nav .btn {
    padding: 0.5rem 1rem;
    background: var(--accent);
    border-radius: var(--radius);
    font-weight: bold;
    transition: var(--transition);
    border: none;
    cursor: pointer;
    color: var(--text);
}
.nav .btn:hover { background: var(--accent-dark); }

.container {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.table-container {
    overflow-x: auto;
    margin-top: 1rem;
    background: white;
    border-radius: var(--radius);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}

.table th, .table td {
    border-bottom: 1px solid var(--border);
}

.table th { font-weight: 600; }

.badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: var(--radius);
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-active { background: var(--success); color: #fff; }
.badge-inactive { background: var(--danger); color: #fff; }

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
}
.action-buttons .btn {
    padding: 0.3rem 0.5rem;
    border-radius: var(--radius);
    font-size: 0.8rem;
    font-weight: 600;
    transition: var(--transition);
    cursor: pointer;
    border: none;
}
.action-buttons .btn:hover { opacity: 0.85; }
.btn.soft-delete { background: var(--warning); color: #fff; }
.btn.delete { background: var(--danger); color: #fff; }
.btn.update { background: var(--primary-light); color: #333; }

.pagination-container {
    margin-top: 1rem;
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
    gap: 0.3rem;
}
.pagination .btn {
    padding: 0.3rem 0.6rem;
    border-radius: var(--radius);
    background: var(--primary-light);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}
.pagination .btn:hover { background: var(--primary); color: #fff; }
.pagination .btn.active { background: var(--primary-dark); color: #fff; }
.pagination .btn.disabled { background: #eee; color: #aaa; cursor: default; }

.search-container {
    position: relative;
    margin-bottom: 1rem;
}
.search-box {
    width: 100%;
    padding: 0.5rem 2.5rem 0.5rem 1rem;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    font-size: 1rem;
    outline: none;
    transition: var(--transition);
}
.search-box:focus { border-color: var(--primary-dark); }

.search-icon {
    position: absolute;
    right: 0.8rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1rem;
    color: #666;
}

.loading-spinner {
    position: absolute;
    right: 2rem;
    top: 50%;
    width: 1rem;
    height: 1rem;
    border: 2px solid var(--primary);
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    display: none;
}
.search-container.loading .loading-spinner { display: block; }

@keyframes spin {
    0% { transform: translateY(-50%) rotate(0deg); }
    100% { transform: translateY(-50%) rotate(360deg); }
}

.fade-in { animation: fadeIn 0.3s ease-in-out; }
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: var(--radius);
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

form label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

form select, form textarea {
    width: 100%;
    padding: 0.5rem;
    margin-bottom: 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-family: inherit;
}

form button {
    padding: 0.5rem 1rem;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-weight: 600;
}

form button:hover {
    background: var(--primary-dark);
}

@media(max-width:768px){
    .action-buttons { flex-direction: column; }
    .header { flex-direction: column; align-items: flex-start; }
    .nav { display: flex; gap:0.5rem; flex-wrap: wrap; }
}

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: var(--radius);
    color: #fff;
}
.alert-success { background: var(--success); }
.alert-error { background: var(--danger); }
</style>
</head>
<body>
<div class="header">
    <h1>User Addresses Management</h1>
    <div class="nav">
        <button id="add-btn" class="btn">+ Add Address</button>
        <a href="../manage-dashboard.php" class="btn">← Back to Dashboard</a>
    </div>
</div>

<div class="container">
<?php if (isset($_GET['success'])): ?>
    <div class="alert <?= $_GET['success'] == 1 ? 'alert-success' : 'alert-error' ?>">
        <?= $_GET['success'] == 1 ? 'Operation successful' : 'Operation failed' ?>
    </div>
<?php endif; ?>

<div class="search-container">
    <input type="text" id="search-box" class="search-box" placeholder="Search by address or user name..." value="<?= htmlspecialchars($q) ?>">
    <span class="search-icon">🔍</span>
    <div class="loading-spinner" id="loading-spinner"></div>
</div>

<div class="table-container">
<table class="table">
    <thead>
        <tr>
            <th>ID</th><th>User</th><th>Address</th><th>Status</th><th>Actions</th>
        </tr>
    </thead>
    <tbody id="addresses-table-body">
        <?php
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $id = htmlspecialchars($row['address_id']);
                $user_name = htmlspecialchars($row['user_name']);
                $address = htmlspecialchars($row['address'], ENT_QUOTES);
                $status = $row['is_active'] ? 'Active' : 'Inactive';
                $badgeClass = $row['is_active'] ? 'badge-active' : 'badge-inactive';

                echo "
                    <tr>
                        <td>{$id}</td>
                        <td>{$user_name}</td>
                        <td>{$address}</td>
                        <td><span class='badge {$badgeClass}'>{$status}</span></td>
                        <td>
                            <div class='action-buttons'>
                                <button class='btn update' data-id='{$id}' data-user_id='{$row['user_id']}' data-address='{$address}'>Update</button>
                                <a href='?delete_id={$id}&type=soft&page={$page}&q=" . urlencode($q) . "' onclick=\"return confirm('Soft delete this address?');\" class='btn soft-delete'>Soft Delete</a>
                                <a href='?delete_id={$id}&type=hard&page={$page}&q=" . urlencode($q) . "' onclick=\"return confirm('⚠️ PERMANENTLY DELETE?');\" class='btn delete'>Delete Forever</a>
                            </div>
                        </td>
                    </tr>
                ";
            }
        } else {
            echo "<tr><td colspan='5' style='text-align:center;padding:2rem;color:#666;'>No addresses found</td></tr>";
        }
        ?>
    </tbody>
</table>
</div>

<div id="pagination-container"></div>
</div>

<!-- Add/Update Modal -->
<div id="address-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modal-title">Add Address</h2>
        <form id="address-form">
            <input type="hidden" id="action" name="action" value="add">
            <input type="hidden" id="address_id" name="address_id">
            <label for="user_id">User:</label>
            <select id="user_id" name="user_id" required>
                <option value="">Select User</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="address">Address:</label>
            <textarea id="address" name="address" rows="3" required></textarea>
            <button type="submit">Save</button>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('address-modal');
const closeBtn = document.getElementsByClassName('close')[0];
const form = document.getElementById('address-form');
const title = document.getElementById('modal-title');
const actionInput = document.getElementById('action');
const idInput = document.getElementById('address_id');

closeBtn.onclick = () => { modal.style.display = 'none'; }
window.onclick = (event) => { if (event.target == modal) modal.style.display = 'none'; }

document.getElementById('add-btn').onclick = () => {
    title.textContent = 'Add Address';
    actionInput.value = 'add';
    idInput.value = '';
    document.getElementById('user_id').value = '';
    document.getElementById('address').value = '';
    modal.style.display = 'block';
}

$(document).on('click', '.update', function() {
    title.textContent = 'Update Address';
    actionInput.value = 'update';
    idInput.value = $(this).data('id');
    document.getElementById('user_id').value = $(this).data('user_id');
    document.getElementById('address').value = $(this).data('address');
    modal.style.display = 'block';
});

form.onsubmit = (e) => {
    e.preventDefault();
    $.ajax({
        url: 'addresses.php',
        method: 'POST',
        data: $(form).serialize(),
        dataType: 'json',
        success: (res) => {
            alert(res.message);
            if (res.success) {
                modal.style.display = 'none';
                loadAddresses();
            }
        }
    });
}

function loadAddresses(page=1, query='') {
    $('#loading-spinner').show();
    $.ajax({
        url: 'addresses.php',
        method: 'GET',
        data: { ajax: 1, page: page, q: query },
        dataType: 'json',
        success: function(res) {
            $('#addresses-table-body').html(res.html);
            $('#pagination-container').html(res.pagination);
        },
        complete: function() { $('#loading-spinner').hide(); }
    });
}

$(document).ready(function(){
    $('#search-box').on('input', function(){
        const q=$(this).val();
        loadAddresses(1,q);
    });
    $(document).on('click','.page-btn', function(e){
        e.preventDefault();
        const page=$(this).data('page');
        const q=$('#search-box').val();
        loadAddresses(page,q);
    });
    loadAddresses(<?= $page ?>,'<?= addslashes($q) ?>');
});
</script>
</body>
</html>