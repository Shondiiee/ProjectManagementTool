<?php
/** 
 * Logout Handler
 * 
 * This script handles user logout by destroying the session and all session data.
 * It demontrates prper session management and security best practices for logout.
 */

session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (isset($_COOKIE[session_name()])){
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session

session_destroy();

header("Location: login.php");
exit;
?>

<?php
if(isLoggedIn()){
    header('Location: dashboard.php');
    exit;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
}
if(empty($username || empty($password)){
    $error = 'Please enter both username and password.';
} else {
    try{
        $db=getDB();
        $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username=?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    }
})