<?php
// admin_contact.php - Contact Messages Management for Admin
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

// Check what columns exist in the users table
$check_users_table = $conn->query("DESCRIBE users");
$users_columns = [];
while ($row = $check_users_table->fetch_assoc()) {
    $users_columns[] = $row['Field'];
}

// Initialize variables
$message = "";
$message_type = "";

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $message_id = intval($_GET['id'] ?? 0);
    
    switch ($action) {
        case 'mark_read':
            $update_sql = "UPDATE contact_messages SET is_read = 1 WHERE message_id = ?";
            $stmt = $conn->prepare($update_sql);
            if ($stmt) {
                $stmt->bind_param("i", $message_id);
                if ($stmt->execute()) {
                    $message = "Message marked as read";
                    $message_type = "success";
                } else {
                    $message = "Error updating message: " . $stmt->error;
                    $message_type = "danger";
                }
                $stmt->close();
            } else {
                $message = "Error preparing statement: " . $conn->error;
                $message_type = "danger";
            }
            break;
            
        case 'mark_unread':
            $update_sql = "UPDATE contact_messages SET is_read = 0 WHERE message_id = ?";
            $stmt = $conn->prepare($update_sql);
            if ($stmt) {
                $stmt->bind_param("i", $message_id);
                if ($stmt->execute()) {
                    $message = "Message marked as unread";
                    $message_type = "success";
                } else {
                    $message = "Error updating message: " . $stmt->error;
                    $message_type = "danger";
                }
                $stmt->close();
            } else {
                $message = "Error preparing statement: " . $conn->error;
                $message_type = "danger";
            }
            break;
            
        case 'delete':
            $delete_sql = "DELETE FROM contact_messages WHERE message_id = ?";
            $stmt = $conn->prepare($delete_sql);
            if ($stmt) {
                $stmt->bind_param("i", $message_id);
                if ($stmt->execute()) {
                    $message = "Message deleted successfully";
                    $message_type = "success";
                } else {
                    $message = "Error deleting message: " . $stmt->error;
                    $message_type = "danger";
                }
                $stmt->close();
            } else {
                $message = "Error preparing statement: " . $conn->error;
                $message_type = "danger";
            }
            break;
    }
    
    // Redirect to avoid resubmission
    header("Location: admin_contact.php");
    exit();
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_messages'])) {
    $selected_messages = $_POST['selected_messages'];
    $bulk_action = $_POST['bulk_action'];
    
    if (!empty($selected_messages) && is_array($selected_messages)) {
        $placeholders = implode(',', array_fill(0, count($selected_messages), '?'));
        $types = str_repeat('i', count($selected_messages));
        
        switch ($bulk_action) {
            case 'mark_read':
                $sql = "UPDATE contact_messages SET is_read = 1 WHERE message_id IN ($placeholders)";
                break;
            case 'mark_unread':
                $sql = "UPDATE contact_messages SET is_read = 0 WHERE message_id IN ($placeholders)";
                break;
            case 'delete':
                $sql = "DELETE FROM contact_messages WHERE message_id IN ($placeholders)";
                break;
            default:
                $sql = "";
        }
        
        if (!empty($sql)) {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$selected_messages);
                if ($stmt->execute()) {
                    $message = "Bulk action completed successfully";
                    $message_type = "success";
                } else {
                    $message = "Error executing bulk action: " . $stmt->error;
                    $message_type = "danger";
                }
                $stmt->close();
            } else {
                $message = "Error preparing bulk action: " . $conn->error;
                $message_type = "danger";
            }
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$search_term = $_GET['search'] ?? '';

// Build query with filters based on actual database structure
$where_clauses = [];
$params = [];
$types = "";

if ($status_filter === 'unread') {
    $where_clauses[] = "is_read = 0";
} elseif ($status_filter === 'read') {
    $where_clauses[] = "is_read = 1";
}

if ($type_filter !== 'all' && in_array($type_filter, ['inquiry', 'recipe_request', 'feedback', 'other'])) {
    $where_clauses[] = "message_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if (!empty($search_term)) {
    $where_clauses[] = "(full_name LIKE ? OR email LIKE ? OR subject LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_sql = empty($where_clauses) ? "" : "WHERE " . implode(" AND ", $where_clauses);

// Build the SELECT query based on available user columns
// Check which user fields exist
$has_first_name = in_array('first_name', $users_columns);
$has_last_name = in_array('last_name', $users_columns);
$has_email = in_array('email', $users_columns);
$has_username = in_array('username', $users_columns);
$has_name = in_array('name', $users_columns);

// Create user field selection based on what's available
$user_fields = "cm.*";
if ($has_first_name && $has_last_name) {
    $user_fields .= ", u.first_name, u.last_name";
} elseif ($has_name) {
    $user_fields .= ", u.name";
} elseif ($has_username) {
    $user_fields .= ", u.username";
}
if ($has_email) {
    $user_fields .= ", u.email as user_email";
}

// Get messages with filters - updated for actual database structure
$sql = "SELECT $user_fields
        FROM contact_messages cm 
        LEFT JOIN users u ON cm.user_id = u.user_id 
        $where_sql 
        ORDER BY 
            is_read ASC,
            submitted_at DESC";

$messages = [];
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        echo "<div class='alert alert-danger'>Error preparing query: " . $conn->error . "</div>";
    }
} else {
    $result = $conn->query($sql);
    if ($result) {
        $messages = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        echo "<div class='alert alert-danger'>Error executing query: " . $conn->error . "</div>";
    }
}

// Get statistics - updated for actual database structure
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(is_read = 0) as unread_count,
    SUM(is_read = 1) as read_count,
    SUM(message_type = 'inquiry') as inquiries,
    SUM(message_type = 'recipe_request') as requests,
    SUM(message_type = 'feedback') as feedback,
    SUM(message_type = 'other') as other
    FROM contact_messages";

$stats_result = $conn->query($stats_sql);
if ($stats_result) {
    $stats = $stats_result->fetch_assoc();
} else {
    $stats = [
        'total' => 0,
        'unread_count' => 0,
        'read_count' => 0,
        'inquiries' => 0,
        'requests' => 0,
        'feedback' => 0,
        'other' => 0
    ];
}

// Function to get user display name based on available fields
function getUserDisplayName($user_data, $has_first_name, $has_last_name, $has_name, $has_username) {
    if ($has_first_name && $has_last_name && !empty($user_data['first_name'])) {
        return htmlspecialchars($user_data['first_name'] . ' ' . ($user_data['last_name'] ?? ''));
    } elseif ($has_name && !empty($user_data['name'])) {
        return htmlspecialchars($user_data['name']);
    } elseif ($has_username && !empty($user_data['username'])) {
        return htmlspecialchars($user_data['username']);
    }
    return null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodFusion - Contact Messages Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin_contact.css">
</head>
<body data-status-filter="<?php echo htmlspecialchars($status_filter, ENT_QUOTES, 'UTF-8'); ?>" data-type-filter="<?php echo htmlspecialchars($type_filter, ENT_QUOTES, 'UTF-8'); ?>" data-search-term="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>" data-unread-count="<?php echo (int)($stats['unread_count'] ?? 0); ?>">
    <!-- Navigation -->
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
                        <a class="nav-link" href="admin_community.php">
                            <i class="fas fa-comments me-1"></i> Community
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="admin_contact.php">
                            <i class="fas fa-envelope me-1"></i> Contact Messages
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

    <!-- Header -->
    <div class="contact-management-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-envelope me-3"></i>Contact Messages</h1>
                    <p class="mb-0">Manage all incoming contact messages and inquiries</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="fas fa-envelope me-1"></i>
                        <?php echo number_format($stats['total'] ?? 0); ?> Total Messages
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 col-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon total">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="mb-1"><?php echo number_format($stats['total'] ?? 0); ?></h3>
                    <p class="mb-0 text-muted">Total Messages</p>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon unread">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h3 class="mb-1"><?php echo number_format($stats['unread_count'] ?? 0); ?></h3>
                    <p class="mb-0 text-muted">Unread Messages</p>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="stats-card">
                    <div class="stats-icon read">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="mb-1"><?php echo number_format($stats['read_count'] ?? 0); ?></h3>
                    <p class="mb-0 text-muted">Read Messages</p>
                </div>
            </div>
        </div>
        
        <!-- Type Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="filter-section">
                    <h5><i class="fas fa-chart-pie me-2"></i>Message Type Breakdown</h5>
                    <div class="row mt-3">
                        <div class="col-md-3 mb-2">
                            <div class="stat-item">
                                <span class="stat-label">Inquiries:</span>
                                <span class="stat-value"><?php echo number_format($stats['inquiries'] ?? 0); ?></span>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="stat-item">
                                <span class="stat-label">Recipe Requests:</span>
                                <span class="stat-value"><?php echo number_format($stats['requests'] ?? 0); ?></span>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="stat-item">
                                <span class="stat-label">Feedback:</span>
                                <span class="stat-value"><?php echo number_format($stats['feedback'] ?? 0); ?></span>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="stat-item">
                                <span class="stat-label">Other:</span>
                                <span class="stat-value"><?php echo number_format($stats['other'] ?? 0); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters and Search -->
        <div class="filter-section mb-4">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="all" <?php echo ($status_filter === 'all') ? 'selected' : ''; ?>>All Status</option>
                            <option value="unread" <?php echo ($status_filter === 'unread') ? 'selected' : ''; ?>>Unread</option>
                            <option value="read" <?php echo ($status_filter === 'read') ? 'selected' : ''; ?>>Read</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Message Type</label>
                        <select class="form-select" name="type">
                            <option value="all" <?php echo ($type_filter === 'all') ? 'selected' : ''; ?>>All Types</option>
                            <option value="inquiry" <?php echo ($type_filter === 'inquiry') ? 'selected' : ''; ?>>Inquiry</option>
                            <option value="recipe_request" <?php echo ($type_filter === 'recipe_request') ? 'selected' : ''; ?>>Recipe Request</option>
                            <option value="feedback" <?php echo ($type_filter === 'feedback') ? 'selected' : ''; ?>>Feedback</option>
                            <option value="other" <?php echo ($type_filter === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Search</label>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" class="form-control" name="search" placeholder="Search by name, email, or subject..." value="<?php echo htmlspecialchars($search_term); ?>">
                        </div>
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($search_term) || $status_filter !== 'all' || $type_filter !== 'all'): ?>
                <div class="mt-3">
                    <a href="admin_contact.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </a>
                    <small class="text-muted ms-2">
                        Showing <?php echo count($messages); ?> messages
                        <?php if (!empty($search_term)): ?>
                            matching "<?php echo htmlspecialchars($search_term); ?>"
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Bulk Actions -->
        <form method="POST" action="" id="bulkForm">
            <div class="bulk-actions">
                <div class="row align-items-center">
                    <div class="col-md-3 mb-2">
                        <select class="form-select" name="bulk_action" id="bulkAction">
                            <option value="">Bulk Actions</option>
                            <option value="mark_read">Mark as Read</option>
                            <option value="mark_unread">Mark as Unread</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="selectAll()">
                            <i class="fas fa-check-square me-1"></i>Select All
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="deselectAll()">
                            <i class="far fa-square me-1"></i>Deselect All
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary" onclick="return confirmBulkAction()">
                            <i class="fas fa-play me-1"></i>Apply
                        </button>
                    </div>
                    <div class="col-md-3 mb-2 text-end">
                        <a href="#" class="export-btn" onclick="exportMessages()">
                            <i class="fas fa-download me-2"></i>Export
                        </a>
                    </div>
                </div>
            </div>
        
        <!-- Messages Table -->
        <?php if (count($messages) > 0): ?>
            <div class="contact-table">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)">
                            </th>
                            <th width="60">ID</th>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th width="100">Type</th>
                            <th width="100">Status</th>
                            <th width="150">Date</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr class="<?php echo ($msg['is_read'] ?? 0) == 0 ? 'table-row-unread' : 'table-row-read'; ?>">
                                <td>
                                    <input type="checkbox" name="selected_messages[]" value="<?php echo $msg['message_id']; ?>" class="message-checkbox">
                                </td>
                                <td>
                                    <small class="text-muted">#<?php echo $msg['message_id']; ?></small>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <?php if (!empty($msg['user_id'])): 
                                            $user_display_name = getUserDisplayName($msg, $has_first_name, $has_last_name, $has_name, $has_username);
                                        ?>
                                            <div class="user-avatar">
                                                <?php 
                                                if ($user_display_name) {
                                                    echo strtoupper(substr($user_display_name, 0, 1));
                                                } else {
                                                    echo 'U';
                                                }
                                                ?>
                                            </div>
                                            <div>
                                                <?php if ($user_display_name): ?>
                                                    <div class="fw-bold"><?php echo $user_display_name; ?></div>
                                                <?php endif; ?>
                                                <small class="text-muted">Registered User</small>
                                            </div>
                                        <?php else: ?>
                                            <div class="user-avatar" style="background: #6c757d;">
                                                <?php echo strtoupper(substr($msg['full_name'] ?? 'G', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($msg['full_name'] ?? 'Guest'); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($msg['email'] ?? ''); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($msg['subject'] ?? 'No Subject'); ?></strong>
                                </td>
                                <td>
                                    <div class="message-preview" title="<?php echo htmlspecialchars($msg['message'] ?? ''); ?>">
                                        <?php 
                                        $message_text = $msg['message'] ?? '';
                                        echo nl2br(htmlspecialchars(substr($message_text, 0, 100)));
                                        if (strlen($message_text) > 100) {
                                            echo '...';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $type_class = [
                                        'inquiry' => 'badge-inquiry',
                                        'recipe_request' => 'badge-request',
                                        'feedback' => 'badge-feedback',
                                        'other' => 'badge-other'
                                    ];
                                    $msg_type = $msg['message_type'] ?? 'inquiry';
                                    ?>
                                    <span class="type-badge <?php echo $type_class[$msg_type] ?? 'badge-inquiry'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $msg_type)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $is_read = $msg['is_read'] ?? 0;
                                    if ($is_read == 1): ?>
                                        <span class="badge badge-read">
                                            <i class="fas fa-check-circle me-1"></i>Read
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-unread">
                                            <i class="fas fa-exclamation-circle me-1"></i>Unread
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <?php echo date('M j, Y', strtotime($msg['submitted_at'])); ?><br>
                                        <span class="text-muted"><?php echo date('g:i A', strtotime($msg['submitted_at'])); ?></span>
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap">
                                        <button type="button" class="action-btn btn-read" data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $msg['message_id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        
                                        <?php if ($msg['is_read'] == 0): ?>
                                            <a href="?action=mark_read&id=<?php echo $msg['message_id']; ?>" class="action-btn btn-read">
                                                <i class="fas fa-check"></i> Read
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=mark_unread&id=<?php echo $msg['message_id']; ?>" class="action-btn btn-unread">
                                                <i class="fas fa-eye-slash"></i> Unread
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?action=delete&id=<?php echo $msg['message_id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this message?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Message Detail Modal -->
                            <div class="modal fade" id="messageModal<?php echo $msg['message_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-envelope me-2"></i>
                                                Message #<?php echo $msg['message_id']; ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="message-details">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>From:</strong><br>
                                                        <?php echo htmlspecialchars($msg['full_name']); ?><br>
                                                        <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>">
                                                            <?php echo htmlspecialchars($msg['email']); ?>
                                                        </a>
                                                        <?php if (!empty($msg['user_id'])): ?>
                                                            <br><small class="text-success">Registered User</small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Submitted:</strong><br>
                                                        <?php echo date('F j, Y g:i A', strtotime($msg['submitted_at'])); ?><br>
                                                        <strong>Status:</strong>
                                                        <?php if ($msg['is_read'] == 1): ?>
                                                            <span class="badge badge-read ms-2">
                                                                <i class="fas fa-check-circle me-1"></i>Read
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-unread ms-2">
                                                                <i class="fas fa-exclamation-circle me-1"></i>Unread
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <strong>Subject:</strong><br>
                                                    <h5><?php echo htmlspecialchars($msg['subject']); ?></h5>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <strong>Message Type:</strong><br>
                                                    <span class="type-badge <?php echo $type_class[$msg_type] ?? 'badge-inquiry'; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $msg_type)); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-4">
                                                <h6><i class="fas fa-file-alt me-2"></i>Message:</h6>
                                                <div class="border rounded p-3 bg-light">
                                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <?php if ($msg['is_read'] == 0): ?>
                                                <a href="?action=mark_read&id=<?php echo $msg['message_id']; ?>" class="btn btn-success">
                                                    <i class="fas fa-check me-1"></i>Mark as Read
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=mark_unread&id=<?php echo $msg['message_id']; ?>" class="btn btn-warning">
                                                    <i class="fas fa-eye-slash me-1"></i>Mark as Unread
                                                </a>
                                            <?php endif; ?>
                                            <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>?subject=RE: <?php echo urlencode($msg['subject']); ?>" class="btn btn-info">
                                                <i class="fas fa-reply me-1"></i>Reply
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-messages">
                <i class="fas fa-inbox"></i>
                <h4>No messages found</h4>
                <p>There are no contact messages matching your criteria.</p>
                <a href="admin_contact.php" class="btn btn-danger">
                    <i class="fas fa-sync me-2"></i>Reset Filters
                </a>
            </div>
        <?php endif; ?>
        </form>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/admin_contact.js"></script>
</body>
</html>

<?php
$conn->close();
?>