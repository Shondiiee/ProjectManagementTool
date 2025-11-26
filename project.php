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