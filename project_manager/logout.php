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
if(empty($username) || empty($password)){
    $error = 'Please enter both username and password.';
} else {
    try{
        $db=getDB();
        $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username=?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    }
    if($user && password_verify($password, $uder['password_hash'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}catch(PDOException $e){
    $error = 'Login failed. Please try again later.';
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Project Management</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
<div class="auth-box">
<h1>Login</h1>

<?php if($error): ?>
    <div class="alert alert-error"><?php echo h($error); ?></div>
<?php endif; ?>