
<?php if(empty($_SERVER['HTTPS'])||$_SERVER["HTTPS"]=='off'){
    $secureUrl="https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
header(
"Location:$secureUrl"
);} ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login</title>

  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #111;
      color: #f5f5f5;
      font-family: Arial, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .main-box {
      background-color:white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(212, 175, 55, 0.2);
      width: 100%;
      max-width: 400px;
    }

    h1 {
      color: #d4af37;
      margin-bottom: 10px;
      text-align: center;
    }

    p {
      text-align: center;
      color: #aaa;
      margin-bottom: 20px;
    }

    input {
      width: 100%;
      padding: 10px 12px;
      margin-bottom: 15px;
      border: none;
      border-radius: 5px;
      background-color: #2b2b2b;
      color: #f5f5f5;
      font-size: 14px;
      
    }

    input::placeholder {
      color: #888;
    }

    .sign-in-button {
      width: 100%;
      padding: 10px;
      background-color: #d4af37;
      color: #000;
      font-weight: bold;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      
    }

    .sign-in-button:hover {
      background-color: #c19d2b;
    }
    form{
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }
  </style>
</head>
<body>

  <div class="main-box">
    <h1>Admin Login</h1>
    <p>Welcome back, admin.</p>
    <form action="../Backend/process-login.php" method="POST">      
      <input type="text" name="username" placeholder="Username" required />
      <input type="password" name="password" placeholder="Password" required />
      <button class="sign-in-button">Sign In</button>
    </form>
  </div>

</body>
</html>
