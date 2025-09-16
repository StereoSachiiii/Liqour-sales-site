<?php
session_start();
include('../sql-config.php');

if (!isset($_SESSION['userId'], $_SESSION['username'], $_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../adminlogin.php");
    exit();
}

if (isset($_POST['name'], $_POST['description'], $_FILES['image'], $_POST['price'], $_POST['category'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $desc = filter_input(INPUT_POST, "description", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $price = filter_input(INPUT_POST, "price", FILTER_VALIDATE_FLOAT);
    $category = filter_input(INPUT_POST, "category", FILTER_VALIDATE_INT);

    if (!$name || !$desc || $price === false || $category === false) {
        echo "Invalid input data.";
        exit;
    }

    $file = $_FILES['image'];
    $filename = basename($file["name"]);
    $tempName = $file['tmp_name'];
    $targetDirectory = "../../public/src/product-images/";

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "File upload error.";
        exit;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($tempName);
    if (!in_array($fileType, $allowedTypes)) {
        echo "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.";
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        echo "File too large. Maximum size is 5MB.";
        exit;
    }

    $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
    $uniqueFilename = uniqid() . '.' . $fileExtension;
    $to = $targetDirectory . $uniqueFilename;
    $imagePath = "src/product-images/" . $uniqueFilename;

    if (!move_uploaded_file($tempName, $to)) {
        echo "Failed to move file.";
        exit;
    }

    $sql = "INSERT INTO liqours (name, description, price, image_url, category_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $name, $desc, $price, $imagePath, $category);

    if ($stmt->execute()) {
        echo "SUCCESS";
    } else {
        echo "DB ERR: " . $stmt->error;
    }
    $stmt->close();
    exit;
}

$categories = $conn->query("SELECT liqour_category_id, name FROM liqour_categories WHERE is_active=1");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Liquor Product</title>
<style>
* { box-sizing: border-box; }

body {
    font-family: 'Inter', Arial, sans-serif;
    background: #f8f9fa;
    margin: 0;
    padding: 20px;
    min-height: 100vh;
    line-height: 1.6;
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.back-button {
    position: fixed;
    top: 20px;
    left: 20px;
    background-color: #6c757d;
    color: white;
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: 100;
}

.back-button:hover { background-color: #545b62; transform: translateY(-1px); }

.form-container {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    border: 1px solid #dee2e6;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    width: 100%;
    max-width: 400px;
    margin-top: 4rem;
}

.form-container h2 {
    text-align: center;
    margin-bottom: 1.5rem;
    color: #212529;
}

.form-group { margin-bottom: 1.25rem; }
label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #495057; }
input[type="text"], input[type="number"], select, input[type="file"] {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: #fff;
}

input[type="text"]:focus, input[type="number"]:focus, select:focus, input[type="file"]:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

input[type="submit"] {
    width: 100%;
    background: #212529;
    color: white;
    padding: 0.875rem 1.5rem;
    cursor: pointer;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.2s;
}

input[type="submit"]:hover { background: #343a40; transform: translateY(-1px); }
input[type="submit"]:active { transform: translateY(0); }

#message {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
}

.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Responsive */
@media (max-width: 480px) {
    .form-container { padding: 1.5rem; }
}
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<a href="../manage-dashboard.php#liqours" class="back-button">← Back to Dashboard</a>

<div class="form-container">
    <h2>Add Liquor Product</h2>
    <form id="formUpload" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" required>
        </div>
        <div class="form-group">
            <label>Price</label>
            <input type="number" name="price" step="0.01" min="0" required>
        </div>
        <div class="form-group">
            <label>Image</label>
            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category" required>
                <option value="">--Select Category--</option>
                <?php while($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($cat['liqour_category_id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <input type="submit" value="Add Product">
    </form>
    <div id="message"></div>
</div>

<script>
$("#formUpload").on("submit", function(e){
    e.preventDefault();
    let formData = new FormData(this);
    $("#message").html("").removeClass("error");

    $.ajax({
        url: 'add.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response){
            if(response.trim() === "SUCCESS") {
                window.location.href = "../manage-dashboard.php#liqours";
            } else {
                $("#message").html(response).addClass("error");
            }
        },
        error: function() {
            $("#message").html("An error occurred.").addClass("error");
        }
    });
});
</script>

</body>
</html>
