<?php
require_once 'config.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error = 'Project name is required.';
    } else {
        try {
            $db = getDB();
            $user_id = getCurrentUserId();
            
            // Insert project
            $stmt = $db->prepare("INSERT INTO projects (name, description, owner_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $user_id]);
            
            $project_id = $db->lastInsertId();
            
            // Add owner as a member
            $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id) VALUES (?, ?)");
            $stmt->execute([$project_id, $user_id]);
            
            header('Location: project.php?id=' . $project_id);
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to create project. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Project - Project Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h2>Project Management</h2>
            <div>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="form-container">
            <h1>Create New Project</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Project Name:</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo h($_POST['name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4"><?php echo h($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Project</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
