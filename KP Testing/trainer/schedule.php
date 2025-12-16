<?php
define('PAGE_TITLE', 'My Schedule');
require_once '../includes/config.php';
require_trainer();

$trainerId = $_SESSION['UserID'];
$feedback = [];

// Get Month and Year from GET or default to current
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Navigation Logic
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Calendar Generation Variables
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayOfMonth);
$dateComponents = getdate($firstDayOfMonth);
$dayOfWeek = $dateComponents['wday']; // 0 (Sun) - 6 (Sat)
$monthName = $dateComponents['month'];

// Fetch sessions for this month
try {
    $startDate = "$year-$month-01";
    $endDate = "$year-$month-$daysInMonth";
    
    $stmt = $pdo->prepare("
        SELECT s.SessionID, s.SessionDate, s.Time, s.Room, s.Status, s.CurrentBookings, c.ClassName, c.MaxCapacity
        FROM sessions s
        JOIN activities c ON s.ClassID = c.ClassID
        WHERE s.TrainerID = ? AND s.SessionDate BETWEEN ? AND ?
        ORDER BY s.Time ASC
    ");
    $stmt->execute([$trainerId, $startDate, $endDate]);
    $monthSessions = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    
    $sessionsByDay = [];
    foreach ($monthSessions as $row) {
        $day = intval(date('j', strtotime($row['SessionDate'])));
        $sessionsByDay[$day][] = $row;
    }

} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch schedule data: ' . $e->getMessage()];
    $sessionsByDay = [];
}

include 'includes/trainer_header.php';
?>

<style>
    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 10px;
        margin-bottom: 2rem;
    }
    .calendar-day-header {
        text-align: center;
        font-weight: bold;
        color: #aaa;
        padding: 10px 0;
    }
    .calendar-day {
        background-color: #2b2b2b;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        min-height: 100px;
        padding: 10px;
        position: relative;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .calendar-day:hover {
        background-color: #333;
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }
    .calendar-day.empty {
        background-color: transparent;
        border: none;
        cursor: default;
    }
    .calendar-day.empty:hover {
        background-color: transparent;
        transform: none;
    }
    .calendar-day.today {
        border: 1px solid var(--primary-color);
        background-color: rgba(255, 107, 0, 0.1);
    }
    .day-number {
        font-size: 1.1rem;
        font-weight: bold;
        margin-bottom: 5px;
        color: #fff;
    }
    .session-indicator {
        font-size: 0.8rem;
        color: #ccc;
        background-color: rgba(255, 255, 255, 0.1);
        padding: 2px 6px;
        border-radius: 4px;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Modal Navigation */
    .modal-date-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }
    .modal-arrow {
        cursor: pointer;
        padding: 0 10px;
        font-size: 1.2rem;
        color: var(--primary-color);
    }
    .modal-arrow:hover {
        color: #fff;
    }
</style>

<div class="container-fluid pt-3">
    <div class="calendar-header mb-2">
        <h2 class="h3 mb-0 text-white">My Schedule</h2>
        <div class="d-flex align-items-center">
            <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-outline-secondary me-2"><i class="fas fa-chevron-left"></i></a>
            <h4 class="mb-0 mx-3 text-white" style="min-width: 180px; text-align: center;"><?php echo "$monthName $year"; ?></h4>
            <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-outline-secondary ms-2"><i class="fas fa-chevron-right"></i></a>
        </div>
    </div>
    <hr class="border-white opacity-100 mb-4">

    <?php if (!empty($feedback)): ?>
        <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $feedback['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Day Headers -->
    <div class="calendar-grid mb-0">
        <div class="calendar-day-header">Sun</div>
        <div class="calendar-day-header">Mon</div>
        <div class="calendar-day-header">Tue</div>
        <div class="calendar-day-header">Wed</div>
        <div class="calendar-day-header">Thu</div>
        <div class="calendar-day-header">Fri</div>
        <div class="calendar-day-header">Sat</div>
    </div>

    <div class="calendar-grid">
        <?php
        // Empty cells for days before the 1st
        for ($i = 0; $i < $dayOfWeek; $i++) {
            echo '<div class="calendar-day empty"></div>';
        }

        // Days of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateString = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
            $isToday = ($dateString == date('Y-m-d'));
            $daySessions = $sessionsByDay[$day] ?? [];
            $sessionCount = count($daySessions);
            
            echo "<div class='calendar-day " . ($isToday ? 'today' : '') . "' onclick='openDayModal(\"$dateString\")'>";
            echo "<div class='day-number'>$day</div>";
            
            if ($sessionCount > 0) {
                echo "<div class='text-primary small mb-1'>$sessionCount Classes</div>";
                // Show first 2 classes as preview
                $count = 0;
                foreach ($daySessions as $s) {
                    if ($count >= 2) break;
                    echo "<div class='session-indicator'>" . format_time($s['Time']) . " " . htmlspecialchars($s['ClassName']) . "</div>";
                    $count++;
                }
                if ($sessionCount > 2) {
                    echo "<div class='text-muted small'>+ " . ($sessionCount - 2) . " more</div>";
                }
            }
            
            echo "</div>"; // Close calendar-day
            
            // Generate invisible data container for this day to easily load into modal
            if ($sessionCount > 0) {
                echo "<div id='data-$dateString' style='display:none;'>";
                foreach ($daySessions as $session) {
                    $live = is_session_live($session['SessionDate'], $session['Time']);
                    echo "<div class='card mb-3 bg-dark border-secondary'>";
                    echo "<div class='card-body p-3'>";
                    echo "<div class='d-flex justify-content-between'>";
                    echo "<h6 class='card-title text-white'>" . htmlspecialchars($session['ClassName']) . "</h6>";
                    if ($live) echo "<span class='badge bg-success'>LIVE</span>";
                    echo "</div>";
                    echo "<p class='card-text text-muted small mb-2'>";
                    echo "<i class='far fa-clock me-1'></i> " . format_time($session['Time']) . "<br>";
                    echo "<i class='fas fa-door-open me-1'></i> " . htmlspecialchars($session['Room']) . "<br>";
                    echo "<i class='fas fa-users me-1'></i> " . $session['CurrentBookings'] . " / " . $session['MaxCapacity'];
                    echo "</p>";
                    echo "<a href='attendance.php?session_id=" . $session['SessionID'] . "' class='btn btn-sm btn-primary w-100 mb-2'>Take Attendance</a>";
                    if ($session['Status'] == 'scheduled') {
                        echo "<button class='btn btn-sm btn-outline-danger w-100' onclick='openCancelModal(" . $session['SessionID'] . ", \"" . htmlspecialchars($session['ClassName']) . "\")'>Cancel / Reschedule</button>";
                    }
                    echo "</div></div>";
                }
                echo "</div>";
            } else {
                echo "<div id='data-$dateString' style='display:none;'><p class='text-muted text-center py-3'>No classes scheduled for this day.</p></div>";
            }
        }
        
        // Fill remaining cells
        $remainingDays = 7 - (($dayOfWeek + $daysInMonth) % 7);
        if ($remainingDays < 7) {
            for ($i = 0; $i < $remainingDays; $i++) {
                echo '<div class="calendar-day empty"></div>';
            }
        }
        ?>
    </div>
</div>

<!-- Day Details Modal -->
<div class="modal fade" id="dayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <div class="modal-date-nav">
                    <i class="fas fa-chevron-left modal-arrow" onclick="changeDay(-1)"></i>
                    <h5 class="modal-title" id="modalDateTitle">Date</h5>
                    <i class="fas fa-chevron-right modal-arrow" onclick="changeDay(1)"></i>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalBodyContent">
                <!-- Content injected by JS -->
            </div>
        </div>
    </div>
</div>

<!-- Cancel/Reschedule Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
             <div class="modal-header border-secondary">
                <h5 class="modal-title">Cancel Session: <span id="cancelClassName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="cancelForm" method="POST">
                    <input type="hidden" name="action" value="cancel_session">
                    <input type="hidden" name="session_id" id="cancelSessionId">
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Cancellation</label>
                        <textarea class="form-control bg-secondary text-white border-0" name="reason" rows="3" required placeholder="e.g. Emergency, Sick leave..."></textarea>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rescheduleCheck" name="reschedule" value="yes" onchange="toggleRescheduleInputs()">
                        <label class="form-check-label" for="rescheduleCheck">Reschedule this session?</label>
                    </div>
                    
                    <div id="rescheduleInputs" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">New Date</label>
                            <input type="date" class="form-control bg-secondary text-white border-0" name="new_date" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Time</label>
                            <input type="time" class="form-control bg-secondary text-white border-0" name="new_time">
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let currentDate = ''; // Format YYYY-MM-DD

    function openDayModal(dateStr) {
        currentDate = dateStr;
        updateModalContent();
        new bootstrap.Modal(document.getElementById('dayModal')).show();
    }

    function changeDay(offset) {
        let date = new Date(currentDate);
        date.setDate(date.getDate() + offset);
        
        // Format YYYY-MM-DD manually to avoid timezone issues causing off-by-one
        let y = date.getFullYear();
        let m = String(date.getMonth() + 1).padStart(2, '0');
        let d = String(date.getDate()).padStart(2, '0');
        let newDateStr = `${y}-${m}-${d}`;
        
        currentDate = newDateStr;
        updateModalContent();
    }

    function updateModalContent() {
        // Update Title
        const dateObj = new Date(currentDate);
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('modalDateTitle').textContent = dateObj.toLocaleDateString('en-US', options);
        
        const dataContainer = document.getElementById('data-' + currentDate);
        const modalBody = document.getElementById('modalBodyContent');
        
        if (dataContainer) {
            modalBody.innerHTML = dataContainer.innerHTML;
        } else {
            modalBody.innerHTML = "<p class='text-center text-muted py-3'>No schedule data available for this date.<br><small>(Try navigating to that month in the calendar view)</small></p>";
        }
    }
    
    function openCancelModal(sessionId, className) {
        // Hide day modal first (optional, but cleaner)
        // bootstrap.Modal.getInstance(document.getElementById('dayModal')).hide();
        
        document.getElementById('cancelSessionId').value = sessionId;
        document.getElementById('cancelClassName').textContent = className;
        
        // Reset form
        document.getElementById('rescheduleCheck').checked = false;
        toggleRescheduleInputs();
        
        new bootstrap.Modal(document.getElementById('cancelModal')).show();
    }
    
    function toggleRescheduleInputs() {
        const isChecked = document.getElementById('rescheduleCheck').checked;
        const inputs = document.getElementById('rescheduleInputs');
        inputs.style.display = isChecked ? 'block' : 'none';
        
        // Toggle required attributes for HTML5 validation
        const dateInput = inputs.querySelector('input[name="new_date"]');
        const timeInput = inputs.querySelector('input[name="new_time"]');
        if (isChecked) {
            dateInput.setAttribute('required', 'required');
            timeInput.setAttribute('required', 'required');
        } else {
            dateInput.removeAttribute('required');
            timeInput.removeAttribute('required');
        }
    }
</script>

<?php include 'includes/trainer_footer.php'; ?>
