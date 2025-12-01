<?php
/**
 * Invite Member to Project Page
 * 
 * This page allows project owners to invite other registered users to join
 * their projects. It demonstrates owner-only authorization, user lookup by
 * username or email, and preventing duplicate memberships.
 */

require_once 'config.php';

requireLogin();

$project_id = $_GET['project_id'] ?? 0;
$user_id = getCurrentUserId();
$db = getDB();

$stmt = $db->prepare("SELECT name FROM projects WHERE id = ? AND owner_id = ?");
$stmt->execute([$project_id, $user_id]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $identifier = trim($_POST['identifier'] ?? '');
    
    if (empty($identifier)) {
        $error = 'Please enter a username or email.';
    } else {
       
        try {
       
            $stmt = $db->prepare("SELECT id, username FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$identifier, $identifier]);
            $invite_user = $stmt->fetch();
            
            if (!$invite_user) {
                $error = 'User not found.';
            } elseif ($invite_user['id'] == $user_id) {
            
                $error = 'You are already the owner of this project.';
            } else {
               
                $stmt = $db->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
                $stmt->execute([$project_id, $invite_user['id']]);
                
                if ($stmt->fetch()) {
                  
                    $error = 'This user is already a member of the project.';
                } else {
                  
                    $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id) VALUES (?, ?)");
                    $stmt->execute([$project_id, $invite_user['id']]);
                    
                    $success = 'Successfully added ' . h($invite_user['username']) . ' to the project!';
                }
            }
        } catch (PDOException $e) {
           
            $error = 'Failed to invite member. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invite Member - Project Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
   
    <nav class="navbar">
        <div class="container">
            <h2>Project Management</h2>
            <div>
                <!-- Return to project page -->
                <a href="project.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">Back to Project</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="form-container">
            <h1>Invite Member to Project</h1>
            <!-- Show project name for context -->
            <h3>Project: <?php echo h($project['name']); ?></h3>
            
            <?php 
          
            if ($error): 
            ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <?php 
            if ($success): 
            ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
           
            <form method="POST" action="">
                <div class="form-group">
                    <label for="identifier">Username or Email:</label>
                   
                    <input type="text" id="identifier" name="identifier" required 
                           placeholder="Enter username or email"
                           value="<?php echo h($_POST['identifier'] ?? ''); ?>">
                </div>
                
             
                <button type="submit" class="btn btn-primary">Invite Member</button>
                <a href="project.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
