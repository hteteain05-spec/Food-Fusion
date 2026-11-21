<?php
// contact.php - Contact Us Page
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

// Handle Contact Form Submission
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['contact_submit'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message_text = $_POST['message'];
    $message_type_select = $_POST['message_type'];
    
    // Validation
    if (empty($full_name) || empty($email) || empty($subject) || empty($message_text)) {
        $message = "All fields are required!";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address!";
        $message_type = "danger";
    } else {
        // Insert into database
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
        
        $sql = "INSERT INTO contact_messages (user_id, full_name, email, subject, message, message_type) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssss", $user_id, $full_name, $email, $subject, $message_text, $message_type_select);
        
        if ($stmt->execute()) {
            $message = "Thank you for contacting us! We'll get back to you within 24 hours.";
            $message_type = "success";
            
            // Clear form
            $_POST = array();
        } else {
            $message = "Sorry, there was an error sending your message. Please try again.";
            $message_type = "danger";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - FoodFusion</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/contact_us.css">
</head>
<body>
 <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-utensils me-2"></i>FoodFusion
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Home</a>
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
                                <li><a class="dropdown-item" href="index.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- User is not logged in - show login/signup buttons -->
                        <a href="index.php" class="btn btn-outline-light me-2">
                            <i class="fas fa-user-plus me-1"></i>Sign Up
                        </a>
                        <a href="index.php" class="btn btn-light">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <!-- Hero Section -->
    <section class="hero-contact text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Contact FoodFusion</h1>
            <p class="lead mb-4">We'd love to hear from you! Get in touch with our team based in Myanmar.</p>
            <p><i class="fas fa-flag myanmar-flag"></i> Proudly serving from Yangon, Myanmar</p>
        </div>
    </section>

    <!-- Contact Form & Info -->
    <section class="py-5">
        <div class="container">
            <!-- Success/Error Message -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Contact Form -->
                <div class="col-lg-8 mb-4">
                    <div class="card contact-card">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Send Us a Message</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" name="full_name" 
                                               value="<?php echo isset($_POST['full_name']) ? $_POST['full_name'] : ''; ?>" 
                                               required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Subject *</label>
                                    <input type="text" class="form-control" name="subject" 
                                           value="<?php echo isset($_POST['subject']) ? $_POST['subject'] : ''; ?>" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Message Type</label>
                                    <select class="form-select" name="message_type">
                                        <option value="inquiry" <?php echo (isset($_POST['message_type']) && $_POST['message_type'] == 'inquiry') ? 'selected' : ''; ?>>General Inquiry</option>
                                        <option value="recipe_request" <?php echo (isset($_POST['message_type']) && $_POST['message_type'] == 'recipe_request') ? 'selected' : ''; ?>>Recipe Request</option>
                                        <option value="feedback" <?php echo (isset($_POST['message_type']) && $_POST['message_type'] == 'feedback') ? 'selected' : ''; ?>>Feedback</option>
                                        <option value="other" <?php echo (isset($_POST['message_type']) && $_POST['message_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Your Message *</label>
                                    <textarea class="form-control" name="message" rows="6" 
                                              required><?php echo isset($_POST['message']) ? $_POST['message'] : ''; ?></textarea>
                                </div>
                                
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="privacyCheck" required>
                                    <label class="form-check-label" for="privacyCheck">
                                        I agree to the <a href="#" class="text-danger" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                                    </label>
                                </div>
                                
                                <button type="submit" name="contact_submit" class="btn btn-danger btn-lg w-100">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information - Myanmar -->
                <div class="col-lg-4">
                    <div class="card contact-card mb-4">
                        <div class="card-body text-center">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h4>Visit Our Office</h4>
                            <p class="mb-0">No. 123, Bogyoke Road</p>
                            <p class="mb-0">Kyauktada Township</p>
                            <p class="mb-0">Yangon 11182</p>
                            <p>Myanmar (Burma)</p>
                            <a href="https://maps.google.com" target="_blank" class="btn btn-outline-danger">
                                <i class="fas fa-directions me-2"></i>Get Directions
                            </a>
                        </div>
                    </div>
                    
                    <div class="card contact-card mb-4">
                        <div class="card-body text-center">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h4>Call Us</h4>
                            <p class="mb-2">+95 1 234 5678</p>
                            <p class="mb-2">+95 9 123 456789</p>
                            <p class="mb-2">+95 9 876 543210</p>
                            <p class="text-muted small">Mon-Fri: 9am-5pm MMT</p>
                            <p class="text-muted small">Sat: 9am-1pm MMT</p>
                        </div>
                    </div>
                    
                    <div class="card contact-card mb-4">
                        <div class="card-body text-center">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h4>Email Us</h4>
                            <p class="mb-2">
                                <a href="mailto:info@foodfusion.com.mm" class="text-decoration-none">
                                    info@foodfusion.com.mm
                                </a>
                            </p>
                            <p class="mb-2">
                                <a href="mailto:support@foodfusion.com.mm" class="text-decoration-none">
                                    support@foodfusion.com.mm
                                </a>
                            </p>
                            <p class="mb-2">
                                <a href="mailto:recipes@foodfusion.com.mm" class="text-decoration-none">
                                    recipes@foodfusion.com.mm
                                </a>
                            </p>
                            <p class="text-muted small">Response within 24 hours</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Frequently Asked Questions</h2>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle me-2 text-danger"></i>How can I submit my own recipe?</h5>
                        <p>After logging in, go to "Community Cookbook" and click "Submit Recipe". Fill in the details and our team will review it within 48 hours.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle me-2 text-danger"></i>Do you have Myanmar traditional recipes?</h5>
                        <p>Yes! We have a special collection of authentic Myanmar recipes including Mohinga, Lahpet Thoke, and Shan Noodles. Check our "Recipe Collection" section.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle me-2 text-danger"></i>Can I request a specific Myanmar recipe?</h5>
                        <p>Absolutely! Use the contact form and select "Recipe Request" as the message type. Our Myanmar culinary experts will help you.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle me-2 text-danger"></i>Are cooking classes available in Myanmar?</h5>
                        <p>Yes! We offer both in-person classes in Yangon and online classes. Check our "Culinary Resources" section for upcoming workshops.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section - Yangon, Myanmar -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">Find Us in Yangon</h2>
            <div class="map-container">
                <!-- Google Maps Embed for Yangon, Myanmar -->
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15272.858935471853!2d96.13476067022461!3d16.828024199999995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30c194c1cae8b8bd%3A0x1ffb5aa3e0ff310e!2sDowntown%20Yangon%2C%20Yangon%2C%20Myanmar%20(Burma)!5e0!3m2!1sen!2s!4v1698765432105!5m2!1sen!2s" 
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>

    <!-- Response Time Info -->
    <section class="py-5 bg-danger text-white">
        <div class="container text-center">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <i class="fas fa-clock fa-3x mb-3"></i>
                    <h4>24-Hour Response</h4>
                    <p>We aim to respond to all inquiries within 24 hours on business days (Myanmar Time).</p>
                </div>
                <div class="col-md-4 mb-4">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h4>Local Experts</h4>
                    <p>Our team includes Myanmar chefs, nutritionists, and food culture experts.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <i class="fas fa-language fa-3x mb-3"></i>
                    <h4>မြန်မာစာ Support</h4>
                    <p>We provide support in both English and Myanmar language (Burmese).</p>
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
                    <p>Your culinary community for sharing recipes and cooking tips from Myanmar and beyond.</p>
                    <div class="social-icons mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                        <li><a href="about.php" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li><a href="contact.php" class="text-white-50 text-decoration-none">Contact Us</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Myanmar Recipes</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Business Hours (Myanmar Time)</h5>
                    <p class="mb-1">Monday - Friday: 9:00 AM - 5:00 PM</p>
                    <p class="mb-1">Saturday: 9:00 AM - 1:00 PM</p>
                    <p class="mb-1">Sunday: Closed</p>
                    <p>Public Holidays: Closed</p>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 FoodFusion Myanmar. All rights reserved.</p>
                <p class="mb-0">
                    <a href="#" class="text-white-50 text-decoration-none me-3" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                    <a href="#" class="text-white-50 text-decoration-none" data-bs-toggle="modal" data-bs-target="#cookieModal">Cookie Policy</a>
                </p>
                <p class="mt-2">
                    <i class="fas fa-flag myanmar-flag me-2"></i>
                    <span class="myanmar-text">ကျေးဇူးပြု၍ ဆက်သွယ်ပါ။</span>
                </p>
            </div>
        </div>
    </footer>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-shield-alt me-2"></i>Privacy Policy</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h5>Contact Information Privacy</h5>
                    <p>When you contact us through this form, we collect your name, email, and message to respond to your inquiry. We never share this information with third parties.</p>
                    <p class="mt-3"><strong>Myanmar Data Protection:</strong> We comply with Myanmar's data protection regulations and ensure your information is securely stored on our servers in Yangon.</p>
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
                    <h5>Contact Form Cookies</h5>
                    <p>We use essential cookies to ensure our contact form functions properly and remembers your form entries if there's an error.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/contact_us.js"></script>
</body>
</html>

<?php
$conn->close();
?>