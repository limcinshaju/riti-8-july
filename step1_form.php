<?php
// This MUST be the absolute first line - no whitespace before
session_start();
require_once 'db_connect.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    // Sanitize and validate inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $roll_number = trim($_POST['roll_number'] ?? '');
    $semester = intval($_POST['semester'] ?? 0);
    $college = trim($_POST['college'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate full name
    if (!preg_match('/^[A-Za-z\s]{3,50}$/', $full_name)) {
        $errors['full_name'] = 'Name must be 3-50 letters with no numbers/special chars';
    }
    
    // Validate roll number
    if (!preg_match('/^[A-Za-z0-9]{5,20}$/', $roll_number)) {
        $errors['roll_number'] = 'Roll number must be 5-20 alphanumeric characters';
    }
    
    // Validate semester
    if ($semester < 1 || $semester > 8) {
        $errors['semester'] = 'Please select a valid semester';
    }
    
    // Validate college
    if (!preg_match('/^[A-Za-z\s\.\-]{5,100}$/', $college)) {
        $errors['college'] = 'College name must be 5-100 valid characters';
    }
    
    // Validate phone
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid 10-digit phone number';
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    // Validate username
    if (!preg_match('/^[A-Za-z0-9_]{5,20}$/', $username)) {
        $errors['username'] = '5-20 characters (letters, numbers, underscores only)';
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT participant_id FROM participants WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['username'] = 'Username already taken';
        }
    }
    
    // Validate password
    if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password)) {
        $errors['password'] = 'Must contain at least one number, one uppercase and lowercase letter, and at least 8 characters';
    }
    
    // Check for duplicate roll number or email
    $stmt = $conn->prepare("SELECT participant_id FROM participants WHERE roll_number = ? OR email = ?");
    $stmt->bind_param("ss", $roll_number, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors['general'] = 'User with this roll number or email already exists';
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO participants (username, password, full_name, roll_number, email, college, semester, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssis", $username, $hashed_password, $full_name, $roll_number, $email, $college, $semester, $phone);
        
        if ($stmt->execute()) {
            $_SESSION['participant_id'] = $conn->insert_id;
            $_SESSION['full_name'] = $full_name;
            header("Location: step2_events.php");
            exit(); // Critical - stops further execution
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}

// Now include navbar after all possible header redirects
include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Video Background Styles */
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        
        /* Content Container */
        .content-container {
            position: relative;
            z-index: 1;
        }
        
        .content-overlay {
            background-color: rgba(255, 255, 255, 0.92);
            border-radius: 10px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.15);
        }
        
        /* Form Styles */
        .invalid-feedback { display: none; }
        .is-invalid ~ .invalid-feedback { display: block; }
        .card { border: none; }
        .card-header { 
            border-radius: 10px 10px 0 0 !important;
            background-color: #212529;
        }
        
        /* Improved button styling */
        .btn-register {
            background-color: #212529;
            border: none;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            background-color: #343a40;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Video Background -->
    <video autoplay muted loop playsinline class="video-background">
        <source src="videos/contactbg.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    
    <div class="content-container">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow content-overlay">
                        <div class="card-header text-white">
                            <h3 class="mb-0">Event Registration</h3>
                        </div>
                        
                        <div class="card-body">
                            <?php if (!empty($errors['general'])): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                            <?php endif; ?>
                            
                            <form id="registrationForm" method="POST" novalidate>
                                <h4 class="mb-4">Participant Information</h4>
                                
                                <div class="row g-3">
                                    <!-- Full Name -->
                                    <div class="col-md-6">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" 
                                               id="full_name" name="full_name" 
                                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                               pattern="[A-Za-z\s]{3,50}" required>
                                        <div class="invalid-feedback">
                                            <?= $errors['full_name'] ?? 'Name must be 3-50 letters with no numbers/special characters' ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Roll Number -->
                                    <div class="col-md-6">
                                        <label for="roll_number" class="form-label">Roll Number</label>
                                        <input type="text" class="form-control <?= isset($errors['roll_number']) ? 'is-invalid' : '' ?>" 
                                               id="roll_number" name="roll_number" 
                                               value="<?= htmlspecialchars($_POST['roll_number'] ?? '') ?>"
                                               pattern="[A-Za-z0-9]{5,20}" required>
                                        <div class="invalid-feedback">
                                            <?= $errors['roll_number'] ?? 'Must be 5-20 alphanumeric characters' ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Semester -->
                                    <div class="col-md-4">
                                        <label for="semester" class="form-label">Semester</label>
                                        <select class="form-select <?= isset($errors['semester']) ? 'is-invalid' : '' ?>" 
                                                id="semester" name="semester" required>
                                            <option value="" selected disabled>Select Semester</option>
                                            <?php for($i=1; $i<=8; $i++): ?>
                                                <option value="<?= $i ?>" <?= ($_POST['semester'] ?? '') == $i ? 'selected' : '' ?>>
                                                    Semester <?= $i ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            <?= $errors['semester'] ?? 'Please select your semester' ?>
                                        </div>
                                    </div>
                                    
                                    <!-- College -->
                                    <div class="col-md-8">
                                        <label for="college" class="form-label">College</label>
                                        <input type="text" class="form-control <?= isset($errors['college']) ? 'is-invalid' : '' ?>" 
                                               id="college" name="college" 
                                               value="<?= htmlspecialchars($_POST['college'] ?? '') ?>"
                                               pattern="[A-Za-z\s\.\-]{5,100}" required>
                                        <div class="invalid-feedback">
                                            <?= $errors['college'] ?? 'Please enter a valid college name' ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Phone Number -->
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                               id="phone" name="phone" 
                                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                               pattern="[0-9]{10}" required>
                                        <div class="invalid-feedback">
                                            <?= $errors['phone'] ?? 'Please enter a valid 10-digit phone number' ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Email -->
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                               id="email" name="email" 
                                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                               pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" required>
                                        <div class="invalid-feedback">
                                            <?= $errors['email'] ?? 'Please enter a valid email address' ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Username -->
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" 
                                               id="username" name="username" 
                                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                               pattern="[A-Za-z0-9_]{5,20}" required>
                                        <div class="invalid-feedback">
                                            <?= $errors['username'] ?? '5-20 characters (letters, numbers, underscores only)' ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Password -->
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                               id="password" name="password" 
                                               minlength="8" 
                                               pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" required>
                                        <div class="invalid-feedback">
                                            <?= $errors['password'] ?? 'Must contain at least one number, one uppercase and lowercase letter, and at least 8 characters' ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" class="btn btn-register text-white">Register</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced client-side validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const form = e.target;
            let isValid = true;

            // Validate all fields
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            // Custom validations
            if (!validateEmail(document.getElementById('email').value)) {
                document.getElementById('email').classList.add('is-invalid');
                isValid = false;
            }

            if (!validatePhone(document.getElementById('phone').value)) {
                document.getElementById('phone').classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                // Scroll to first invalid field with smooth animation
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    firstInvalid.focus();
                }
            }
        });

        // Live validation on blur with debounce
        let validationTimer;
        document.querySelectorAll('#registrationForm input, #registrationForm select').forEach(element => {
            element.addEventListener('blur', function() {
                clearTimeout(validationTimer);
                validationTimer = setTimeout(() => {
                    if (!this.checkValidity()) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                        
                        // Additional validation for specific fields
                        if (this.id === 'email' && !validateEmail(this.value)) {
                            this.classList.add('is-invalid');
                        }
                        if (this.id === 'phone' && !validatePhone(this.value)) {
                            this.classList.add('is-invalid');
                        }
                    }
                }, 300);
            });
        });

        // Custom validation functions
        function validateEmail(email) {
            const re = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;
            return re.test(email);
        }

        function validatePhone(phone) {
            return /^\d{10}$/.test(phone);
        }
    </script>
</body>
</html>
<?php include 'footer.php'; ?>