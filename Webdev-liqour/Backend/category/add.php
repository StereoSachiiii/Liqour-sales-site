<?php
session_start();
include('../sql-config.php');

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $imagePath = ''; // will store relative path for DB

    // Check if file uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $error = "Only JPG, PNG, and GIF images are allowed.";
        } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB max
            $error = "Image size must be less than 2MB.";
        } else {
            // Ensure destination folder exists
            $destDir = __DIR__ . '/../../public/src/category images/';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('cat_', true) . '.' . $ext;
            $destination = $destDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Save relative path for DB
                $imagePath = 'public/src/category images/' . $fileName;
            } else {
                $error = "Failed to upload image.";
            }
        }
    }

    if (!isset($error)) {
        if ($name === '') {
            $error = "Category name cannot be empty.";
        } else {
            // Check for existing category (soft delete aware)
            $stmt = $conn->prepare("SELECT liqour_category_id, is_active FROM liqour_categories WHERE name=?");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                $existing = $res->fetch_assoc();
                if ($existing['is_active'] == 1) {
                    $error = "Category already exists and is active.";
                } else {
                    $error = "Category exists but is deleted. Please restore it or use a different name.";
                }
            } else {
                // Insert category
                $insert = $conn->prepare("INSERT INTO liqour_categories (name, image_url, is_active, created_at) VALUES (?, ?, 1, CURRENT_TIMESTAMP)");
                $insert->bind_param("ss", $name, $imagePath);
                $insert->execute();

                if ($insert->affected_rows > 0) {
                    header("Location: ../manage-dashboard.php#categories");
                    exit();
                } else {
                    $error = "Failed to add category. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Category</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
}
.back-button {
    position: absolute; top: 20px; left: 20px; background-color: #6c757d; color: white; 
    padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none;
}
.back-button:hover { background-color: #545b62; }

form {
    border: 1px solid #ccc;
    padding: 30px;
    background: white;
    border-radius: 8px;
    width: 100%;
    max-width: 400px;
}
label { display: block; margin: 10px 0 5px; font-weight: bold; }
input[type="text"], input[type="file"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
input[type="submit"] {
    background: black;
    color: white;
    margin-top: 15px;
    padding: 10px 15px;
    cursor: pointer;
    border: none;
    border-radius: 4px;
}
input[type="submit"]:hover { background: #333; }
.error { color: red; margin-top: 10px; font-size: 0.9rem; }
</style>
</head>
<body>

<a href="../manage-dashboard.php#categories" class="back-button">← Back to Dashboard</a>

<form action="add.php" method="POST" enctype="multipart/form-data">
    <label for="name">Category Name</label>
    <input type="text" name="name" required
           value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
    
    <label for="image">Category Image (optional)</label>
    <input type="file" name="image" accept="image/*">
    
    <input type="submit" value="ADD CATEGORY">
    
    <?php if(isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
</form>

</body>
</html>
