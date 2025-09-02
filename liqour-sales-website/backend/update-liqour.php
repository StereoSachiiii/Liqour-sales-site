<?php 
session_start(); 
include("sql-config.php");  

if (isset($_GET['id'])) {     
    $lid = $_GET['id'];      
    if (isset($_POST['name'], $_POST['price'], $_POST['category'], $_POST['imageUrl'])) {         
        $name = $_POST['name'];         
        $price = $_POST['price'];         
        $category = $_POST['category'];         
        $imageUrl = $_POST['imageUrl'];          
        
        $sql = "UPDATE `liqours`                  
                SET name = ?, price = ?, image_url = ?, category_id = ?                 
                WHERE liqour_id = ?";          
        
        $stmt = $conn->prepare($sql);         
        $stmt->bind_param('sisii', $name, $price, $imageUrl, $category, $lid);         
        $stmt->execute();          
        
        if ($stmt->affected_rows > 0) {             
            header("Location: ../Backend/manage-site.php");             
            exit();         
        } else {             
            echo '<p>didn\'t work</p>';         
        }     
    } 
} 
?> 

<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: white;
            color: black;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        
        form {
            border: 1px solid black;
            padding: 30px;
            background: white;
        }
        
        label {
            display: block;
            margin: 10px 0 5px 0;
        }
        
        input {
            width: 200px;
            padding: 8px;
            border: 1px solid black;
            background: white;
            color: black;
        }
        
        input[type="submit"] {
            background: black;
            color: white;
            margin-top: 15px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <form action="update-liqour.php?id=<?php echo $lid ?>" method="POST">
        <label for="name">Name</label>
        <input type="text" name="name">
        
        <label for="price">Price</label>
        <input type="number" name="price">
        
        <label for="category">Category</label>
        <input type="number" name="category">
        
        <label for="imageUrl">Image</label>
        <input type="text" name="imageUrl">
        
        <input type="submit" value="SUBMIT">
    </form>
</body>
</html>