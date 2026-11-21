<?php
// about.php - About Us Page
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - FoodFusion</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="assets/css/style.css">
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
    <section class="hero-about text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">About FoodFusion</h1>
            <p class="lead mb-4">Where Passion Meets Flavor - Building a Global Culinary Community</p>
        </div>
    </section>

    <!-- Our Story -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-4">Our Story</h2>
                    <p class="lead">FoodFusion began as a simple idea in a small kitchen in 2018. Our founder, Sarah Chen, noticed that while there were countless recipe websites, none truly captured the spirit of community and cultural exchange that makes cooking so special.</p>
                    <p>What started as a personal blog sharing family recipes has grown into a global platform with over 500,000 members from 120 countries. We believe that food is more than just sustenance - it's a language that transcends borders, a way to preserve traditions, and a means to create new connections.</p>
                    <p>Today, FoodFusion stands as a testament to what happens when passion meets technology. We've created a space where grandmothers can share century-old family secrets alongside professional chefs experimenting with molecular gastronomy.</p>
                </div>
                <div class="col-md-6">
                    <img src="https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                         class="img-fluid rounded shadow" alt="Our Kitchen">
                </div>
            </div>
        </div>
    </section>

    <!-- Culinary Philosophy -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Our Culinary Philosophy</h2>
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="philosophy-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h4>Cooking with Love</h4>
                    <p>Every dish should be prepared with care and attention. We believe that the energy you put into cooking directly affects the outcome.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="philosophy-icon">
                        <i class="fas fa-globe-americas"></i>
                    </div>
                    <h4>Global Inspiration</h4>
                    <p>Food has no borders. We celebrate culinary traditions from around the world while encouraging creative fusion and innovation.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="philosophy-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h4>Sustainability First</h4>
                    <p>We promote responsible cooking - using seasonal ingredients, reducing food waste, and supporting local producers.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Values -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Our Core Values</h2>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="value-card">
                        <h4><i class="fas fa-hands-helping me-2 text-danger"></i>Community First</h4>
                        <p>We believe in the power of shared knowledge. Every member has something valuable to contribute, whether they're a Michelin-star chef or cooking their first meal.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="value-card">
                        <h4><i class="fas fa-graduation-cap me-2 text-danger"></i>Continuous Learning</h4>
                        <p>Cooking is a journey, not a destination. We provide resources for cooks at every level to improve their skills and expand their culinary horizons.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="value-card">
                        <h4><i class="fas fa-star me-2 text-danger"></i>Quality Over Quantity</h4>
                        <p>We carefully curate our recipes and resources to ensure they meet high standards of clarity, accuracy, and deliciousness.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="value-card">
                        <h4><i class="fas fa-users me-2 text-danger"></i>Inclusivity</h4>
                        <p>Our platform welcomes everyone regardless of dietary restrictions, skill level, or cultural background. Everyone has a seat at our table.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Team -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Meet Our Team</h2>
            <div class="row justify-content-center">
                <div class="col-md-3 mb-4">
                    <div class="team-member text-center">
                        <img src="https://images.unsplash.com/photo-1494790108755-2616b786d4d7?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" 
                             class="team-img rounded-circle mb-3" alt="Sarah Chen">
                        <h5>Sarah Chen</h5>
                        <p class="text-danger fw-bold">Founder & CEO</p>
                        <p>Former chef at Le Cordon Bleu with 15 years of culinary experience. Passionate about making cooking accessible to everyone.</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="team-member text-center">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" 
                             class="team-img rounded-circle mb-3" alt="Marcus Johnson">
                        <h5>Marcus Johnson</h5>
                        <p class="text-danger fw-bold">Head of Culinary Development</p>
                        <p>Food scientist and nutritionist specializing in creating healthy, flavorful recipes that anyone can make at home.</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="team-member text-center">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" 
                             class="team-img rounded-circle mb-3" alt="Elena Rodriguez">
                        <h5>Elena Rodriguez</h5>
                        <p class="text-danger fw-bold">Community Manager</p>
                        <p>Manages our vibrant community, organizes cooking challenges, and ensures every member feels heard and valued.</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="team-member text-center">
                        <img src="https://images.unsplash.com/photo-1567532939604-b6b5b0db2604?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" 
                             class="team-img rounded-circle mb-3" alt="David Kim">
                        <h5>David Kim</h5>
                        <p class="text-danger fw-bold">Tech Director</p>
                        <p>Ensures our platform runs smoothly and develops new features to enhance the user experience.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Statement -->
    <section class="py-5 bg-danger text-white">
        <div class="container text-center">
            <h2 class="mb-4">Our Mission</h2>
            <p class="lead mb-4">"To democratize culinary knowledge and create a global community where food lovers of all levels can learn, share, and grow together. We believe that everyone deserves access to delicious, nutritious food and the skills to prepare it."</p>
            <div class="row mt-5">
                <div class="col-md-4">
                    <h3>500K+</h3>
                    <p>Community Members</p>
                </div>
                <div class="col-md-4">
                    <h3>50K+</h3>
                    <p>Recipes Shared</p>
                </div>
                <div class="col-md-4">
                    <h3>120</h3>
                    <p>Countries Represented</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Join Us CTA -->
    <section class="py-5">
        <div class="container text-center">
            <h2 class="mb-4">Join Our Culinary Journey</h2>
            <p class="lead mb-4">Whether you're here to learn, share, or simply enjoy great food - you're welcome at FoodFusion.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="index.php" class="btn btn-danger btn-lg">
                    <i class="fas fa-user-plus me-2"></i>Join Our Community
                </a>
            <?php else: ?>
                <a href="#" class="btn btn-danger btn-lg">
                    <i class="fas fa-share-alt me-2"></i>Share Your Recipe
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><i class="fas fa-utensils me-2"></i>FoodFusion</h5>
                    <p>Your culinary community for sharing recipes and cooking tips.</p>
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
                        <li><a href="#" class="text-white-50 text-decoration-none">Recipe Collection</a></li>
                        <li><a href="about.php" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li><a href="contact.php" class="text-white-50 text-decoration-none">Contact Us</a></li>
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
                <p class="mb-0">
                    <a href="#" class="text-white-50 text-decoration-none me-3" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                    <a href="#" class="text-white-50 text-decoration-none" data-bs-toggle="modal" data-bs-target="#cookieModal">Cookie Policy</a>
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
                    <h5>Your Privacy Matters</h5>
                    <p>At FoodFusion, we are committed to protecting your privacy and personal information.</p>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>