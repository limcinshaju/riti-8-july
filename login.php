<?php
session_start();

// If already logged in, redirect appropriately
if (!empty($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: coordinator.php");
    }
    exit();
}

// Get error message if exists
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RITI Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="login-container">
        <h2>RITI Login</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="auth.php">
            <div class="form-group">
                <label for="role">Login As:</label>
                <select id="role" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="coordinator">Coordinator</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>