<!doctype html>
<html lang="en">
<head>
    <title>About Us</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <style>
        .hero { 
            background: url('binary-code-background-PYA41Y.jpg') center/cover no-repeat; 
            height: 300px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            text-align: center; 
            color: white; 
            position: relative; 
        }
        .hero h1 { 
            font-size: 3rem; 
            padding: 10px 20px; 
            border-radius: 10px; 
        }
        .about { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 40px; 
            flex-wrap: wrap; 
            gap: 20px; 
        }
        .about video { 
            width: 45%; 
            border-radius: 10px; 
            padding-right: 200px;
        }
        .about .textus { 
            max-width: 600px; 
            text-align: left;
            color: white; 
        }
        .aboutus { 
            text-align: center; 
            padding: 40px 20px;
            color: white;  
        }
        .aboutus h2 { 
            margin-bottom: 20px; 
            color: white; 
        }
        .aboutus-list { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; 
            justify-items: center; 
        }
        .aboutus-item { text-align: center; }
        .aboutus-item img { 
            width: 120px; 
            height: 120px; 
            border-radius: 50%; 
            border: 3px solid white; 
        }
        .aboutus-item h3 { 
            margin-top: 10px; 
            font-size: 1.2rem; 
        }
        .video-background {
            position: fixed;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1; /* Puts the video behind content */
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

    <section class="hero">
        <h1>About RITI Techfest</h1>
    </section>

    <section class="about">
        <video src="images\riti tech fest.mp4" class="pic" autoplay></video>
        <div class="textus">
            <h2>About RITI Techfest</h2>
            <p>RITI Techfest is a premier annual technology festival that brings together students, professionals, and tech enthusiasts from various disciplines to celebrate innovation, creativity, and advancements in technology. Organized by the Department of Computer Science at Vimala College (Autonomous), Thrissur, RITI serves as a platform for knowledge exchange, skill development, and showcasing groundbreaking projects.</p>
            <p>With a strong emphasis on emerging technologies, the fest features a diverse range of events, including hackathons, coding competitions, expert talks, workshops, and project exhibitions. Participants get the opportunity to interact with industry experts, collaborate on real-world problem-solving, and gain hands-on experience with new technologies.</p>
            <p>RITI Techfest has evolved over the years, growing in scale and impact, fostering an environment where aspiring technologists can refine their skills, build meaningful connections, and explore future career opportunities. Whether you are a student eager to learn, a developer looking to compete, or an entrepreneur seeking inspiration, RITI has something for everyone.</p>
        </div>
    </section>

    <div class="aboutus">
        <h2>Meet Our Coordinators</h2>
        <div class="aboutus-list">
            <div class="aboutus-item">
                <img src="images/sreekala.jpg" alt="MS SREEKALA M.">
                <h3>MS SREEKALA M.</h3>
            </div>
            <div class="aboutus-item">
                <img src="images/sareena.jpeg" alt="MS SAREENA ROSE">
                <h3>MS SAREENA ROSE</h3>
            </div>
            <div class="aboutus-item">
                <img src="images/sreedevi.jpeg" alt="MS SREEDEVI SAGEESH">
                <h3>MS SREEDEVI SAGEESH</h3>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'footer.php'; ?>

</body>
</html>
