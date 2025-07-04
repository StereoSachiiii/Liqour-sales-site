



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin login</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <div class="main-box" id="mainBox">


   
    <div class="form-box sign-in-box">
      <form action="../Backend/process-login-admin.php" method="POST">

       <h1>Sign In</h1>
        <p>Get back to your shopping!</p>
        <input type="username" name="username"  placeholder="Username" />
        <input type="password" name="password" placeholder="Password" />       
        <button class="sign-in-button">Sign In</button>

      </form>     
   </div>

    <div class="switch-panel">
      <div class="panel panel-right">
        <h1>Are you new to our service?<h1>
          <h2>No worries! you can sign up here!</h2>
        <p>Join us and enjoy exclusive liquor deals</p>
        <button class="ghost-btn" id="signUp">Sign Up</button>
      </div>
    </div>
  </div>

</body>
</html>
