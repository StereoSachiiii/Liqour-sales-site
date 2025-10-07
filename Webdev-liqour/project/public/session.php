<?php session_start();

// Default: guest
$isGuest = true;
$username = 'Guest';
$userId = null;

// Check if user is logged in (not guest)
if (isset($_SESSION['userId']) && isset($_SESSION['username']) && 
    isset($_SESSION['isGuest']) && $_SESSION['isGuest'] === false) {
    $isGuest = false;
    $username = $_SESSION['username'];
    $userId = $_SESSION['userId'];
}

// Handle guest users
if ($isGuest) {
    if (!isset($_SESSION['guestId'])) {
        $_SESSION['isGuest'] = true;
        $_SESSION['guestId'] = 'guest_' . time() . '_' . rand(1000, 9999);
        $_SESSION['username'] = 'Guest';
    }
    $userId = $_SESSION['guestId'];
}
?>