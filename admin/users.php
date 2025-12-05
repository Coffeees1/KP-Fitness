<?php
require_once '../includes/config.php';
require_admin();

$error = '';
$success = '';

// Handle trainer creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_trainer'])) {
    $fullName = sanitize_input($_POST['fullName']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $password = $_POST['password'];
    $specialty = sanitize_input($_POST['specialty']);
    
    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT UserID FROM users WHERE Email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert trainer
                $stmt = $pdo->prepare("INSERT INTO users (FullName, Email, Phone, Password, Role) VALUES (?, ?, ?, ?, 'trainer')");
                if ($stmt->execute([$fullName, $email, $phone, $hashedPassword])) {
                    $userId = $pdo->lastInsertId();
                    
                    // Create notification
                    create_notification($userId, 'Welcome to KP Fitness!', 'Your trainer account has been created successfully. You can now log in and manage your classes.', 'success');
                    
                    $success = 'Trainer created successfully! They can now log in with their credentials.';
                } else {
                    $error = 'Failed to create trainer account. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = intval($_POST['userId']);
    
    try {
        // Don't allow admin deletion
        $stmt = $pdo->prepare("SELECT Role FROM users WHERE UserID = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['Role'] === 'admin') {
            $error = 'Cannot delete admin account.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET IsActive = FALSE WHERE UserID = ?");
            if ($stmt->execute([$userId])) {
                $success = 'User deactivated successfully.';
            } else {
                $error = 'Failed to deactivate user.';
            }
        }
    } catch (PDOException $e) {
        $error = 'An error occurred. Please try again.';
    }
}

// Get all users
$stmt = $pdo->prepare("SELECT u.*, m.Type as MembershipType 
                      FROM users u 
                      LEFT JOIN membership m ON u.MembershipID = m.MembershipID 
                      WHERE u.Role != 'admin' 
                      ORDER BY u.Role, u.FullName");
$stmt->execute();
$users = $stmt->fetchAll();

// Separate users by role
$trainers = array_filter($users, fn($u) => $u['Role'] === 'trainer');
$clients = array_filter($users, fn($u) => $u['Role'] === 'client');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo SITE_NAME; ?></title>
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

        .form-input, .form-select {
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

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 107, 0, 0.1);
        }

        .users-table th {
            background: rgba(255, 107, 0, 0.1);
            color: #ff6b00;
            font-weight: 600;
        }

        .users-table td {
            color: #cccccc;
        }

        .users-table tr:hover {
            background: rgba(255, 107, 0, 0.05);
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .role-trainer {
            background: rgba(40, 167, 69, 0.2);
            color: #51cf66;
        }

        .role-client {
            background: rgba(23, 162, 184, 0.2);
            color: #17a2b8;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.2);
            color: #51cf66;
        }

        .status-inactive {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
        }

        .user-actions {
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

            .users-table {
                font-size: 0.9rem;
            }

            .users-table th,
            .users-table td {
                padding: 0.5rem;
            }

            .user-actions {
                flex-direction: column;
                gap: 0.25rem;
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
            <a href="users.php" class="nav-item active">
                <i class="fas fa-users"></i>
                User Management
            </a>
            <a href="classes.php" class="nav-item">
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
            <h1 class="page-title">User Management</h1>
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

        <!-- Create Trainer Section -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Create New Trainer</h2>
                <button type="button" class="btn btn-primary" onclick="toggleTrainerForm()">
                    <i class="fas fa-plus"></i>
                    Add Trainer
                </button>
            </div>

            <div class="card" id="trainerFormCard" style="display: none;">
                <h3 class="card-title">Trainer Registration</h3>
                
                <form method="POST" action="users.php" id="trainerForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="fullName" class="form-label">Full Name *</label>
                            <input type="text" id="fullName" name="fullName" class="form-input" placeholder="Enter full name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="Enter email address" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="Enter phone number">
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" id="password" name="password" class="form-input" placeholder="Enter password (min 8 characters)" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="specialty" class="form-label">Specialty</label>
                            <select id="specialty" name="specialty" class="form-select">
                                <option value="">Select specialty</option>
                                <option value="strength">Strength Training</option>
                                <option value="cardio">Cardio & HIIT</option>
                                <option value="yoga">Yoga & Mindfulness</option>
                                <option value="pilates">Pilates</option>
                                <option value="crossfit">CrossFit</option>
                                <option value="boxing">Boxing & Martial Arts</option>
                                <option value="dance">Dance Fitness</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary" name="create_trainer">
                            <i class="fas fa-user-plus"></i>
                            Create Trainer
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="toggleTrainerForm()">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Trainers List -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Trainers</h2>
                <div style="color: #888;">
                    Total: <?php echo count($trainers); ?> trainers
                </div>
            </div>

            <div class="card">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($trainers) > 0): ?>
                            <?php foreach ($trainers as $trainer): ?>
                                <tr>
                                    <td>
                                        <div class="role-badge role-trainer">Trainer</div><br>
                                        <?php echo htmlspecialchars($trainer['FullName']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($trainer['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($trainer['Phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $trainer['IsActive'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $trainer['IsActive'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <button class="btn btn-secondary btn-small" onclick="editUser(<?php echo $trainer['UserID']; ?>")">
                                                <i class="fas fa-edit"></i>
                                                Edit
                                            </button>
                                            <form method="POST" action="users.php" style="display: inline;">
                                                <input type="hidden" name="userId" value="<?php echo $trainer['UserID']; ?>">
                                                <input type="hidden" name="delete_user" value="1">
                                                <button type="submit" class="btn btn-danger btn-small" 
                                                        onclick="return confirm('Are you sure you want to deactivate this trainer?')">
                                                    <i class="fas fa-trash"></i>
                                                    Deactivate
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 3rem; color: #888;">
                                    <i class="fas fa-user-tie" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                    <p>No trainers found. Create your first trainer above!</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Clients List -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Clients</h2>
                <div style="color: #888;">
                    Total: <?php echo count($clients); ?> clients
                </div>
            </div>

            <div class="card">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Membership</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clients) > 0): ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td>
                                        <div class="role-badge role-client">Client</div><br>
                                        <?php echo htmlspecialchars($client['FullName']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($client['Email']); ?></td>
                                    <td><?php echo $client['MembershipType'] ? htmlspecialchars(ucfirst($client['MembershipType'])) : 'None'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $client['IsActive'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $client['IsActive'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <button class="btn btn-secondary btn-small" onclick="editUser(<?php echo $client['UserID']; ?>")">
                                                <i class="fas fa-eye"></i>
                                                View
                                            </button>
                                            <form method="POST" action="users.php" style="display: inline;">
                                                <input type="hidden" name="userId" value="<?php echo $client['UserID']; ?>">
                                                <input type="hidden" name="delete_user" value="1">
                                                <button type="submit" class="btn btn-danger btn-small" 
                                                        onclick="return confirm('Are you sure you want to deactivate this client?')">
                                                    <i class="fas fa-trash"></i>
                                                    Deactivate
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 3rem; color: #888;">
                                    <i class="fas fa-user-friends" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                    <p>No clients found. Clients will appear here when they register!</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function toggleTrainerForm() {
            const card = document.getElementById('trainerFormCard');
            if (card.style.display === 'none') {
                card.style.display = 'block';
                card.scrollIntoView({ behavior: 'smooth' });
            } else {
                card.style.display = 'none';
            }
        }

        function editUser(userId) {
            alert('Edit user functionality - User ID: ' + userId);
        }

        // Form submission handling
        document.getElementById('trainerForm').addEventListener('submit', function(e) {
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