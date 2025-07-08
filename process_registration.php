<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['participant_id'])) {
    header("Location: existinguser.php"); // Redirect to login if not logged in
    exit();
}

// Check if events were selected from the dashboard
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['events'])) {
    // Store selected events in session
    $_SESSION['selected_events'] = $_POST['events'];
    
    // Store team members if any
    if (isset($_POST['team_members'])) {
        $_SESSION['team_members'] = $_POST['team_members'];
    }
    
    // Calculate total amount
    $total_amount = 0;
    $event_details = [];
    foreach ($_SESSION['selected_events'] as $event_id) {
        $stmt = $conn->prepare("SELECT event_id, event_name, fee FROM events WHERE event_id = ?");
        $stmt->bind_param("s", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($event = $result->fetch_assoc()) {
            $total_amount += $event['fee'];
            $event_details[] = $event;
        }
    }
    $_SESSION['total_amount'] = $total_amount;
    
    // Redirect directly to payment since we're coming from dashboard
    header("Location: step3_payment.php");
    exit();
} else {
    // If no events selected, redirect back to dashboard
    header("Location: dashboard.php");
    exit();
}