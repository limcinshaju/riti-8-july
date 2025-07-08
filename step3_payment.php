<?php
session_start();
require_once 'db_connect.php';

// Determine if this is a new registration flow
$is_new_registration = isset($_SESSION['registration_flow']);

// Check if user has completed previous steps
if (!isset($_SESSION['participant_id']) || !isset($_SESSION['full_name']) || !isset($_SESSION['selected_events'])) {
    // Redirect based on flow type
    header("Location: " . ($is_new_registration ? "step1_registration.php" : "existinguser.php"));
    exit();
}

// Set default values if not present
$_SESSION['roll_number'] = $_SESSION['roll_number'] ?? '';
$_SESSION['team_members'] = $_SESSION['team_members'] ?? [];

// Calculate total amount and prepare event details
$total_amount = 0;
$event_details = [];
foreach ($_SESSION['selected_events'] as $event_id) {
    $stmt = $conn->prepare("SELECT event_id, event_name, fee, event_type FROM events WHERE event_id = ?");
    $stmt->bind_param("s", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($event = $result->fetch_assoc()) {
        $total_amount += $event['fee'];
        $event_details[] = $event;
    }
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    // Validate payment details
    $card_name = trim($_POST['card_name'] ?? '');
    $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $expiry_month = $_POST['expiry_month'] ?? '';
    $expiry_year = $_POST['expiry_year'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    if (empty($card_name)) {
        $errors['card_name'] = "Cardholder name is required";
    }
    
    if (!preg_match('/^\d{16}$/', $card_number)) {
        $errors['card_number'] = "Valid 16-digit card number required";
    }
    
    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        $errors['cvv'] = "Valid CVV required (3-4 digits)";
    }
    
    // Process payment if no errors
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            foreach ($_SESSION['selected_events'] as $event_id) {
                // Insert registration
                $stmt = $conn->prepare("INSERT INTO registrations (participant_id, event_id, payment_mode, payment_status) VALUES (?, ?, 'online', 'paid')");
                $stmt->bind_param("is", $_SESSION['participant_id'], $event_id);
                $stmt->execute();
                $registration_id = $conn->insert_id;
                
                // Record payment
                $fee_stmt = $conn->prepare("SELECT fee FROM events WHERE event_id = ?");
                $fee_stmt->bind_param("s", $event_id);
                $fee_stmt->execute();
                $fee_result = $fee_stmt->get_result();
                $fee = $fee_result->fetch_assoc()['fee'];
                
                $transaction_id = 'PAY-' . time() . '-' . $event_id;
                $payment_stmt = $conn->prepare("INSERT INTO payments (registration_id, amount, transaction_id) VALUES (?, ?, ?)");
                $payment_stmt->bind_param("ids", $registration_id, $fee, $transaction_id);
                $payment_stmt->execute();
                
                // Handle group members if this is a group event
                if (isset($_SESSION['team_members'][$event_id]) && is_array($_SESSION['team_members'][$event_id])) {
                    // Create group
                    $group_name = "Team " . $_SESSION['full_name'] . " - " . $event_id;
                    $group_stmt = $conn->prepare("INSERT INTO groups (group_name, event_id, leader_id) VALUES (?, ?, ?)");
                    $group_stmt->bind_param("ssi", $group_name, $event_id, $_SESSION['participant_id']);
                    $group_stmt->execute();
                    $group_id = $conn->insert_id;
                    
                    // Add team members to group_members table
                    foreach ($_SESSION['team_members'][$event_id] as $member) {
                        if (isset($member['full_name']) && isset($member['roll_number'])) {
                            $member_stmt = $conn->prepare("INSERT INTO group_members (group_id, full_name, roll_number) VALUES (?, ?, ?)");
                            $member_stmt->bind_param("iss", $group_id, $member['full_name'], $member['roll_number']);
                            $member_stmt->execute();
                        }
                    }
                }
            }
            
            $conn->commit();
            
            // Clear session data
            unset($_SESSION['selected_events']);
            unset($_SESSION['team_members']);
            
            // Clear registration flow marker if this was a new registration
            if ($is_new_registration) {
                unset($_SESSION['registration_flow']);
            }
            
            header("Location: success.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors['general'] = "Payment processing failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Event Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .payment-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .payment-card-header {
            border-radius: 10px 10px 0 0 !important;
        }
        .card-logo {
            height: 30px;
            margin: 0 5px;
        }
        .summary-item {
            border-left: 3px solid #0d6efd;
            padding-left: 10px;
        }
        .team-member-item {
            padding-left: 25px;
            font-size: 0.9rem;
        }
        .participant-type-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white payment-card-header position-relative">
                        <h3 class="mb-0">Complete Your Registration</h3>
                        <span class="badge bg-light text-dark participant-type-badge">
                            <?= $is_new_registration ? 'New Registration' : 'Additional Events' ?>
                        </span>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <h4>Registration Summary</h4>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Participant Details</h5>
                                    <p><strong>Name:</strong> <?= htmlspecialchars($_SESSION['full_name']) ?></p>
                                    <?php if (!empty($_SESSION['roll_number'])): ?>
                                        <p><strong>Roll Number:</strong> <?= htmlspecialchars($_SESSION['roll_number']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <h5 class="mt-4">Selected Events</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Type</th>
                                            <th class="text-end">Fee</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($event_details as $event): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($event['event_name']) ?></td>
                                                <td><?= ucfirst($event['event_type']) ?></td>
                                                <td class="text-end">₹<?= number_format($event['fee'], 2) ?></td>
                                            </tr>
                                            <?php if ($event['event_type'] == 'group' && isset($_SESSION['team_members'][$event['event_id']])): ?>
                                                <?php foreach ($_SESSION['team_members'][$event['event_id']] as $member): ?>
                                                    <?php if (isset($member['full_name']) && isset($member['roll_number'])): ?>
                                                        <tr class="table-light">
                                                            <td colspan="2" class="team-member-item">
                                                                ↳ <?= htmlspecialchars($member['full_name']) ?> (<?= htmlspecialchars($member['roll_number']) ?>)
                                                            </td>
                                                            <td class="text-end"></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <tr class="table-active">
                                            <th colspan="2">Total Amount</th>
                                            <th class="text-end">₹<?= number_format($total_amount, 2) ?></th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <form method="POST">
                            <div class="card payment-card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Payment Information</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($errors)): ?>
                                        <div class="alert alert-danger">
                                            <ul class="mb-0">
                                                <?php foreach ($errors as $error): ?>
                                                    <li><?= htmlspecialchars($error) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="card_name" class="form-label">Cardholder Name</label>
                                        <input type="text" class="form-control <?= isset($errors['card_name']) ? 'is-invalid' : '' ?>" 
                                               id="card_name" name="card_name" 
                                               value="<?= htmlspecialchars($_POST['card_name'] ?? '') ?>" required>
                                        <?php if (isset($errors['card_name'])): ?>
                                            <div class="invalid-feedback"><?= htmlspecialchars($errors['card_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="card_number" class="form-label">Card Number</label>
                                        <input type="text" class="form-control <?= isset($errors['card_number']) ? 'is-invalid' : '' ?>" 
                                               id="card_number" name="card_number" 
                                               value="<?= htmlspecialchars($_POST['card_number'] ?? '') ?>" 
                                               placeholder="1234 5678 9012 3456" required>
                                        <?php if (isset($errors['card_number'])): ?>
                                            <div class="invalid-feedback"><?= htmlspecialchars($errors['card_number']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-3">
                                            <label for="expiry_month" class="form-label">Expiry Month</label>
                                            <select class="form-select" id="expiry_month" name="expiry_month" required>
                                                <option value="" disabled selected>Month</option>
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                    <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= ($_POST['expiry_month'] ?? '') == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>>
                                                        <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="expiry_year" class="form-label">Expiry Year</label>
                                            <select class="form-select" id="expiry_year" name="expiry_year" required>
                                                <option value="" disabled selected>Year</option>
                                                <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                                    <option value="<?= $i ?>" <?= ($_POST['expiry_year'] ?? '') == $i ? 'selected' : '' ?>>
                                                        <?= $i ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="cvv" class="form-label">CVV</label>
                                            <input type="text" class="form-control <?= isset($errors['cvv']) ? 'is-invalid' : '' ?>" 
                                                   id="cvv" name="cvv" 
                                                   value="<?= htmlspecialchars($_POST['cvv'] ?? '') ?>" 
                                                   placeholder="123" required>
                                            <?php if (isset($errors['cvv'])): ?>
                                                <div class="invalid-feedback"><?= htmlspecialchars($errors['cvv']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center">
                                        <img src="images/visa.png" alt="Visa" class="card-logo">
                                        <img src="images/mastercard.png" alt="MasterCard" class="card-logo">
                                        <img src="images/amex.png" alt="American Express" class="card-logo">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="<?= $is_new_registration ? 'step2_events.php' : 'dashboard.php' ?>" class="btn btn-secondary">Back</a>
                                <button type="submit" class="btn btn-primary">Complete Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format card number input
        document.getElementById('card_number').addEventListener('input', function(e) {
            this.value = this.value.replace(/\s/g, '').replace(/(\d{4})/g, '$1 ').trim();
        });
        
        // Format CVV input
        document.getElementById('cvv').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 4);
        });
    </script>
</body>
</html>