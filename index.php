<?php

session_start();


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "foodfusion_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $message = "All fields are required!";
        $message_type = "danger";
    } else {
        $check_sql = "SELECT email FROM users WHERE email = '$email'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $message = "Email already registered!";
            $message_type = "danger";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (first_name, last_name, email, password_hash) 
                    VALUES ('$first_name', '$last_name', '$email', '$hashed_password')";
            
            if ($conn->query($sql) === TRUE) {
                $message = "Registration successful! You can now login.";
                $message_type = "success";
            } else {
                $message = "Error: " . $conn->error;
                $message_type = "danger";
            }
        }
    }
}

// Handle Login Form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = $_POST['login_email'];
    $password = $_POST['login_password'];
    
    $sql = "SELECT user_id, first_name, last_name, email, password_hash FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['first_name'] . " " . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            
          
            $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = " . $user['user_id'];
            $conn->query($update_sql);
            
         
        } else {
            $message = "Invalid password!";
            $message_type = "danger";
        }
    } else {
        $message = "User not found!";
        $message_type = "danger";
    }
}


if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Cookie Consent
if (!isset($_COOKIE['cookie_consent'])) {
    if (isset($_POST['accept_cookies'])) {
        setcookie('cookie_consent', 'accepted', time() + (365 * 24 * 60 * 60), "/");
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodFusion - Home</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body data-modal-action="<?php echo (isset($_POST['register']) && $message && $message_type == 'danger') ? 'register' : ((isset($_POST['login']) && $message && $message_type == 'danger') ? 'login' : ''); ?>" data-success-message="<?php echo htmlspecialchars(($message && $message_type == 'success') ? $message : '', ENT_QUOTES, 'UTF-8'); ?>">
    <!-- Cookie Consent Banner -->
    <?php if (!isset($_COOKIE['cookie_consent'])): ?>
    <div class="cookie-consent">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-10">
                    <p class="mb-0">This website uses cookies to ensure you get the best experience. 
                    <a href="#" data-bs-toggle="modal" data-bs-target="#cookieModal" class="text-warning">Learn more</a></p>
                </div>
                <div class="col-md-2 text-end">
                    <form method="POST" class="d-inline">
                        <button type="submit" name="accept_cookies" class="btn btn-success btn-sm">Accept</button>
                    </form>
                    <button onclick="hideCookieBanner()" class="btn btn-secondary btn-sm">Decline</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation with dropdown menus -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-utensils me-2"></i>FoodFusion
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><i class="fas fa-home me-1"></i>Home</a>
                    </li>
                    
                    <!-- Menu Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="menuDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="menuDropdown">
                            <li><a class="dropdown-item" href="recipe_collection.php"><i class="fas fa-book me-2"></i>Recipe Collection</a></li>
                            <li><a class="dropdown-item" href="cookbook.php"><i class="fas fa-users me-2"></i>Community Cookbook</a></li>
                            <li><a class="dropdown-item" href="culinary_resources.php"><i class="fas fa-utensil-spoon me-2"></i>Culinary Resources</a></li>
                            <li><a class="dropdown-item" href="educational_resources.php"><i class="fas fa-graduation-cap me-2"></i>Educational Resources</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="about_us.php"><i class="fas fa-address-card me-1"></i>About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact_us.php"><i class="fas fa-phone me-1"></i>Contact Us</a>
                    </li>
                </ul>
                
                <!-- User or Login/Sign Up Buttons -->
                <div class="d-flex">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User is logged in - show user dropdown -->
                        <div class="dropdown user-dropdown">
                            <a class="btn btn-light dropdown-toggle" href="#" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-2"></i><?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-heart me-2"></i>My Recipes</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- User is not logged in - show login/signup buttons -->
                        <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="fas fa-user-plus me-1"></i>Sign Up
                        </button>
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Welcome to FoodFusion</h1>
            <p class="lead mb-4">Discover, Cook, Share. Join our community of food lovers!</p>
            <div class="mt-5">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <button class="btn btn-danger btn-lg me-3" data-bs-toggle="modal" data-bs-target="#registerModal">
                        <i class="fas fa-user-plus me-2"></i>Join Now
                    </button>
                <?php endif; ?>
                <a href="#" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-book me-2"></i>Explore Recipes
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose FoodFusion?</h2>
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h4>Recipe Collection</h4>
                    <p>Access hundreds of recipes from around the world</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4>Community</h4>
                    <p>Share and learn from fellow food enthusiasts</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h4>Learn</h4>
                    <p>Cooking tutorials and educational resources</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Recipes -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Featured Recipes</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card recipe-card">
                        <img src="https://images.unsplash.com/photo-1565958011703-44f9829ba187?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" 
                             class="card-img-top" alt="Salad">
                        <div class="card-body">
                            <h5 class="card-title">Mediterranean Salad</h5>
                            <p class="card-text">Fresh and healthy Mediterranean inspired salad.</p>
                            <span class="badge bg-success">Vegetarian</span>
                            <span class="badge bg-warning">Easy</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card recipe-card">
                        <img src="https://images.unsplash.com/photo-1563379926898-05f4575a45d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" 
                             class="card-img-top" alt="Pasta">
                        <div class="card-body">
                            <h5 class="card-title">Creamy Pasta</h5>
                            <p class="card-text">Delicious creamy pasta with fresh herbs.</p>
                            <span class="badge bg-primary">Italian</span>
                            <span class="badge bg-warning">Easy</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card recipe-card">
                        <img src="https://images.unsplash.com/photo-1529042410759-befb1204b468?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80" 
                             class="card-img-top" alt="Curry">
                        <div class="card-body">
                            <h5 class="card-title">Spicy Curry</h5>
                            <p class="card-text">Authentic Indian curry with rich spices.</p>
                            <span class="badge bg-danger">Spicy</span>
                            <span class="badge bg-secondary">Medium</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Media & Links Section -->
    <section class="py-4 bg-dark text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5>Follow Us</h5>
                    <div class="social-icons mt-3">
                        <a href="https://www.facebook.com" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.twitter.com" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.instargram.com" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.youtube.com" title="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="https://www.pinterest.com" title="Pinterest"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <a href="#" class="text-white-50 me-3" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                    <a href="#" class="text-white-50" data-bs-toggle="modal" data-bs-target="#cookieModal">Cookie Policy</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><i class="fas fa-utensils me-2"></i>FoodFusion</h5>
                    <p>Your culinary community for sharing recipes and cooking tips.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50 text-decoration-none">Home</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Recipe Collection</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Community Cookbook</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">About Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Contact</h5>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i>info@foodfusion.com</p>
                    <p class="mb-1"><i class="fas fa-phone me-2"></i>+1 234 567 8900</p>
                    <p><i class="fas fa-map-marker-alt me-2"></i>123 Food Street, London</p>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 FoodFusion. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Registration Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Join FoodFusion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php if ($message && isset($_POST['register'])): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" 
                                   value="<?php echo isset($_POST['first_name']) ? $_POST['first_name'] : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" 
                                   value="<?php echo isset($_POST['last_name']) ? $_POST['last_name'] : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" required>
                            <label class="form-check-label">
                                I agree to the <a href="#" class="text-danger" data-bs-toggle="modal" data-bs-target="#privacyModal">Terms & Conditions</a>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="register" class="btn btn-danger">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-sign-in-alt me-2"></i>Login</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php if ($message && isset($_POST['login'])): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="login_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="login_password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="remember">
                            <label class="form-check-label">Remember me</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="login" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                        <div class="text-center mt-3">
                            <a href="#" class="text-decoration-none">Forgot Password?</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-shield-alt me-2"></i>Privacy Policy</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h5>Your Privacy Matters</h5>
                    <p>At FoodFusion, we are committed to protecting your privacy and personal information.</p>
                    
                    <h6>Information We Collect</h6>
                    <ul>
                        <li>Name and contact information</li>
                        <li>Email address for account creation</li>
                        <li>Recipe submissions and preferences</li>
                        <li>Cookies for website functionality</li>
                    </ul>
                    
                    <h6>How We Use Your Information</h6>
                    <ul>
                        <li>To provide personalized cooking recommendations</li>
                        <li>To improve our services</li>
                        <li>To communicate with you about updates</li>
                        <li>To ensure website security</li>
                    </ul>
                    
                    <h6>Data Protection</h6>
                    <p>We implement security measures to protect your personal information from unauthorized access.</p>
                    
                    <p class="text-muted">Last updated: December 2024</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cookie Policy Modal -->
    <div class="modal fade" id="cookieModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-cookie me-2"></i>Cookie Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h5>About Cookies</h5>
                    <p>We use cookies to enhance your browsing experience on FoodFusion.</p>
                    
                    <h6>Types of Cookies We Use</h6>
                    <ul>
                        <li><strong>Essential Cookies:</strong> Required for website functionality</li>
                        <li><strong>Preference Cookies:</strong> Remember your settings</li>
                        <li><strong>Analytics Cookies:</strong> Help us improve our website</li>
                        <li><strong>Marketing Cookies:</strong> Provide relevant content</li>
                    </ul>
                    
                    <h6>Managing Cookies</h6>
                    <p>You can control cookie settings through your browser. However, disabling essential cookies may affect website functionality.</p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        By continuing to use our site, you consent to our use of cookies.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <?php if (!isset($_COOKIE['cookie_consent'])): ?>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="accept_cookies" class="btn btn-success">Accept All Cookies</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/index.js"></script>
</body>
</html>

<?php
$conn->close();
?>