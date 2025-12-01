<?php
/**
*Delete Task Handler
* This script handles task deletion. It shows secure deletion with access control, minimal output and proper redirects.
*/
require_once 'config.php';
requireLogin();

$task_id = $_GET['id'] ?? 0;
$user_id = getCurrentUserId();
$db = getDB();

// Get task and verify user is a member of the project
$stmt = $db->prepare("
    SELECT t.project_id
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE t.id = ? AND (p.owner_id = ? OR pm.user_id = ?)
");
$stmt->execute([$task_id, $user_id, $user_id]);
$task = $stmt->fetch();

if ($task) {
    try {
        // Delete the task
        $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
    } catch (PDOException $e) {
        // Handle error silently and redirect
    }
    
    header('Location: project.php?id=' . $task['project_id']);
} else {
    header('Location: dashboard.php');
}
exit;
?>
