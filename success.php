<?php include 'navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .success-container {
            margin-top: 100px;
            margin-bottom:250px;
        }

        .checkmark {
            display: inline-block;
            width: 80px;
            height: 80px;
            background-color: #00c853;
            border-radius: 50%;
            position: relative;
        }
        .checkmark::after {
            content: '✔';
            color: white;
            font-size: 50px;
            font-weight: bold;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .success-message {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-top: 20px;
        }
        .sub-message {
            font-size: 16px;
            color: #777;
            margin-top: 5px;
        }
        .next-button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 20px;
            background-color: #00c853;
            color: white;
            text-decoration: none;
            font-size: 16px;
            border-radius: 5px;
            transition: 0.3s;
        }
        .next-button:hover {
            background-color: #009624;
        }
    </style>
</head>
<body>

<div class="success-container">
    <div class="checkmark"></div>
    <p class="success-message">Successful</p>
    <p class="sub-message">You have succesfully registered.</p>
    <a href="index.php" class="next-button">CONTINUE</a>
</div>

</body>
</html>

<?php include 'footer.php'; ?>
