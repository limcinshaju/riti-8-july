<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Background video styling */
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        /* General styles */
        body {
            background-color: rgba(0, 0, 0, 0.8); /* Dark background with transparency */
            color: white;
            text-align: center;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        /* Gallery container */
        .gallery-container {
            padding: 80px 20px;
            position: relative;
            z-index: 1;
        }

        /* Gallery grid */
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: auto;
            padding-top: 20px;
        }

        /* Gallery images */
        .gallery img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 10px;
            transition: transform 0.3s ease-in-out, box-shadow 0.3s;
            box-shadow: 0 4px 8px rgba(255, 255, 255, 0.2);
        }

        .gallery img:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(255, 255, 255, 0.4);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .gallery {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .gallery {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>

    <!-- Include Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Background Video -->
    <video class="video-background" autoplay loop muted playsinline>
        <source src="videos/contactbg.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <!-- Gallery Section -->
    <div class="gallery-container">
        <h1 class="mb-4">Our Gallery</h1>
        <div class="gallery">
            <?php
            $images = glob("images/gallery/*.jpg"); // Fetch all images from folder
            foreach ($images as $img) {
                echo '<div><a href="'.$img.'" target="_blank"><img src="'.$img.'" class="img-fluid"></a></div>';
            }
            ?>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>
