<?php
define('PAGE_TITLE', 'User Management');
require_once '../includes/config.php';
require_admin();

$feedback = [];

$edit_user = null;

// Handle Edit Request (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE UserID = ?");
        $stmt->execute([$editId]);
        $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $feedback = ['type' => 'danger', 'message' => 'Error fetching user: ' . $e->getMessage()];
    }
}

// Handle Form Submissions ---

// Handle trainer creation OR update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['create_trainer']) || isset($_POST['update_trainer']))) {
    validate_csrf_token($_POST['csrf_token']);
    $isUpdate = isset($_POST['update_trainer']);
    $userId = $isUpdate ? intval($_POST['userId']) : null;
    
    $fullName = sanitize_input($_POST['fullName']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $gender = sanitize_input($_POST['gender']);
    $specialist = sanitize_input($_POST['specialist'] ?? '');
    $workingHours = sanitize_input($_POST['workingHours'] ?? '');
    $jobType = sanitize_input($_POST['jobType'] ?? '');
    
    // Basic Validation
    if (empty($fullName) || empty($email) || empty($gender)) {
        $feedback = ['type' => 'danger', 'message' => 'Please fill in all required fields (Name, Email, Gender).'];
    } elseif (!$isUpdate && empty($password)) {
        $feedback = ['type' => 'danger', 'message' => 'Password is required for new accounts.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback = ['type' => 'danger', 'message' => 'Invalid email format.'];
    } else {
        try {
            // Check if email already exists (exclude current user on update)
            $sql = "SELECT UserID FROM users WHERE Email = ?";
            $params = [$email];
            if ($isUpdate) {
                $sql .= " AND UserID != ?";
                $params[] = $userId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetch()) {
                $feedback = ['type' => 'danger', 'message' => 'An account with this email already exists.'];
            } else {
                if ($isUpdate) {
                    // Update Logic
                    $sql = "UPDATE users SET FullName = ?, Email = ?, Gender = ?, Specialist = ?, WorkingHours = ?, JobType = ?";
                    $params = [$fullName, $email, $gender, $specialist, $workingHours, $jobType];
                    
                    if (!empty($password)) {
                        $sql .= ", Password = ?";
                        $params[] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    
                    $sql .= " WHERE UserID = ?";
                    $params[] = $userId;
                    
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute($params)) {
                        $feedback = ['type' => 'success', 'message' => 'Trainer updated successfully.'];
                        $edit_user = null; // Clear edit mode
                    } else {
                        $feedback = ['type' => 'danger', 'message' => 'Failed to update trainer.'];
                    }
                } else {
                    // Create Logic
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (FullName, Email, Password, Role, Gender, Specialist, WorkingHours, JobType) VALUES (?, ?, ?, 'trainer', ?, ?, ?, ?)");
                    if ($stmt->execute([$fullName, $email, $hashedPassword, $gender, $specialist, $workingHours, $jobType])) {
                        $feedback = ['type' => 'success', 'message' => 'Trainer account created successfully.'];
                    } else {
                        $feedback = ['type' => 'danger', 'message' => 'Failed to create trainer account.'];
                    }
                }
            }
        } catch (PDOException $e) {
            $feedback = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Handle user deactivation/reactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['deactivate_user']) || isset($_POST['reactivate_user']))) {
    validate_csrf_token($_POST['csrf_token']);
    $userId = intval($_POST['userId']);
    $newStatus = isset($_POST['deactivate_user']) ? 0 : 1;
    $action = $newStatus === 0 ? 'deactivated' : 'reactivated';

    try {
        $stmt = $pdo->prepare("UPDATE users SET IsActive = ? WHERE UserID = ? AND Role != 'admin'");
        if ($stmt->execute([$newStatus, $userId])) {
            $feedback = ['type' => 'success', 'message' => "User account has been $action."];
        } else {
            $feedback = ['type' => 'danger', 'message' => "Failed to $action user account."];
        }
    } catch (PDOException $e) {
        $feedback = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    validate_csrf_token($_POST['csrf_token']);
    $userId = intval($_POST['userId']);
    
    try {
        // Check if user is admin
        $stmt = $pdo->prepare("SELECT Role FROM users WHERE UserID = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['Role'] === 'admin') {
            $feedback = ['type' => 'danger', 'message' => 'Cannot delete admin accounts.'];
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE UserID = ? AND Role != 'admin'");
            if ($stmt->execute([$userId])) {
                $feedback = ['type' => 'success', 'message' => 'User account deleted successfully.'];
            }
        }
    } catch (PDOException $e) {
        $feedback = ['type' => 'danger', 'message' => 'Database error: ' . $e->getMessage()];
    }
}


// --- Fetch Data for Display ---
try {
    // Fetch all users including admins, now with Trainer details
    $stmt = $pdo->prepare("SELECT UserID, FullName, Email, Role, IsActive, Gender, Specialist, WorkingHours, JobType FROM users ORDER BY Role, FullName");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $feedback = ['type' => 'danger', 'message' => 'Could not fetch user data: ' . $e->getMessage()];
    $users = [];
}

include 'includes/admin_header.php';
?>

<style>
    .user-folder-card {
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 2px solid transparent;
    }
    .user-folder-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        border-color: var(--primary-color);
    }
    .folder-icon {
        font-size: 3rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }
    .folder-title-style {
        background-color: var(--primary-color);
        color: var(--text-light) !important; /* Ensure text is white */
        padding: 0.25rem 0.75rem;
        border-radius: 0.3rem; /* Slightly rounded corners */
        display: inline-block; /* To allow background to wrap content */
        margin-bottom: 0.5rem; /* Space below the title */
    }
    .modal-header {
        background-color: var(--dark-bg);
        border-bottom: 1px solid var(--border-color);
    }
    .modal-header .modal-title {
        background-color: var(--primary-color);
        color: var(--text-light);
        padding: 0.25rem 0.75rem;
        border-radius: 0.3rem;
    }
    .modal-content {
        background-color: var(--light-bg);
        color: var(--text-light);
        border: 1px solid var(--border-color);
    }
    .modal-close-btn {
        filter: invert(1);
    }
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">User Management</h1>
</div>

<?php if (!empty($feedback)): ?>
    <div class="alert alert-<?php echo $feedback['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Create/Edit Trainer Form -->
<div class="mb-4">
    <h3 class="mb-3"><?php echo $edit_user ? 'Edit Trainer: ' . htmlspecialchars($edit_user['FullName']) : 'Create New Trainer'; ?></h3>
    <form action="users.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
        <?php if ($edit_user): ?>
            <input type="hidden" name="userId" value="<?php echo $edit_user['UserID']; ?>">
            <input type="hidden" name="update_trainer" value="1">
        <?php else: ?>
            <input type="hidden" name="create_trainer" value="1">
        <?php endif; ?>
        
        <div class="row g-3">
            <div class="col-md-3">
                <label for="fullName" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullName" name="fullName" value="<?php echo htmlspecialchars($edit_user['FullName'] ?? ''); ?>" required>
            </div>
            <div class="col-md-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['Email'] ?? ''); ?>" required>
            </div>
            <div class="col-md-3">
                <label for="password" class="form-label">Password <?php echo $edit_user ? '(Leave blank to keep)' : ''; ?></label>
                <input type="password" class="form-control" id="password" name="password" <?php echo $edit_user ? '' : 'required'; ?>>
            </div>
            <div class="col-md-3">
                <label for="gender" class="form-label">Gender</label>
                <select class="form-select" id="gender" name="gender" required>
                    <option value="">Select...</option>
                    <option value="Male" <?php echo ($edit_user['Gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo ($edit_user['Gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo ($edit_user['Gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="specialist" class="form-label">Specialist</label>
                <select class="form-select" id="specialist" name="specialist">
                    <option value="">-- Select Specialty --</option>
                    <?php
                        // Fetch categories for specialist dropdown
                        $stmt_cat = $pdo->query("SELECT CategoryName FROM class_categories ORDER BY CategoryName");
                        while ($cat = $stmt_cat->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($edit_user['Specialist'] ?? '') === $cat['CategoryName'] ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($cat['CategoryName']) . '" ' . $selected . '>' . htmlspecialchars($cat['CategoryName']) . '</option>';
                        }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="workingHours" class="form-label">Working Hours</label>
                <input type="text" class="form-control" id="workingHours" name="workingHours" value="<?php echo htmlspecialchars($edit_user['WorkingHours'] ?? ''); ?>" placeholder="e.g., 9AM - 5PM">
            </div>
            <div class="col-md-3">
                <label for="jobType" class="form-label">Job Type</label>
                <select class="form-select" id="jobType" name="jobType">
                    <option value="">-- Select Type --</option>
                    <option value="Full-time" <?php echo ($edit_user['JobType'] ?? '') === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                    <option value="Part-time" <?php echo ($edit_user['JobType'] ?? '') === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><?php echo $edit_user ? 'Update Trainer' : 'Create Trainer'; ?></button>
                <?php if ($edit_user): ?>
                    <a href="users.php" class="btn btn-secondary">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Users Folders -->
<div class="mb-4">
    <h3 class="mb-3">All Users</h3>
    <div class="row">
        <!-- Admin Folder -->
        <div class="col-md-4 mb-3">
            <div class="card user-folder-card h-100 text-center p-4" id="folder-admin">
                <div class="folder-icon"><i class="fas fa-user-shield"></i></div>
                <h4 class="folder-title-style">Administrators</h4>
                <p class="text-muted">Manage system admins</p>
                <span class="badge bg-secondary"><?php echo count(array_filter($users, fn($u) => $u['Role'] === 'admin')); ?> Users</span>
            </div>
        </div>
        <!-- Trainer Folder -->
        <div class="col-md-4 mb-3">
            <div class="card user-folder-card h-100 text-center p-4" id="folder-trainer">
                <div class="folder-icon"><i class="fas fa-user-tie"></i></div>
                <h4 class="folder-title-style">Trainers</h4>
                <p class="text-muted">Manage fitness trainers</p>
                <span class="badge bg-secondary"><?php echo count(array_filter($users, fn($u) => $u['Role'] === 'trainer')); ?> Users</span>
            </div>
        </div>
        <!-- Client Folder -->
        <div class="col-md-4 mb-3">
            <div class="card user-folder-card h-100 text-center p-4" id="folder-client">
                <div class="folder-icon"><i class="fas fa-user"></i></div>
                <h4 class="folder-title-style">Clients</h4>
                <p class="text-muted">Manage gym members</p>
                <span class="badge bg-secondary"><?php echo count(array_filter($users, fn($u) => $u['Role'] === 'client')); ?> Users</span>
            </div>
        </div>
    </div>
</div>

<!-- User List Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-capitalize" id="userModalTitle">Users</h5>
                <button type="button" class="btn-close modal-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search and Filter Controls -->
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" id="userSearchInput" class="form-control" placeholder="Search by name or email...">
                    </div>
                    <div class="col-md-4">
                        <select id="userStatusFilter" class="form-select">
                            <option value="all">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select id="userGenderFilter" class="form-select">
                            <option value="all">All Genders</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover table-dark mb-0">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th id="genderColHeader">Gender</th>
                                <th id="specColHeader" style="display:none">Specialist</th>
                                <th id="hoursColHeader" style="display:none">Hours</th>
                                <th id="jobColHeader" style="display:none">Job Type</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Controls -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div id="paginationInfo" class="text-muted"></div>
                    <nav aria-label="User list pagination">
                        <ul class="pagination mb-0">
                            <li class="page-item">
                                <button class="page-link" id="prevPageBtn" onclick="changePage(-1)">Previous</button>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link" id="pageIndicator">1</span>
                            </li>
                            <li class="page-item">
                                <button class="page-link" id="nextPageBtn" onclick="changePage(1)">Next</button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const allUsers = <?php echo json_encode($users); ?>;
    const csrfToken = '<?php echo get_csrf_token(); ?>';
    
    // State
    let currentRole = '';
    let currentPage = 1;
    const itemsPerPage = 20;
    let filteredUsers = [];

    // Event Listeners for Folders
    const folderAdmin = document.getElementById('folder-admin');
    const folderTrainer = document.getElementById('folder-trainer');
    const folderClient = document.getElementById('folder-client');

    if(folderAdmin) folderAdmin.addEventListener('click', () => openUserModal('admin'));
    if(folderTrainer) folderTrainer.addEventListener('click', () => openUserModal('trainer'));
    if(folderClient) folderClient.addEventListener('click', () => openUserModal('client'));

    function openUserModal(role) {
        currentRole = role;
        
        // Reset state
        currentPage = 1;
        document.getElementById('userSearchInput').value = '';
        document.getElementById('userStatusFilter').value = 'all';
        document.getElementById('userGenderFilter').value = 'all';
        
        // Show/Hide Gender Column based on role
        const showGender = (role === 'trainer' || role === 'client');
        document.getElementById('genderColHeader').style.display = showGender ? 'table-cell' : 'none';
        document.getElementById('userGenderFilter').parentElement.style.display = showGender ? 'block' : 'none';
        
        // Show/Hide Trainer specific columns
        const showTrainerCols = (role === 'trainer');
        document.getElementById('specColHeader').style.display = showTrainerCols ? 'table-cell' : 'none';
        document.getElementById('hoursColHeader').style.display = showTrainerCols ? 'table-cell' : 'none';
        document.getElementById('jobColHeader').style.display = showTrainerCols ? 'table-cell' : 'none';
        
        // Update Modal Title
        document.getElementById('userModalTitle').textContent = role + 's';
        
        // Initial Render
        filterAndRender();
        
        // Show Modal
        const modalEl = document.getElementById('userModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    // Filter Logic
    function filterAndRender() {
        const searchTerm = document.getElementById('userSearchInput').value.toLowerCase();
        const statusFilter = document.getElementById('userStatusFilter').value;
        const genderFilter = document.getElementById('userGenderFilter').value;

        // Filter master list
        filteredUsers = allUsers.filter(user => {
            // Role filter
            if (user.Role !== currentRole) return false;
            
            // Search filter
            const matchesSearch = user.FullName.toLowerCase().includes(searchTerm) || 
                                  user.Email.toLowerCase().includes(searchTerm);
            if (!matchesSearch) return false;

            // Status filter
            if (statusFilter !== 'all') {
                if (user.IsActive != statusFilter) return false;
            }

            // Gender filter
            if (genderFilter !== 'all') {
                 if ((user.Gender || '') !== genderFilter) return false;
            }

            return true;
        });

        // Reset to page 1 on filter change logic handled by callers or reset manually
        // But if this is called from pagination, we shouldn't reset. 
        // We will reset page only when search/filter changes.
        
        renderTable();
    }

    // Event Listeners for Filters
    document.getElementById('userSearchInput').addEventListener('input', () => {
        currentPage = 1;
        filterAndRender();
    });

    document.getElementById('userStatusFilter').addEventListener('change', () => {
        currentPage = 1;
        filterAndRender();
    });
    
    document.getElementById('userGenderFilter').addEventListener('change', () => {
        currentPage = 1;
        filterAndRender();
    });


    // Pagination Logic
    function changePage(delta) {
        const maxPage = Math.ceil(filteredUsers.length / itemsPerPage) || 1;
        const newPage = currentPage + delta;
        
        if (newPage >= 1 && newPage <= maxPage) {
            currentPage = newPage;
            renderTable();
        }
    }
    window.changePage = changePage;

    function renderTable() {
        const tbody = document.getElementById('userTableBody');
        tbody.innerHTML = '';
        
        if (filteredUsers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No users found.</td></tr>';
            updatePaginationUI(0);
            return;
        }

        // Slice data for pagination
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const pageData = filteredUsers.slice(startIndex, endIndex);

        const showGender = (currentRole === 'trainer' || currentRole === 'client');
        const showTrainerCols = (currentRole === 'trainer');

        pageData.forEach(user => {
            const tr = document.createElement('tr');
            
            // Status Badge logic
            const statusBadge = user.IsActive == 1 
                ? '<span class="badge bg-success">Active</span>' 
                : '<span class="badge bg-danger">Inactive</span>';
            
            // Action Buttons logic
            let actionButtons = '';
            if (user.Role !== 'admin') { 
                const actionName = user.IsActive == 1 ? 'deactivate_user' : 'reactivate_user';
                const actionBtnClass = user.IsActive == 1 ? 'btn-danger' : 'btn-success';
                const actionBtnText = user.IsActive == 1 ? 'Deactivate' : 'Reactivate';
                
                actionButtons = `
                    <form action="users.php" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="${csrfToken}">
                        <input type="hidden" name="userId" value="${user.UserID}">
                `;

                // Add Edit button for Trainers
                if (user.Role === 'trainer') {
                    actionButtons += `
                        <a href="users.php?edit=${user.UserID}" class="btn btn-primary btn-sm me-1">Edit</a>
                    `;
                }

                actionButtons += `
                        <button type="submit" name="${actionName}" class="btn ${actionBtnClass} btn-sm me-1">${actionBtnText}</button>
                `;
                
                // Only allow deletion for non-client/non-admin roles (e.g. Trainers) if needed, 
                // OR as per request: just remove for client. 
                // The prompt says "remove the 'Delete' button for Client". 
                // So if role is NOT client, show delete.
                if (user.Role !== 'client' && user.Role !== 'trainer') {
                     actionButtons += `<button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</button>`;
                }
                
                actionButtons += `</form>`;
            } else {
                actionButtons = '<span class="text-muted fst-italic">Protected</span>';
            }

            const genderCell = showGender ? `<td>${escapeHtml(user.Gender || '-')}</td>` : '<td style="display:none"></td>';
            
            // New Trainer Columns
            const specCell = showTrainerCols ? `<td>${escapeHtml(user.Specialist || '-')}</td>` : '<td style="display:none"></td>';
            const hoursCell = showTrainerCols ? `<td>${escapeHtml(user.WorkingHours || '-')}</td>` : '<td style="display:none"></td>';
            const jobCell = showTrainerCols ? `<td>${escapeHtml(user.JobType || '-')}</td>` : '<td style="display:none"></td>';

            tr.innerHTML = `
                <td>${escapeHtml(user.FullName)}</td>
                <td>${escapeHtml(user.Email)}</td>
                ${genderCell}
                ${specCell}
                ${hoursCell}
                ${jobCell}
                <td>${statusBadge}</td>
                <td>${actionButtons}</td>
            `;
            tbody.appendChild(tr);
        });

        updatePaginationUI(filteredUsers.length);
    }

    function updatePaginationUI(totalItems) {
        const maxPage = Math.ceil(totalItems / itemsPerPage) || 1;
        const startItem = totalItems === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalItems);

        document.getElementById('paginationInfo').textContent = `Showing ${startItem}-${endItem} of ${totalItems}`;
        document.getElementById('pageIndicator').textContent = `${currentPage} / ${maxPage}`;
        
        document.getElementById('prevPageBtn').parentElement.classList.toggle('disabled', currentPage === 1);
        document.getElementById('nextPageBtn').parentElement.classList.toggle('disabled', currentPage === maxPage);
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>
