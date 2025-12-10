<?php
define('PAGE_TITLE', 'AI Workout Planner');
require_once '../includes/config.php';
require_client();

$userId = $_SESSION['UserID'];
$feedback = [];
$workoutPlan = null;

$stmt = $pdo->prepare("SELECT * FROM workout_plans WHERE UserID = ? ORDER BY CreatedAt DESC");
$stmt->execute([$userId]);
$existingPlans = $stmt->fetchAll();

include 'includes/client_header.php';
?>
<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <h1 class="h2">AI Workout Planner</h1>
    <p class="lead text-body-secondary m-0">Generate your personalized workout plan.</p>
</div>

<?php if (!empty($feedback)): ?>
    <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Generator Form -->
    <div class="col-lg-4">
        <div class="card text-bg-dark">
            <div class="card-header fw-bold">New Plan Generator</div>
            <div class="card-body">
                <form action="workout_planner.php" method="POST">
                    <div class="mb-3">
                        <label for="planName" class="form-label">Plan Name</label>
                        <input type="text" class="form-control" name="planName" id="planName" placeholder="e.g., Summer Shred" required>
                    </div>
                    <div class="mb-3">
                        <label for="goal" class="form-label">Primary Goal</label>
                        <select class="form-select" name="goal" id="goal" required>
                            <option value="">-- Select a Goal --</option>
                            <option value="bulking">Muscle Gain (Bulking)</option>
                            <option value="cutting">Fat Loss (Cutting)</option>
                            <option value="general_fitness">General Fitness</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="fitnessLevel" class="form-label">Fitness Level</label>
                        <select class="form-select" name="fitnessLevel" id="fitnessLevel" required>
                            <option value="">-- Select Your Level --</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="generate_plan" class="btn btn-primary">Generate Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Result -->
    <div class="col-lg-8">
        <div class="card text-bg-dark">
            <div class="card-header fw-bold">Generated Plan</div>
            <div class="card-body">
                <?php if ($workoutPlan): ?>
                    <div class="accordion" id="workoutAccordion">
                        <?php foreach($workoutPlan['schedule'] as $day => $workout): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $day; ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $day; ?>">
                                    <?php echo $day; ?> - <strong class="ms-2"><?php echo $workout['type']; ?></strong>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $day; ?>" class="accordion-collapse collapse" data-bs-parent="#workoutAccordion">
                                <div class="accordion-body">
                                    <ul class="list-group list-group-flush">
                                    <?php foreach($workout['exercises'] as $exercise): ?>
                                        <li class="list-group-item bg-transparent"><?php echo $exercise['name']; ?> <?php echo isset($exercise['sets']) ? " - {$exercise['sets']}x{$exercise['reps']}" : (isset($exercise['duration']) ? "- {$exercise['duration']}" : ''); ?></li>
                                    <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-body-secondary">Your new workout plan will appear here once generated.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Existing Plans -->
<div class="card text-bg-dark mt-4">
    <div class="card-header fw-bold">Your Saved Plans</div>
    <div class="card-body">
        <div class="row g-3">
            <?php if(empty($existingPlans)): ?>
                <p class="text-body-secondary">You have no saved workout plans.</p>
            <?php else: ?>
                <?php foreach($existingPlans as $plan): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card text-bg-secondary h-100">
                             <div class="card-body">
                                <h5 class="card-title text-primary"><?php echo htmlspecialchars($plan['PlanName']); ?></h5>
                                <p class="card-text text-capitalize">
                                    <strong>Goal:</strong> <?php echo htmlspecialchars(str_replace('_', ' ', $plan['Goal'])); ?><br>
                                    <strong>Level:</strong> <?php echo htmlspecialchars($plan['FitnessLevel']); ?>
                                </p>
                                <p class="card-text"><small class="text-body-secondary">Created: <?php echo format_date_dmy($plan['CreatedAt']); ?></small></p>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#planModal" data-plan-details='<?php echo json_encode(json_decode($plan['PlanDetails'])); ?>' data-plan-name="<?php echo htmlspecialchars($plan['PlanName']); ?>">
                                    View Plan
                                </button>
                             </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Plan Modal -->
<div class="modal fade" id="planModal" tabindex="-1" aria-labelledby="planModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="planModalLabel">Workout Plan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="planModalBody">
        <!-- JS will populate this -->
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const planModal = document.getElementById('planModal');
    planModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const planName = button.getAttribute('data-plan-name');
        const details = JSON.parse(button.getAttribute('data-plan-details'));
        
        const modalTitle = planModal.querySelector('.modal-title');
        const modalBody = planModal.querySelector('.modal-body');
        
        modalTitle.textContent = planName;
        
        let html = '<div class="accordion" id="modalAccordion">';
        let dayIndex = 0;
        for (const day in details.schedule) {
            const workout = details.schedule[day];
            html += `
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button ${dayIndex > 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#modal-collapse${day}">
                            ${day} - <strong class="ms-2">${workout.type}</strong>
                        </button>
                    </h2>
                    <div id="modal-collapse${day}" class="accordion-collapse collapse ${dayIndex === 0 ? 'show' : ''}" data-bs-parent="#modalAccordion">
                        <div class="accordion-body">
                            <ul class="list-group list-group-flush">`;
            workout.exercises.forEach(exercise => {
                let details = exercise.sets ? ` - ${exercise.sets}x${exercise.reps}` : (exercise.duration ? ` - ${exercise.duration}` : '');
                html += `<li class="list-group-item bg-transparent">${exercise.name}${details}</li>`;
            });
            html += `       </ul>
                        </div>
                    </div>
                </div>`;
            dayIndex++;
        }
        html += '</div>';
        modalBody.innerHTML = html;
    });
});
</script>


<?php 
$todos_list[13]['status'] = 'completed';
include 'includes/client_footer.php'; 
?>