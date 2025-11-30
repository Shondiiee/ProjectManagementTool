<?php
/**
* Main Dashboard Page
*This page displays all projects that the logged-in user is a member of or owns.
*/

require_once 'config.php';
requireLogin();

$db = getDB();
$user_id = getCurrentUserId();

// Get all projects where user is a member or owner
$stmt = $db->prepare("
    SELECT DISTINCT p.*, u.username as owner_name,
           CASE WHEN p.owner_id = ? THEN 1 ELSE 0 END as is_owner
    FROM projects p
    JOIN users u ON p.owner_id = u.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE p.owner_id = ? OR pm.user_id = ?
    ORDER BY p.created_at DESC
");

$stmt->execute([$user_id, $user_id, $user_id]);
$projects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Project Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <h2>Project Management</h2>
            <div>
                <span>Welcome, <?php echo h($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="dashboard-header">
            <h1>My Projects</h1>
            <a href="create_project.php" class="btn btn-primary">+ Create New Project</a>
        </div>
        
        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <p>You don't have any projects yet.</p>
                <a href="create_project.php" class="btn btn-primary">Create Your First Project</a>
            </div>
        <?php else: ?>
        <!-- Display all projects on Projects Grid -->
            <div class="projects-grid">
                <?php foreach ($projects as $project): ?>
                    <div class="project-card">
                        <h3><?php echo h($project['name']); ?></h3>
                        <p><?php echo h($project['description']); ?></p>
                        <div class="project-meta">
                            <span class="badge">
                                <?php echo $project['is_owner'] ? 'Owner' : 'Member'; ?>
                            </span>
                            <span>by <?php echo h($project['owner_name']); ?></span>
                        </div>
                        <a href="project.php?id=<?php echo $project['id']; ?>" class="btn btn-primary">
                            View Project
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
