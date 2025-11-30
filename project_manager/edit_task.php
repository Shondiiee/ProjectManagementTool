<?php
/**
*Edit Task Page 
*This page allows project members to edit the existing tasks.
*/
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
$stmt = $db->prepare("
    SELECT u.id,u.username
    FROM users u
    JOIN project_members pm ON u.id = pm.user_id
    WHERE pm.project_id = ?
    ORDER BY u.username
");
$stmt->execute([$task['project_id']]);
$members = $stmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'To-Do';
    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    
    if (empty($title)) {
        $error = 'Task title is required.';
    } else {
        try {
            $stmt = $db->prepare("UPDATE tasks SET title = ?, description = ?, status = ?, assigned_to = ?, due_date = ? WHERE id = ?");
            $stmt->execute([$title, $description, $status,$assigned_to,$due_date, $task_id]);
            
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
                <div class = "form-group">
                    <label for = "assigned_to">Assign To:</label>
                    <select id="assigned_to" name ="assigned_to">
                        <option value ="">Unassigned</option>
                        <?php
                        $current_assigned = $_POST['assigned_to'] ?? $task['assigned_to'];
                        foreach ($members as $member):
                            ?>
                                <option value = "<?php echo $member['id']; ?>"
                                    <?php echo $member['id'] == $current_assigned ? 'selected' : ''; ?>>
                                  <?php echo h($member['username']); ?>
                                </option>
                            <?php endforeach; ?>
                    </select>
                </div>
                <div class = "form-group">
                    <label for="due_date">Due Date (Optional):</label>
                    <input type = "date" id="due_date" name = "due_date"
                        value = "<?php echo h($_POST['due_date'] ?? $task['due_date'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Update Task</button>
                <a href="project.php?id=<?php echo $task['project_id']; ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
