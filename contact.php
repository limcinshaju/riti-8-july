<?php
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $review_text = trim($_POST['review_text']);
    
    // Validate inputs
    if (empty($username) || empty($review_text)) {
        $error = "Please enter both username and feedback message";
    } else {
        try {
            // Check if username exists in participants table and is registered for at least one event
            $stmt = $conn->prepare("SELECT p.participant_id, r.event_id 
                                   FROM participants p
                                   JOIN registrations r ON p.participant_id = r.participant_id
                                   WHERE p.username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $participant_id = $row['participant_id'];
                $event_id = $row['event_id'];
                
                // First check if feedback already exists
                $check_stmt = $conn->prepare("SELECT 1 FROM reviews WHERE participant_id = ? AND event_id = ?");
                $check_stmt->bind_param("is", $participant_id, $event_id);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error = "You have already submitted feedback for this event";
                } else {
                    // Insert into reviews table with all required fields
                    $insert_stmt = $conn->prepare("INSERT INTO reviews 
                            (participant_id, event_id, review_text, created_at) 
                            VALUES (?, ?, ?, NOW())");
                    $insert_stmt->bind_param("iss", $participant_id, $event_id, $review_text);
                    
                    if ($insert_stmt->execute()) {
                        $success = "Thank you for your feedback!";
                        // Clear form
                        $_POST['username'] = '';
                        $_POST['review_text'] = '';
                    } else {
                        $error = "Failed to submit feedback. Please try again.";
                    }
                }
            } else {
                $error = "Username not found or not registered for any event";
            }
        } catch (Exception $e) {
            $error = "System error. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="contact.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<section class="contact-section">
    <video autoplay loop muted playsinline class="background-video">
        <source src="videos/contactbg.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <div class="overlay"></div>

    <div class="contact-container">
        <h2>Contact Us</h2>
        <p>RITI TechFest - Send your feedback</p>

        <?php if ($error): ?>
            <div class="error-message" style="color: #ff6b6b; margin-bottom: 15px; text-align: center;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message" style="color: #51cf66; margin-bottom: 15px; text-align: center;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="contact-content">
            <!-- Left Side - Contact Info -->
            <div class="contact-info">
                <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> Ramavarmapuram Road, Thrissur - 680009, Kerala, India.</p>
                <p><i class="fas fa-phone-alt"></i> <strong>Phone:</strong> 507-475-60945-6094</p>
                <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <a href="mailto:rititechfest@gmail.com">rititechfest@gmail.com</a></p>
            </div>

            <!-- Right Side - Contact Form -->
            <div class="contact-form">
                <h3>Send Feedback</h3>
                <form method="POST" action="contact.php">
                    <div class="input-box">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Your Username" 
                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
                    </div>
                    <div class="input-box">
                        <i class="fas fa-comment"></i>
                        <textarea name="review_text" placeholder="Type your feedback..." rows="4" required><?= 
                            isset($_POST['review_text']) ? htmlspecialchars($_POST['review_text']) : '' 
                        ?></textarea>
                    </div>
                    <button type="submit">Send Feedback</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
</body>
</html>