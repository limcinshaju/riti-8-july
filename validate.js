document.addEventListener("DOMContentLoaded", function() {
    // DOM Elements
    const form = document.getElementById('registrationForm');
    const steps = document.querySelectorAll('.form-step');
    let currentStep = 0;

    // ======================
    // 1. VALIDATION FUNCTIONS
    // ======================

    function validatePersonalDetails() {
        let isValid = true;
        
        // Name validation
        const name = document.getElementById('name').value.trim();
        if (!/^[a-zA-Z\s]{3,}$/.test(name)) {
            document.getElementById('name-error').textContent = 'Please enter a valid name (letters only, min 3 chars)';
            isValid = false;
        } else {
            document.getElementById('name-error').textContent = '';
        }
        
        // Roll number validation
        const rollno = document.getElementById('rollno').value.trim();
        if (!/^[a-zA-Z0-9]{5,}$/.test(rollno)) {
            document.getElementById('rollno-error').textContent = 'Please enter a valid roll number (min 5 chars)';
            isValid = false;
        } else {
            document.getElementById('rollno-error').textContent = '';
        }

        const semester = document.getElementById('semester').value;
        if (!semester) {
            document.getElementById('semester').classList.add('is-invalid');
            isValid = false;
        } else {
            document.getElementById('semester').classList.remove('is-invalid');
        }
        
        // Phone validation
        const phone = document.getElementById('phone').value.trim();
        if (!/^\d{10}$/.test(phone)) {
            document.getElementById('phone-error').textContent = 'Please enter a 10-digit phone number';
            isValid = false;
        } else {
            document.getElementById('phone-error').textContent = '';
        }
        
        // Email validation
        const email = document.getElementById('email').value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            document.getElementById('email-error').textContent = 'Please enter a valid email address';
            isValid = false;
        } else {
            document.getElementById('email-error').textContent = '';
        }
        
        return isValid;
    }

    function validateEventSelection() {
        const checkedEvents = document.querySelectorAll('input[name="events[]"]:checked');
        if (checkedEvents.length === 0) {
            alert('Please select at least one event');
            return false;
        }
        return true;
    }

    function validateTeamMembers() {
        const teamInputs = document.querySelectorAll('#coParticipantFields input[required]');
        if (teamInputs.length === 0) return true; // No team events selected
        
        let allValid = true;
        teamInputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                allValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        if (!allValid) {
            alert('Please fill all required team member details');
            return false;
        }
        return true;
    }

    function validatePayment() {
        const paymentSelected = document.querySelector('input[name="payment_method"]:checked');
        if (!paymentSelected) {
            alert('Please select a payment method');
            return false;
        }
        return true;
    }

    // ======================
    // 2. HELPER FUNCTIONS
    // ======================

    function showStep(stepIndex) {
        steps.forEach((step, index) => {
            step.classList.toggle('active', index === stepIndex);
        });
        
        if (stepIndex === 3) updateSummary();
    }

    function updateSummary() {
        document.getElementById('summary-name').textContent = document.getElementById('name').value;
        
        const eventsList = Array.from(document.querySelectorAll('input[name="events[]"]:checked'))
            .map(checkbox => checkbox.nextElementSibling.textContent.split(' (')[0])
            .join(', ');
        
        document.getElementById('summary-events').textContent = eventsList || 'None';
        calculateTotalFee();
    }

    function calculateTotalFee() {
        let total = 0;
        document.querySelectorAll('input[name="events[]"]:checked').forEach(event => {
            total += parseInt(event.dataset.fee) || 0;
        });
        document.getElementById('totalAmount').textContent = total;
    }

    function hasTeamEvents() {
        const checkedEvents = document.querySelectorAll('input[name="events[]"]:checked');
        for (const event of checkedEvents) {
            if (event.dataset.type !== 'Individual') {
                return true;
            }
        }
        return false;
    }

    function updateCoParticipantFields() {
        const container = document.getElementById('coParticipantFields');
        container.innerHTML = '';
        
        const checkedEvents = document.querySelectorAll('input[name="events[]"]:checked');
        let eventGroups = {};

        // Organize team events
        checkedEvents.forEach(event => {
            const eventType = event.dataset.type;
            const eventId = event.value;
            
            if (eventType !== 'Individual') {
                if (!eventGroups[eventId]) {
                    eventGroups[eventId] = {
                        name: event.nextElementSibling.textContent.split(' (')[0],
                        participantsNeeded: eventType === 'Two-Member' ? 1 : 2
                    };
                }
            }
        });

        // Create fields for each team event
        for (const [eventId, eventData] of Object.entries(eventGroups)) {
            const eventHeader = document.createElement('h5');
            eventHeader.textContent = `${eventData.name} Team Members`;
            eventHeader.style.marginTop = '20px';
            container.appendChild(eventHeader);

            for (let i = 1; i <= eventData.participantsNeeded; i++) {
                const memberDiv = document.createElement('div');
                memberDiv.className = 'row mb-3';

                // Name Field
                const nameCol = document.createElement('div');
                nameCol.className = 'col-md-6';
                nameCol.innerHTML = `
                    <label class="form-label">Member ${i} Name</label>
                    <input type="text" class="form-control" 
                           name="team_${eventId}_member_${i}_name" required>
                `;

                // Roll No Field
                const rollCol = document.createElement('div');
                rollCol.className = 'col-md-6';
                rollCol.innerHTML = `
                    <label class="form-label">Member ${i} Roll No</label>
                    <input type="text" class="form-control" 
                           name="team_${eventId}_member_${i}_rollno" required>
                `;

                memberDiv.appendChild(nameCol);
                memberDiv.appendChild(rollCol);
                container.appendChild(memberDiv);
            }
        }
    }

    // ======================
    // 3. NAVIGATION CONTROL
    // ======================

    function validateCurrentStep() {
        switch(currentStep) {
            case 0: return validatePersonalDetails();
            case 1: 
                if (!validateEventSelection()) return false;
                if (!hasTeamEvents()) {
                    currentStep = 3; // Skip to payment
                    showStep(currentStep);
                    return false;
                }
                updateCoParticipantFields();
                return true;
            case 2: return validateTeamMembers();
            default: return true;
        }
    }

    function nextStep() {
        if (validateCurrentStep()) {
            currentStep++;
            showStep(currentStep);
        }
    }

    function prevStep() {
        currentStep--;
        showStep(currentStep);
    }

    // ======================
    // 4. INITIALIZATION
    // ======================

    // Initialize first step
    showStep(currentStep);

    // Navigation buttons
    document.getElementById('nextStep1').addEventListener('click', function(e) {
        e.preventDefault();
        nextStep();
    });

    document.getElementById('nextStep2').addEventListener('click', function(e) {
        e.preventDefault();
        nextStep();
    });

    document.getElementById('nextStep3').addEventListener('click', function(e) {
        e.preventDefault();
        nextStep();
    });

    document.getElementById('prevStep2').addEventListener('click', function(e) {
        e.preventDefault();
        prevStep();
    });

    document.getElementById('prevStep3').addEventListener('click', function(e) {
        e.preventDefault();
        prevStep();
    });

    document.getElementById('prevStep4').addEventListener('click', function(e) {
        e.preventDefault();
        prevStep();
    });

    // Event selection changes
    document.querySelectorAll('input[name="events[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            calculateTotalFee();
        });
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        if (!validateCurrentStep() || !validatePayment()) {
            e.preventDefault(); // This stops form submission
        }
    });

    console.log('Step validation:', validateCurrentStep());
    console.log('Payment validation:', validatePayment());
});