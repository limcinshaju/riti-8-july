<?php
    // You can fetch images from a database or a directory
    $images = [
"DSC08415.jpg","DSC08418.jpg","DSC08451.jpg","DSC08465.jpg","DSC08470.jpg","DSC08482.jpg","DSC08495.jpg","DSC08515.jpg","DSC08528.jpg","DSC08533.jpg","DSC08560.jpg","DSC08574 - Copy.jpg"    ];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery</title>
    <link rel="stylesheet" href="riti.css">
</head>
<body>
    <video autoplay muted loop id="bg-video">
        <source src="videos/contactbg.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    
    <nav class="navbar">
    <div class="container">
        <div class="logo">
            <a href="index.php"><img src="images/logo.png" alt="RITI Logo"></a>
            <span>Riti</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="events.php">Events</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="gallery.php">Gallery</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <li class="user-info">
                    <span class="username"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <?php if($_SESSION['user_role'] === 'coordinator'): ?>
                        <span class="event-badge"><?= htmlspecialchars($_SESSION['event_name']) ?></span>
                    <?php endif; ?>
                </li>
                <li><a href="logout.php" class="logout-btn">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
        <?php if(!isset($_SESSION['user_id'])): ?>
            <a href="register.php" class="btn">Register</a>
        <?php endif; ?>
    </div>
</nav>

    <div class="gallery-container">
        <div class="gallery">
            <?php foreach ($images as $image): ?>
                <img src="<?php echo $image; ?>" alt="Gallery Image">
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer Section -->
<footer>
    <div class="footer-container">
        <!-- Follow Us Text -->
        <p class="follow-text">Follow us on</p>

        <!-- Social Media Icons -->
        <div class="social-icons">
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-whatsapp"></i></a>
        </div>

        <!-- Footer Links -->
        <div class="footer-links">
            <a href="#">CONTACT US</a> |
            <a href="#">ABOUT US</a>
        </div>
    </div>
</footer>
</body>
</html>