<?php
session_start();
require_once 'db_connect.php';

// Clear any existing session data
$_SESSION = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validate inputs
    if (empty($username) || empty($password)) {
        header("Location: login.php?error=Username+and+password+are+required");
        exit();
    }

    // Determine table and ID field based on role
    $table = ($role === 'admin') ? 'admins' : 'coordinators';
    $id_field = $role . '_id';
    
    try {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Set common session variables
                $_SESSION['user_id'] = $user[$id_field];
                $_SESSION['user_role'] = $role;
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_login'] = time();
                
                // Set coordinator-specific session data
                if ($role === 'coordinator') {
                    $_SESSION['event_id'] = $user['event_id'];
                    
                    // Get event name
                    $event_stmt = $conn->prepare("SELECT event_name FROM events WHERE event_id = ?");
                    $event_stmt->bind_param("s", $user['event_id']);
                    $event_stmt->execute();
                    $event_result = $event_stmt->get_result();
                    
                    if ($event_result->num_rows === 1) {
                        $_SESSION['event_name'] = $event_result->fetch_assoc()['event_name'];
                    }
                }
                
                // Redirect based on role
                header("Location: " . ($role === 'admin' ? 'admin.php' : 'coordinator.php'));
                exit();
            }
        }
        
        // If we get here, login failed
        header("Location: login.php?error=Invalid+credentials");
        exit();
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: login.php?error=Database+error");
        exit();
    }
}

// If not POST request, redirect to login
header("Location: login.php");
exit();
?>