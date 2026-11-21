<?php
// admin_login.php - Admin Login Page
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "foodfusion_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Admin Login Form
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admin_login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $message = "All fields are required!";
        $message_type = "danger";
    } else {
        // Use prepared statement to prevent SQL injection
        $sql = "SELECT user_id, first_name, last_name, email, password_hash, user_role, is_active FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if user is admin
            if ($user['user_role'] !== 'admin') {
                $message = "Access denied. Admin privileges required.";
                $message_type = "danger";
            }
            // Check if account is active
            elseif (!$user['is_active']) {
                $message = "Your account has been deactivated. Please contact support.";
                $message_type = "danger";
            }
            // Verify password
            elseif (password_verify($password, $user['password_hash'])) {
                // Set session variables for admin
                $_SESSION['admin_id'] = $user['user_id'];
                $_SESSION['admin_name'] = $user['first_name'] . " " . $user['last_name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_role'] = $user['user_role'];
                $_SESSION['admin_logged_in'] = true;
                
                // Update last login
                $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = " . $user['user_id'];
                $conn->query($update_sql);
                
                // Redirect to admin dashboard
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $message = "Invalid password!";
                $message_type = "danger";
            }
        } else {
            $message = "Admin user not found!";
            $message_type = "danger";
        }
        $stmt->close();
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodFusion - Admin Login</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin_login.css">
</head>
<body>
    <div class="admin-login-card">
        <!-- Admin Login Header -->
        <div class="admin-header">
            <div class="admin-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h3>FoodFusion Admin</h3>
            <p class="mb-0">Restricted Access Area</p>
        </div>
        
        <!-- Login Form -->
        <div class="login-form">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="email" class="form-label">Admin Email</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" 
                               class="form-control with-icon" 
                               id="email" 
                               name="email" 
                               required 
                               placeholder="admin@foodfusion.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control with-icon" 
                               id="password" 
                               name="password" 
                               required 
                               placeholder="••••••••">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="security-notice">
                        <i class="fas fa-shield-alt me-1"></i>
                        Secure admin portal - All activities are logged
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" name="admin_login" class="btn btn-admin">
                        <i class="fas fa-sign-in-alt me-2"></i>Login as Admin
                    </button>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="back-to-site">
                        <i class="fas fa-arrow-left me-2"></i>Back to Main Site
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="admin-footer">
            <div class="mb-2">
                <i class="fas fa-lock me-1"></i>
                <strong>Secure Access Only</strong>
            </div>
            <small>
                <i class="far fa-copyright me-1"></i>
                FoodFusion Admin Portal • <?php echo date('Y'); ?>
            </small>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin_login.js"></script>
</body>
</html>

<?php
$conn->close();
?>