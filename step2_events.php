<?php
session_start();
require_once 'db_connect.php';

// Check if user has completed step 1
if (!isset($_SESSION['participant_id'])) {
    header("Location: step1_form.php");
    exit();
}

// Fetch all events from database
$events = [];
$query = "SELECT event_id, event_name, description, event_type, fee, max_participants FROM events";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
} else {
    die("Error fetching events: " . mysqli_error($conn));
}

// Initialize team members from session if available
$teamMembers = $_SESSION['team_members'] ?? [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['events'])) {
        $_SESSION['selected_events'] = $_POST['events'];
        
        // Process team members with proper structure
        $teamMembers = [];
        foreach ($_POST['events'] as $eventId) {
            if (isset($_POST['team_members'][$eventId])) {
                foreach ($_POST['team_members'][$eventId] as $member) {
                    if (!empty($member['full_name']) && !empty($member['roll_number'])) {
                        $teamMembers[$eventId][] = [
                            'full_name' => trim($member['full_name']),
                            'roll_number' => trim($member['roll_number'])
                        ];
                    }
                }
            }
        }
        
        $_SESSION['team_members'] = $teamMembers;
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
    <title>Select Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Video Background Styles */
        .video-background-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        .video-background {
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            object-fit: cover;
        }
        
        /* Content Styles */
        body {
            position: relative;
        }
        .content-wrapper {
            position: relative;
            z-index: 1;
        }
        
        .event-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .event-card.selected {
            border-left: 4px solid #28a745;
            background-color: #f8f9fa;
        }
        .team-section {
            display: none;
            margin-top: 15px;
        }
        .badge-group {
            background-color: #17a2b8;
        }
        .badge-individual {
            background-color: #28a745;
        }
        .team-member-card {
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            background-color: #f8f9fa;
        }
        .member-count {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Video Background -->
    <div class="video-background-container">
        <video autoplay muted loop playsinline class="video-background">
            <source src="videos/contactbg.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <?php include 'navbar.php'; ?>
        
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Select Events</h4>
                            <p class="mb-0">Choose one or more events to participate in</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="eventForm">
                                <div class="row" id="eventList">
                                    <?php foreach ($events as $event): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="event-card card" 
                                                 data-event-id="<?= htmlspecialchars($event['event_id']) ?>"
                                                 data-event-type="<?= htmlspecialchars($event['event_type']) ?>"
                                                 data-fee="<?= htmlspecialchars($event['fee']) ?>"
                                                 data-max-participants="<?= htmlspecialchars($event['max_participants']) ?>">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?= htmlspecialchars($event['event_name'] ?? 'Event') ?></h5>
                                                    <p class="card-text text-muted"><?= htmlspecialchars($event['description'] ?? '') ?></p>
                                                    <div class="d-flex justify-content-between">
                                                        <span class="badge <?= $event['event_type'] == 'group' ? 'badge-group' : 'badge-individual' ?>">
                                                            <?= ucfirst(htmlspecialchars($event['event_type'])) ?>
                                                            <?php if ($event['event_type'] == 'group'): ?>
                                                                (Max <?= htmlspecialchars($event['max_participants']) ?>)
                                                            <?php endif; ?>
                                                        </span>
                                                        <span class="fw-bold">₹<?= htmlspecialchars($event['fee']) ?></span>
                                                    </div>
                                                    <input type="checkbox" 
                                                           name="events[]" 
                                                           value="<?= htmlspecialchars($event['event_id']) ?>" 
                                                           style="display: none;"
                                                           <?= isset($_SESSION['selected_events']) && in_array($event['event_id'], $_SESSION['selected_events']) ? 'checked' : '' ?>>
                                                </div>
                                            </div>
                                            
                                            <?php if ($event['event_type'] == 'group'): ?>
                                            <div class="team-section mt-3" id="team-<?= htmlspecialchars($event['event_id']) ?>"
                                                <?= isset($_SESSION['selected_events']) && in_array($event['event_id'], $_SESSION['selected_events']) ? 'style="display:block;"' : '' ?>>
                                                <h6>Team Members (Max <?= ($event['max_participants'] - 1) ?>)</h6>
                                                <div class="team-members-container" id="team-container-<?= htmlspecialchars($event['event_id']) ?>">
                                                    <?php if (isset($teamMembers[$event['event_id']])): ?>
                                                        <?php foreach ($teamMembers[$event['event_id']] as $index => $member): ?>
                                                            <div class="team-member-card">
                                                                <div class="row g-2">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Full Name</label>
                                                                        <input type="text" class="form-control" 
                                                                               name="team_members[<?= htmlspecialchars($event['event_id']) ?>][<?= $index ?>][full_name]" 
                                                                               value="<?= htmlspecialchars($member['full_name'] ?? '') ?>" required>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Roll Number</label>
                                                                        <input type="text" class="form-control" 
                                                                               name="team_members[<?= htmlspecialchars($event['event_id']) ?>][<?= $index ?>][roll_number]" 
                                                                               value="<?= htmlspecialchars($member['roll_number'] ?? '') ?>" required>
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn btn-sm btn-danger mt-2" 
                                                                        onclick="this.closest('.team-member-card').remove(); updateMemberCount('<?= htmlspecialchars($event['event_id']) ?>')">
                                                                    Remove
                                                                </button>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-member-btn"
                                                        data-event-id="<?= htmlspecialchars($event['event_id']) ?>"
                                                        onclick="addTeamMember('<?= htmlspecialchars($event['event_id']) ?>', <?= htmlspecialchars($event['max_participants']) ?>)">
                                                    + Add Team Member (<span id="member-count-<?= htmlspecialchars($event['event_id']) ?>" class="member-count">
                                                        <?= isset($teamMembers[$event['event_id']]) ? count($teamMembers[$event['event_id']]) : 0 ?>
                                                    </span>/<?= ($event['max_participants'] - 1) ?>)
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="step1_form.php" class="btn btn-secondary">Previous</a>
                                    <button type="submit" class="btn btn-primary">Proceed to Payment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <?php include 'footer.php'; ?>
    </div>

    <!-- Team Member Template (hidden) -->
    <div id="teamMemberTemplate" style="display: none;">
        <div class="team-member-card">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="team_members[EVENT_ID][MEMBER_INDEX][full_name]" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Roll Number</label>
                    <input type="text" class="form-control" name="team_members[EVENT_ID][MEMBER_INDEX][roll_number]" required>
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
        // Initialize event cards based on session data
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.event-card').forEach(card => {
                const checkbox = card.querySelector('input[type="checkbox"]');
                if (checkbox.checked) {
                    card.classList.add('selected');
                }
            });
        });

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
                    document.getElementById(`team-container-${eventId}`).innerHTML = '';
                    document.getElementById(`member-count-${eventId}`).textContent = '0';
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
            const newIndex = currentCount;
            const newMember = template
                .replace(/EVENT_ID/g, eventId)
                .replace(/MEMBER_INDEX/g, newIndex);
            
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
                
                if (eventCard.dataset.eventType === 'group') {
                    const memberCount = document.getElementById(`team-container-${eventId}`).querySelectorAll('.team-member-card').length;
                    
                    if (memberCount === 0) {
                        isValid = false;
                        alert(`Please add at least one team member for ${eventCard.querySelector('.card-title').textContent}`);
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