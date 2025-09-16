<?php
session_start();
include("../sql-config.php");

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../public/login-signup.php");
    exit();
}

$cid = $_GET['id'] ?? null;
if (!$cid) die("No category ID provided.");

// Fetch current category
$stmt = $conn->prepare("SELECT name, image_url FROM liqour_categories WHERE liqour_category_id = ?");
$stmt->bind_param("i", $cid);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Category not found.");
$category = $result->fetch_assoc();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $image_path = $category['image_url'];

    // Handle image upload
    if (!empty($_FILES['image_file']['name'])) {
        $targetDir = "../../public/src/category-images/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        $fileName = basename($_FILES["image_file"]["name"]);
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileType, $allowedTypes)) {
            $error = "Only JPG, JPEG, PNG, GIF files are allowed.";
        } else {
            if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $targetFile)) {
                $image_path = "public/src/category-images/" . $fileName; // relative path
            } else {
                $error = "Failed to upload image.";
            }
        }
    }

    if (empty($error)) {
        $update_stmt = $conn->prepare("UPDATE liqour_categories SET name=?, image_url=? WHERE liqour_category_id=?");
        $update_stmt->bind_param("ssi", $name, $image_path, $cid);
        if ($update_stmt->execute()) {
            $success = "Category updated successfully!";
            // Update current values for preview
            $category['name'] = $name;
            $category['image_url'] = $image_path;
        } else {
            $error = "Update failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Category</title>
<style>
* { box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: #f8f9fa; margin:0; padding:20px; min-height:100vh; line-height:1.6; }
.container { max-width: 600px; margin: 0 auto; padding-top:2rem; }
.back-button { position: fixed; top: 20px; left: 20px; background-color:#6c757d; color:white; padding:0.75rem 1rem; border:none; border-radius:6px; cursor:pointer; text-decoration:none; font-size:0.875rem; font-weight:500; transition: all 0.2s; display:flex; align-items:center; gap:0.5rem; z-index:100; }
.back-button:hover { background-color:#545b62; transform:translateY(-1px); }
.form-card { background:white; border-radius:12px; padding:2rem; box-shadow:0 4px 6px rgba(0,0,0,0.05); border:1px solid #dee2e6; }
.form-header { text-align:center; margin-bottom:2rem; }
.form-header h1 { margin:0; color:#212529; font-size:1.75rem; font-weight:600; }
.form-header p { margin:0.5rem 0 0 0; color:#6c757d; font-size:0.875rem; }
.form-group { margin-bottom:1.5rem; }
.form-group label { display:block; margin-bottom:0.5rem; font-weight:600; color:#495057; font-size:0.875rem; }
.form-group input[type="text"], .form-group input[type="file"] { width:100%; padding:0.75rem; border:2px solid #dee2e6; border-radius:6px; font-size:1rem; transition:border-color 0.2s, box-shadow 0.2s; background:#fff; }
.form-group input[type="text"]:focus, .form-group input[type="file"]:focus { outline:none; border-color:#007bff; box-shadow:0 0 0 3px rgba(0,123,255,0.1); }
.submit-btn { width:100%; background:#212529; color:white; padding:0.875rem 1.5rem; cursor:pointer; border:none; border-radius:6px; font-size:1rem; font-weight:600; transition: all 0.2s; margin-top:1rem; }
.submit-btn:hover { background:#343a40; transform:translateY(-1px); }
.alert-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; padding:1rem; border-radius:6px; margin-top:1rem; font-size:0.875rem; }
.alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:1rem; border-radius:6px; margin-top:1rem; font-size:0.875rem; }
.preview-img { max-width:150px; display:block; margin-top:10px; border-radius:8px; }
</style>
</head>
<body>

<div class="container">
    <a href="../manage-dashboard.php#categories" class="back-button">← Back to Dashboard</a>
    
    <div class="form-card">
        <div class="form-header">
            <h1>Update Category</h1>
            <p>Edit category name or image</p>
        </div>

        <?php if($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="image_file">Category Image</label>
                <input type="file" name="image_file" accept="image/*" onchange="previewImage(event)">
                <?php if(!empty($category['image_url'])): ?>
                    <img id="preview" src="../../<?= htmlspecialchars($category['image_url']) ?>" class="preview-img" alt="Category Image">
                <?php else: ?>
                    <img id="preview" class="preview-img" style="display:none;" alt="Preview">
                <?php endif; ?>
            </div>
            <button type="submit" class="submit-btn">UPDATE CATEGORY</button>
        </form>
    </div>
</div>

<script>
function previewImage(event) {
    const preview = document.getElementById('preview');
    preview.src = URL.createObjectURL(event.target.files[0]);
    preview.style.display = 'block';
}
</script>

</body>
</html>
