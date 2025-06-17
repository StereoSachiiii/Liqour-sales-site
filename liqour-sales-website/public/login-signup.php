<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | Royal Liquor</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>

  <div class="main-box" id="mainBox">

    <div class="form-box sign-up-box">
      <form action="../Backend/process-signup.php" method="POST">

         <h1>Create Account</h1>
        <p>Register to unlock premium spirits</p>
        <input type="text" name="name" placeholder="Full Name" />
        <input type="password"  name="password" placeholder="password" />
        <input type="password"  name = "password" placeholder=" Confirm Password" />
        <button>Sign Up</button>

      </form>     
    </div>

   
    <div class="form-box sign-in-box">
      <form action="../Backend/process-login.php" method="POST">

       <h1>Sign In</h1>
        <p>Get back to your shopping!</p>
        <input type="username" name="username"  placeholder="Username" />
        <input type="password" name="password" placeholder="Password" />       
        <button class="sign-in-button">Sign In</button>

      </form>     
   </div>

    <div class="switch-panel">
      <div class="panel panel-left">
        <h1>Already have an account?</h1>
        <p>Enter your details to continue exploring</p>
        <button class="ghost-btn" id="signIn">Sign In</button>
      </div>
      <div class="panel panel-right">
        <h1>Are you new to our service?<h1>
          <h2>No worries! you can sign up here!</h2>
        <p>Join us and enjoy exclusive liquor deals</p>
        <button class="ghost-btn" id="signUp">Sign Up</button>
      </div>
    </div>
  </div>

  <script>
    const mainBox = document.getElementById("mainBox");
    const signUpBtn = document.getElementById("signUp");
    const signInBtn = document.getElementById("signIn");

    signUpBtn.addEventListener("click", () => {
      mainBox.classList.add("active");
    });

    signInBtn.addEventListener("click", () => {
      mainBox.classList.remove("active");
    });
  </script>
</body>
</html>
