# Project Management System

**Team Members:** 
Shondel Hayles
Lily Le
Poojitha Kammari

## Project Description

This is a web-based project management application built using the LAMP stack (Linux, Apache, MySQL, PHP).
The system allows users to collaborate on projects by creating tasks, assigning them to team members, tracking progress and communicating through comments.
The application features a modern, Trello-inspired interface with a display board for visual task management.
Users can register accounts, create projects, invite team members, and manage tasks with statuses (To-Do, In Progress, Done).
Additional features added include task assignments, due dates with overdue warnings and a commenting system for team collaborations.


### Requirements
- PHP 8+
- MySQL / MariaDB
- Web server (Apache/XAMPP)
- Linux

### Setup Instructions
**Step 1: Clone the Repository**

**Step 2: Move Files to Web Server Directory**

**Step 3: Create the Database:**
  - Start your web server
  - Open phpMyAdmin in your browser: http://localhost/phpmyadmin
  - Click on "New" to create a database
  - Database name: project_manager
  - Collation: utf8mb4_general_ci
  - click "Create"
    
**Step 4: Import the Database Schema**
  - Click on the project_manager database in the left sidebar
  - Click on the "SQL" tab
  - Copy the contents of the database.sql file that is provided in the repository
  - Paste it into the SQL text area
  - Click "Go" to execute
    
**Step 5: Configure Database Connection**
  - Open the config.php file and verify the database credentials match your setup:
    
    define('DB_HOST', 'localhost');
    
    define('DB_USER', 'root');
    
    define('DB_PASS', '');  // Enter your MySQL password if you have one
    
    define('DB_NAME', 'project_manager');
    
**Step 6: Run the Application**
  - Ensure your web server is running
  - Open your browser
  - Navigate to http://localhost/project_manager/login.php
  - You should see the login page and be able to use the full application.

### Demo User Credentials
You can use these pre-made demo accounts to test the application:

Demo Admin Account:

Username: demo_admin
Email: admin@demo.com
Password: password

Demo User Account:

Username: demo_user
Email: user@demo.com
Password: password

Alternatively, you can register a new account directly from the registration page: http://localhost/project_manager/register.php
  
