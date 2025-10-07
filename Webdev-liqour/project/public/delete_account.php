<?php
include('session.php');
include('../Backend/sql-config.php');

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper: Get or create deleted user
function getOrCreateDeletedUser($conn) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = 'deleted@system.local' AND name = 'Deleted User'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        return $result['id'];
    }
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, is_admin, is_active, created_at, updated_at) VALUES ('Deleted User', 'deleted@system.local', '', 0, 0, NOW(), NOW())");
    $stmt->execute();
    $deletedUserId = $conn->insert_id;
    $stmt->close();
    
    return $deletedUserId;
}

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    
    // Authentication check
    if (!isset($_SESSION['userId']) || !isset($_SESSION['isGuest']) || $_SESSION['isGuest']) {
        $error = 'You must be logged in to delete account.';
    }
    // CSRF check
    else if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token.';
    }
    // Validate confirmation
    else if (!isset($_POST['confirmation_text']) || strtolower(trim($_POST['confirmation_text'])) !== 'delete my account') {
        $error = 'Confirmation text does not match. Please type "DELETE MY ACCOUNT" exactly.';
    }
    // Validate checkboxes
    else if (!isset($_POST['understand1']) || !isset($_POST['understand2'])) {
        $error = 'Please acknowledge all consequences.';
    }
    else {
        // All validations passed - perform soft delete exactly like backend handler
        $userId = $_SESSION['userId'];
        
        try {
            $conn->begin_transaction();
            
            // Get counts for user feedback
            $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE user_id=?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $orderCount = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
            $stmt->close();

            $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM reviews WHERE user_id=?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $reviewCount = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
            $stmt->close();
            
            // Get user name
            $userName = $_SESSION['username'] ?? 'Unknown';
            
            // 1️⃣ Get or create the system deleted user
            $deletedUserId = getOrCreateDeletedUser($conn);
            
            // 2️⃣ Transfer orders to deleted user (if any)
            if ($orderCount > 0) {
                $stmt = $conn->prepare("UPDATE orders SET user_id=? WHERE user_id=?");
                $stmt->bind_param("ii", $deletedUserId, $userId);
                $stmt->execute();
                $stmt->close();
            }
            
            // 3️⃣ Transfer reviews to deleted user (if any)  
            if ($reviewCount > 0) {
                $stmt = $conn->prepare("UPDATE reviews SET user_id=? WHERE user_id=?");
                $stmt->bind_param("ii", $deletedUserId, $userId);
                $stmt->execute();
                $stmt->close();
            }
            
            // 4️⃣ Soft delete the user (mark inactive, keep name/email)
            $stmt = $conn->prepare("UPDATE users SET is_active=0, updated_at=NOW() WHERE id=?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
            
            // Store success message in session for redirect page
            $_SESSION['delete_success'] = "Account soft-deleted successfully. User marked inactive, $orderCount orders and $reviewCount reviews transferred to system. Account can be restored but will lose order/review history.";

            // Destroy session and redirect
            session_unset();
            session_destroy();

            // Redirect to login-signup page
            header('Location: login-signup.php?account_deleted=1');
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Account deletion error for user $userId: " . $e->getMessage());
            $error = 'Failed to delete account: Database error occurred.';
        }
    }
}

// Ensure user is logged in for page access (not guest)
if (!isset($_SESSION['userId']) || !isset($_SESSION['isGuest']) || $_SESSION['isGuest']) {
    header('Location: login-signup.php');
    exit();
}

// Get user's data counts for display
$userId = $_SESSION['userId'];
$userName = $_SESSION['username'] ?? 'User';

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE user_id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userOrderCount = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM reviews WHERE user_id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userReviewCount = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - LiquorStore</title>
    <link rel="stylesheet" href="css/delete_account.css">
    <style>
        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success-message {
            background: #efe;
            border: 1px solid #cfc;
            color: #060;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .progress-container {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #dc3545, #c82333);
            width: 0%;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
          
        <div class="content">
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="data-summary">
                <h3>Your Account Data</h3>
                <div class="data-item">
                    <span class="data-label">Account Holder</span>
                    <span class="data-value"><?php echo htmlspecialchars($userName); ?></span>
                </div>
                <div class="data-item">
                    <span class="data-label">Orders</span>
                    <span class="data-value"><?php echo $userOrderCount; ?></span>
                </div>
                <div class="data-item">
                    <span class="data-label">Reviews</span>
                    <span class="data-value"><?php echo $userReviewCount; ?></span>
                </div>
            </div>

            <div class="warning-box">
                <h3>What happens when you delete your account (Soft Delete):</h3>
                <ul class="warning-list">
                    <li>Your account will be marked inactive </li>
                    <li>Your <?php echo $userOrderCount; ?> order(s) will be unavailable</li>
                    <li>Your <?php echo $userReviewCount; ?> review(s) will be unavailable</li>
                    <li>You will be immediately logged out and cannot access this account</li>
                </ul>
            </div>

            <!-- Progress bar -->
            <div class="progress-container">
                <div class="progress-fill" id="progressFill"></div>
            </div>

            <form method="POST" action="" id="deleteForm" onsubmit="return confirmFinalDelete()">
                <div class="confirmation-steps">
                    <div class="step active" id="step1">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <span>Acknowledge the consequences</span>
                        </div>
                        <div class="checkbox-container">
                            <label class="checkbox-label">
                                <input type="checkbox" name="understand1" id="understand1" onchange="checkStep1()">
                                I understand that deleting my account will mark it inactive and this action transfers my data to system records.
                            </label>
                        </div>
                        <div class="checkbox-container">
                            <label class="checkbox-label">
                                <input type="checkbox" name="understand2" id="understand2" onchange="checkStep1()">
                                I understand that my orders and reviews will be transferred to system records and I will lose access to my history if restored.
                            </label>
                        </div>
                    </div>

                    <div class="step" id="step2">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <span>Final confirmation</span>
                        </div>
                        <div class="input-group">
                            <label for="confirmText">
                                Type "DELETE MY ACCOUNT" to confirm (case-insensitive):
                            </label>
                            <input 
                                type="text" 
                                id="confirmText" 
                                name="confirmation_text"
                                placeholder="Type here..."
                                oninput="checkStep2()"
                                autocomplete="off"
                                value="<?php echo isset($_POST['confirmation_text']) ? htmlspecialchars($_POST['confirmation_text']) : ''; ?>"
                            >
                        </div>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="delete_account">
                
                <div class="buttons">
                    <a href="profile.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-delete" id="deleteBtn" disabled>
                        Delete My Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let step1Complete = false;
        let step2Complete = false;

        function checkStep1() {
            const checkbox1 = document.getElementById('understand1');
            const checkbox2 = document.getElementById('understand2');
            
            step1Complete = checkbox1.checked && checkbox2.checked;
            
            if (step1Complete) {
                document.getElementById('step1').classList.add('completed');
                document.getElementById('step1').classList.remove('active');
                document.getElementById('step2').classList.add('active');
                updateProgress();
            } else {
                document.getElementById('step1').classList.remove('completed');
                document.getElementById('step1').classList.add('active');
                document.getElementById('step2').classList.remove('active');
                step2Complete = false;
                document.getElementById('confirmText').value = '';
                updateProgress();
            }
            
            updateDeleteButton();
        }

        function checkStep2() {
            const confirmText = document.getElementById('confirmText').value.toLowerCase().trim();
            step2Complete = confirmText === 'delete my account';
            
            const input = document.getElementById('confirmText');
            if (step2Complete) {
                input.classList.add('valid');
                document.getElementById('step2').classList.add('completed');
            } else {
                input.classList.remove('valid');
                document.getElementById('step2').classList.remove('completed');
            }
            
            updateProgress();
            updateDeleteButton();
        }

        function updateProgress() {
            let progress = 0;
            if (step1Complete) progress += 50;
            if (step2Complete) progress += 50;
            
            // Fixed: Check if element exists before accessing style
            const progressFill = document.getElementById('progressFill');
            if (progressFill) {
                progressFill.style.width = progress + '%';
            }
        }

        function updateDeleteButton() {
            const deleteBtn = document.getElementById('deleteBtn');
            if (step1Complete && step2Complete) {
                deleteBtn.disabled = false;
                deleteBtn.classList.add('enabled');
                deleteBtn.style.cursor = 'pointer';
            } else {
                deleteBtn.disabled = true;
                deleteBtn.classList.remove('enabled');
                deleteBtn.style.cursor = 'not-allowed';
            }
        }

        function confirmFinalDelete() {
            if (!step1Complete || !step2Complete) {
                alert('Please complete all confirmation steps');
                return false;
            }

            return confirm('Are you absolutely sure? This will soft delete your account (mark inactive and transfer data to system records). This action cannot be easily undone!');
        }

        // Initialize based on POST data (if form was submitted with errors)
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if (isset($_POST['understand1'])): ?>
                    document.getElementById('understand1').checked = true;
                <?php endif; ?>
                <?php if (isset($_POST['understand2'])): ?>
                    document.getElementById('understand2').checked = true;
                <?php endif; ?>
                checkStep1();
                checkStep2();
            });
        <?php endif; ?>

        // Prevent accidental page refresh
        window.addEventListener('beforeunload', function(e) {
            if (step1Complete || step2Complete) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>