<?php
define('PAGE_TITLE', 'Membership');
require_once '../includes/config.php';
require_client();

$userId = $_SESSION['UserID'];
$feedback = [];

    <p class="text-body-secondary">Active Since: <?php echo format_date($currentMembership['PaymentDate']); ?></p>
    <?php else: ?>
        <p class="card-text">You do not have an active membership plan. Choose one below to get started!</p>
    <?php endif; ?>
    </div>
</div>

<!-- Subscription Plans -->
<div class="mb-4">
    <h2 class="h3 mb-3">Subscription Plans</h2>
    <div class="row g-4">
        <?php foreach($subscriptionPlans as $plan): ?>
            <?php 
                $isPopular = $plan['Type'] === 'monthly';
                $isYearly = $plan['Type'] === 'yearly';
                $savings = 0;
                if ($isYearly && $monthly_cost > 0) {
                    $savings = ($monthly_cost * 12) - $plan['Cost'];
                }
            ?>
            <div class="col-lg-6">
                <div class="card text-bg-dark h-100 <?php echo $isPopular ? 'border-primary' : ''; ?>">
                    <div class="card-body text-center d-flex flex-column">
                        <?php if ($isPopular): ?>
                            <span class="badge bg-primary position-absolute top-0 start-50 translate-middle">Most Popular</span>
                        <?php endif; ?>
                        <h3 class="text-primary text-capitalize"><?php echo htmlspecialchars($plan['Type']); ?></h3>
                        <div class="display-4 fw-bold my-3"><?php echo format_currency($plan['Cost']); ?></div>
                        <p class="text-body-secondary"><?php echo $isYearly ? 'per year' : 'per month'; ?></p>
                        <?php if($savings > 0): ?>
                             <p class="fw-bold text-success">You save <?php echo format_currency($savings); ?>!</p>
                        <?php endif; ?>
                        <ul class="list-unstyled my-4">
                            <?php foreach (explode(',', $plan['Benefits']) as $benefit): ?>
                                <li><i class="fas fa-check text-success me-2"></i><?php echo htmlspecialchars(trim($benefit)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-auto">
                            <form action="membership.php" method="POST">
                                <input type="hidden" name="membershipId" value="<?php echo $plan['MembershipID']; ?>">
                                <button type="submit" name="purchase_membership" class="btn btn-primary w-100">Purchase Plan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Payment History -->
<div class="card text-bg-dark">
    <div class="card-header fw-bold">Payment History</div>
    <div class="card-body">
         <div class="table-responsive">
            <table class="table table-dark table-striped table-hover">
                <thead>
                    <tr><th>Date</th><th>Plan</th><th>Amount</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if(empty($paymentHistory)): ?>
                        <tr><td colspan="4" class="text-center text-body-secondary">No payment history found.</td></tr>
                    <?php else: ?>
                        <?php foreach($paymentHistory as $payment): ?>
                            <tr>
                                <td><?php echo format_date($payment['PaymentDate']); ?></td>
                                <td class="text-capitalize"><?php echo htmlspecialchars($payment['Type']); ?></td>
                                <td><?php echo format_currency($payment['Amount']); ?></td>
                                <td><span class="badge text-bg-<?php echo strtolower($payment['Status']) === 'completed' ? 'success' : 'warning'; ?> text-capitalize"><?php echo htmlspecialchars($payment['Status']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php 
// Mark related TODOs
$todos_list[10]['status'] = 'in_progress'; // Membership refactor is in progress
$todos_list[8]['status'] = 'in_progress'; // Date format is being worked on
include 'includes/client_footer.php'; 
?>