<?php
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
    SELECT t.*, u.username as created_by_name
    FROM tasks t
    JOIN users u ON t.created_by = u.id
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
    SELECT u.id, u.username 
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