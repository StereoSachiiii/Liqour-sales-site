<?php 
session_start(); 
include("sql-config.php");  

if (isset($_GET['id'])) {     
    $cid = $_GET['id'];      
    if (isset($_POST['name'])) {         
        $name = $_POST['name'];           
        
        $sql = "UPDATE `liqour_categories`                  
                SET name = ?                 
                WHERE liqour_category_id = ?";          
        
        $stmt = $conn->prepare($sql);         
        $stmt->bind_param('si', $name, $cid);         
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
    <form action="update-category.php?id=<?php echo $cid ?>" method="POST">
        <label for="name">Name</label>
        <input type="text" name="name">
        
        <input type="submit" value="SUBMIT">
    </form>
</body>
</html>