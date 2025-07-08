<?php
session_start();
require_once 'db_connect.php';

// Check if admin is logged in
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit(json_encode(['error' => 'Access denied']));
}

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_participant':
        if (empty($_GET['id'])) {
            exit(json_encode(['error' => 'Participant ID is required']));
        }
        $id = (int)$_GET['id'];
        $result = $conn->query("SELECT * FROM participants WHERE participant_id = $id");
        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(['error' => 'Participant not found']);
        }
        break;

    case 'get_event':
        if (empty($_GET['id'])) {
            exit(json_encode(['error' => 'Event ID is required']));
        }
        $id = $conn->real_escape_string($_GET['id']);
        $result = $conn->query("SELECT * FROM events WHERE event_id = '$id'");
        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(['error' => 'Event not found']);
        }
        break;

    case 'get_coordinator':
        if (empty($_GET['id'])) {
            exit(json_encode(['error' => 'Coordinator ID is required']));
        }
        $id = (int)$_GET['id'];
        $result = $conn->query("SELECT * FROM coordinators WHERE coordinator_id = $id");
        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(['error' => 'Coordinator not found']);
        }
        break;

    case 'edit_participant':
        if (empty($_POST['participant_id'])) {
            header("Location: admin_dashboard.php?error=Participant ID is required");
            exit();
        }
        
        $id = (int)$_POST['participant_id'];
        $username = $conn->real_escape_string($_POST['username']);
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $roll_number = $conn->real_escape_string($_POST['roll_number']);
        $email = $conn->real_escape_string($_POST['email']);
        $college = $conn->real_escape_string($_POST['college']);
        $course = $conn->real_escape_string($_POST['course']);
        $semester = (int)$_POST['semester'];
        
        // Check if password was provided
        $password_update = '';
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_update = ", password = '$password'";
        }
        
        $sql = "UPDATE participants SET 
                username = '$username',
                full_name = '$full_name',
                roll_number = '$roll_number',
                email = '$email',
                college = '$college',
                course = '$course',
                semester = $semester
                $password_update
                WHERE participant_id = $id";
        
        if ($conn->query($sql)) {
            header("Location: admin_dashboard.php?success=Participant updated successfully");
        } else {
            header("Location: admin_dashboard.php?error=Failed to update participant");
        }
        break;

    case 'edit_event':
        if (empty($_POST['event_id'])) {
            header("Location: admin_dashboard.php?error=Event ID is required");
            exit();
        }
        
        $id = $conn->real_escape_string($_POST['event_id']);
        $name = $conn->real_escape_string($_POST['event_name']);
        $type = $conn->real_escape_string($_POST['event_type']);
        $description = $conn->real_escape_string($_POST['description']);
        $max_participants = (int)$_POST['max_participants'];
        $fee = (float)$_POST['fee'];
        
        $sql = "UPDATE events SET 
                event_name = '$name',
                event_type = '$type',
                description = '$description',
                max_participants = $max_participants,
                fee = $fee
                WHERE event_id = '$id'";
        
        if ($conn->query($sql)) {
            header("Location: admin_dashboard.php?success=Event updated successfully");
        } else {
            header("Location: admin_dashboard.php?error=Failed to update event");
        }
        break;

    case 'edit_coordinator':
        if (empty($_POST['coordinator_id'])) {
            header("Location: admin_dashboard.php?error=Coordinator ID is required");
            exit();
        }
        
        $id = (int)$_POST['coordinator_id'];
        $username = $conn->real_escape_string($_POST['username']);
        $event_id = $conn->real_escape_string($_POST['event_id']);
        
        // Check if password was provided
        $password_update = '';
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $password_update = ", password = '$password'";
        }
        
        $sql = "UPDATE coordinators SET 
                username = '$username',
                event_id = '$event_id'
                $password_update
                WHERE coordinator_id = $id";
        
        if ($conn->query($sql)) {
            header("Location: admin_dashboard.php?success=Coordinator updated successfully");
        } else {
            header("Location: admin_dashboard.php?error=Failed to update coordinator");
        }
        break;

    default:
        header("HTTP/1.1 400 Bad Request");
        exit(json_encode(['error' => 'Invalid action']));
}