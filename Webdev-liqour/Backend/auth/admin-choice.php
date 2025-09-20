<?php
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['login']) || $_SESSION['login'] !== 'success' || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../adminlogin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | Royal Liquor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #2b2b2b 0%, #5C5C5C 50%, #B8860B 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 500px;
            width: 100%;
            backdrop-filter: blur(10px);
        }

        .welcome-text {
            color: #2b2b2b;
            margin-bottom: 10px;
            font-size: 1.8rem;
            font-weight: 300;
        }

        .admin-name {
            color: #B8860B;
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(184, 134, 11, 0.2);
        }

        .question {
            color: #5C5C5C;
            font-size: 1.2rem;
            margin-bottom: 40px;
            line-height: 1.5;
        }

        .choice-buttons {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }

        .choice-btn {
            padding: 18px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .manage-btn {
            background: linear-gradient(45deg, #B8860B, #A0760A);
            color: white;
            box-shadow: 0 8px 25px rgba(184, 134, 11, 0.3);
        }

        .manage-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(184, 134, 11, 0.4);
            background: linear-gradient(45deg, #A0760A, #8B6508);
        }

        .visit-btn {
            background: linear-gradient(45deg, #5C5C5C, #404040);
            color: white;
            box-shadow: 0 8px 25px rgba(92, 92, 92, 0.3);
        }

        .visit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(92, 92, 92, 0.4);
            background: linear-gradient(45deg, #404040, #2b2b2b);
        }

        .choice-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .choice-btn:hover::before {
            left: 100%;
        }

        .logout-link {
            color: #B8860B;
            text-decoration: none;
            font-size: 0.95rem;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .logout-link:hover {
            background-color: rgba(184, 134, 11, 0.1);
            color: #A0760A;
        }

        .icon {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        @media (max-width: 600px) {
            .container {
                padding: 40px 30px;
                margin: 20px;
            }

            .welcome-text {
                font-size: 1.5rem;
            }

            .admin-name {
                font-size: 1.8rem;
            }

            .question {
                font-size: 1.1rem;
            }

            .choice-btn {
                padding: 16px 25px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="welcome-text">Hello Admin</h1>
        <h2 class="admin-name">
            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?>
        </h2>
        
        <p class="question">
            You seem to be an admin!<br>
            Do you want to view the Royal liqour Website?
            or view the Management dashboard?
            
        </p>
        
        <div class="choice-buttons">
            <a href="../manage-dashboard.php" class="choice-btn manage-btn">
                <span class="icon">⚙️</span>
                Manage the Site
            </a>
            
            <a href="../../public/index.php" class="choice-btn visit-btn">
                <span class="icon">🌐</span>
                Visit the Site
            </a>
        </div>
        
        <a href="logout.php" class="logout-link">
             Logout
        </a>
    </div>

    <script>
        // Add some interactive effects
        document.querySelectorAll('.choice-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Create ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    </script>

    <style>
        .ripple {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
</body>
</html>