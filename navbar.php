<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" href="navbar.css"> -->
    <style>
        /* Dialog box styles */
        .dialog-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .dialog-box {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .dialog-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .dialog-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .dialog-btn {
            background-color:black;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration:none;
            color:white;
        }
        
        .dialog-btn-primary {
            background-color: #0d6efd;
            color: white;
        }
        
        .dialog-btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .dialog-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .navbar {
    background-color: #000000;
    padding: 15px 0;
    font-family: 'Arial', sans-serif;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.container {
    width: 90%;
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo img {
    width: 40px;
    height: 40px;
    transition: transform 0.3s;
}

.logo:hover img {
    transform: rotate(15deg);
}

.logo span {
    color: white;
    font-size: 24px;
    font-weight: bold;
    letter-spacing: 1px;
}

.nav-links {
    list-style: none;
    display: flex;
    gap: 30px;
    margin: 0;
    padding: 0;
    align-items: center;
}

.nav-links li {
    position: relative;
}

.nav-links a {
    color: white;
    text-decoration: none;
    font-size: 18px;
    font-weight: bold;
    transition: color 0.3s;
    padding: 5px 0;
}

.nav-links a:hover {
    color: #12905a;
}

.nav-links a::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: 0;
    left: 0;
    background-color: #12905a;
    transition: width 0.3s;
}

.nav-links a:hover::after {
    width: 100%;
}

.btn {
    background-color: #12905a;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    font-weight: bold;
    transition: all 0.3s;
    font-size: 16px;
    border: 2px solid transparent;
}

.btn:hover {
    background-color: transparent;
    border-color: #12905a;
    color: #12905a;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
        gap: 15px;
    }
    
    .nav-links {
        flex-direction: column;
        gap: 15px;
        align-items: center;
    }
    
    .btn {
        margin-top: 10px;
    }
}
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <div class="logo">
            <a href="index.php"><img src="images/logo.png" alt="RITI Logo"></a>
            <span>Riti</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="event.php">Events</a></li>
            <li><a href="aboutus.php">About</a></li>
            <li><a href="gallery.php">Gallery</a></li>
            <li><a href="contact.php">Contact Us</a></li>
        </ul>
        <button id="registerBtn" class="btn">Register</button>
    </div>
</nav>

<!-- Registration Dialog Box -->
<div id="registrationDialog" class="dialog-overlay">
    <div class="dialog-box">
        <h3 class="dialog-title">How would you like to register?</h3>
        <p>Are you a new participant or an existing user?</p>
        <!-- In the dialog-box div -->
    <div class="dialog-buttons">
        <a href="step1_form.php" class="dialog-btn dialog-btn-dark">New User</a>
        <a href="existinguser.php" class="dialog-btn dialog-btn-dark">Existing User</a>
    </div>
    </div>
</div>

<script>
    // Handle register button click
    document.getElementById('registerBtn').addEventListener('click', function() {
        document.getElementById('registrationDialog').style.display = 'flex';
    });
    
    // Close dialog when clicking outside
    document.getElementById('registrationDialog').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
</script>

</body>
</html>