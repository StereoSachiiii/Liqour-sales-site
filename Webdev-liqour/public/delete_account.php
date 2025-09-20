<?php
session_start();
include('../Backend/sql-config.php');

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle AJAX deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    header('Content-Type: application/json');
    
    // user is logged in
    if (!isset($_SESSION['userId']) || !isset($_SESSION['isGuest']) || $_SESSION['isGuest']) {
        die(json_encode(['status' => 'error', 'message' => 'You must be logged in to delete account.']));
    }
    
    $userId = $_SESSION['userId'];
    
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die(json_encode(['status' => 'error', 'message' => 'Invalid security token.']));
    }
    
    // Validate confirmation 
    if (!isset($_POST['confirmation_text']) || strtolower(trim($_POST['confirmation_text'])) !== 'delete my account') {
        die(json_encode(['status' => 'error', 'message' => 'Confirmation text does not match.']));
    }
    
    try {
        $conn->begin_transaction();
        
        // Soft-delete user: mark inactive a
        $stmt = $conn->prepare("
            UPDATE users 
            SET is_active = 0,
                name = 'Deleted User',
                email = CONCAT('deleted_', id, '@example.com'),
                address = NULL,
                phone = NULL,
                password_hash = NULL,
                profile_pic = NULL,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        // Soft-delete user orders
        $stmt = $conn->prepare("
            UPDATE orders
            SET is_active = 0,
                status = 'cancelled',
                updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        // Soft-delete order items
        $stmt = $conn->prepare("
            UPDATE order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            SET oi.is_active = 0
            WHERE o.user_id = ?
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        // Soft-delete user reviews
        $stmt = $conn->prepare("
            UPDATE reviews
            SET is_active = 0,
                comment = 'Deleted'
            WHERE user_id = ?
        ");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        // Destroy session
        session_unset();
        session_destroy();
        
        echo json_encode(['status' => 'success', 'message' => 'Account deleted successfully.']);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete account: '.$e->getMessage()]);
        exit();
    }
}

// Ensure user is logged in for page access
if (!isset($_SESSION['userId']) || !isset($_SESSION['isGuest']) || $_SESSION['isGuest']) {
    header('Location: login-signup.php');
    exit();
}

$userName = $_SESSION['userName'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - LiquorStore</title>
   
</head>
<body>
    <div class="delete-container">
        <div class="header">
            <div class="warning-icon">NOTICE</div>
            <h1>Delete Account</h1>
            <p>This action cannot be undone</p>
        </div>
        
        <div class="content">
            <div class="warning-box">
                <h3>What happens when you delete your account:</h3>
                <ul class="warning-list">
                    <li>Your personal information will be permanently deleted</li>
                    <li>All your orders will be cancelled and marked as inactive</li>
                    <li>Your reviews and comments will be removed</li>
                    <li>You will lose access to your order history</li>
                    <li>This action cannot be reversed or undone</li>
                </ul>
            </div>

            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>

            <form id="deleteForm">
                <div class="confirmation-steps">
                    <div class="step active" id="step1">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <span>Acknowledge the consequences</span>
                        </div>
                        <div class="checkbox-container">
                            <input type="checkbox" id="understand1" onchange="checkStep1()">
                            <label for="understand1" class="checkbox-label">
                                I understand that deleting my account will permanently remove all my data and this action cannot be undone.
                            </label>
                        </div>
                        <div class="checkbox-container">
                            <input type="checkbox" id="understand2" onchange="checkStep1()">
                            <label for="understand2" class="checkbox-label">
                                I understand that all my pending orders will be cancelled.
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
                                placeholder="Type here..."
                                oninput="checkStep2()"
                                autocomplete="off"
                            >
                        </div>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="delete_account">
                
                <div class="buttons">
                    <a href="profile.php" class="btn btn-cancel">Cancel</a>
                    <button type="button" class="btn btn-delete" id="deleteBtn" onclick="confirmDelete()">
                        Delete My Account
                    </button>
                </div>
            </form>

            <div class="loading hidden" id="loading">
                <div class="spinner"></div>
                <p>Deleting your account...</p>
            </div>
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
            
            document.getElementById('progressFill').style.width = progress + '%';
        }

        function updateDeleteButton() {
            const deleteBtn = document.getElementById('deleteBtn');
            if (step1Complete && step2Complete) {
                deleteBtn.classList.add('enabled');
                deleteBtn.style.cursor = 'pointer';
            } else {
                deleteBtn.classList.remove('enabled');
                deleteBtn.style.cursor = 'not-allowed';
            }
        }

        function confirmDelete() {
            if (!step1Complete || !step2Complete) {
                showToast('Please complete all confirmation steps', 'error');
                return;
            }

            if (!confirm('Are you absolutely sure? This action cannot be undone!')) {
                return;
            }

            // Show loading
            document.querySelector('.content > form').classList.add('hidden');
            document.getElementById('loading').classList.remove('hidden');

            // Prepare form data
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            formData.append('action', 'delete_account');
            formData.append('confirmation_text', document.getElementById('confirmText').value);

            // Send delete request
            fetch('delete-account.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('Account deleted successfully. Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch(error => {
                document.querySelector('.content > form').classList.remove('hidden');
                document.getElementById('loading').classList.add('hidden');
                showToast('Error: ' + error.message, 'error');
            });
        }

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

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