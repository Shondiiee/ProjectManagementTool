<?php
/**
 * Invite Member to Project Page
 * 
 * This page allows project owners to invite other registered users to join
 * their projects. It demonstrates owner-only authorization, user lookup by
 * username or email, and preventing duplicate memberships.
 */

// Include configuration and helper functions
require_once 'config.php';

/**
 * Enforce Authentication
 */
requireLogin();

/**
 * Get Project ID from URL
 * 
 * This page is accessed via: invite_member.php?project_id=5
 */
$project_id = $_GET['project_id'] ?? 0;
$user_id = getCurrentUserId();
$db = getDB();

/**
 * Verify User is Project Owner
 * 
 * This query checks if the current user is the OWNER of this project.
 * Unlike other pages where members can perform actions, only the owner
 * can invite new members.
 * 
 * Query breakdown:
 * - SELECT name: We only need the project name for display
 * - WHERE p.id = ? AND owner_id = ?: Both conditions must be true
 *   1. Project must exist (p.id matches)
 *   2. Current user must be the owner
 * 
 * This is role-based access control (RBAC):
 * - Members can view and edit tasks
 * - Only owners can invite members
 * 
 * Why restrict invitations to owners?
 * - Prevents members from inviting unauthorized users
 * - Owner maintains control over project team
 * - Reduces security risks
 */
$stmt = $db->prepare("SELECT name FROM projects WHERE id = ? AND owner_id = ?");
$stmt->execute([$project_id, $user_id]);
$project = $stmt->fetch();

/**
 * Handle Unauthorized Access
 * 
 * If project doesn't exist or user is not the owner, redirect to dashboard.
 * This prevents non-owners from accessing the invitation page.
 */
if (!$project) {
    header('Location: dashboard.php');
    exit;
}

// Initialize message variables
$error = '';
$success = '';

/**
 * Invitation Form Handler
 * 
 * Process invitation when form is submitted
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /**
     * Get User Identifier
     * 
     * User can enter either username or email to invite someone.
     * This flexibility improves user experience - they can use
     * whichever information they know about the person.
     */
    $identifier = trim($_POST['identifier'] ?? '');
    
    /**
     * Validate Input
     * 
     * Ensure user entered something
     */
    if (empty($identifier)) {
        $error = 'Please enter a username or email.';
    } else {
        /**
         * Process Invitation
         */
        try {
            /**
             * Find User by Username OR Email
             * 
             * This query searches for a user by either username or email.
             * 
             * Query explanation:
             * - SELECT id, username: Get user info for display and invitation
             * - WHERE username = ? OR email = ?: Search both fields
             * - We pass $identifier twice because it's used in both conditions
             * 
             * Why search both?
             * - Users might remember username but not email, or vice versa
             * - More flexible and user-friendly
             * - Single query is more efficient than two separate queries
             * 
             * Security note: Using prepared statements prevents SQL injection
             * even with user-provided search terms.
             */
            $stmt = $db->prepare("SELECT id, username FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$identifier, $identifier]);
            $invite_user = $stmt->fetch();
            
            /**
             * Validate Invitation Target
             * 
             * Perform several checks before adding member:
             */
            
            if (!$invite_user) {
                /**
                 * User Not Found
                 * 
                 * The username/email doesn't match any registered user.
                 * They must register before being invited to projects.
                 */
                $error = 'User not found.';
            } elseif ($invite_user['id'] == $user_id) {
                /**
                 * Self-Invitation Check
                 * 
                 * Prevent owner from inviting themselves.
                 * This would be redundant because:
                 * - Owner is automatically added as member when creating project
                 * - No point in inviting yourself
                 * 
                 * Note: Using == instead of === because IDs might be different types
                 * (string from session vs int from database)
                 */
                $error = 'You are already the owner of this project.';
            } else {
                /**
                 * Check for Existing Membership
                 * 
                 * Before adding, verify user isn't already a member.
                 * 
                 * Query explanation:
                 * - SELECT id: Just check if a record exists
                 * - WHERE project_id = ? AND user_id = ?: Both must match
                 * 
                 * Why check this?
                 * - Prevents duplicate membership records
                 * - Database has UNIQUE constraint on (project_id, user_id)
                 * - Better to show friendly error than database constraint error
                 */
                $stmt = $db->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
                $stmt->execute([$project_id, $invite_user['id']]);
                
                if ($stmt->fetch()) {
                    /**
                     * Already a Member
                     * 
                     * User is already part of this project, no need to add again
                     */
                    $error = 'This user is already a member of the project.';
                } else {
                    /**
                     * Add User as Project Member
                     * 
                     * All validations passed, now add the user to the project.
                     * 
                     * INSERT explanation:
                     * - project_id: Links to the project
                     * - user_id: The invited user's ID
                     * - joined_at: Automatically set by database (CURRENT_TIMESTAMP)
                     * 
                     * Foreign key constraints ensure:
                     * - project_id must be a valid project
                     * - user_id must be a valid user
                     * - If project is deleted, membership is deleted (CASCADE)
                     * 
                     * The UNIQUE constraint on (project_id, user_id) prevents duplicates
                     * as an extra safety measure.
                     */
                    $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id) VALUES (?, ?)");
                    $stmt->execute([$project_id, $invite_user['id']]);
                    
                    /**
                     * Success Message
                     * 
                     * Show confirmation with the invited user's username.
                     * We use h() even though the username is from our database
                     * as a defensive programming practice.
                     */
                    $success = 'Successfully added ' . h($invite_user['username']) . ' to the project!';
                }
            }
        } catch (PDOException $e) {
            /**
             * Database Error Handling
             * 
             * Handle any unexpected database errors.
             * Common errors might include:
             * - Connection issues
             * - Foreign key constraint violations (shouldn't happen with our validation)
             * - Unique constraint violations (shouldn't happen with our duplicate check)
             */
            $error = 'Failed to invite member. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invite Member - Project Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <h2>Project Management</h2>
            <div>
                <!-- Return to project page -->
                <a href="project.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">Back to Project</a>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="form-container">
            <h1>Invite Member to Project</h1>
            <!-- Show project name for context -->
            <h3>Project: <?php echo h($project['name']); ?></h3>
            
            <?php 
            /**
             * Display Error Messages
             * 
             * Show validation or database errors
             */
            if ($error): 
            ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            
            <?php 
            /**
             * Display Success Messages
             * 
             * Show confirmation when member is successfully added.
             * $success contains HTML-escaped username, so we can echo it directly.
             */
            if ($success): 
            ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Invitation Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="identifier">Username or Email:</label>
                    <!-- 
                        Single Input Field for Flexibility
                        
                        Instead of separate username and email fields, we use one field
                        that accepts either. This is more user-friendly because:
                        - Users might only remember one piece of information
                        - Reduces form complexity
                        - Our backend searches both fields anyway
                        
                        The placeholder text explains both options
                        
                        We re-populate on error (except after success) to save retyping
                    -->
                    <input type="text" id="identifier" name="identifier" required 
                           placeholder="Enter username or email"
                           value="<?php echo h($_POST['identifier'] ?? ''); ?>">
                </div>
                
                <!-- Form Action Buttons -->
                <button type="submit" class="btn btn-primary">Invite Member</button>
                <a href="project.php?id=<?php echo $project_id; ?>" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
