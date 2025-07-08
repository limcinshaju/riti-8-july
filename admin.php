<?php
session_start();
require_once 'db_connect.php';

// Check if admin is logged in
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php?error=Please login as admin");
    exit();
}

// Get all data needed for dashboard
$participants = $conn->query("SELECT * FROM participants ORDER BY created_at DESC");
$registrations = $conn->query("SELECT r.*, p.full_name, p.email, e.event_name, 
                             IFNULL((SELECT a.attended FROM attendance a WHERE a.registration_id = r.registration_id), 0) as attendance_status
                             FROM registrations r
                             JOIN participants p ON r.participant_id = p.participant_id
                             JOIN events e ON r.event_id = e.event_id
                             ORDER BY r.registration_id ASC");
$events = $conn->query("SELECT * FROM events ORDER BY created_at DESC");
$payments = $conn->query("SELECT py.*, r.participant_id, r.event_id, p.full_name, e.event_name
                         FROM payments py
                         JOIN registrations r ON py.registration_id = r.registration_id
                         JOIN participants p ON r.participant_id = p.participant_id
                         JOIN events e ON r.event_id = e.event_id
                         ORDER BY py.payment_date DESC");
$reviews = $conn->query("SELECT rv.*, p.full_name, e.event_name
                        FROM reviews rv
                        JOIN participants p ON rv.participant_id = p.participant_id
                        JOIN events e ON rv.event_id = e.event_id
                        ORDER BY rv.created_at DESC");

// Get all event participants (for Event Participants tab)
$event_participants = $conn->query("
    SELECT e.event_id, e.event_name, p.participant_id, p.full_name, p.roll_number, p.college,
           r.registration_id, r.registration_date,
           IFNULL(g.group_name, 'Individual') AS participation_type
    FROM events e
    JOIN registrations r ON e.event_id = r.event_id
    JOIN participants p ON r.participant_id = p.participant_id
    LEFT JOIN group_members gm ON p.participant_id = gm.participant_id
    LEFT JOIN groups g ON gm.group_id = g.group_id AND g.event_id = r.event_id
    ORDER BY e.event_name, p.full_name
");

// Get attended participants only (for Attended Participants tab)
$attended_participants = $conn->query("
    SELECT e.event_id, e.event_name, p.participant_id, p.full_name, p.roll_number, p.college,
           r.registration_id, r.registration_date,
           IFNULL(g.group_name, 'Individual') AS participation_type
    FROM events e
    JOIN registrations r ON e.event_id = r.event_id
    JOIN participants p ON r.participant_id = p.participant_id
    LEFT JOIN group_members gm ON p.participant_id = gm.participant_id
    LEFT JOIN groups g ON gm.group_id = g.group_id AND g.event_id = r.event_id
    JOIN attendance a ON r.registration_id = a.registration_id
    WHERE a.attended = 1
    ORDER BY e.event_name, p.full_name
");

// Get all groups with their members
$groups = $conn->query("
    SELECT g.group_id, g.group_name, g.event_id, e.event_name, 
           p.participant_id as leader_id, p.full_name as leader_name,
           COUNT(gm.participant_id) as member_count
    FROM groups g
    JOIN events e ON g.event_id = e.event_id
    JOIN participants p ON g.leader_id = p.participant_id
    LEFT JOIN group_members gm ON g.group_id = gm.group_id
    GROUP BY g.group_id
");

// Get group members details
$group_members = [];
$group_members_result = $conn->query("
    SELECT gm.group_id, p.participant_id, p.full_name, p.roll_number, p.college
    FROM group_members gm
    JOIN participants p ON gm.participant_id = p.participant_id
    ORDER BY gm.group_id
");

while ($row = $group_members_result->fetch_assoc()) {
    $group_members[$row['group_id']][] = $row;
}

// Organize participants by event
$organized_all = [];
$organized_attended = [];
$event_participation_counts = [];

while ($row = $event_participants->fetch_assoc()) {
    $organized_all[$row['event_id']]['event_name'] = $row['event_name'];
    $organized_all[$row['event_id']]['participants'][] = $row;
    
    // Count participation
    if (!isset($event_participation_counts[$row['event_id']])) {
        $event_participation_counts[$row['event_id']] = [
            'event_name' => $row['event_name'],
            'total' => 0,
            'attended' => 0
        ];
    }
    $event_participation_counts[$row['event_id']]['total']++;
}

while ($row = $attended_participants->fetch_assoc()) {
    $organized_attended[$row['event_id']]['event_name'] = $row['event_name'];
    $organized_attended[$row['event_id']]['participants'][] = $row;
    $event_participation_counts[$row['event_id']]['attended']++;
}

// Get event-wise participation counts with attendance
$event_participation = $conn->query("
    SELECT e.event_id, e.event_name, 
           COUNT(r.registration_id) as participant_count,
           SUM(IFNULL(a.attended, 0)) as attended_count
    FROM events e
    LEFT JOIN registrations r ON e.event_id = r.event_id
    LEFT JOIN attendance a ON r.registration_id = a.registration_id
    GROUP BY e.event_id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RITI TechFest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.75);
        }
        .sidebar .nav-link:hover {
            color: rgba(255,255,255,1);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #007bff;
        }
        .card-counter {
            box-shadow: 2px 2px 10px #DADADA;
            margin: 5px;
            padding: 20px 10px;
            background-color: #fff;
            height: 120px;
            border-radius: 5px;
            transition: .3s linear all;
            position: relative;
        }
        .card-counter:hover {
            box-shadow: 4px 4px 20px #DADADA;
            transition: .3s linear all;
            transform: translateY(-5px);
        }
        .card-counter.primary {
            background-color: #007bff;
            color: #FFF;
        }
        .card-counter.danger {
            background-color: #ef5350;
            color: #FFF;
        }  
        .card-counter.success {
            background-color: #66bb6a;
            color: #FFF;
        }  
        .card-counter.info {
            background-color: #26c6da;
            color: #FFF;
        }  
        .card-counter i {
            font-size: 3em;
            opacity: 0.3;
            position: absolute;
            left: 20px;
            top: 20px;
        }
        .card-counter .count-numbers {
            position: absolute;
            right: 35px;
            top: 30px;
            font-size: 32px;
            display: block;
            font-weight: bold;
        }
        .card-counter .count-name {
            position: absolute;
            right: 35px;
            top: 70px;
            text-transform: capitalize;
            opacity: 0.9;
            display: block;
            font-size: 16px;
        }
        .card-counter .count-description {
            position: absolute;
            left: 20px;
            bottom: 15px;
            font-size: 12px;
            opacity: 0.8;
        }
        .counter-link {
            text-decoration: none;
            color: inherit;
        }
        .counter-link:hover {
            color: inherit;
        }
        .dashboard-card {
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
        }
        .dashboard-card .card-header {
            font-weight: 600;
        }
        .participation-table th {
            background-color: #495057;
            color: white;
        }
        .recent-feedback-item {
            border-left: 3px solid #0d6efd;
            padding-left: 10px;
            margin-bottom: 15px;
        }
        .participant-table th {
            background-color: #495057;
            color: white;
        }
        .attended-badge {
            background-color: #28a745;
        }
        .not-attended-badge {
            background-color: #dc3545;
        }
        .accordion-button:not(.collapsed) {
            background-color: #f8f9fa;
        }
        .group-badge {
            background-color: #6f42c1;
        }
        .individual-badge {
            background-color: #20c997;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="text-center p-3 bg-dark text-white">
                    <h4>RITI TechFest</h4>
                    <p class="mb-0">Admin Dashboard</p>
                </div>
                <div class="p-3">
                    <p class="text-white mb-1">Welcome,</p>
                    <h5 class="text-white"><?= htmlspecialchars($_SESSION['username']) ?></h5>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#dashboard" data-bs-toggle="tab">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="#participants" data-bs-toggle="tab">
                        <i class="fas fa-users me-2"></i>Participants
                    </a>
                    <a class="nav-link" href="#registrations" data-bs-toggle="tab">
                        <i class="fas fa-clipboard-list me-2"></i>Registrations
                    </a>
                    <a class="nav-link" href="#eventParticipants" data-bs-toggle="tab">
                        <i class="fas fa-users me-2"></i>Event Participants
                    </a>
                    <a class="nav-link" href="#attendedParticipants" data-bs-toggle="tab">
                        <i class="fas fa-user-check me-2"></i>Attended Participants
                    </a>
                    <a class="nav-link" href="#groups" data-bs-toggle="tab">
                        <i class="fas fa-users me-2"></i>Groups
                    </a>
                    <a class="nav-link" href="#events" data-bs-toggle="tab">
                        <i class="fas fa-calendar-alt me-2"></i>Events
                    </a>
                    <a class="nav-link" href="#payments" data-bs-toggle="tab">
                        <i class="fas fa-money-bill-wave me-2"></i>Payments
                    </a>
                    <a class="nav-link" href="#reviews" data-bs-toggle="tab">
                        <i class="fas fa-comments me-2"></i>Feedback
                    </a>
                    <a class="nav-link" href="#coordinators" data-bs-toggle="tab">
                        <i class="fas fa-user-tie me-2"></i>Coordinators
                    </a>
                    <a class="nav-link mt-4" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($_GET['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($_GET['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane active" id="dashboard">
                        <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h2>
                        <hr>
                        
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <a href="#participants" class="counter-link" data-bs-toggle="tab">
                                    <div class="card-counter primary">
                                        <i class="fas fa-users"></i>
                                        <span class="count-numbers"><?= $participants->num_rows ?></span>
                                        <span class="count-name">Participants</span>
                                        <span class="count-description">Total registered participants</span>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="#events" class="counter-link" data-bs-toggle="tab">
                                    <div class="card-counter success">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span class="count-numbers"><?= $events->num_rows ?></span>
                                        <span class="count-name">Events</span>
                                        <span class="count-description">Total events created</span>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="#registrations" class="counter-link" data-bs-toggle="tab">
                                    <div class="card-counter info">
                                        <i class="fas fa-clipboard-list"></i>
                                        <span class="count-numbers"><?= $registrations->num_rows ?></span>
                                        <span class="count-name">Registrations</span>
                                        <span class="count-description">Total event registrations</span>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="#payments" class="counter-link" data-bs-toggle="tab">
                                    <div class="card-counter danger">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span class="count-numbers"><?= $payments->num_rows ?></span>
                                        <span class="count-name">Payments</span>
                                        <span class="count-description">Total payments received</span>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card dashboard-card">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-chart-bar me-2"></i>Event Participation & Attendance
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-striped participation-table">
                                            <thead>
                                                <tr>
                                                    <th>Event</th>
                                                    <th>Registered</th>
                                                    <th>Attended</th>
                                                    <th>Attendance %</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($event = $event_participation->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($event['event_name']) ?></td>
                                                        <td><?= $event['participant_count'] ?></td>
                                                        <td><?= $event['attended_count'] ?></td>
                                                        <td>
                                                            <?= $event['participant_count'] > 0 ? 
                                                                round(($event['attended_count'] / $event['participant_count']) * 100, 2) : 0 ?>%
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card dashboard-card">
                                    <div class="card-header bg-success text-white">
                                        <i class="fas fa-comments me-2"></i>Recent Feedback
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $reviews->data_seek(0);
                                        $count = 0;
                                        
                                        if($reviews->num_rows > 0): ?>
                                            <?php while($review = $reviews->fetch_assoc()): ?>
                                                <?php if($count >= 3) break; ?>
                                                <div class="recent-feedback-item">
                                                    <strong><?= htmlspecialchars($review['full_name']) ?></strong> 
                                                    (<?= htmlspecialchars($review['event_name']) ?>)
                                                    <p><?= htmlspecialchars($review['review_text']) ?></p>
                                                    <small class="text-muted"><?= $review['created_at'] ?></small>
                                                </div>
                                                <?php $count++; ?>
                                            <?php endwhile; ?>
                                            <div class="text-center mt-3">
                                                <a href="#reviews" class="btn btn-sm btn-primary" data-bs-toggle="tab">View All Feedback</a>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No feedback submitted yet.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Participants Tab -->
                    <div class="tab-pane" id="participants">
                        <h2><i class="fas fa-users me-2"></i>Participant Management</h2>
                        <hr>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Roll Number</th>
                                        <th>Email</th>
                                        <th>College</th>
                                        <th>Course</th>
                                        <th>Semester</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($participant = $participants->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $participant['participant_id'] ?></td>
                                            <td><?= htmlspecialchars($participant['username']) ?></td>
                                            <td><?= htmlspecialchars($participant['full_name']) ?></td>
                                            <td><?= htmlspecialchars($participant['roll_number']) ?></td>
                                            <td><?= htmlspecialchars($participant['email']) ?></td>
                                            <td><?= htmlspecialchars($participant['college']) ?></td>
                                            <td><?= htmlspecialchars($participant['course']) ?></td>
                                            <td><?= $participant['semester'] ?></td>
                                            <td><?= $participant['created_at'] ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Registrations Tab -->
                    <div class="tab-pane" id="registrations">
                        <h2><i class="fas fa-clipboard-list me-2"></i>Registration Management</h2>
                        <hr>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Reg ID</th>
                                        <th>Participant</th>
                                        <th>Event</th>
                                        <th>Registered On</th>
                                        <th>Attendance Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($registration = $registrations->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $registration['registration_id'] ?></td>
                                            <td><?= htmlspecialchars($registration['full_name']) ?></td>
                                            <td><?= htmlspecialchars($registration['event_name']) ?></td>
                                            <td><?= $registration['registration_date'] ?></td>
                                            <td>
                                                <?php if ($registration['attendance_status'] == 1): ?>
                                                    <span class="badge attended-badge">Attended</span>
                                                <?php else: ?>
                                                    <span class="badge not-attended-badge">Not Attended</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Event Participants Tab -->
                    <div class="tab-pane" id="eventParticipants">
                        <h2><i class="fas fa-users me-2"></i>Event Participants</h2>
                        <hr>
                        
                        <div class="accordion" id="eventParticipantsAccordion">
                            <?php foreach ($organized_all as $event_id => $event_data): ?>
                            <?php 
                                $participants = $event_data['participants'];
                                $event_name = $event_data['event_name'];
                            ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="eventHeading<?= $event_id ?>">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#eventCollapse<?= $event_id ?>" aria-expanded="true" 
                                            aria-controls="eventCollapse<?= $event_id ?>">
                                        <?= htmlspecialchars($event_name) ?>
                                        <span class="badge bg-primary ms-2"><?= count($participants) ?> registered</span>
                                    </button>
                                </h2>
                                <div id="eventCollapse<?= $event_id ?>" class="accordion-collapse collapse" 
                                     aria-labelledby="eventHeading<?= $event_id ?>" data-bs-parent="#eventParticipantsAccordion">
                                    <div class="accordion-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped participant-table">
                                                <thead>
                                                    <tr>
                                                        <th>Participant</th>
                                                        <th>Roll No.</th>
                                                        <th>College</th>
                                                        <th>Participation</th>
                                                        <th>Registered On</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($participants as $participant): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($participant['full_name']) ?></td>
                                                        <td><?= htmlspecialchars($participant['roll_number']) ?></td>
                                                        <td><?= htmlspecialchars($participant['college']) ?></td>
                                                        <td>
                                                            <?php if ($participant['participation_type'] == 'Individual'): ?>
                                                                <span class="badge individual-badge">Individual</span>
                                                            <?php else: ?>
                                                                <span class="badge group-badge"><?= htmlspecialchars($participant['participation_type']) ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= date('d M Y', strtotime($participant['registration_date'])) ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Attended Participants Tab -->
                    <div class="tab-pane" id="attendedParticipants">
                        <h2><i class="fas fa-user-check me-2"></i>Attended Participants</h2>
                        <hr>
                        
                        <div class="accordion" id="attendedAccordion">
                            <?php foreach ($organized_attended as $event_id => $event_data): ?>
                            <?php 
                                $participants = $event_data['participants'];
                                $event_name = $event_data['event_name'];
                            ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="attendedHeading<?= $event_id ?>">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#attendedCollapse<?= $event_id ?>" aria-expanded="true" 
                                            aria-controls="attendedCollapse<?= $event_id ?>">
                                        <?= htmlspecialchars($event_name) ?>
                                        <span class="badge bg-success ms-2"><?= count($participants) ?> attended</span>
                                    </button>
                                </h2>
                                <div id="attendedCollapse<?= $event_id ?>" class="accordion-collapse collapse" 
                                     aria-labelledby="attendedHeading<?= $event_id ?>" data-bs-parent="#attendedAccordion">
                                    <div class="accordion-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped participant-table">
                                                <thead>
                                                    <tr>
                                                        <th>Participant</th>
                                                        <th>Roll No.</th>
                                                        <th>College</th>
                                                        <th>Participation</th>
                                                        <th>Registered On</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($participants as $participant): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($participant['full_name']) ?></td>
                                                        <td><?= htmlspecialchars($participant['roll_number']) ?></td>
                                                        <td><?= htmlspecialchars($participant['college']) ?></td>
                                                        <td>
                                                            <?php if ($participant['participation_type'] == 'Individual'): ?>
                                                                <span class="badge individual-badge">Individual</span>
                                                            <?php else: ?>
                                                                <span class="badge group-badge"><?= htmlspecialchars($participant['participation_type']) ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= date('d M Y', strtotime($participant['registration_date'])) ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Groups Tab -->
                    <div class="tab-pane" id="groups">
                        <h2><i class="fas fa-users me-2"></i>Group Management</h2>
                        <hr>
                        
                        <div class="accordion" id="groupsAccordion">
                            <?php while($group = $groups->fetch_assoc()): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="groupHeading<?= $group['group_id'] ?>">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#groupCollapse<?= $group['group_id'] ?>" aria-expanded="true" 
                                            aria-controls="groupCollapse<?= $group['group_id'] ?>">
                                        <?= htmlspecialchars($group['group_name']) ?> 
                                        <span class="badge bg-primary ms-2"><?= $group['member_count'] ?> members</span>
                                        <span class="ms-2">(<?= htmlspecialchars($group['event_name']) ?>)</span>
                                    </button>
                                </h2>
                                <div id="groupCollapse<?= $group['group_id'] ?>" class="accordion-collapse collapse" 
                                     aria-labelledby="groupHeading<?= $group['group_id'] ?>" data-bs-parent="#groupsAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <h5>Group Details</h5>
                                            <p><strong>Event:</strong> <?= htmlspecialchars($group['event_name']) ?></p>
                                            <p><strong>Leader:</strong> <?= htmlspecialchars($group['leader_name']) ?></p>
                                        </div>
                                        
                                        <h5>Group Members</h5>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Member</th>
                                                        <th>Roll Number</th>
                                                        <th>College</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (isset($group_members[$group['group_id']])): ?>
                                                        <?php foreach ($group_members[$group['group_id']] as $member): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($member['full_name']) ?></td>
                                                            <td><?= htmlspecialchars($member['roll_number']) ?></td>
                                                            <td><?= htmlspecialchars($member['college']) ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="3" class="text-center">No members found</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Events Tab -->
                    <div class="tab-pane" id="events">
                        <h2><i class="fas fa-calendar-alt me-2"></i>Event Management</h2>
                        <hr>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Event ID</th>
                                        <th>Event Name</th>
                                        <th>Type</th>
                                        <th>Max Participants</th>
                                        <th>Fee</th>
                                        <th>Created On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($event = $events->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($event['event_id']) ?></td>
                                            <td><?= htmlspecialchars($event['event_name']) ?></td>
                                            <td><?= ucfirst($event['event_type']) ?></td>
                                            <td><?= $event['max_participants'] ?></td>
                                            <td>₹<?= number_format($event['fee'], 2) ?></td>
                                            <td><?= $event['created_at'] ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payments Tab -->
                    <div class="tab-pane" id="payments">
                        <h2><i class="fas fa-money-bill-wave me-2"></i>Payment Management</h2>
                        <hr>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Participant</th>
                                        <th>Event</th>
                                        <th>Amount</th>
                                        <th>Transaction ID</th>
                                        <th>Payment Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($payment = $payments->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $payment['payment_id'] ?></td>
                                            <td><?= htmlspecialchars($payment['full_name']) ?></td>
                                            <td><?= htmlspecialchars($payment['event_name']) ?></td>
                                            <td>₹<?= number_format($payment['amount'], 2) ?></td>
                                            <td><?= $payment['transaction_id'] ? htmlspecialchars($payment['transaction_id']) : 'N/A' ?></td>
                                            <td><?= $payment['payment_date'] ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Reviews Tab -->
                    <div class="tab-pane" id="reviews">
                        <h2><i class="fas fa-comments me-2"></i>Feedback Management</h2>
                        <hr>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Review ID</th>
                                        <th>Participant</th>
                                        <th>Event</th>
                                        <th>Feedback</th>
                                        <th>Submitted On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $reviews->data_seek(0); ?>
                                    <?php while($review = $reviews->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $review['review_id'] ?></td>
                                            <td><?= htmlspecialchars($review['full_name']) ?></td>
                                            <td><?= htmlspecialchars($review['event_name']) ?></td>
                                            <td><?= htmlspecialchars($review['review_text']) ?></td>
                                            <td><?= $review['created_at'] ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Coordinators Tab -->
                    <div class="tab-pane" id="coordinators">
                        <h2><i class="fas fa-user-tie me-2"></i>Coordinator Management</h2>
                        <hr>
                        
                        <?php 
                        $coordinators = $conn->query("SELECT c.*, e.event_name, e.event_id 
                                                     FROM coordinators c
                                                     JOIN events e ON c.event_id = e.event_id");
                        ?>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Coord ID</th>
                                        <th>Username</th>
                                        <th>Event ID</th>
                                        <th>Assigned Event</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($coordinator = $coordinators->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $coordinator['coordinator_id'] ?></td>
                                            <td><?= htmlspecialchars($coordinator['username']) ?></td>
                                            <td><?= $coordinator['event_id'] ?></td>
                                            <td><?= htmlspecialchars($coordinator['event_name']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>