<?php
include('session.php');
include("../Backend/sql-config.php");
$loggedIn = !$isGuest;

// If no session, show sign-up/login prompt



// Handle AJAX edits
if($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])){
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if($action === 'edit_field'){
        $field = $_POST['field'];
        $value = trim($_POST['value']);
        $password = $_POST['password'] ?? '';
        $allowedFields = ['name','email','phone','address'];

        if(!in_array($field,$allowedFields)){
            echo json_encode(['success'=>false,'message'=>'Invalid field']);
            exit();
        }

        if(empty($value) && ($field==='name'||$field==='email')){
            echo json_encode(['success'=>false,'message'=>ucfirst($field).' cannot be empty']);
            exit();
        }

        // Verify current password
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id=? AND is_active=1");
        $stmt->bind_param("i",$userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if(!$res || !password_verify($password,$res['password_hash'])){
            echo json_encode(['success'=>false,'message'=>'Incorrect password']);
            exit();
        }

        $stmt = $conn->prepare("UPDATE users SET $field=? WHERE id=? AND is_active=1");
        $stmt->bind_param("si",$value,$userId);
        if($stmt->execute()){
            echo json_encode(['success'=>true,'message'=>ucfirst($field).' updated','value'=>htmlspecialchars($value)]);
        }else{
            echo json_encode(['success'=>false,'message'=>'Database error']);
        }
        $stmt->close();
        exit();
    }

    if($action === 'change_password'){
        $current = $_POST['current'] ?? '';
        $newpw = $_POST['new'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if($newpw !== $confirm){
            echo json_encode(['success'=>false,'message'=>'New password and confirm do not match']);
            exit();
        }

        // Verify current password
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id=? AND is_active=1");
        $stmt->bind_param("i",$userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if(!$res || !password_verify($current,$res['password_hash'])){
            echo json_encode(['success'=>false,'message'=>'Incorrect current password']);
            exit();
        }

        $newHash = password_hash($newpw,PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=? AND is_active=1");
        $stmt->bind_param("si",$newHash,$userId);
        if($stmt->execute()){
            echo json_encode(['success'=>true,'message'=>'Password changed successfully']);
        }else{
            echo json_encode(['success'=>false,'message'=>'Database error']);
        }
        $stmt->close();
        exit();
    }

    if($action==='upload_pic' && isset($_FILES['profile_pic'])){
        $file = $_FILES['profile_pic'];
        if($file['error'] === 0){
            $ext = pathinfo($file['name'],PATHINFO_EXTENSION);
            $allowed = ['jpg','jpeg','png','webp'];
            if(!in_array(strtolower($ext),$allowed)){
                echo json_encode(['success'=>false,'message'=>'Invalid file type']);
                exit();
            }
            
            // Create uploads directory if it doesn't exist
            $uploadDir = "uploads/";
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Get current profile pic before updating
            $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id=? AND is_active=1");
            $stmt->bind_param("i",$userId);
            $stmt->execute();
            $currentUser = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            $dest = $uploadDir."profile_$userId.".time().".$ext";
            
            if(move_uploaded_file($file['tmp_name'],$dest)){
                // Delete old profile picture if it exists
                if(!empty($currentUser['profile_pic']) && file_exists($currentUser['profile_pic'])){
                    unlink($currentUser['profile_pic']);
                }
                
                $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
                $stmt->bind_param("si",$dest,$userId);
                if($stmt->execute()){
                    echo json_encode(['success'=>true,'message'=>'Profile picture updated','url'=>$dest]);
                }else{
                    echo json_encode(['success'=>false,'message'=>'Database update failed']);
                }
                $stmt->close();
            }else{
                echo json_encode(['success'=>false,'message'=>'File upload failed']);
            }
            exit();
        }else{
            echo json_encode(['success'=>false,'message'=>'File upload error']);
            exit();
        }
    }

    // Delete review functionality
    if($action === 'delete_review'){
        $reviewId = intval($_POST['review_id']);
        $password = $_POST['password'] ?? '';

        // Verify current password
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id=? AND is_active=1");
        $stmt->bind_param("i",$userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if(!$res || !password_verify($password,$res['password_hash'])){
            echo json_encode(['success'=>false,'message'=>'Incorrect password']);
            exit();
        }

        // Check if review belongs to user
        $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE review_id=? AND user_id=? AND is_active=1");
        $stmt->bind_param("ii",$reviewId,$userId);
        $stmt->execute();
        if($stmt->get_result()->num_rows === 0){
            echo json_encode(['success'=>false,'message'=>'Review not found']);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // Soft delete the review
        $stmt = $conn->prepare("UPDATE reviews SET is_active=0 WHERE review_id=? AND user_id=?");
        $stmt->bind_param("ii",$reviewId,$userId);
        if($stmt->execute()){
            echo json_encode(['success'=>true,'message'=>'Review deleted successfully']);
        }else{
            echo json_encode(['success'=>false,'message'=>'Database error']);
        }
        $stmt->close();
        exit();
    }
}

// Fetch user info
if($loggedIn){
    $userRes = $conn->query("SELECT name,email,phone,address,profile_pic FROM users WHERE id=$userId AND is_active=1");
    $user = $userRes->fetch_assoc();

    $orderRes = $conn->query("SELECT COUNT(*) AS total_orders FROM orders WHERE user_id=$userId");
    $orderCount = $orderRes->fetch_assoc()['total_orders'];

    $reviewRes = $conn->query("
        SELECT r.review_id,r.rating,r.comment,l.name AS liquor_name
        FROM reviews r
        JOIN liqours l ON r.liqour_id = l.liqour_id
        WHERE r.user_id=$userId AND r.is_active=1
    ");
    
  
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - LiquorStore</title>

<link rel="stylesheet" href="css/profile.css">
<link rel="stylesheet" href="css/index.css">

</head>
<body>

<?php include('navbar.php') ?>

<!-- Back to Home Button -->
<div style="padding: 20px 40px;">
    <a href="index.php" class="back-to-home-btn">
        <span class="back-arrow">←</span>
        Back to Home
    </a>
</div>

<div class="profile-page">
<?php if($loggedIn): ?>
    <div class="profile-card">
        <h2>👤 Your Profile</h2>
       <img src="<?= $user['profile_pic'] ?>" 
     class="profile-pic" 
     id="profilePic" 
     alt="<?= !empty($user['profile_pic']) ? 'Profile Picture' : 'No Image' ?>">

        <input type="file" id="profileUpload" accept="image/*" style="display:none;">
        <button class="upload-btn" id="uploadBtn">Change Profile Picture</button>

        <?php foreach(['name','email','phone','address'] as $field): ?>
        <div class="profile-section" data-field="<?= $field ?>">
            <strong><?= ucfirst($field) ?>:</strong>
            <span class="field-value"><?= htmlspecialchars($user[$field]) ?: 'Not set' ?></span>
            <button class="edit-btn">Edit</button>
        </div>
        <?php endforeach; ?>

        <div class="profile-section">
            <strong>Total Orders:</strong> <?= $orderCount ?> - <a href="my-orders.php">View Orders</a>
        </div>

        <div class="profile-section" style="flex-direction:column;align-items:flex-start;">
            <strong>Your Reviews:</strong>
            <?php if($reviewRes->num_rows > 0): while($row = $reviewRes->fetch_assoc()): ?>
            <div class="review" data-review-id="<?= $row['review_id'] ?>">
                <div class="review-header">
                    <span class="review-title"><?= htmlspecialchars($row['liquor_name']) ?></span>
                    <button class="delete-review-btn" onclick="deleteReview(<?= $row['review_id'] ?>)">Delete</button>
                </div>
                <div class="review-rating">Rating: <?= $row['rating'] ?> / 5</div>
                <?php if($row['comment']): ?>
                <div class="review-comment">Comment: <?= htmlspecialchars($row['comment']) ?></div>
                <?php endif; ?>
            </div>
            <?php endwhile; else: ?>
            <p>No reviews yet.</p>
            <?php endif; ?>
        </div>

        <button class="edit-btn" id="changePasswordBtn">Change Password</button>
        <a class="edit-btn" href="delete_account.php">DELETE ACCOUNT</a> 
        <!-- softdelete -->
    </div>

<?php else: ?>
    <div class="profile-card">
        <h2>👤 You are not logged in</h2>
        <a href="public/login-signup.php"><button class="edit-btn">Sign Up / Login</button></a>
    </div>
<?php endif; ?>
</div>

<!-- Modal -->
<div class="modal" id="editModal">
    <div class="modal-content" id="modalContent">
        <h3 id="modalTitle">Edit</h3>
        <input type="text" id="modalInput" placeholder="Enter value">
        <input type="password" id="modalPassword" placeholder="Current Password">
        <div>
            <button class="save-btn" id="modalSave">Save</button>
            <button class="cancel-btn" id="modalCancel">Cancel</button>
        </div>
    </div>
</div>

<!-- Password Modal -->
<div class="modal" id="passwordModal">
    <div class="modal-content">
        <h3>Change Password</h3>
        <input type="password" id="currentPw" placeholder="Current Password">
        <input type="password" id="newPw" placeholder="New Password">
        <input type="password" id="confirmPw" placeholder="Confirm New Password">
        <div>
            <button class="save-btn" id="passwordSave">Change</button>
            <button class="cancel-btn" id="passwordCancel">Cancel</button>
        </div>
    </div>
</div>

<!-- Delete Review Modal -->
<div class="modal" id="deleteReviewModal">
    <div class="modal-content">
        <h3>Delete Review</h3>
        <p>Are you sure you want to delete this review? This action cannot be undone.</p>
        <input type="password" id="deleteReviewPassword" placeholder="Current Password">
        <div>
            <button class="save-btn" id="confirmDeleteReview">Delete Review</button>
            <button class="cancel-btn" id="cancelDeleteReview">Cancel</button>
        </div>
    </div>
</div>

<!-- LOGOUT MODAL -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to logout?</p>
    <button class="btn-primary" onclick="logoutNow()">Yes</button>
    <button class="btn-secondary" onclick="closeLogoutModal()">Cancel</button>
  </div>
</div>

<script>



const isGuest = <?= $isGuest ? 'true' : 'false' ?>;


// Profile edit modal
const modal = document.getElementById('editModal');
const modalTitle = document.getElementById('modalTitle');
const modalInput = document.getElementById('modalInput');
const modalPassword = document.getElementById('modalPassword');
const modalSave = document.getElementById('modalSave');
const modalCancel = document.getElementById('modalCancel');

let currentField = '';
let currentSpan = null;

document.querySelectorAll('.profile-section .edit-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        const section = btn.closest('.profile-section');
        currentField = section.dataset.field;
        currentSpan = section.querySelector('.field-value');

        modalTitle.textContent = `Edit ${currentField}`;
        modalInput.value = currentSpan.textContent.trim() === "Not set" ? "" : currentSpan.textContent.trim();
        modalInput.type = currentField==='email'?'email':(currentField==='phone'?'tel':'text');
        modalPassword.value = '';
        modal.style.display='block';
        modalInput.focus();
    });
});

modalCancel.addEventListener('click',()=>modal.style.display='none');

modalSave.addEventListener('click', async ()=>{
    const value = modalInput.value.trim();
    const pw = modalPassword.value.trim();
    if(!pw){ alert('Please enter your current password'); return; }

    const fd = new FormData();
    fd.append('action','edit_field');
    fd.append('field',currentField);
    fd.append('value',value);
    fd.append('password',pw);

    const res = await fetch('',{method:'POST',body:fd});
    const data = await res.json();
    if(data.success){ currentSpan.textContent = data.value || 'Not set'; }
    else{ alert(data.message); }
    modal.style.display='none';
});

// Password change modal
const pwModal = document.getElementById('passwordModal');
const changePwBtn = document.getElementById('changePasswordBtn');
const pwSave = document.getElementById('passwordSave');
const pwCancel = document.getElementById('passwordCancel');

changePwBtn?.addEventListener('click',()=>{pwModal.style.display='block';});
pwCancel.addEventListener('click',()=>pwModal.style.display='none');
document.addEventListener('click',(e)=>{
    if(pwModal.style.display==='flex'&& pwModal.contains(e.target)){
        pwModal.style.display="none"
    }
    
})
pwSave.addEventListener('click', async ()=>{
    const current = document.getElementById('currentPw').value;
    const newPw = document.getElementById('newPw').value;
    const confirmPw = document.getElementById('confirmPw').value;
    if(!current||!newPw||!confirmPw){alert('Fill all fields'); return;}

    const fd = new FormData();
    fd.append('action','change_password');
    fd.append('current',current);
    fd.append('new',newPw);
    fd.append('confirm',confirmPw);

    const res = await fetch('',{method:'POST',body:fd});
    const data = await res.json();
    alert(data.message);
    if(data.success){ pwModal.style.display='none'; document.getElementById('currentPw').value=''; document.getElementById('newPw').value=''; document.getElementById('confirmPw').value='';}
});

// Delete review functionality
const deleteReviewModal = document.getElementById('deleteReviewModal');
const deleteReviewPassword = document.getElementById('deleteReviewPassword');
const confirmDeleteReview = document.getElementById('confirmDeleteReview');
const cancelDeleteReview = document.getElementById('cancelDeleteReview');
let currentReviewId = null;

function deleteReview(reviewId) {
    currentReviewId = reviewId;
    deleteReviewPassword.value = '';
    deleteReviewModal.style.display = 'block';
    deleteReviewPassword.focus();
}

cancelDeleteReview.addEventListener('click', () => {
    deleteReviewModal.style.display = 'none';
    currentReviewId = null;
});

confirmDeleteReview.addEventListener('click', async () => {
    const password = deleteReviewPassword.value.trim();
    if (!password) {
        alert('Please enter your current password');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'delete_review');
    fd.append('review_id', currentReviewId);
    fd.append('password', password);

    try {
        const res = await fetch('', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            // Remove the review element from DOM
            const reviewElement = document.querySelector(`[data-review-id="${currentReviewId}"]`);
            if (reviewElement) {
                reviewElement.remove();
            }
            deleteReviewModal.style.display = 'none';
            alert(data.message);
            
            // Check if no reviews left
            const remainingReviews = document.querySelectorAll('[data-review-id]');
            if (remainingReviews.length === 0) {
                const reviewSection = document.querySelector('.profile-section:last-of-type');
                const noReviewsMsg = document.createElement('p');
                noReviewsMsg.textContent = 'No reviews yet.';
                reviewSection.appendChild(noReviewsMsg);
            }
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('An error occurred while deleting the review');
        console.error('Error:', error);
    }
    
    currentReviewId = null;
});

// Close modals on outside click
window.addEventListener('click',e=>{
    if(e.target===modal) modal.style.display='none';
    if(e.target===pwModal) pwModal.style.display='none';
    if(e.target===deleteReviewModal) deleteReviewModal.style.display='none';
});

// Profile picture upload
const uploadBtn = document.getElementById('uploadBtn');
const profileUpload = document.getElementById('profileUpload');
const profilePic = document.getElementById('profilePic');

uploadBtn.addEventListener('click',()=>profileUpload.click());

// Allow clicking on profile pic to change it
profilePic.addEventListener('click',()=>profileUpload.click());

profileUpload.addEventListener('change', async ()=>{
    if(profileUpload.files.length===0) return;
    
    // Show loading state
    uploadBtn.textContent = 'Uploading...';
    uploadBtn.disabled = true;
    
    const fd = new FormData();
    fd.append('action','upload_pic');
    fd.append('profile_pic',profileUpload.files[0]);

    try {
        const res = await fetch('',{method:'POST',body:fd});
        const data = await res.json();
        
        if(data.success) { 
            profilePic.src = data.url + '?t=' + Date.now(); // Add timestamp to force reload
            alert(data.message);
        } else { 
            alert(data.message); 
        }
    } catch(error) {
        alert('Upload failed. Please try again.');
        console.error('Upload error:', error);
    } finally {
        // Reset button state
        uploadBtn.textContent = 'Change Profile Picture';
        uploadBtn.disabled = false;
        profileUpload.value = ''; // Clear file input
    }
});
// Profile dropdown toggle
const profileContainer = document.querySelector(".profile-container");
const profileDropdown = document.querySelector(".profile-expand");
let dropdownOpen = false;

profileContainer.addEventListener("click", e => {
    e.stopPropagation(); // prevent document click from closing immediately
    dropdownOpen = !dropdownOpen;
    profileDropdown.classList.toggle("profile-expand-active", dropdownOpen);
});

// Close dropdown when clicking outside
document.addEventListener("click", () => {
    if (dropdownOpen) {
        dropdownOpen = false;
        profileDropdown.classList.remove("profile-expand-active");
    }
});

// Close dropdown on Escape key
document.addEventListener("keydown", e => {
    if (e.key === "Escape" && dropdownOpen) {
        dropdownOpen = false;
        profileDropdown.classList.remove("profile-expand-active");
    }
});

//logout
function showLogoutModal(){ if(isGuest){ window.location.href='login-signup.php'; } else { document.getElementById('logoutModal').style.display='flex'; } }
function closeLogoutModal(){ document.getElementById('logoutModal').style.display='none'; }
function logoutNow(){ window.location.href="../Backend/auth/logout.php"; }
window.addEventListener('click',(e)=>{ ['logoutModal','guest-login-modal','payment-modal'].forEach(id=>{ if(e.target===document.getElementById(id)) document.getElementById(id).style.display='none'; }); });


</script>

</body>
</html>