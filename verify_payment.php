<?php
session_start();
require_once 'db_connect.php';

// Check if admin is logged in
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied");
}

// Check if registration ID is provided
if (empty($_GET['id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit("Registration ID required");
}

$registration_id = (int)$_GET['id'];

// Start transaction
$conn->begin_transaction();

try {
    // Check if payment already exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM payments WHERE registration_id = ?");
    $check_stmt->bind_param("i", $registration_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $count = $check_result->fetch_row()[0];
    
    if ($count > 0) {
        throw new Exception("Payment already verified for this registration");
    }
    
    // Get registration details including event fee
    $reg_stmt = $conn->prepare("SELECT r.*, e.fee FROM registrations r 
                              JOIN events e ON r.event_id = e.event_id 
                              WHERE r.registration_id = ?");
    $reg_stmt->bind_param("i", $registration_id);
    $reg_stmt->execute();
    $reg_result = $reg_stmt->get_result();
    
    if ($reg_result->num_rows === 0) {
        throw new Exception("Registration not found");
    }
    
    $registration = $reg_result->fetch_assoc();
    
    // Create payment record
    $payment_stmt = $conn->prepare("INSERT INTO payments 
                                  (registration_id, amount, payment_date) 
                                  VALUES (?, ?, NOW())");
    $payment_stmt->bind_param("id", $registration_id, $registration['fee']);
    
    if (!$payment_stmt->execute()) {
        throw new Exception("Failed to create payment record");
    }
    
    // Update registration payment status
    $update_stmt = $conn->prepare("UPDATE registrations SET payment_status = 'paid' WHERE registration_id = ?");
    $update_stmt->bind_param("i", $registration_id);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update registration status");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Get participant and event details for success message
    $details_stmt = $conn->prepare("SELECT p.full_name, e.event_name 
                                  FROM registrations r
                                  JOIN participants p ON r.participant_id = p.participant_id
                                  JOIN events e ON r.event_id = e.event_id
                                  WHERE r.registration_id = ?");
    $details_stmt->bind_param("i", $registration_id);
    $details_stmt->execute();
    $details_result = $details_stmt->get_result();
    $details = $details_result->fetch_assoc();
    
    // Redirect with success message
    header("Location: admin.php?tab=registrations&success=Payment verified for " . 
           htmlspecialchars($details['full_name']) . 
           " (" . htmlspecialchars($details['event_name']) . ")");
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    header("Location: admin.php?tab=registrations&error=" . urlencode($e->getMessage()));
}