<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
    $secureUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location:$secureUrl");
    exit();
}

session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login / Sign Up | Royal Liquor</title>
  <link rel="stylesheet" href="css/login-signup.css" />
</head>
<body>
  <!-- Guest access link -->


 <div class="main-box" id="mainBox">

  <!-- SIGN UP FORM -->
  <div class="form-box sign-up-box">
    <form action="../Backend/auth/signup.php" method="POST">
      <h1>Create Account</h1>
      <p>Sign up to unlock premium spirits, exclusive offers, and personalized recommendations.</p>

      <label for="name">Full Name</label>
      <input type="text" id="name" name="name" placeholder="John Doe" required />

      <label for="email">Email Address</label>
      <input type="email" id="email" name="email" placeholder="you@example.com" required />

      <label for="address">Shipping Address</label>
      <input type="text" id="address" name="address" placeholder="123 Main Street, City" required />

      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Create a strong password" required />

      <label for="phoneNumber">Mobile Number</label>
      <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="+94 77 123 4567" required />

      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />

      <button class="sign-up-button">Sign Up</button>
    </form>
  </div>

  <!-- SIGN IN FORM -->
  <div class="form-box sign-in-box">
    <form action="../Backend/auth/login.php" method="POST">
      <h1>Welcome Back!</h1>
      <p>Sign in to continue shopping</p>

      <label for="username">Username or Email</label>
      <input type="text" id="username" name="username" placeholder="Enter your username or email" required />

      <label for="passwordLogin">Password</label>
      <input type="password" id="passwordLogin" name="password" placeholder="Enter your password" required />

      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />

      <button class="sign-in-button">Sign In</button>

      <!-- Error message -->
      <p class="err">
        <?php
        if(isset($_SESSION['error'])){
            echo htmlspecialchars($_SESSION["error"]);
            unset($_SESSION['error']);
        }
        ?>
      </p>
    </form>
  </div>

 
  <!-- SLIDE PANEL -->
  <div class="switch-panel">
    <div class="panel panel-left">
      <h1>Already a Member?</h1>
      <p>Sign in to access your saved carts and exclusive offers.</p>
      <button class="ghost-btn" id="signIn">Sign In</button>
    </div>
    <div class="panel panel-right">
      <h1>New Here?</h1>
      <h2>No worries! Create your account</h2>
      <p>Join us and enjoy premium liquor deals and personalized recommendations.</p>
      <button class="ghost-btn" id="signUp">Sign Up</button>
    </div>
  </div>

</div>
 <!-- GUEST BUTTON -->
  <div class="guest">
    <a href="index.php" class="guest-btn">Continue as Guest 🍷</a>
  </div>


  
  <script>
    const mainBox = document.getElementById("mainBox");
    const signUpBtn = document.getElementById("signUp");
    const signInBtn = document.getElementById("signIn");

    // Slide animation
    signUpBtn.addEventListener("click", () => {
      mainBox.classList.add("active");
    });

    signInBtn.addEventListener("click", () => {
      mainBox.classList.remove("active");
    });
  </script>
</body>
</html>
