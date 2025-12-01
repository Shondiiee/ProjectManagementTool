<?php
/**
 * Project Details Page
 * 
 * Displays tasks in a Kanban board layout. 
 * Demonstrates complex SQL queries with multiple JOINS, access control, data aggregation, and dynamic UI generation
 * based on task status.
 */


require_once 'config.php';


requireLogin();
$project_id = $_GET['id'] ?? 0;
$user_id = getCurrentUserId();
$db = getDB();

$stmt = $db->prepare("
    SELECT p.*, u.username as owner_name, 
        CASE WHEN p.owner_id = ? THEN 1 ELSE 0 END as is_owner 
    FROM projects p
    JOIN users u ON p.owner_id = u.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE p.id = ? AND (p.owner_id = ? OR pm.user_id = ?)
");
$stmt->execute([$user_id, $project_id, $user_id, $user_id]);
$project = $stmt->fetch();

if(!$project){
    header("Location: dashboard.php");
    exit;
}

$stmt = $db->prepare("
    SELECT t.*, u.username as created_by_name, ua.username as assigned_to_name
    FROM tasks t
    JOIN users u ON t.created_by = u.id
    LEFT JOIN users ua ON t.assigned_to = ua.id
    WHERE t.project_id = ?
    ORDER BY t.created_at DESC");

$stmt->execute([$project_id]);
$all_tasks = $stmt->fetchAll();

$tasks = [
    'To-Do' => [],
    'In Progress' => [],
    'Done' => []
];

foreach($all_tasks as $task){
    $tasks[$task['status']][] = $task;
}

$stmt = $db->prepare("
    SELECT u.id, u.username, u.email
    FROM users u
    JOIN project_members pm ON u.id = pm.user_id
    WHERE pm.project_id = ?
");
$stmt->execute([$project_id]);
$members = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($project['name']); ?> - Project Management</title>
    <link rel="stylesheet" href="styles.css">
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
        <div class="project-header">
            <div>
                <h1><?php echo h($project['name']); ?></h1>
                <p><?php echo h($project['description']); ?></p>
                <p class="project-owner">Owner: <?php echo h($project['owner_name']); ?></p>
            </div>
            <div class="project-actions">
                <a href="create_task.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                    + Add Task
                </a>
                
                <?php if ($project['is_owner']): ?>
                    <a href="invite_member.php?project_id=<?php echo $project_id; ?>" class="btn btn-secondary">
                        Invite Member
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="members-section">
            <h3>Team Members (<?php echo count($members); ?>)</h3>
            <div class="members-list">
                <?php foreach ($members as $member): ?>
                    <span class="member-badge"><?php echo h($member['username']); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="tasks-board">
            <?php foreach (['To-Do', 'In Progress', 'Done'] as $status): ?>
                <div class="task-column">
                    <h2><?php echo h($status); ?> (<?php echo count($tasks[$status]); ?>)</h2>
                    <div class="task-list">
                        <?php if (empty($tasks[$status])): ?>
                            <p class="empty-column">No tasks</p>
                        <?php else: ?>
                            <?php foreach ($tasks[$status] as $task): ?>
                                <div class="task-card">
                                    <h3><?php echo h($task['title']); ?></h3>
                                    <p><?php echo h($task['description']); ?></p>
                                    <?php if ($task['assigned_to_name']): ?>
                                        <div class="task-assigned">
                                            Assigned to: <strong><?php echo h($task['assigned_to_name']); ?></strong>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($task['due_date']): ?>
                                        <div class="task-due-date">
                                            Due: <strong><?php echo date('M d, Y', strtotime($task['due_date'])); ?></strong>
                                            <?php
                                                $today = date('Y-m-d');
                                                $due = $task['due_date'];
                                                if($due < $today && $task['status'] != 'Done'){
                                                    echo '<span class="overdue-badge">Overdue</span>';
                                                }
                                                ?>
                                                </div>
                                    <?php endif; ?>
                                    <div class="task-meta">
                                        <small>by <?php echo h($task['created_by_name']); ?></small>
                                    </div>
                                    <div class="task-actions">
                                        <a href="view_task.php?id=<?php echo $task['id']; ?>" class="btn-small">View</a>
                                        <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn-small">Edit</a>
                                        <a href="delete_task.php?id=<?php echo $task['id']; ?>" 
                                           class="btn-small btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this task?')">
                                            Delete
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
