<?php include 'navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events</title>
    <style>
        /* Background Video Styling */
        .video-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        .video-background {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Banner Styling */
        .ev-banner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 50vh;
            text-align: center;
        }
        .ev-banner-content {
            padding: 20px 40px;
            border-radius: 10px;
            animation: fadeIn 1.5s ease-in-out;
        }
        .ev-banner h1 {
            font-size: 28px;
            color: white;
        }
        .ev-register-btn {
            background-color: white;
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 18px;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s ease-in-out;
        }
        .ev-register-btn:hover {
            background-color: grey;
            transform: scale(1.1);
        }

        /* Event Grid */
        .ev-container {
            display: flex;
            justify-content: center;
            padding: 30px;
        }
        .ev-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 50px;
            max-width: 1100px;
        }
        .ev-card {
            background-color: #white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease-in-out;
            color:white;
        }
        .ev-card:hover {
            transform: scale(1.05);
        }
        .ev-card-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
        }
        .ev-title {
            font-size: 20px;
        }
        .ev-date {
            font-size: 16px;
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .ev-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .ev-grid {
                grid-template-columns: repeat(1, 1fr);
            }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Background Video -->
    <div class="video-container">
        <video class="video-background" autoplay loop muted playsinline>
            <source src="videos/contactbg.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <!-- Banner Section -->
    <div class="ev-banner">
        <div class="ev-banner-content">
            <h1>Let's Get Ready to Dive into Tech!</h1>
        </div>
    </div>

    <!-- Events Section -->
    <div class="ev-container">
        <div class="ev-grid">
            <div class="ev-card">
                <img src="images\1.png" alt="Chatbots and Virtual Assistants" class="ev-card-img">
                <h2 class="ev-title">24-hour coding competition with prizes for best innovative solution</h2>
                <p class="ev-date">August 05, 2025</p>
            </div>
            <div class="ev-card">
                <img src="images\2.png" alt="Modern Marketing Summit" class="ev-card-img">
                <h2 class="ev-title">Individual programming competition with 3 problem rounds</h2>
                <p class="ev-date">August 05, 2025</p>
            </div>
            <div class="ev-card">
                <img src="images\3.png" alt="Opening Workshop Registration" class="ev-card-img">
                <h2 class="ev-title">2-day hands-on workshop building autonomous robots</h2>
                <p class="ev-date">August 05, 2025</p>
            </div>
            <div class="ev-card">
                <img src="images\4.png" alt="Immersive Tech & Culture" class="ev-card-img">
                <h2 class="ev-title">Keynote speeches and panel discussions on AI advancements</h2>
                <p class="ev-date">August 05, 2025</p>
            </div>
            <div class="ev-card">
                <img src="images\5.png" alt="Chatbots and Virtual Assistants" class="ev-card-img">
                <h2 class="ev-title">Capture The Flag competition testing security skills</h2>
                <p class="ev-date">August 05, 2025</p>
            </div>
            <div class="ev-card">
                <img src="images\6.png" alt="Modern Marketing Summit" class="ev-card-img">
                <h2 class="ev-title">Analyze real-world datasets and present insights</h2>
                <p class="ev-date">August 05, 2025</p>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>
</body>
</html>
