<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link your CSS file -->
</head>
<body>

    <?php include 'navbar.php'; ?> <!-- Include the Navbar -->

    <section class="hero">
        <video autoplay muted loop playsinline class="background-video">
            <source src="videos/contactbg.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="hero-content">
            <h1>Riti Tech Fest</h1>
            <p>Reflect The Radiance</p>
            <div class="hero-buttons">
                <a href="step1_form.php" class="btn btn-primary">Register Now</a>
                <a href="aboutus.php" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
    </section>
    <?php include 'footer.php'; ?>

</body>
</html>
