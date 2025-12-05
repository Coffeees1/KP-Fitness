<?php
require_once '../includes/config.php';
require_client();

$userId = $_SESSION['UserID'];
$error = '';
$success = '';

// Handle membership purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase'])) {
    $membershipId = intval($_POST['membershipId']);
    $paymentMethod = sanitize_input($_POST['paymentMethod']);
    
    try {
        // Get membership details
        $stmt = $pdo->prepare("SELECT * FROM membership WHERE MembershipID = ?");
        $stmt->execute([$membershipId]);
        $membership = $stmt->fetch();
        
        if ($membership) {
            // Create payment record
            $stmt = $pdo->prepare("INSERT INTO payments (UserID, MembershipID, Amount, PaymentMethod, Status) VALUES (?, ?, ?, ?, 'completed')");
            if ($stmt->execute([$userId, $membershipId, $membership['Cost'], $paymentMethod])) {
                $paymentId = $pdo->lastInsertId();
                
                // Update user's membership
                $stmt = $pdo->prepare("UPDATE users SET MembershipID = ? WHERE UserID = ?");
                if ($stmt->execute([$membershipId, $userId])) {
                    $success = 'Membership purchased successfully! Welcome to KP Fitness.';
                    create_notification($userId, 'Membership Activated!', 'Your ' . $membership['Type'] . ' membership has been activated successfully.', 'success');
                } else {
                    $error = 'Failed to update membership. Please contact support.';
                }
            } else {
                $error = 'Failed to process payment. Please try again.';
            }
        } else {
            $error = 'Invalid membership selected.';
        }
    } catch (PDOException $e) {
        $error = 'An error occurred. Please try again.';
    }
}

// Get current membership
$stmt = $pdo->prepare("SELECT m.*, p.PaymentDate, p.Status as PaymentStatus 
                      FROM membership m 
                      LEFT JOIN payments p ON m.MembershipID = p.MembershipID AND p.UserID = ? 
                      WHERE m.MembershipID = (SELECT MembershipID FROM users WHERE UserID = ?)");
$stmt->execute([$userId, $userId]);
$currentMembership = $stmt->fetch();

// Get all membership plans
$stmt = $pdo->prepare("SELECT * FROM membership WHERE IsActive = TRUE ORDER BY Cost ASC");
$stmt->execute();
$membershipPlans = $stmt->fetchAll();

// Get payment history
$stmt = $pdo->prepare("SELECT p.*, m.Type, m.Cost 
                      FROM payments p 
                      JOIN membership m ON p.MembershipID = m.MembershipID 
                      WHERE p.UserID = ? 
                      ORDER BY p.PaymentDate DESC");
$stmt->execute([$userId]);
$paymentHistory = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership - <?php echo SITE_NAME; ?></title>
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
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .card {
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

        .current-membership {
            text-align: center;
            padding: 2rem;
        }

        .membership-type {
            font-size: 2rem;
            font-weight: 800;
            color: #ff6b00;
            margin-bottom: 1rem;
        }

        .membership-status {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: inline-block;
        }

        .status-active {
            background: rgba(40, 167, 69, 0.2);
            color: #51cf66;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-inactive {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .membership-details {
            color: #cccccc;
            margin-bottom: 2rem;
        }

        .membership-details p {
            margin-bottom: 0.5rem;
        }

        .plans-grid {
            display: grid;
            gap: 1.5rem;
        }

        .plan-card {
            background: rgba(26, 26, 26, 0.5);
            border: 2px solid rgba(255, 107, 0, 0.2);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            border-color: #ff6b00;
        }

        .plan-card.popular {
            border-color: #ff6b00;
            transform: scale(1.02);
        }

        .popular-badge {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff6b00;
            color: #ffffff;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .plan-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff6b00;
            margin-bottom: 0.5rem;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .plan-duration {
            color: #cccccc;
            margin-bottom: 2rem;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
            text-align: left;
        }

        .plan-features li {
            padding: 0.5rem 0;
            color: #cccccc;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .plan-features li i {
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
            font-size: 1rem;
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

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .payment-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 107, 0, 0.2);
        }

        .payment-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-option {
            background: rgba(26, 26, 26, 0.5);
            border: 2px solid rgba(255, 107, 0, 0.2);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option:hover, .payment-option.selected {
            border-color: #ff6b00;
            background: rgba(255, 107, 0, 0.1);
        }

        .payment-option i {
            font-size: 2rem;
            color: #ff6b00;
            margin-bottom: 0.5rem;
        }

        .payment-option div {
            font-size: 0.9rem;
            color: #cccccc;
        }

        .qr-code {
            text-align: center;
            margin: 1rem 0;
            padding: 2rem;
            background: rgba(26, 26, 26, 0.5);
            border-radius: 8px;
            display: none;
        }

        .qr-code.show {
            display: block;
        }

        .qr-placeholder {
            width: 200px;
            height: 200px;
            background: #ffffff;
            border-radius: 8px;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000000;
            font-weight: 600;
        }

        .payment-history {
            margin-top: 3rem;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .history-table th,
        .history-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 107, 0, 0.1);
        }

        .history-table th {
            background: rgba(255, 107, 0, 0.1);
            color: #ff6b00;
            font-weight: 600;
        }

        .history-table td {
            color: #cccccc;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            background: rgba(40, 167, 69, 0.2);
            color: #51cf66;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .status-failed {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
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
            .content-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .payment-options {
                grid-template-columns: 1fr;
            }

            .history-table {
                font-size: 0.9rem;
            }

            .history-table th,
            .history-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-id-card"></i> Membership Management
            </h1>
            <p class="page-subtitle">
                Choose the perfect membership plan for your fitness journey and manage your payments.
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
            <!-- Current Membership -->
            <div class="card">
                <h3 class="card-title">Current Membership</h3>
                
                <?php if ($currentMembership && $currentMembership['PaymentStatus'] === 'completed'): ?>
                    <div class="current-membership">
                        <div class="membership-type"><?php echo ucfirst($currentMembership['Type']); ?></div>
                        <div class="membership-status status-active">Active</div>
                        
                        <div class="membership-details">
                            <p><strong>Cost:</strong> <?php echo format_currency($currentMembership['Cost']); ?></p>
                            <p><strong>Duration:</strong> <?php echo $currentMembership['Duration']; ?> days</p>
                            <p><strong>Benefits:</strong></p>
                            <p><?php echo htmlspecialchars($currentMembership['Benefits']); ?></p>
                            <p><strong>Purchased:</strong> <?php echo date('M d, Y', strtotime($currentMembership['PaymentDate'])); ?></p>
                        </div>
                        
                        <button class="btn btn-secondary" onclick="showUpgradeOptions()">
                            <i class="fas fa-arrow-up"></i>
                            Upgrade Plan
                        </button>
                    </div>
                <?php else: ?>
                    <div class="current-membership">
                        <div class="membership-status status-inactive">No Active Membership</div>
                        <p style="color: #cccccc; margin-bottom: 2rem;">
                            You don't have an active membership. Choose a plan below to get started!
                        </p>
                        <a href="#plans" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i>
                            Choose a Plan
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Membership Plans -->
            <div class="card" id="plans">
                <h3 class="card-title">Available Plans</h3>
                
                <div class="plans-grid">
                    <?php foreach ($membershipPlans as $plan): ?>
                        <div class="plan-card <?php echo $plan['Type'] === 'monthly' ? 'popular' : ''; ?>">
                            <?php if ($plan['Type'] === 'monthly'): ?>
                                <div class="popular-badge">Most Popular</div>
                            <?php endif; ?>
                            
                            <div class="plan-name"><?php echo ucfirst($plan['Type']); ?></div>
                            <div class="plan-price"><?php echo format_currency($plan['Cost']); ?></div>
                            <div class="plan-duration">
                                <?php echo $plan['Duration'] == 1 ? 'Per class' : ($plan['Duration'] . ' days'); ?>
                            </div>
                            
                            <ul class="plan-features">
                                <?php $features = explode(',', $plan['Benefits']); ?>
                                <?php foreach ($features as $feature): ?>
                                    <li>
                                        <i class="fas fa-check"></i>
                                        <?php echo trim($feature); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <form method="POST" action="membership.php" class="purchase-form">
                                <input type="hidden" name="membershipId" value="<?php echo $plan['MembershipID']; ?>">
                                <input type="hidden" name="purchase" value="1">
                                
                                <div class="payment-section">
                                    <h4 style="color: #ff6b00; margin-bottom: 1rem;">Payment Method</h4>
                                    <div class="payment-options">
                                        <div class="payment-option" data-method="credit_card">
                                            <i class="fas fa-credit-card"></i>
                                            <div>Credit Card</div>
                                        </div>
                                        <div class="payment-option" data-method="debit_card">
                                            <i class="fas fa-credit-card"></i>
                                            <div>Debit Card</div>
                                        </div>
                                        <div class="payment-option" data-method="touch_n_go">
                                            <i class="fas fa-qrcode"></i>
                                            <div>Touch & Go</div>
                                        </div>
                                        <div class="payment-option" data-method="bank_transfer">
                                            <i class="fas fa-university"></i>
                                            <div>Bank Transfer</div>
                                        </div>
                                    </div>
                                    
                                    <div class="qr-code" id="qrCode">
                                        <div class="qr-placeholder">
                                            Touch & Go QR Code
                                        </div>
                                        <p>Scan with Touch & Go app to pay</p>
                                    </div>
                                    
                                    <input type="hidden" name="paymentMethod" id="paymentMethod" required>
                                    
                                    <button type="submit" class="btn btn-primary purchase-btn" disabled>
                                        <i class="fas fa-shopping-cart"></i>
                                        Purchase <?php echo ucfirst($plan['Type']); ?> Plan
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <?php if (count($paymentHistory) > 0): ?>
            <div class="payment-history">
                <div class="card">
                    <h3 class="card-title">Payment History</h3>
                    
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Membership</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentHistory as $payment): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($payment['PaymentDate'])); ?></td>
                                    <td><?php echo ucfirst($payment['Type']); ?></td>
                                    <td><?php echo format_currency($payment['Amount']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['PaymentMethod'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $payment['Status']; ?>">
                                            <?php echo ucfirst($payment['Status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Payment method selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                const form = this.closest('.purchase-form');
                const method = this.dataset.method;
                const paymentMethodInput = form.querySelector('#paymentMethod');
                const purchaseBtn = form.querySelector('.purchase-btn');
                const qrCode = form.querySelector('#qrCode');
                
                // Remove selected class from all options in this form
                form.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Set payment method
                paymentMethodInput.value = method;
                
                // Enable purchase button
                purchaseBtn.disabled = false;
                
                // Show/hide QR code for Touch & Go
                if (method === 'touch_n_go') {
                    qrCode.classList.add('show');
                } else {
                    qrCode.classList.remove('show');
                }
            });
        });

        // Form submission handling
        document.querySelectorAll('.purchase-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const btn = this.querySelector('.purchase-btn');
                const originalContent = btn.innerHTML;
                
                btn.disabled = true;
                btn.innerHTML = '<div class="loading"></div> Processing...';
                
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }, 3000);
            });
        });

        function showUpgradeOptions() {
            document.getElementById('plans').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>