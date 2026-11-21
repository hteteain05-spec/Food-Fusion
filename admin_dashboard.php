<?php
// admin_dashboard.php - User Management for Admin
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "foodfusion_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle user deletion - UPDATED TO HANDLE PROPERLY
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Prevent deleting yourself (admin)
    if ($delete_id == $_SESSION['admin_id']) {
        $message = "You cannot delete your own account!";
        $message_type = "danger";
    } else {
        // Check if user exists
        $check_sql = "SELECT user_id, first_name, last_name FROM users WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $delete_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $user_to_delete = $check_result->fetch_assoc();
            
            // First, disable foreign key checks to allow deletion
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
            
            // Delete user
            $delete_sql = "DELETE FROM users WHERE user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $delete_id);
            
            if ($delete_stmt->execute()) {
                $message = "User '" . htmlspecialchars($user_to_delete['first_name'] . ' ' . $user_to_delete['last_name']) . "' has been deleted successfully!";
                $message_type = "success";
                
                // Redirect to refresh the page and show the message
                header("Location: admin_dashboard.php?message=" . urlencode($message) . "&type=" . $message_type);
                exit();
            } else {
                $message = "Error deleting user: " . $delete_stmt->error;
                $message_type = "danger";
            }
            
            // Re-enable foreign key checks
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            $delete_stmt->close();
        } else {
            $message = "User not found!";
            $message_type = "danger";
        }
        $check_stmt->close();
    }
}

// Handle bulk deletion - UPDATED
if (isset($_POST['bulk_delete']) && isset($_POST['selected_users'])) {
    $selected_users = $_POST['selected_users'];
    $deleted_count = 0;
    $failed_count = 0;
    
    // Prevent deleting yourself
    if (in_array($_SESSION['admin_id'], $selected_users)) {
        $message = "You cannot delete your own account!";
        $message_type = "danger";
    } else {
        // Disable foreign key checks for bulk deletion
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        
        foreach ($selected_users as $user_id) {
            $user_id = intval($user_id);
            
            $delete_sql = "DELETE FROM users WHERE user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $user_id);
            
            if ($delete_stmt->execute()) {
                $deleted_count++;
            } else {
                $failed_count++;
            }
            $delete_stmt->close();
        }
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        
        if ($deleted_count > 0) {
            $message = "Successfully deleted $deleted_count user(s).";
            if ($failed_count > 0) {
                $message .= " Failed to delete $failed_count user(s).";
            }
            $message_type = "success";
            
            // Redirect to refresh
            header("Location: admin_dashboard.php?message=" . urlencode($message) . "&type=" . $message_type);
            exit();
        } else {
            $message = "No users were deleted.";
            $message_type = "warning";
        }
    }
}

// Get any messages from redirects
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
}

// Search and filter functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $conn->real_escape_string($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build SQL query with filters
$sql = "SELECT user_id, first_name, last_name, email, registration_date, last_login, 
               failed_login_attempts, account_locked_until, is_active, user_role 
        FROM users WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}

if (!empty($role_filter)) {
    $sql .= " AND user_role = '$role_filter'";
}

if ($status_filter === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($status_filter === 'inactive') {
    $sql .= " AND is_active = 0";
} elseif ($status_filter === 'locked') {
    $sql .= " AND account_locked_until IS NOT NULL AND account_locked_until > NOW()";
}

$sql .= " ORDER BY registration_date DESC";

$result = $conn->query($sql);
$total_users = $result->num_rows;

// Get statistics for filters
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(user_role = 'admin') as admins,
    SUM(user_role = 'user') as regular_users,
    SUM(is_active = 1) as active_users,
    SUM(is_active = 0) as inactive_users,
    SUM(account_locked_until IS NOT NULL AND account_locked_until > NOW()) as locked_users
    FROM users";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodFusion - User Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS for Admin Dashboard -->
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
</head>
<body>
    <!-- Navigation (Add your admin navigation here) -->
    
    <!-- Header -->
    <div class="user-management-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-users me-3"></i>User Management</h1>
                    <p class="mb-0">Manage all registered users of FoodFusion</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="fas fa-user-friends me-1"></i>
                        <?php echo number_format($stats['total']); ?> Total Users
                    </span>
                </div>
            </div>
                        <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
                <div class="container-fluid">

                    <a class="navbar-brand fw-bold" href="admin_dashboard.php">
                        <i class="fas fa-utensils me-2"></i>FoodFusion Admin
                    </a>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="adminNavbar">
                        <ul class="navbar-nav me-auto">

                            <li class="nav-item">
                                <a class="nav-link active" href="admin_dashboard.php">
                                    <i class="fas fa-users me-1"></i> Users
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="admin_recipes.php">
                                    <i class="fas fa-book me-1"></i> Recipes
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="admin_community.php">
                                    <i class="fas fa-comments me-1"></i> Community
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="admin_contact.php">
                                    <i class="fas fa-envelope me-1"></i> Contact Us
                                </a>
                            </li>
                        </ul>

                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-shield me-1"></i>
                                    <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item text-danger" href="admin_login.php">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>

                    </div>
                </div>
            </nav>
        </div>
    </div>
    

    <div class="container">
        <!-- Messages -->
        <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon total">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p class="text-muted mb-0">Total Users</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon admins">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3><?php echo number_format($stats['admins']); ?></h3>
                    <p class="text-muted mb-0">Admins</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon users">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3><?php echo number_format($stats['regular_users']); ?></h3>
                    <p class="text-muted mb-0">Regular Users</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon active">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3><?php echo number_format($stats['active_users']); ?></h3>
                    <p class="text-muted mb-0">Active</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon locked">
                        <i class="fas fa-user-lock"></i>
                    </div>
                    <h3><?php echo number_format($stats['locked_users']); ?></h3>
                    <p class="text-muted mb-0">Locked</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: rgba(108, 117, 125, 0.1); color: #6c757d;">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h3><?php echo number_format($stats['inactive_users']); ?></h3>
                    <p class="text-muted mb-0">Inactive</p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="admin_dashboard.php" class="row g-3">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by name or email..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="locked" <?php echo $status_filter == 'locked' ? 'selected' : ''; ?>>Locked</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
            <div class="mt-3">
                <small class="text-muted">
                    Filtering results: 
                    <?php if (!empty($search)): ?><span class="badge bg-info">Search: <?php echo htmlspecialchars($search); ?></span><?php endif; ?>
                    <?php if (!empty($role_filter)): ?><span class="badge bg-secondary ms-2">Role: <?php echo htmlspecialchars($role_filter); ?></span><?php endif; ?>
                    <?php if (!empty($status_filter)): ?><span class="badge bg-warning ms-2">Status: <?php echo htmlspecialchars($status_filter); ?></span><?php endif; ?>
                    <a href="admin_dashboard.php" class="text-danger ms-2"><i class="fas fa-times"></i> Clear filters</a>
                </small>
            </div>
            <?php endif; ?>
        </div>

        <!-- Users Table -->
        <div class="user-table">
            <?php if ($total_users > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th width="50" class="text-center">#</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Last Login</th>
                            <th width="100" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $result->fetch_assoc()): 
                            $is_locked = $user['account_locked_until'] && strtotime($user['account_locked_until']) > time();
                            $is_current_admin = ($user['user_id'] == $_SESSION['admin_id']);
                        ?>
                        <tr>
                            <td class="text-center">
                                <?php if (!$is_current_admin): ?>
                                <input class="form-check-input user-checkbox" type="checkbox" 
                                       name="selected_users[]" value="<?php echo $user['user_id']; ?>">
                                <?php else: ?>
                                <i class="fas fa-star text-warning" title="Current Admin"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                        <div class="small text-muted">ID: <?php echo $user['user_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge <?php echo $user['user_role'] == 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                    <i class="fas fa-<?php echo $user['user_role'] == 'admin' ? 'user-shield' : 'user'; ?> me-1"></i>
                                    <?php echo ucfirst($user['user_role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($is_locked): ?>
                                    <span class="badge badge-locked">
                                        <i class="fas fa-lock me-1"></i>Locked
                                    </span>
                                <?php elseif ($user['is_active']): ?>
                                    <span class="badge badge-active">
                                        <i class="fas fa-check-circle me-1"></i>Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">
                                        <i class="fas fa-times-circle me-1"></i>Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small">
                                    <?php echo date('M d, Y', strtotime($user['registration_date'])); ?>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <?php if ($user['last_login']): ?>
                                        <?php echo date('M d, Y', strtotime($user['last_login'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Never</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if (!$is_current_admin): ?>
                                <a href="admin_dashboard.php?delete_id=<?php echo $user['user_id']; ?>"
                                   class="btn btn-sm delete-btn"
                                   onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>? This action cannot be undone.')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <?php else: ?>
                                <span class="text-muted small">Current</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Bulk Actions Form -->
            <form method="POST" action="admin_dashboard.php" id="bulkForm" class="p-3 border-top">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">
                                Select all <?php echo $total_users; ?> user(s) for bulk deletion
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group">
                            <button type="submit" name="bulk_delete" class="btn btn-outline-danger" 
                                    onclick="return confirm('Are you sure you want to delete all selected users? This action cannot be undone!')">
                                <i class="fas fa-trash-alt me-2"></i>Delete Selected
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Hidden input for selected users will be populated by JavaScript -->
            </form>
            
            <?php else: ?>
            <div class="no-users">
                <i class="fas fa-user-slash"></i>
                <h4>No users found</h4>
                <p>No users match your search criteria.</p>
                <a href="admin_dashboard.php" class="btn btn-outline-danger">
                    <i class="fas fa-redo me-2"></i>Reset Filters
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/admin_dashboard.js"></script>
</body>
</html>

<?php
$conn->close();
?>