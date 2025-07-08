<?php
session_start();
require 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['participant_id'])) {
    header("Location: existing.php");
    exit();
}

// Fetch participant data
$participant = [];
$stmt = $conn->prepare("SELECT * FROM participants WHERE participant_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['participant_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $participant = $result->fetch_assoc();
}
$stmt->close();

// Fetch events the participant is already registered for
$registered_events = [];
$stmt = $conn->prepare("SELECT e.event_id, e.event_name, e.fee, e.event_type, e.max_participants 
                       FROM registrations r
                       JOIN events e ON r.event_id = e.event_id
                       WHERE r.participant_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['participant_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $registered_events[] = $row;
}
$stmt->close();

// Fetch all available events (excluding already registered ones)
$available_events = [];
$stmt = $conn->prepare("SELECT * FROM events 
                       WHERE event_id NOT IN (
                           SELECT event_id FROM registrations 
                           WHERE participant_id = ?
                       )");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['participant_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $available_events[] = $row;
}
$stmt->close();

// Process new event registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['register_events'])) {
        $_SESSION['selected_events'] = $_POST['events'] ?? [];
        
        // Process team members if any
        if (isset($_POST['team_members'])) {
            $_SESSION['team_members'] = $_POST['team_members'];
        }
        
        header("Location: step3_payment.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Event System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .profile-card, .events-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border: none;
        }
        .profile-card .card-header, .events-card .card-header {
            background-color: #343a40;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .event-card {
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
            border-left: 4px solid transparent;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .event-card.selected {
            border-left-color: #28a745;
            background-color: #f8f9fa;
        }
        .badge-group {
            background-color: #17a2b8;
        }
        .badge-individual {
            background-color: #28a745;
        }
        .team-section {
            display: none;
            margin-top: 15px;
        }
        .team-member-card {
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h1><i class="bi bi-person-circle"></i> Welcome, <?= htmlspecialchars($participant['full_name'] ?? 'User') ?></h1>
                    <p class="lead">Manage your event registrations</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="index.php" class="btn btn-outline-light"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Profile Information -->
        <div class="profile-card card">
            <div class="card-header">
                <h3 class="mb-0">Your Profile</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Username:</strong> <?= htmlspecialchars($participant['username'] ?? '') ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($participant['email'] ?? '') ?></p>
                        <p><strong>College:</strong> <?= htmlspecialchars($participant['college'] ?? '') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Roll Number:</strong> <?= htmlspecialchars($participant['roll_number'] ?? '') ?></p>
                        <p><strong>Semester:</strong> <?= htmlspecialchars($participant['semester'] ?? '') ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($participant['phone'] ?? '') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registered Events -->
        <div class="events-card card">
            <div class="card-header">
                <h3 class="mb-0">Your Registered Events</h3>
            </div>
            <div class="card-body">
                <?php if (empty($registered_events)): ?>
                    <div class="alert alert-info">You haven't registered for any events yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Type</th>
                                    <th class="text-end">Fee</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registered_events as $event): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($event['event_name']) ?></td>
                                        <td>
                                            <span class="badge <?= $event['event_type'] == 'group' ? 'badge-group' : 'badge-individual' ?>">
                                                <?= ucfirst($event['event_type']) ?>
                                                <?php if ($event['event_type'] == 'group'): ?>
                                                    (Max <?= $event['max_participants'] ?>)
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td class="text-end">₹<?= number_format($event['fee'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Events -->
        <div class="events-card card">
            <div class="card-header">
                <h3 class="mb-0">Register for More Events</h3>
            </div>
            <div class="card-body">
                <form method="POST" id="eventForm">
                    <div class="row" id="eventList">
                        <?php foreach ($available_events as $event): ?>
                            <div class="col-md-6 mb-4">
                                <div class="event-card card" 
                                     data-event-id="<?= htmlspecialchars($event['event_id']) ?>"
                                     data-event-type="<?= htmlspecialchars($event['event_type']) ?>"
                                     data-fee="<?= htmlspecialchars($event['fee']) ?>"
                                     data-max-participants="<?= htmlspecialchars($event['max_participants']) ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($event['event_name']) ?></h5>
                                        <p class="card-text text-muted"><?= htmlspecialchars($event['description']) ?></p>
                                        <div class="d-flex justify-content-between">
                                            <span class="badge <?= $event['event_type'] == 'group' ? 'badge-group' : 'badge-individual' ?>">
                                                <?= ucfirst($event['event_type']) ?>
                                                <?php if ($event['event_type'] == 'group'): ?>
                                                    (Max <?= $event['max_participants'] ?>)
                                                <?php endif; ?>
                                            </span>
                                            <span class="fw-bold">₹<?= $event['fee'] ?></span>
                                        </div>
                                        <input type="checkbox" 
                                               name="events[]" 
                                               value="<?= htmlspecialchars($event['event_id']) ?>" 
                                               style="display: none;">
                                    </div>
                                </div>
                                
                                <?php if ($event['event_type'] == 'group'): ?>
                                <div class="team-section mt-3" id="team-<?= htmlspecialchars($event['event_id']) ?>">
                                    <h6>Team Members (Max <?= ($event['max_participants'] - 1) ?>)</h6>
                                    <div class="team-members-container" id="team-container-<?= htmlspecialchars($event['event_id']) ?>"></div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-member-btn"
                                            data-event-id="<?= htmlspecialchars($event['event_id']) ?>"
                                            onclick="addTeamMember('<?= htmlspecialchars($event['event_id']) ?>', <?= $event['max_participants'] ?>)">
                                        + Add Team Member (<span id="member-count-<?= htmlspecialchars($event['event_id']) ?>">0</span>/<?= ($event['max_participants'] - 1) ?>)
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($available_events)): ?>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" name="register_events" class="btn btn-primary">Proceed to Payment</button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No more events available for registration.</div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Team Member Template (hidden) -->
    <div id="teamMemberTemplate" style="display: none;">
        <div class="team-member-card">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="team_members[EVENT_ID][][full_name]" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Roll Number</label>
                    <input type="text" class="form-control" name="team_members[EVENT_ID][][roll_number]" required>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-danger mt-2" 
                    onclick="this.closest('.team-member-card').remove(); updateMemberCount('EVENT_ID')">
                Remove
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle event selection
        document.querySelectorAll('.event-card').forEach(card => {
            card.addEventListener('click', function() {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
                
                if (checkbox.checked) {
                    this.classList.add('selected');
                    const eventId = this.dataset.eventId;
                    const eventType = this.dataset.eventType;
                    
                    if (eventType === 'group') {
                        document.getElementById(`team-${eventId}`).style.display = 'block';
                    }
                } else {
                    this.classList.remove('selected');
                    const eventId = this.dataset.eventId;
                    document.getElementById(`team-${eventId}`).style.display = 'none';
                    
                    // Clear team members when unselecting
                    const container = document.getElementById(`team-container-${eventId}`);
                    if (container) {
                        container.innerHTML = '';
                        document.getElementById(`member-count-${eventId}`).textContent = '0';
                    }
                }
            });
        });
        
        // Add team member
        function addTeamMember(eventId, maxParticipants) {
            const container = document.getElementById(`team-container-${eventId}`);
            const currentCount = container.querySelectorAll('.team-member-card').length;
            const maxAllowed = maxParticipants - 1;
            
            if (currentCount >= maxAllowed) {
                alert(`Maximum ${maxAllowed} team members allowed for this event`);
                return;
            }
            
            const template = document.getElementById('teamMemberTemplate').innerHTML;
            const newMember = template.replace(/EVENT_ID/g, eventId);
            container.insertAdjacentHTML('beforeend', newMember);
            updateMemberCount(eventId);
        }
        
        // Update member count display
        function updateMemberCount(eventId) {
            const container = document.getElementById(`team-container-${eventId}`);
            const count = container.querySelectorAll('.team-member-card').length;
            document.getElementById(`member-count-${eventId}`).textContent = count;
        }
        
        // Form validation
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            const selectedEvents = document.querySelectorAll('input[name="events[]"]:checked');
            
            if (selectedEvents.length === 0) {
                e.preventDefault();
                alert('Please select at least one event');
                return;
            }
            
            // Validate team members for group events
            let isValid = true;
            selectedEvents.forEach(event => {
                const eventId = event.value;
                const eventCard = document.querySelector(`.event-card[data-event-id="${eventId}"]`);
                
                if (eventCard && eventCard.dataset.eventType === 'group') {
                    const memberContainer = document.getElementById(`team-container-${eventId}`);
                    const memberCount = memberContainer ? memberContainer.querySelectorAll('.team-member-card').length : 0;
                    const requiredMembers = parseInt(eventCard.dataset.maxParticipants) - 1;
                    
                    // Check if all required fields are filled
                    let allFilled = true;
                    if (memberContainer) {
                        const inputs = memberContainer.querySelectorAll('input');
                        inputs.forEach(input => {
                            if (!input.value.trim()) allFilled = false;
                        });
                    }
                    
                    if (memberCount < requiredMembers || !allFilled) {
                        isValid = false;
                        alert(`Please add all required team members (${requiredMembers}) and fill all fields for ${eventCard.querySelector('.card-title').textContent}`);
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>