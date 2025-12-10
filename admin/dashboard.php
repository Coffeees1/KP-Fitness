<?php
define('PAGE_TITLE', 'Admin Dashboard');
require_once '../includes/config.php';
require_admin(); // Ensure only admins can access

// --- Fetch dashboard statistics ---
try {
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE Role != 'admin'")->fetchColumn(),
        'total_trainers' => $pdo->query("SELECT COUNT(*) FROM users WHERE Role = 'trainer'")->fetchColumn(),
        'total_clients' => $pdo->query("SELECT COUNT(*) FROM users WHERE Role = 'client'")->fetchColumn(),
        'total_classes' => $pdo->query("SELECT COUNT(*) FROM classes WHERE IsActive = TRUE")->fetchColumn(),
        'sessions_this_month' => $pdo->query("SELECT COUNT(*) FROM sessions WHERE MONTH(SessionDate) = MONTH(CURRENT_DATE()) AND YEAR(SessionDate) = YEAR(CURRENT_DATE())")->fetchColumn(),
        'monthly_revenue' => $pdo->query("SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE MONTH(PaymentDate) = MONTH(CURRENT_DATE()) AND YEAR(PaymentDate) = YEAR(CURRENT_DATE()) AND Status = 'completed'")->fetchColumn()
    ];
} catch (PDOException $e) {
    $error_message = 'Error loading dashboard data: ' . $e->getMessage();
    // Initialize stats to 0 on error
    $stats = array_fill_keys(array_keys($stats ?? []), 0);
}

// Data for the cards
$cards = [
    ['label' => 'Total Users', 'value' => number_format($stats['total_users']), 'icon' => 'fa-users', 'color' => 'primary'],
    ['label' => 'Trainers', 'value' => number_format($stats['total_trainers']), 'icon' => 'fa-user-tie', 'color' => 'success'],
    ['label' => 'Clients', 'value' => number_format($stats['total_clients']), 'icon' => 'fa-user-friends', 'color' => 'info'],
    ['label' => 'Active Classes', 'value' => number_format($stats['total_classes']), 'icon' => 'fa-dumbbell', 'color' => 'danger'],
    ['label' => 'Sessions This Month', 'value' => number_format($stats['sessions_this_month']), 'icon' => 'fa-calendar-alt', 'color' => 'warning'],
    ['label' => 'Revenue This Month', 'value' => format_currency($stats['monthly_revenue']), 'icon' => 'fa-money-bill-wave', 'color' => 'primary'],
];

include 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <p class="lead text-body-secondary m-0">Overview of the KP Fitness system.</p>
</div>


<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="row g-4 mb-4">
    <?php foreach ($cards as $card): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card text-bg-dark h-100">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 fs-2 text-<?php echo $card['color']; ?>">
                    <i class="fas <?php echo $card['icon']; ?>"></i>
                </div>
                <div>
                    <h4 class="card-title h2 mb-0"><?php echo $card['value']; ?></h4>
                    <p class="card-text text-body-secondary"><?php echo $card['label']; ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Quick Actions -->
<div class="pb-3 mb-4 border-bottom">
    <h2 class="h3">Quick Actions</h2>
</div>
<div class="row g-4">
    <div class="col-md-6 col-lg-3">
        <a href="users.php" class="card text-bg-dark text-center text-decoration-none h-100">
            <div class="card-body p-4">
                <i class="fas fa-users-cog fa-3x text-primary mb-3"></i>
                <h5 class="card-title">Manage Users</h5>
            </div>
        </a>
    </div>
     <div class="col-md-6 col-lg-3">
        <a href="classes.php" class="card text-bg-dark text-center text-decoration-none h-100">
            <div class="card-body p-4">
                <i class="fas fa-dumbbell fa-3x text-primary mb-3"></i>
                <h5 class="card-title">Manage Classes</h5>
            </div>
        </a>
    </div>
     <div class="col-md-6 col-lg-3">
        <a href="sessions.php" class="card text-bg-dark text-center text-decoration-none h-100">
            <div class="card-body p-4">
                <i class="fas fa-calendar-plus fa-3x text-primary mb-3"></i>
                <h5 class="card-title">Schedule Sessions</h5>
            </div>
        </a>
    </div>
     <div class="col-md-6 col-lg-3">
        <a href="reports.php" class="card text-bg-dark text-center text-decoration-none h-100">
            <div class="card-body p-4">
                <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                <h5 class="card-title">View Reports</h5>
            </div>
        </a>
    </div>
</div>


<?php 
// Also mark the corresponding TODO as complete
$todos_list[3]['status'] = 'completed';
include 'includes/admin_footer.php'; 
?>