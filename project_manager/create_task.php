<?php
require_once 'config.php';
requireLogin();

$project_id = $_GET['project_id'] ?? 0;
$user_id = getCurrentUserId();
$db = getDB();

// Verify user is a member of this project
$stmt = $db->prepare("
    SELECT p.name
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE p.id = ? AND (p.owner_id = ? OR pm.user_id = ?)
");
$stmt->execute([$project_id, $user_id, $user_id]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'To-Do';
    
    if (empty($title)) {
        $error = 'Task title is required.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO tasks (project_id, title, description, status, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$project_id, $title, $description, $status, $user_id]);
            
            header('Location: project.php?id=' . $project_id);
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to create task. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task - Project Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h2>Project Management</h2>
            <div>
                <a href="project.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">Back to Project</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="form-container">
            <h1>Create New Task</h1>
            <h3>Project: <?php echo h($project['name']); ?></h3>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Task Title:</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo h($_POST['title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4"><?php echo h($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="To-Do">To-Do</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Done">Done</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Task</button>
                <a href="project.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
