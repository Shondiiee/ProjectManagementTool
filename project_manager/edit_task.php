<?php
require_once 'config.php';
requireLogin();

$task_id = $_GET['id'] ?? 0;
$user_id = getCurrentUserId();
$db = getDB();

// Get task and verify user is a member of the project
$stmt = $db->prepare("
    SELECT t.*, p.name as project_name, p.id as project_id
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE t.id = ? AND (p.owner_id = ? OR pm.user_id = ?)
");
$stmt->execute([$task_id, $user_id, $user_id]);
$task = $stmt->fetch();

if (!$task) {
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
            $stmt = $db->prepare("UPDATE tasks SET title = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $description, $status, $task_id]);
            
            header('Location: project.php?id=' . $task['project_id']);
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to update task. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - Project Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h2>Project Management</h2>
            <div>
                <a href="project.php?id=<?php echo $task['project_id']; ?>" class="btn btn-secondary">Back to Project</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="form-container">
            <h1>Edit Task</h1>
            <h3>Project: <?php echo h($task['project_name']); ?></h3>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Task Title:</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo h($_POST['title'] ?? $task['title']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4"><?php echo h($_POST['description'] ?? $task['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <?php
                        $current_status = $_POST['status'] ?? $task['status'];
                        foreach (['To-Do', 'In Progress', 'Done'] as $status):
                        ?>
                            <option value="<?php echo h($status); ?>" 
                                    <?php echo $status === $current_status ? 'selected' : ''; ?>>
                                <?php echo h($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Task</button>
                <a href="project.php?id=<?php echo $task['project_id']; ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
