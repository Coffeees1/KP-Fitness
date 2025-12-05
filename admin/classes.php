<?php
require_once '../includes/config.php';
require_admin();

$error = '';
$success = '';

// Handle class creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_class'])) {
    $className = sanitize_input($_POST['className']);
    $description = sanitize_input($_POST['description']);
    $duration = intval($_POST['duration']);
    $maxCapacity = intval($_POST['maxCapacity']);
    $difficultyLevel = sanitize_input($_POST['difficultyLevel']);
    
    if (empty($className) || empty($description) || $duration <= 0 || $maxCapacity <= 0) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO classes (ClassName, Description, Duration, MaxCapacity, DifficultyLevel) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$className, $description, $duration, $maxCapacity, $difficultyLevel])) {
                $success = 'Class created successfully!';
            } else {
                $error = 'Failed to create class. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Handle class deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_class'])) {
    $classId = intval($_POST['classId']);
    
    try {
        $stmt = $pdo->prepare("UPDATE classes SET IsActive = FALSE WHERE ClassID = ?");
        if ($stmt->execute([$classId])) {
            $success = 'Class deactivated successfully.';
        } else {
            $error = 'Failed to deactivate class.';
        }
    } catch (PDOException $e) {
        $error = 'An error occurred. Please try again.';
    }
}

// Get all classes
$stmt = $pdo->prepare("SELECT c.*, COUNT(s.SessionID) as session_count 
                      FROM classes c 
                      LEFT JOIN sessions s ON c.ClassID = s.ClassID 
                      WHERE c.IsActive = TRUE 
                      GROUP BY c.ClassID 
                      ORDER BY c.ClassName");
$stmt->execute();
$classes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management - <?php echo SITE_NAME; ?></title>
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
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 107, 0, 0.2);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #ff6b00;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(45, 45, 45, 0.8);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 107, 0, 0.2);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b00, #ff8533);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 600;
        }

        .content-section {
            margin-bottom: 3rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff6b00;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
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

        .btn-secondary {
            background: transparent;
            color: #ffffff;
            border: 2px solid #ff6b00;
        }

        .btn-secondary:hover {
            background: #ff6b00;
            color: #ffffff;
        }

        .btn-danger {
            background: rgba(220, 53, 69, 0.8);
            color: #ffffff;
            border: 2px solid rgba(220, 53, 69, 0.5);
        }

        .btn-danger:hover {
            background: #dc3545;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .card {
            background: rgba(45, 45, 45, 0.8);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #ff6b00;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #ffffff;
        }

        .form-input, .form-select, .form-textarea {
            padding: 1rem;
            background: rgba(26, 26, 26, 0.9);
            border: 2px solid rgba(255, 107, 0, 0.2);
            border-radius: 8px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #ff6b00;
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.1);
        }

        .form-input::placeholder, .form-textarea::placeholder {
            color: #888888;
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .class-card {
            background: rgba(26, 26, 26, 0.5);
            border: 1px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .class-card:hover {
            border-color: #ff6b00;
            transform: translateY(-5px);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .class-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff6b00;
        }

        .difficulty-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .difficulty-beginner {
            background: rgba(40, 167, 69, 0.2);
            color: #51cf66;
        }

        .difficulty-intermediate {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .difficulty-advanced {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
        }

        .class-details {
            color: #cccccc;
            margin-bottom: 1rem;
        }

        .class-details p {
            margin-bottom: 0.5rem;
        }

        .class-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(255, 107, 0, 0.1);
            border-radius: 8px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff6b00;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #cccccc;
        }

        .class-actions {
            display: flex;
            gap: 0.5rem;
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
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .classes-grid {
                grid-template-columns: 1fr;
            }

            .class-stats {
                grid-template-columns: 1fr;
            }

            .class-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h1><i class="fas fa-dumbbell"></i> KP FITNESS</h1>
            <p>Admin Panel</p>
        </div>
        
        <div class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="users.php" class="nav-item">
                <i class="fas fa-users"></i>
                User Management
            </a>
            <a href="classes.php" class="nav-item active">
                <i class="fas fa-dumbbell"></i>
                Class Management
            </a>
            <a href="reports.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                Reports & Analytics
            </a>
            <a href="../dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
                Main Site
            </a>
            <a href="../logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1 class="page-title">Class Management</h1>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo substr($_SESSION['FullName'], 0, 1); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($_SESSION['FullName']); ?></div>
                        <div style="font-size: 0.8rem; color: #888;">Administrator</div>
                    </div>
                </div>
            </div>
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

        <!-- Create Class Section -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Create New Class</h2>
                <button type="button" class="btn btn-primary" onclick="toggleClassForm()">
                    <i class="fas fa-plus"></i>
                    Add Class
                </button>
            </div>

            <div class="card" id="classFormCard" style="display: none;">
                <h3 class="card-title">Class Information</h3>
                
                <form method="POST" action="classes.php" id="classForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="className" class="form-label">Class Name *</label>
                            <input type="text" id="className" name="className" class="form-input" placeholder="Enter class name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration" class="form-label">Duration (minutes) *</label>
                            <input type="number" id="duration" name="duration" class="form-input" placeholder="Enter duration in minutes" min="30" max="180" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="maxCapacity" class="form-label">Max Capacity *</label>
                            <input type="number" id="maxCapacity" name="maxCapacity" class="form-input" placeholder="Enter maximum capacity" min="5" max="50" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="difficultyLevel" class="form-label">Difficulty Level *</label>
                            <select id="difficultyLevel" name="difficultyLevel" class="form-select" required>
                                <option value="">Select difficulty</option>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description *</label>
                        <textarea id="description" name="description" class="form-textarea" placeholder="Enter class description" required></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary" name="create_class">
                            <i class="fas fa-plus-circle"></i>
                            Create Class
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="toggleClassForm()">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Classes List -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">All Classes</h2>
                <div style="color: #888;">
                    Total: <?php echo count($classes); ?> active classes
                </div>
            </div>

            <div class="classes-grid">
                <?php if (count($classes) > 0): ?>
                    <?php foreach ($classes as $class): ?>
                        <div class="class-card">
                            <div class="class-header">
                                <div class="class-name"><?php echo htmlspecialchars($class['ClassName']); ?></div>
                                <div class="difficulty-badge difficulty-<?php echo $class['DifficultyLevel']; ?>">
                                    <?php echo ucfirst($class['DifficultyLevel']); ?>
                                </div>
                            </div>
                            
                            <div class="class-details">
                                <p><?php echo htmlspecialchars($class['Description']); ?></p>
                            </div>
                            
                            <div class="class-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $class['Duration']; ?> min</div>
                                    <div class="stat-label">Duration</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $class['MaxCapacity']; ?></div>
                                    <div class="stat-label">Capacity</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $class['session_count']; ?></div>
                                    <div class="stat-label">Sessions</div>
                                </div>
                            </div>
                            
                            <div class="class-actions">
                                <button class="btn btn-secondary btn-small" onclick="editClass(<?php echo $class['ClassID']; ?>")">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </button>
                                <button class="btn btn-primary btn-small" onclick="scheduleClass(<?php echo $class['ClassID']; ?>")">
                                    <i class="fas fa-calendar-plus"></i>
                                    Schedule
                                </button>
                                <form method="POST" action="classes.php" style="display: inline;">
                                    <input type="hidden" name="classId" value="<?php echo $class['ClassID']; ?>">
                                    <input type="hidden" name="delete_class" value="1">
                                    <button type="submit" class="btn btn-danger btn-small" 
                                            onclick="return confirm('Are you sure you want to deactivate this class?')">
                                        <i class="fas fa-trash"></i>
                                        Deactivate
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #888;">
                        <i class="fas fa-dumbbell" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>No classes found</h3>
                        <p>Create your first class using the form above!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleClassForm() {
            const card = document.getElementById('classFormCard');
            if (card.style.display === 'none') {
                card.style.display = 'block';
                card.scrollIntoView({ behavior: 'smooth' });
            } else {
                card.style.display = 'none';
            }
        }

        function editClass(classId) {
            alert('Edit class functionality - Class ID: ' + classId);
        }

        function scheduleClass(classId) {
            alert('Schedule class functionality - Class ID: ' + classId);
        }

        // Form submission handling
        document.getElementById('classForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            const originalContent = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<div class="loading"></div> Creating...';
            
            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }, 2000);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>