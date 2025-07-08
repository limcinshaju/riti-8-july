<?php
session_start();

// Redirect if not logged in as coordinator
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'coordinator') {
    header("Location: login.php?error=Please+login+as+coordinator");
    exit();
}

// Database configuration - Update with your actual credentials
$db_host = 'localhost';
$db_name = 'riti';
$db_user = 'root';
$db_pass = '';

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed. Please check your credentials. Error: " . $e->getMessage());
}

// Handle attendance update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $registration_id = $_POST['registration_id'];
    $attended = $_POST['attended'] === 'present' ? 1 : 0;
    
    try {
        // Check if attendance record already exists
        $check_stmt = $pdo->prepare("SELECT * FROM attendance WHERE registration_id = ?");
        $check_stmt->execute([$registration_id]);
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing record
            $update_stmt = $pdo->prepare("UPDATE attendance SET attended = ? WHERE registration_id = ?");
            $update_stmt->execute([$attended, $registration_id]);
        } else {
            // Insert new record
            $insert_stmt = $pdo->prepare("INSERT INTO attendance (registration_id, attended) VALUES (?, ?)");
            $insert_stmt->execute([$registration_id, $attended]);
        }
        
        $_SESSION['success'] = "Attendance updated successfully!";
        header("Location: coordinator.php");
        exit();
    } catch (PDOException $e) {
        die("Error updating attendance: " . $e->getMessage());
    }
}

// Get coordinator's event details
try {
    $coordinator_stmt = $pdo->prepare("SELECT * FROM coordinators WHERE coordinator_id = ?");
    $coordinator_stmt->execute([$_SESSION['user_id']]);
    $coordinator = $coordinator_stmt->fetch();
    
    if (!$coordinator) {
        die("Coordinator not found.");
    }

    $event_stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
    $event_stmt->execute([$coordinator['event_id']]);
    $event = $event_stmt->fetch();
    
    if (!$event) {
        die("No event assigned to this coordinator.");
    }

    // Get participants for this event
    $participants_stmt = $pdo->prepare("
        SELECT p.participant_id, p.full_name, p.roll_number, p.email, p.college, 
               r.registration_id, r.registration_date,
               IFNULL(g.group_name, 'Individual') AS participation_type
        FROM registrations r
        JOIN participants p ON r.participant_id = p.participant_id
        LEFT JOIN group_members gm ON p.participant_id = gm.participant_id
        LEFT JOIN groups g ON gm.group_id = g.group_id AND g.event_id = r.event_id
        WHERE r.event_id = ?
        ORDER BY r.registration_date DESC
    ");
    $participants_stmt->execute([$event['event_id']]);
    $participants = $participants_stmt->fetchAll();

    // Get attendance data
    $attendance_stmt = $pdo->prepare("
        SELECT a.registration_id, a.attended, a.marked_at
        FROM attendance a
        JOIN registrations r ON a.registration_id = r.registration_id
        WHERE r.event_id = ?
    ");
    $attendance_stmt->execute([$event['event_id']]);
    $attendance_data = $attendance_stmt->fetchAll();
    
    // Create attendance lookup array
    $attendance = [];
    foreach ($attendance_data as $record) {
        $attendance[$record['registration_id']] = $record;
    }
    
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard - <?= htmlspecialchars($event['event_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .event-card {
            margin-bottom: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .participant-table th {
            background-color: #495057;
            color: white;
        }
        .attended-badge {
            background-color: #28a745;
        }
        .absent-badge {
            background-color: #dc3545;
        }
        .attendance-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Coordinator Dashboard</h1>
                    <p class="lead mb-0">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-12">
                <div class="card event-card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">Event Details</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h3><?= htmlspecialchars($event['event_name']) ?></h3>
                                <p class="text-muted">Event ID: <?= htmlspecialchars($event['event_id']) ?></p>
                                <p><?= htmlspecialchars($event['description']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <strong>Event Type:</strong> 
                                        <?= ucfirst(htmlspecialchars($event['event_type'])) ?>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Max Participants:</strong> 
                                        <?= htmlspecialchars($event['max_participants']) ?>
                                    </li>
                                    <li class="list-group-item">
                                        <strong>Total Registrations:</strong> 
                                        <?= count($participants) ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2 class="mb-0">Registered Participants</h2>
                        <span class="badge bg-light text-dark">Total: <?= count($participants) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (count($participants) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped participant-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Roll No.</th>
                                            <th>College</th>
                                            <th>Participation</th>
                                            <th>Attendance</th>
                                            <th>Actions</th>
                                            <th>Registered On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($participants as $participant): 
                                            $current_attendance = isset($attendance[$participant['registration_id']]) ? $attendance[$participant['registration_id']]['attended'] : null;
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($participant['participant_id']) ?></td>
                                            <td><?= htmlspecialchars($participant['full_name']) ?></td>
                                            <td><?= htmlspecialchars($participant['roll_number']) ?></td>
                                            <td><?= htmlspecialchars($participant['college']) ?></td>
                                            <td><?= htmlspecialchars($participant['participation_type']) ?></td>
                                            <td>
                                                <?php if ($current_attendance !== null): ?>
                                                    <?php if ($current_attendance == 1): ?>
                                                        <span class="badge attended-badge">Present</span>
                                                    <?php else: ?>
                                                        <span class="badge absent-badge">Absent</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Not Marked</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- Attendance Form -->
                                                <form method="post" class="attendance-form">
                                                    <input type="hidden" name="registration_id" value="<?= $participant['registration_id'] ?>">
                                                    <select name="attended" class="form-select form-select-sm">
                                                        <option value="present" <?= ($current_attendance === 1) ? 'selected' : '' ?>>Present</option>
                                                        <option value="absent" <?= ($current_attendance === 0) ? 'selected' : '' ?>>Absent</option>
                                                    </select>
                                                    <button type="submit" name="update_attendance" class="btn btn-sm btn-primary">Update</button>
                                                </form>
                                            </td>
                                            <td><?= date('d M Y', strtotime($participant['registration_date'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No participants have registered for this event yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>
</html>