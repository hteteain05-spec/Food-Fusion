<?php
// admin_community.php - Community Cookbook Management for Admin
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

// Handle post deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Get post details before deleting (for message)
    $check_sql = "SELECT recipe_title FROM community_cookbook WHERE contribution_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $post_to_delete = $check_result->fetch_assoc();
        
        // Delete post
        $delete_sql = "DELETE FROM community_cookbook WHERE contribution_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $delete_id);
        
        if ($delete_stmt->execute()) {
            $message = "Post '" . htmlspecialchars($post_to_delete['recipe_title']) . "' has been deleted successfully!";
            $message_type = "success";
            
            // Redirect to refresh the page and show the message
            header("Location: admin_community.php?message=" . urlencode($message) . "&type=" . $message_type);
            exit();
        } else {
            $message = "Error deleting post: " . $delete_stmt->error;
            $message_type = "danger";
        }
        $delete_stmt->close();
    } else {
        $message = "Post not found!";
        $message_type = "danger";
    }
    $check_stmt->close();
}

// Handle bulk deletion
if (isset($_POST['bulk_delete']) && isset($_POST['selected_posts'])) {
    $selected_posts = $_POST['selected_posts'];
    $deleted_count = 0;
    $failed_count = 0;
    
    foreach ($selected_posts as $post_id) {
        $post_id = intval($post_id);
        
        $delete_sql = "DELETE FROM community_cookbook WHERE contribution_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $post_id);
        
        if ($delete_stmt->execute()) {
            $deleted_count++;
        } else {
            $failed_count++;
        }
        $delete_stmt->close();
    }
    
    if ($deleted_count > 0) {
        $message = "Successfully deleted $deleted_count post(s).";
        if ($failed_count > 0) {
            $message .= " Failed to delete $failed_count post(s).";
        }
        $message_type = "success";
        
        // Redirect to refresh
        header("Location: admin_community.php?message=" . urlencode($message) . "&type=" . $message_type);
        exit();
    } else {
        $message = "No posts were deleted.";
        $message_type = "warning";
    }
}

// Get any messages from redirects
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $message_type = isset($_GET['type']) ? $_GET['type'] : 'info';
}

// Search and filter functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build SQL query with filters and join to get user info
$sql = "SELECT 
            cc.contribution_id, 
            cc.recipe_title, 
            cc.recipe_content, 
            cc.recipe_photo, 
            cc.tips, 
            cc.experience,
            cc.submitted_at,
            cc.status,
            cc.likes_count,
            u.user_id,
            u.first_name,
            u.last_name,
            u.email
        FROM community_cookbook cc
        LEFT JOIN users u ON cc.user_id = u.user_id
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (cc.recipe_title LIKE '%$search%' OR cc.recipe_content LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%')";
}

if (!empty($status_filter)) {
    $sql .= " AND cc.status = '$status_filter'";
}

if (!empty($date_filter)) {
    if ($date_filter === 'today') {
        $sql .= " AND DATE(cc.submitted_at) = CURDATE()";
    } elseif ($date_filter === 'week') {
        $sql .= " AND cc.submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($date_filter === 'month') {
        $sql .= " AND cc.submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
}

$sql .= " ORDER BY cc.submitted_at DESC";

$result = $conn->query($sql);
$total_posts = $result->num_rows;

// Get statistics for filters
$stats_sql = "SELECT 
    COUNT(*) as total_posts,
    SUM(status = 'approved') as approved_posts,
    SUM(status = 'rejected') as rejected_posts,
    AVG(LENGTH(recipe_content)) as avg_content_length,
    SUM(likes_count) as total_likes,
    COUNT(DISTINCT user_id) as unique_authors
    FROM community_cookbook";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get recent posts count
$recent_sql = "SELECT COUNT(*) as recent_posts FROM community_cookbook WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$recent_result = $conn->query($recent_sql);
$recent = $recent_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodFusion - Community Cookbook Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS for Admin Community -->
    <link rel="stylesheet" href="assets/css/admin_community.css">
</head>
<body>
    <!-- Header -->
    <div class="community-management-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-book me-3"></i>Community Cookbook</h1>
                    <p class="mb-0">Manage community recipe posts</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="fas fa-receipt me-1"></i>
                        <?php echo number_format($stats['total_posts']); ?> Total Posts
                    </span>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="navbar navbar-expand-lg navbar-dark bg-danger mt-3">
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
                                <a class="nav-link" href="admin_dashboard.php">
                                    <i class="fas fa-users me-1"></i> Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="admin_recipes.php">
                                    <i class="fas fa-book me-1"></i> Recipes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="admin_community.php">
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
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3><?php echo number_format($stats['total_posts']); ?></h3>
                    <p class="text-muted mb-0">Total Posts</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon approved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3><?php echo number_format($stats['approved_posts']); ?></h3>
                    <p class="text-muted mb-0">Approved</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon rejected">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h3><?php echo number_format($stats['rejected_posts']); ?></h3>
                    <p class="text-muted mb-0">Rejected</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon authors">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3><?php echo number_format($stats['unique_authors']); ?></h3>
                    <p class="text-muted mb-0">Unique Authors</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon likes">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3><?php echo number_format($stats['total_likes']); ?></h3>
                    <p class="text-muted mb-0">Total Likes</p>
                </div>
            </div>
            <div class="col-md-2 col-sm-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon recent">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3><?php echo number_format($recent['recent_posts']); ?></h3>
                    <p class="text-muted mb-0">Last 7 Days</p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="admin_community.php" class="row g-3">
                <div class="col-md-5">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by title, content, or author..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="date">
                        <option value="">All Time</option>
                        <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($search) || !empty($status_filter) || !empty($date_filter)): ?>
            <div class="mt-3">
                <small class="text-muted">
                    Filtering results: 
                    <?php if (!empty($search)): ?><span class="badge bg-info">Search: <?php echo htmlspecialchars($search); ?></span><?php endif; ?>
                    <?php if (!empty($status_filter)): ?><span class="badge bg-secondary ms-2">Status: <?php echo htmlspecialchars($status_filter); ?></span><?php endif; ?>
                    <?php if (!empty($date_filter)): ?><span class="badge bg-warning ms-2">Date: <?php echo htmlspecialchars($date_filter); ?></span><?php endif; ?>
                    <a href="admin_community.php" class="text-danger ms-2"><i class="fas fa-times"></i> Clear filters</a>
                </small>
            </div>
            <?php endif; ?>
        </div>

        <!-- Posts Table -->
        <div class="posts-table">
            <?php if ($total_posts > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover" id="postsTable">
                    <thead>
                        <tr>
                            <th width="50" class="text-center">#</th>
                            <th>Post Details</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Likes</th>
                            <th width="150" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($post = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center">
                                <input class="form-check-input post-checkbox" type="checkbox" 
                                       name="selected_posts[]" value="<?php echo $post['contribution_id']; ?>">
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($post['recipe_title']); ?></strong>
                                    <div class="post-preview small text-muted mt-1">
                                        <?php echo htmlspecialchars(substr($post['recipe_content'], 0, 150)); ?>...
                                    </div>
                                    <?php if ($post['recipe_photo']): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($post['recipe_photo']); ?>" 
                                             class="post-image" 
                                             alt="Recipe image"
                                             title="Click to view">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($post['first_name'] ?: 'A', 0, 1) . substr($post['last_name'] ?: 'U', 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong>
                                            <?php echo $post['first_name'] ? htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) : 'Deleted User'; ?>
                                        </strong>
                                        <div class="small text-muted">
                                            <?php echo $post['email'] ? htmlspecialchars($post['email']) : 'N/A'; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $post['status'] == 'approved' ? 'badge-approved' : 'badge-rejected'; ?>">
                                    <i class="fas fa-<?php echo $post['status'] == 'approved' ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                                    <?php echo ucfirst($post['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <?php echo date('M d, Y', strtotime($post['submitted_at'])); ?>
                                    <div class="text-muted">
                                        <?php echo date('h:i A', strtotime($post['submitted_at'])); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="likes-count">
                                    <i class="fas fa-heart text-danger"></i>
                                    <?php echo number_format($post['likes_count']); ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" 
                                            class="btn view-btn"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewPostModal"
                                            data-id="<?php echo $post['contribution_id']; ?>"
                                            data-title="<?php echo htmlspecialchars($post['recipe_title']); ?>"
                                            data-content="<?php echo htmlspecialchars($post['recipe_content']); ?>"
                                            data-tips="<?php echo htmlspecialchars($post['tips']); ?>"
                                            data-experience="<?php echo htmlspecialchars($post['experience']); ?>"
                                            data-photo="<?php echo htmlspecialchars($post['recipe_photo']); ?>"
                                            data-author="<?php echo htmlspecialchars(($post['first_name'] ? $post['first_name'] . ' ' . $post['last_name'] : 'Deleted User')); ?>"
                                            data-email="<?php echo htmlspecialchars($post['email'] ?: 'N/A'); ?>"
                                            data-date="<?php echo date('F j, Y \a\t h:i A', strtotime($post['submitted_at'])); ?>"
                                            data-status="<?php echo ucfirst($post['status']); ?>"
                                            data-likes="<?php echo $post['likes_count']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="admin_community.php?delete_id=<?php echo $post['contribution_id']; ?>"
                                       class="btn delete-btn"
                                       onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Bulk Actions Form -->
            <form method="POST" action="admin_community.php" id="bulkForm" class="p-3 border-top">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">
                                Select all <?php echo $total_posts; ?> post(s) for bulk deletion
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group">
                            <button type="submit" name="bulk_delete" class="btn btn-outline-danger" 
                                    onclick="return confirm('Are you sure you want to delete all selected posts? This action cannot be undone!')">
                                <i class="fas fa-trash-alt me-2"></i>Delete Selected
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <?php else: ?>
            <div class="no-posts">
                <i class="fas fa-receipt"></i>
                <h4>No posts found</h4>
                <p>No community posts match your search criteria.</p>
                <a href="admin_community.php" class="btn btn-outline-danger">
                    <i class="fas fa-redo me-2"></i>Reset Filters
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Post Modal -->
    <div class="modal fade post-details-modal" id="viewPostModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Post Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h4 id="modalPostTitle"></h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Author:</strong> <span id="modalPostAuthor"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Email:</strong> <span id="modalPostEmail"></span>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Submitted:</strong> <span id="modalPostDate"></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Status:</strong> <span id="modalPostStatus"></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Likes:</strong> <span id="modalPostLikes"></span>
                        </div>
                    </div>
                    
                    <div id="modalPostImage" class="text-center mb-4"></div>
                    
                    <div class="content-section">
                        <h6>Recipe Content</h6>
                        <p id="modalPostContent"></p>
                    </div>
                    
                    <div id="modalPostTipsSection" class="content-section">
                        <h6>Cooking Tips</h6>
                        <p id="modalPostTips"></p>
                    </div>
                    
                    <div id="modalPostExperienceSection" class="content-section">
                        <h6>Personal Experience</h6>
                        <p id="modalPostExperience"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="modalDeleteLink" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>Delete Post
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom JS for Admin Community -->
    <script src="assets/js/admin_community.js"></script>
</body>
</html>

<?php
$conn->close();
?>