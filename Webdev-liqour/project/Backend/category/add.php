<?php
session_start();
include('../sql-config.php');

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}
//check request method

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
$destDir = __DIR__ . '/../../public/src/category-images/';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('cat_', true) . '.' . $ext;
            $destination = $destDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Save relative path for DB
$imagePath = 'public/src/category-images/' . $fileName;
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
            //fetch actives
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

.back-button {
    position: absolute;
    top: 20px;
    left: 20px;
    background: var(--primary-dark);
    color: #fff;
    padding: 10px 16px;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    transition: var(--transition);
}
.back-button:hover { background: var(--primary); }

form {
    background: var(--bg);
    border: 1px solid var(--border);
    padding: 25px 30px;
    border-radius: var(--radius);
    width: 100%;
    max-width: 420px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin: 2rem auto;
}

label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 14px;
}

input[type="text"],
input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 14px;
    font-family: inherit;
    transition: border-color var(--transition);
}
input[type="text"]:focus,
input[type="file"]:focus {
    border-color: var(--primary-dark);
    outline: none;
}

input[type="submit"] {
    background: var(--primary);
    color: #fff;
    margin-top: 18px;
    padding: 12px 18px;
    cursor: pointer;
    border: none;
    border-radius: var(--radius);
    font-size: 15px;
    font-weight: bold;
    transition: background var(--transition);
}
input[type="submit"]:hover {
    background: var(--primary-dark);
}

.error {
    color: var(--danger);
    margin-top: 10px;
    font-size: 13px;
    font-weight: 500;
}

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
