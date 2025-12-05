<?php
require_once '../includes/config.php';
require_client();

$userId = $_SESSION['UserID'];
$error = '';
$success = '';
$workoutPlan = null;

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE UserID = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal = sanitize_input($_POST['goal']);
    $fitnessLevel = sanitize_input($_POST['fitnessLevel']);
    $planName = sanitize_input($_POST['planName']);
    
    if (empty($goal) || empty($fitnessLevel) || empty($planName)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Generate workout plan based on user data
        $workoutPlan = generateWorkoutPlan($user, $goal, $fitnessLevel);
        
        // Save workout plan to database
        $planDetails = json_encode($workoutPlan);
        $stmt = $pdo->prepare("INSERT INTO workout_plans (UserID, PlanName, Age, Height, Weight, Goal, FitnessLevel, PlanDetails) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $age = date('Y') - date('Y', strtotime($user['DateOfBirth']));
        
        if ($stmt->execute([$userId, $planName, $age, $user['Height'], $user['Weight'], $goal, $fitnessLevel, $planDetails])) {
            $success = 'Workout plan generated successfully!';
            create_notification($userId, 'New Workout Plan Created!', 'Your personalized workout plan "' . $planName . '" has been created successfully.', 'success');
        } else {
            $error = 'Failed to save workout plan. Please try again.';
        }
    }
}

// Get user's existing workout plans
$stmt = $pdo->prepare("SELECT * FROM workout_plans WHERE UserID = ? ORDER BY CreatedAt DESC");
$stmt->execute([$userId]);
$existingPlans = $stmt->fetchAll();

function generateWorkoutPlan($user, $goal, $fitnessLevel) {
    $age = date('Y') - date('Y', strtotime($user['DateOfBirth']));
    $height = $user['Height'];
    $weight = $user['Weight'];
    $bmi = calculate_bmi($height, $weight);
    
    // Base workout structure
    $workoutPlan = [
        'goal' => $goal,
        'fitnessLevel' => $fitnessLevel,
        'duration' => '8 weeks',
        'schedule' => [],
        'nutrition' => [],
        'supplements' => []
    ];
    
    // Generate weekly schedule
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    foreach ($days as $day) {
        $workoutPlan['schedule'][$day] = generateDayWorkout($day, $goal, $fitnessLevel, $bmi);
    }
    
    // Generate nutrition plan
    $workoutPlan['nutrition'] = generateNutritionPlan($goal, $weight, $bmi);
    
    // Generate supplement recommendations
    $workoutPlan['supplements'] = generateSupplementPlan($goal, $fitnessLevel);
    
    return $workoutPlan;
}

function generateDayWorkout($day, $goal, $fitnessLevel, $bmi) {
    $workout = [];
    
    // Rest days
    if ($day === 'Sunday') {
        return ['type' => 'Rest Day', 'activities' => ['Active recovery', 'Stretching', 'Light walking']];
    }
    
    // Adjust intensity based on fitness level
    $sets = $fitnessLevel === 'beginner' ? 3 : ($fitnessLevel === 'intermediate' ? 4 : 5);
    $reps = $fitnessLevel === 'beginner' ? '8-12' : ($fitnessLevel === 'intermediate' ? '10-15' : '12-20');
    
    switch ($goal) {
        case 'bulking':
            if ($day === 'Monday' || $day === 'Thursday') {
                $workout = [
                    'type' => 'Upper Body Strength',
                    'exercises' => [
                        ['name' => 'Bench Press', 'sets' => $sets, 'reps' => $reps],
                        ['name' => 'Incline Dumbbell Press', 'sets' => $sets, 'reps' => $reps],
                        ['name' => 'Pull-ups', 'sets' => $sets, 'reps' => 'Max'],
                        ['name' => 'Barbell Rows', 'sets' => $sets, 'reps' => $reps],
                        ['name' => 'Overhead Press', 'sets' => $sets, 'reps' => $reps],
                        ['name' => 'Bicep Curls', 'sets' => $sets, 'reps' => $reps],
                        ['name' => 'Tricep Extensions', 'sets' => $sets, 'reps' => $reps]
                    ]
                ];
            } elseif ($day === 'Tuesday' || $day === 'Friday') {
                $workout = [
                    'type' => 'Lower Body Strength',
                    'exercises' => [
                        ['name' => 'Squats', 'sets' => $sets, 'reps' => $reps],
                        ['name' => 'Deadlifts', 'sets' => $sets, 'reps' => $reps],
                        ['name' => 'Leg Press', 'sets' => $sets, 'reps' => $reps],
                        ['name' => 'Leg Curls', 'sets' => $sets, 'reps' => $reps],
                        ['name' => 'Calf Raises', 'sets' => $sets, 'reps' => '15-20'],
                        ['name' => 'Plank', 'sets' => 3, 'reps' => '30-60 sec']
                    ]
                ];
            } elseif ($day === 'Wednesday') {
                $workout = [
                    'type' => 'Cardio & Core',
                    'exercises' => [
                        ['name' => 'Treadmill Running', 'duration' => '20-30 min'],
                        ['name' => 'Crunches', 'sets' => 3, 'reps' => '20-25'],
                        ['name' => 'Russian Twists', 'sets' => 3, 'reps' => '20-30'],
                        ['name' => 'Leg Raises', 'sets' => 3, 'reps' => '15-20'],
                        ['name' => 'Mountain Climbers', 'sets' => 3, 'reps' => '30 sec']
                    ]
                ];
            } else {
                $workout = [
                    'type' => 'Active Recovery',
                    'exercises' => [
                        ['name' => 'Light Cardio', 'duration' => '15-20 min'],
                        ['name' => 'Stretching', 'duration' => '15-20 min'],
                        ['name' => 'Foam Rolling', 'duration' => '10-15 min']
                    ]
                ];
            }
            break;
            
        case 'cutting':
            if ($day === 'Monday' || $day === 'Wednesday' || $day === 'Friday') {
                $workout = [
                    'type' => 'Full Body Circuit',
                    'exercises' => [
                        ['name' => 'Burpees', 'sets' => 4, 'reps' => '10-15'],
                        ['name' => 'Jump Squats', 'sets' => 4, 'reps' => '15-20'],
                        ['name' => 'Push-ups', 'sets' => 4, 'reps' => 'Max'],
                        ['name' => 'Mountain Climbers', 'sets' => 4, 'reps' => '30 sec'],
                        ['name' => 'High Knees', 'sets' => 4, 'reps' => '30 sec'],
                        ['name' => 'Plank', 'sets' => 4, 'reps' => '30-45 sec']
                    ]
                ];
            } elseif ($day === 'Tuesday' || $day === 'Thursday') {
                $workout = [
                    'type' => 'Strength Training',
                    'exercises' => [
                        ['name' => 'Squats', 'sets' => $sets, 'reps' => $reps],
                        ['name' => 'Push-ups', 'sets' => $sets, 'reps' => 'Max'],
                        ['name' => 'Lunges', 'sets' => $sets, 'reps' => $reps],
                        ['name' => 'Plank', 'sets' => 3, 'reps' => '30-60 sec']
                    ]
                ];
            } else {
                $workout = [
                    'type' => 'Cardio Day',
                    'exercises' => [
                        ['name' => 'HIIT Running', 'duration' => '20-30 min'],
                        ['name' => 'Cycling', 'duration' => '30-45 min'],
                        ['name' => 'Swimming', 'duration' => '30-45 min']
                    ]
                ];
            }
            break;
            
        case 'endurance':
            if ($day === 'Monday' || $day === 'Wednesday' || $day === 'Friday') {
                $workout = [
                    'type' => 'Cardio Endurance',
                    'exercises' => [
                        ['name' => 'Running', 'duration' => '30-45 min'],
                        ['name' => 'Cycling', 'duration' => '45-60 min'],
                        ['name' => 'Rowing', 'duration' => '20-30 min'],
                        ['name' => 'Swimming', 'duration' => '30-45 min']
                    ]
                ];
            } else {
                $workout = [
                    'type' => 'Strength & Flexibility',
                    'exercises' => [
                        ['name' => 'Yoga', 'duration' => '30-45 min'],
                        ['name' => 'Pilates', 'duration' => '30-45 min'],
                        ['name' => 'Light Weights', 'sets' => 3, 'reps' => '12-15'],
                        ['name' => 'Stretching', 'duration' => '15-20 min']
                    ]
                ];
            }
            break;
            
        default: // general_fitness
            $workout = [
                'type' => 'General Fitness',
                'exercises' => [
                    ['name' => 'Warm-up', 'duration' => '10 min'],
                    ['name' => 'Strength Training', 'sets' => $sets, 'reps' => $reps],
                    ['name' => 'Cardio', 'duration' => '20-30 min'],
                    ['name' => 'Cool-down', 'duration' => '10 min']
                ]
            ];
    }
    
    return $workout;
}

function generateNutritionPlan($goal, $weight, $bmi) {
    $nutrition = [];
    
    switch ($goal) {
        case 'bulking':
            $nutrition = [
                'calories' => $weight * 35 . ' - ' . $weight * 40 . ' kcal/day',
                'protein' => $weight * 2 . 'g/day',
                'carbs' => $weight * 4 . 'g/day',
                'fats' => $weight * 1 . 'g/day',
                'meals' => [
                    'Breakfast' => 'Oatmeal with banana and protein powder',
                    'Morning Snack' => 'Greek yogurt with berries',
                    'Lunch' => 'Grilled chicken, rice, and vegetables',
                    'Pre-workout' => 'Banana and protein shake',
                    'Dinner' => 'Salmon, sweet potato, and salad',
                    'Before bed' => 'Casein protein shake'
                ]
            ];
            break;
            
        case 'cutting':
            $nutrition = [
                'calories' => $weight * 25 . ' - ' . $weight * 30 . ' kcal/day',
                'protein' => $weight * 2.5 . 'g/day',
                'carbs' => $weight * 2 . 'g/day',
                'fats' => $weight * 0.8 . 'g/day',
                'meals' => [
                    'Breakfast' => 'Egg whites with spinach',
                    'Morning Snack' => 'Protein shake',
                    'Lunch' => 'Grilled fish with salad',
                    'Afternoon Snack' => 'Almonds and apple',
                    'Dinner' => 'Chicken breast with steamed vegetables',
                    'Before bed' => 'Cottage cheese'
                ]
            ];
            break;
            
        default:
            $nutrition = [
                'calories' => $weight * 30 . ' - ' . $weight * 35 . ' kcal/day',
                'protein' => $weight * 1.5 . 'g/day',
                'carbs' => $weight * 3 . 'g/day',
                'fats' => $weight * 0.8 . 'g/day',
                'meals' => [
                    'Breakfast' => 'Balanced meal with protein, carbs, and fats',
                    'Morning Snack' => 'Fruit and nuts',
                    'Lunch' => 'Lean protein with vegetables',
                    'Adternoon Snack' => 'Yogurt or protein bar',
                    'Dinner' => 'Balanced meal with all macronutrients'
                ]
            ];
    }
    
    return $nutrition;
}

function generateSupplementPlan($goal, $fitnessLevel) {
    $supplements = [];
    
    // Basic supplements for everyone
    $supplements = [
        'Essential' => [
            'Multivitamin' => 'Daily multivitamin for general health',
            'Vitamin D3' => '2000-4000 IU daily',
            'Omega-3' => '1-2g daily'
        ]
    ];
    
    // Goal-specific supplements
    switch ($goal) {
        case 'bulking':
            $supplements['Performance'] = [
                'Whey Protein' => 'Post-workout and between meals',
                'Creatine' => '5g daily',
                'BCAAs' => 'During workouts'
            ];
            break;
            
        case 'cutting':
            $supplements['Performance'] = [
                'Whey Protein' => 'To maintain muscle mass',
                'L-Carnitine' => 'For fat metabolism',
                'Green Tea Extract' => 'Natural fat burner'
            ];
            break;
            
        case 'endurance':
            $supplements['Performance'] = [
                'Electrolytes' => 'During long workouts',
                'Beta-Alanine' => 'For muscular endurance',
                'Beetroot Extract' => 'For oxygen efficiency'
            ];
            break;
    }
    
    return $supplements;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Workout Planner - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            line-height: 1.6;
            min-height: 100vh;
            padding: 2rem 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ff6b00;
            margin-bottom: 1rem;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: #cccccc;
            max-width: 600px;
            margin: 0 auto;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .form-card, .result-card {
            background: rgba(45, 45, 45, 0.8);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 2rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff6b00;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #ffffff;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 1rem;
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(255, 107, 0, 0.2);
            border-radius: 8px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #ff6b00;
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.1);
        }

        .form-input::placeholder {
            color: #888888;
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6b00, #ff8533);
            color: #ffffff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 0, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #51cf66;
        }

        .user-stats {
            background: rgba(26, 26, 26, 0.5);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #888888;
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff6b00;
        }

        .workout-plan {
            display: none;
        }

        .workout-plan.show {
            display: block;
        }

        .workout-day {
            background: rgba(26, 26, 26, 0.5);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #ff6b00;
        }

        .day-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff6b00;
            margin-bottom: 0.5rem;
        }

        .day-type {
            color: #cccccc;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .exercise-list {
            list-style: none;
        }

        .exercise-item {
            padding: 0.5rem 0;
            color: #ffffff;
            border-bottom: 1px solid rgba(255, 107, 0, 0.1);
        }

        .exercise-item:last-child {
            border-bottom: none;
        }

        .existing-plans {
            margin-top: 3rem;
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .plan-card {
            background: rgba(45, 45, 45, 0.8);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            transition: transform 0.3s ease;
        }

        .plan-card:hover {
            transform: translateY(-5px);
        }

        .plan-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #ff6b00;
            margin-bottom: 0.5rem;
        }

        .plan-meta {
            color: #cccccc;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .plan-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            width: auto;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-robot"></i> AI Workout Planner
            </h1>
            <p class="page-subtitle">
                Get personalized workout plans based on your fitness level, goals, and body metrics using our AI system.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Workout Plan Generator -->
            <div class="form-card">
                <h3 class="card-title">Generate New Workout Plan</h3>
                
                <div class="user-stats">
                    <h4 style="color: #ff6b00; margin-bottom: 1rem; text-align: center;">Your Profile Stats</h4>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-label">Age</div>
                            <div class="stat-value"><?php echo date('Y') - date('Y', strtotime($user['DateOfBirth'])); ?> years</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Height</div>
                            <div class="stat-value"><?php echo $user['Height']; ?> cm</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Weight</div>
                            <div class="stat-value"><?php echo $user['Weight']; ?> kg</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">BMI</div>
                            <div class="stat-value"><?php echo calculate_bmi($user['Height'], $user['Weight']); ?></div>
                        </div>
                    </div>
                </div>

                <form id="workoutForm" method="POST" action="workout_planner.php">
                    <div class="form-group">
                        <label for="planName" class="form-label">Plan Name *</label>
                        <input type="text" id="planName" name="planName" class="form-input" placeholder="Enter a name for your workout plan" required>
                    </div>

                    <div class="form-group">
                        <label for="goal" class="form-label">Fitness Goal *</label>
                        <select id="goal" name="goal" class="form-select" required>
                            <option value="">Select your goal</option>
                            <option value="bulking">Bulking (Muscle Gain)</option>
                            <option value="cutting">Cutting (Fat Loss)</option>
                            <option value="endurance">Endurance Training</option>
                            <option value="general_fitness">General Fitness</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fitnessLevel" class="form-label">Fitness Level *</label>
                        <select id="fitnessLevel" name="fitnessLevel" class="form-select" required>
                            <option value="">Select your level</option>
                            <option value="beginner">Beginner (0-1 years)</option>
                            <option value="intermediate">Intermediate (1-3 years)</option>
                            <option value="advanced">Advanced (3+ years)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" id="generateBtn">
                        <i class="fas fa-magic"></i>
                        <span id="btnText">Generate Workout Plan</span>
                    </button>
                </form>
            </div>

            <!-- Workout Plan Result -->
            <div class="result-card">
                <h3 class="card-title">Your Personalized Plan</h3>
                
                <?php if ($workoutPlan): ?>
                    <div class="workout-plan show">
                        <div style="margin-bottom: 1.5rem;">
                            <h4 style="color: #ff6b00; margin-bottom: 0.5rem;">Plan Overview</h4>
                            <p><strong>Goal:</strong> <?php echo ucfirst($workoutPlan['goal']); ?></p>
                            <p><strong>Duration:</strong> <?php echo $workoutPlan['duration']; ?></p>
                            <p><strong>Fitness Level:</strong> <?php echo ucfirst($workoutPlan['fitnessLevel']); ?></p>
                        </div>

                        <h4 style="color: #ff6b00; margin-bottom: 1rem;">Weekly Schedule</h4>
                        <?php foreach ($workoutPlan['schedule'] as $day => $workout): ?>
                            <div class="workout-day">
                                <div class="day-title"><?php echo $day; ?></div>
                                <div class="day-type"><?php echo $workout['type']; ?></div>
                                <ul class="exercise-list">
                                    <?php if (isset($workout['exercises'])): ?>
                                        <?php foreach ($workout['exercises'] as $exercise): ?>
                                            <li class="exercise-item">
                                                <strong><?php echo $exercise['name']; ?></strong>
                                                <?php if (isset($exercise['sets']) && isset($exercise['reps'])): ?>
                                                    - <?php echo $exercise['sets']; ?> sets × <?php echo $exercise['reps']; ?> reps
                                                <?php elseif (isset($exercise['duration'])): ?>
                                                    - <?php echo $exercise['duration']; ?>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>

                        <div style="margin-top: 2rem;">
                            <h4 style="color: #ff6b00; margin-bottom: 1rem;">Nutrition Guidelines</h4>
                            <p><strong>Daily Calories:</strong> <?php echo $workoutPlan['nutrition']['calories']; ?></p>
                            <p><strong>Protein:</strong> <?php echo $workoutPlan['nutrition']['protein']; ?></p>
                            <p><strong>Carbs:</strong> <?php echo $workoutPlan['nutrition']['carbs']; ?></p>
                            <p><strong>Fats:</strong> <?php echo $workoutPlan['nutrition']['fats']; ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: #888;">
                        <i class="fas fa-dumbbell" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>Fill out the form to generate your personalized workout plan</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Existing Workout Plans -->
        <?php if (count($existingPlans) > 0): ?>
            <div class="existing-plans">
                <h3 class="card-title">Your Existing Workout Plans</h3>
                <div class="plans-grid">
                    <?php foreach ($existingPlans as $plan): ?>
                        <?php $planDetails = json_decode($plan['PlanDetails'], true); ?>
                        <div class="plan-card">
                            <div class="plan-title"><?php echo htmlspecialchars($plan['PlanName']); ?></div>
                            <div class="plan-meta">
                                Goal: <?php echo ucfirst($plan['Goal']); ?> | 
                                Level: <?php echo ucfirst($plan['FitnessLevel']); ?> | 
                                Created: <?php echo date('M d, Y', strtotime($plan['CreatedAt'])); ?>
                            </div>
                            <div class="plan-actions">
                                <button class="btn btn-primary btn-small" onclick="viewPlan(<?php echo $plan['PlanID']; ?>")">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <button class="btn btn-secondary btn-small" onclick="deletePlan(<?php echo $plan['PlanID']; ?>")">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function viewPlan(planId) {
            alert('View plan functionality - Plan ID: ' + planId);
        }

        function deletePlan(planId) {
            if (confirm('Are you sure you want to delete this workout plan?')) {
                alert('Delete plan functionality - Plan ID: ' + planId);
            }
        }

        // Form submission handling
        document.getElementById('workoutForm').addEventListener('submit', function(e) {
            const generateBtn = document.getElementById('generateBtn');
            const btnText = document.getElementById('btnText');
            
            generateBtn.disabled = true;
            btnText.innerHTML = 'Generating Plan...';
            generateBtn.innerHTML = '<div class="loading"></div> Generating Plan...';
        });
    </script>
</body>
</html>