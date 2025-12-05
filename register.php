<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize_input($_POST['firstName']);
    $lastName = sanitize_input($_POST['lastName']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $dateOfBirth = sanitize_input($_POST['dateOfBirth']);
    $height = intval($_POST['height']);
    $weight = intval($_POST['weight']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $agreeTerms = isset($_POST['agreeTerms']);
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || 
        empty($dateOfBirth) || empty($height) || empty($weight) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!$agreeTerms) {
        $error = 'You must agree to the Terms and Conditions and Privacy Policy.';
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
                $fullName = $firstName . ' ' . $lastName;
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (FullName, Email, Phone, Password, DateOfBirth, Height, Weight) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$fullName, $email, $phone, $hashedPassword, $dateOfBirth, $height, $weight])) {
                    $userId = $pdo->lastInsertId();
                    
                    // Create welcome notification
                    create_notification($userId, 'Welcome to KP Fitness!', 'Your account has been created successfully. Start exploring our classes and membership options!', 'success');
                    
                    $_SESSION['success'] = 'Account created successfully! Please login.';
                    redirect('login.php');
                } else {
                    $error = 'An error occurred during registration. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
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

        .register-container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(26, 26, 26, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 107, 0, 0.2);
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ff6b00;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: #cccccc;
            font-size: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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

        .form-input {
            width: 100%;
            padding: 1rem;
            background: rgba(45, 45, 45, 0.8);
            border: 2px solid rgba(255, 107, 0, 0.2);
            border-radius: 8px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #ff6b00;
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.1);
        }

        .form-input::placeholder {
            color: #888888;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle .form-input {
            padding-right: 3rem;
        }

        .toggle-btn {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #ff6b00;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .checkbox-group input[type="checkbox"] {
            margin-top: 0.25rem;
            accent-color: #ff6b00;
        }

        .checkbox-group label {
            color: #cccccc;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .checkbox-group label a {
            color: #ff6b00;
            text-decoration: none;
            font-weight: 600;
        }

        .checkbox-group label a:hover {
            text-decoration: underline;
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

        .form-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 107, 0, 0.2);
        }

        .form-footer p {
            color: #cccccc;
            margin-bottom: 0.5rem;
        }

        .form-footer a {
            color: #ff6b00;
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
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
            .register-container {
                padding: 2rem;
                margin: 1rem;
            }

            .register-header h1 {
                font-size: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1><i class="fas fa-dumbbell"></i> KP FITNESS</h1>
            <p>Create your account and start your fitness journey</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="register.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="firstName" class="form-label">First Name *</label>
                    <input type="text" id="firstName" name="firstName" class="form-input" placeholder="Enter your first name" required value="<?php echo isset($firstName) ? htmlspecialchars($firstName) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="lastName" class="form-label">Last Name *</label>
                    <input type="text" id="lastName" name="lastName" class="form-input" placeholder="Enter your last name" required value="<?php echo isset($lastName) ? htmlspecialchars($lastName) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">Phone Number *</label>
                <input type="tel" id="phone" name="phone" class="form-input" placeholder="Enter your phone number" required value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="dateOfBirth" class="form-label">Date of Birth *</label>
                <input type="date" id="dateOfBirth" name="dateOfBirth" class="form-input" required value="<?php echo isset($dateOfBirth) ? htmlspecialchars($dateOfBirth) : ''; ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="height" class="form-label">Height (cm) *</label>
                    <input type="number" id="height" name="height" class="form-input" placeholder="Enter height in cm" min="100" max="250" required value="<?php echo isset($height) ? htmlspecialchars($height) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="weight" class="form-label">Weight (kg) *</label>
                    <input type="number" id="weight" name="weight" class="form-input" placeholder="Enter weight in kg" min="30" max="200" required value="<?php echo isset($weight) ? htmlspecialchars($weight) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password *</label>
                <div class="password-toggle">
                    <input type="password" id="password" name="password" class="form-input" placeholder="Create a password (min 8 characters)" required>
                    <button type="button" class="toggle-btn" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirmPassword" class="form-label">Confirm Password *</label>
                <div class="password-toggle">
                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" placeholder="Confirm your password" required>
                    <button type="button" class="toggle-btn" onclick="togglePassword('confirmPassword')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="agreeTerms" name="agreeTerms" required>
                <label for="agreeTerms">
                    I agree to the <a href="#" onclick="showTerms()">Terms and Conditions</a> and <a href="#" onclick="showPrivacy()">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" id="registerBtn">
                <i class="fas fa-user-plus"></i>
                <span id="btnText">Create Account</span>
            </button>
        </form>

        <div class="form-footer">
            <p>Already have an account?</p>
            <a href="login.php">Login to your account</a>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleBtn = passwordInput.nextElementSibling.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }

        function showTerms() {
            alert('Terms and Conditions:\n\n1. You must be 18 years or older to join.\n2. All memberships are non-transferable.\n3. Payment is due at time of registration.\n4. Cancellation policies apply.\n5. Gym rules must be followed at all times.');
        }

        function showPrivacy() {
            alert('Privacy Policy:\n\n1. Your personal information is kept confidential.\n2. We use your data only for gym operations.\n3. Health data is used for fitness planning only.\n4. We do not share your information with third parties.\n5. You can request data deletion at any time.');
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const height = document.getElementById('height').value;
            const weight = document.getElementById('weight').value;
            const dateOfBirth = document.getElementById('dateOfBirth').value;

            // Basic validation
            if (password.length < 8) {
                alert('Password must be at least 8 characters long.');
                e.preventDefault();
                return;
            }

            if (password !== confirmPassword) {
                alert('Passwords do not match.');
                e.preventDefault();
                return;
            }

            if (!email.includes('@')) {
                alert('Please enter a valid email address.');
                e.preventDefault();
                return;
            }

            if (!/^[0-9\-\+\s]+$/.test(phone)) {
                alert('Please enter a valid phone number.');
                e.preventDefault();
                return;
            }

            if (height < 100 || height > 250) {
                alert('Please enter a valid height between 100-250 cm.');
                e.preventDefault();
                return;
            }

            if (weight < 30 || weight > 200) {
                alert('Please enter a valid weight between 30-200 kg.');
                e.preventDefault();
                return;
            }

            // Check if user is at least 16 years old
            const birthDate = new Date(dateOfBirth);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (age < 16 || (age === 16 && monthDiff < 0)) {
                alert('You must be at least 16 years old to register.');
                e.preventDefault();
                return;
            }

            // Show loading state
            const registerBtn = document.getElementById('registerBtn');
            const btnText = document.getElementById('btnText');
            
            registerBtn.disabled = true;
            btnText.innerHTML = 'Creating Account...';
            registerBtn.innerHTML = '<div class="loading"></div> Creating Account...';
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

        // Focus on first input on page load
        window.addEventListener('load', function() {
            document.getElementById('firstName').focus();
        });
    </script>
</body>
</html>