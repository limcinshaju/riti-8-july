<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer</title>
    <!-- <link rel="stylesheet" href="footer.css"> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
    /* Footer Styles */
footer {
    background: #000000; /* Black background */
    padding: 30px 0;
    text-align: center;
    color: white;
    font-family: Arial, sans-serif;
}

.footer-container {
    max-width: 800px;
    margin: 0 auto;
}

/* Follow Us Text */
.follow-text {
    font-family: 'Cursive', sans-serif;
    font-size: 20px;
    margin-bottom: 10px;
}

/* Social Media Icons */
.social-icons {
    margin-bottom: 15px;
}

.social-icons a {
    display: inline-block;
    margin: 0 10px;
    font-size: 24px;
    color: white;
    transition: color 0.3s;
}

/* Icon Colors on Hover */
.social-icons a:hover:nth-child(1) { color: #E1306C; } /* Instagram - Pink */
.social-icons a:hover:nth-child(2) { color: #1877F2; } /* Facebook - Blue */
.social-icons a:hover:nth-child(3) { color: #25D366; } /* WhatsApp - Green */

/* Footer Links */
.footer-links {
    font-size: 14px;
    margin-top: 10px;
}

.footer-links a {
    color: white;
    text-decoration: none;
    margin: 0 5px;
    transition: color 0.3s;
}

.footer-links a:hover {
    color: #f3f3f3;
}

</style>
<body>

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
            <a href="contact.php">CONTACT US</a> |
            <a href="aboutus.php">ABOUT US</a>
        </div>
    </div>
</footer>

</body>
</html>
