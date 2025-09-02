<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("sql-config.php");

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
    $targetDirectory = "../src/";
    
   
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

    if (move_uploaded_file($tempName, $to)) {
        $imagePath = "src/" . $uniqueFilename;
        $sql = "INSERT INTO liqours (name, description, price, image_url, category_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param("ssssi", $name, $desc, $price, $imagePath, $category);

        if ($stmt->execute()) {
            echo "SUCCESS"; 
        } else {
            echo "DB ERR: " . $stmt->error;
        }
        $stmt->close();
        
    } else {
        echo "Failed to move file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Liquor Product</title>
    <style>
        * {
            color: black;
        }
        body {
            display: flex;
            justify-content: center;
            height: 100vh;
            align-items: center;
            background-color: gainsboro;
            font-family: Arial, sans-serif;
        }
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="number"], input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        #message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="form-container">
        <h2>Add Liquor Product</h2>
        <form id="formUpload" action="add-liqour.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" name="description" id="description" required>
            </div>
            
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" name="price" id="price" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="image">Image</label>
                <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category ID</label>
                <input type="number" name="category" id="category" min="1" required>
            </div>
            
            <input type="submit" value="Add Product">
        </form>
        
        <div id="message"></div>
    </div>

<script>
$("#formUpload").on("submit", function(e){
    e.preventDefault();
    let formData = new FormData(this);

    // Clear previous messages
    $("#message").html("").removeClass("error");

    $.ajax({
        url: 'add-liqour.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response){
            if(response.trim() === "SUCCESS") {
                window.location.href = "../Backend/manage-site.php";
            } else {
                $("#message").html(response).addClass("error");
            }
        },
        error: function() {
            $("#message").html("An error occurred while processing your request.").addClass("error");
        }
    });
});
</script>

</body>
</html>