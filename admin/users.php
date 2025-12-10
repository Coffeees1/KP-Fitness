<?php
define('PAGE_TITLE', 'User Management');
require_once '../includes/config.php';
require_admin();

$feedback = [];

// Handle trainer creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_trainer'])) {
    // ... (PHP logic remains the same)
    $fullName = sanitize_input($_POST['fullName']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($fullName) || empty($email) || empty($password)) {
        $feedback = ['type' => 'danger', 'message' => 'Please fill in all required fields.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback = ['type' => 'danger', 'message' => 'Invalid email format.'];
    } else {
        try {
            $stmt = $pdo->prepare("SELECT UserID FROM users WHERE Email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $feedback = ['type' => 'warning', 'message' => 'An account with this email already exists.'];
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (FullName, Email, Password, Role) VALUES (?, ?, ?, 'trainer')");
                if ($stmt->execute([$fullName, $email, $hashedPassword])) {
                    $feedback = ['type' => 'success', 'message' => 'Trainer account created successfully.'];
                }
            }
        } catch (PDOException $e) {
            $feedback = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Handle user deactivation/reactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['deactivate_user']) || isset($_POST['reactivate_user']))) {
    // ... (PHP logic remains the same)
    $userId = intval($_POST['userId']);
    $newStatus = isset($_POST['deactivate_user']) ? 0 : 1;
    $action = $newStatus === 0 ? 'deactivated' : 'reactivated';

    try {
        $stmt = $pdo->prepare("UPDATE users SET IsActive = ? WHERE UserID = ? AND Role != 'admin'");
        if ($stmt->execute([$newStatus, $userId])) {
            $feedback = ['type' => 'success', 'message' => "User account has been $action."];
        }
    } catch (PDOException $e) {
        $feedback = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Fetch all non-admin users
try {
    $stmt = $pdo->prepare("SELECT UserID, FullName, Email, Role, IsActive FROM users WHERE Role != 'admin' ORDER BY CreatedAt DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch user data: ' . $e->getMessage()];
    $users = [];
}

include 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
    <h1 class="h2">User Management</h1>
    <p class="lead text-body-secondary m-0">Manage client and trainer accounts.</p>
</div>

<?php if (!empty($feedback)): ?>
    <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Create Trainer Form -->
<div class="card text-bg-dark mb-4">
    <div class="card-header fw-bold">Create New Trainer</div>
    <div class="card-body">
        <form action="users.php" method="POST">
            <div class="row g-3 align-items-end">
                <div class="col-md">
                    <label for="fullName" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="fullName" name="fullName" required>
                </div>
                <div class="col-md">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="col-md">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="col-md-auto">
                    <button type="submit" name="create_trainer" class="btn btn-primary w-100">Create Trainer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Users List -->
<div class="card text-bg-dark">
     <div class="card-header fw-bold">All Users</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">Full Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['FullName']); ?></td>
                                <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                <td>
                                    <span class="badge text-bg-<?php echo $user['Role'] === 'trainer' ? 'success' : 'info'; ?>">
                                        <?php echo htmlspecialchars($user['Role']); ?>
                                    </span>
                                </td>
                                <td>
                                     <span class="badge text-bg-<?php echo $user['IsActive'] ? 'light' : 'secondary'; ?>">
                                        <?php echo $user['IsActive'] ? 'Active' : 'Inactive'; ?>
                                     </span>
                                </td>
                                <td>
                                    <form action="users.php" method="POST" class="d-inline">
                                        <input type="hidden" name="userId" value="<?php echo $user['UserID']; ?>">
                                        <?php if ($user['IsActive']): ?>
                                            <button type="submit" name="deactivate_user" class="btn btn-warning btn-sm">Deactivate</button>
                                        <?php else: ?>
                                            <button type="submit" name="reactivate_user" class="btn btn-success btn-sm">Reactivate</button>
                                        <?php endif; ?>
                                    </form>
                                    <!-- Future edit button can go here -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>