<?php
/**
* Task Detail with Comments
* This page displays complete task information and allows team members to add comments.
*/

require_once 'config.php';
requireLogin();

$task_id = $_GET['id'] ?? 0;
$user_id = getCurrentUserId();
$db = getDB();

// Get task details and verify user is a member
$stmt = $db->prepare("
    SELECT t.*, p.name as project_name, p.id as project_id,
           u.username as created_by_name,
           ua.username as assigned_to_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    JOIN users u ON t.created_by = u.id
    LEFT JOIN users ua ON t.assigned_to = ua.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE t.id = ? AND (p.owner_id = ? OR pm.user_id = ?)
");
$stmt->execute([$task_id, $user_id, $user_id]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: dashboard.php');
    exit;
}

// Handle comment submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment = trim($_POST['comment'] ?? '');
    
    if (empty($comment)) {
        $error = 'Comment cannot be empty.';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->execute([$task_id, $user_id, $comment]);
            $success = 'Comment added successfully!';
            
            // Refresh to show new comment
            header('Location: view_task.php?id=' . $task_id);
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to add comment. Please try again.';
        }
    }
}

// Get all comments for this task
$stmt = $db->prepare("
    SELECT c.*, u.username
    FROM task_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.task_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$task_id]);
$comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($task['title']); ?> - Project Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <h2>Project Management</h2>
            <div>
                <a href="project.php?id=<?php echo $task['project_id']; ?>" class="btn btn-secondary">Back to Project</a>
                <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="task-detail-container">
            <div class="task-detail-header">
                <h1><?php echo h($task['title']); ?></h1>
                <div class="task-detail-actions">
                    <a href="edit_task.php?id=<?php echo $task_id; ?>" class="btn btn-primary">Edit Task</a>
                </div>
            </div>
            <!-- Task Information Section -->
            <div class="task-detail-info">
                <div class="info-row">
                    <span class="info-label">Project:</span>
                    <span><?php echo h($task['project_name']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $task['status'])); ?>">
                        <?php echo h($task['status']); ?>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Created By:</span>
                    <span><?php echo h($task['created_by_name']); ?></span>
                </div>
                
                <?php if ($task['assigned_to_name']): ?>
                    <div class="info-row">
                        <span class="info-label">Assigned To:</span>
                        <span>👤 <?php echo h($task['assigned_to_name']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($task['due_date']): ?>
                    <div class="info-row">
                        <span class="info-label">Due Date:</span>
                        <span>
                            📅 <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                            <?php
                            $today = date('Y-m-d');
                            $due = $task['due_date'];
                            if ($due < $today && $task['status'] != 'Done') {
                                echo ' <span class="overdue-badge">⚠️ Overdue</span>';
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="task-description">
                <h3>Description</h3>
                <p><?php echo nl2br(h($task['description'])); ?></p>
            </div>
            
            <!-- Comments Section -->
            <div class="comments-section">
                <h3>Comments (<?php echo count($comments); ?>)</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo h($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo h($success); ?></div>
                <?php endif; ?>
                
                <!-- Add Comment Form -->
                <form method="POST" action="" class="comment-form">
                    <div class="form-group">
                        <textarea name="comment" rows="3" placeholder="Add a comment..." required></textarea>
                    </div>
                    <button type="submit" name="add_comment" class="btn btn-primary">Add Comment</button>
                </form>
                
                <!-- Comments List -->
                <div class="comments-list">
                    <?php if (empty($comments)): ?>
                        <p class="no-comments">No comments yet. Be the first to comment!</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <strong><?php echo h($comment['username']); ?></strong>
                                    <span class="comment-date">
                                        <?php echo date('M d, Y g:i A', strtotime($comment['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="comment-body">
                                    <?php echo nl2br(h($comment['comment'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
