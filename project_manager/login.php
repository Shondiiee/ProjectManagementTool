<?php
/**
 * User Login Handler
 * 
 * This script handles user authentication by using secure password verification.
 * It demonstrates proper session management, proper password validation using password_verify, and SQL injection prevention.
 */

require_once 'config.php';

//redirect if already logged in
if(isLoggedIn()){
    header('Location: dashboard.php');
    exit;
}

$error = '';

//login process
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
}
if(empty($username) || empty($password)){
    $error = 'Please enter both username and password.';
} else {
    try{
        $db=getDB();

        //fetch user by username
        $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username=?");
        $stmt->execute([$username]);

        //fetch user record
        $user = $stmt->fetch();

        //verify password
        if($user && password_verify($password, $user['password_hash'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } catch(PDOException $e){
        $error = 'Login failed. Please try again later.';
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

<form method="POST" action="">
    <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required value="<?php echo h($_POST['username']?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
    </div>

    <button type="submit" class="btn btn-primary">Login</button>
</form>
<p class="text-center">Don't have an account? <a href="register.php">Register here</a></p>
</div>
</div>
</body>
</html>
