<?php
// recipe_collection.php - Recipe Collection Page
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

// Get filter parameters
$cuisine_filter = isset($_GET['cuisine']) ? $_GET['cuisine'] : '';
$diet_filter = isset($_GET['diet']) ? $_GET['diet'] : '';
$difficulty_filter = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Build SQL query with filters
$sql = "SELECT r.*, u.first_name, u.last_name 
        FROM recipes r 
        LEFT JOIN users u ON r.user_id = u.user_id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($cuisine_filter)) {
    $sql .= " AND r.cuisine_type = ?";
    $params[] = $cuisine_filter;
    $types .= "s";
}

if (!empty($diet_filter)) {
    $sql .= " AND r.dietary_preference = ?";
    $params[] = $diet_filter;
    $types .= "s";
}

if (!empty($difficulty_filter)) {
    $sql .= " AND r.difficulty_level = ?";
    $params[] = $difficulty_filter;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.ingredients LIKE ?)";
    $search_term = "%" . $search_query . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql .= " ORDER BY r.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$recipes = $result->fetch_all(MYSQLI_ASSOC);

// Get unique values for filter dropdowns
$cuisines_result = $conn->query("SELECT DISTINCT cuisine_type FROM recipes WHERE cuisine_type IS NOT NULL ORDER BY cuisine_type");
$cuisines = $cuisines_result->fetch_all(MYSQLI_ASSOC);

$diets_result = $conn->query("SELECT DISTINCT dietary_preference FROM recipes WHERE dietary_preference IS NOT NULL ORDER BY dietary_preference");
$diets = $diets_result->fetch_all(MYSQLI_ASSOC);

$difficulties_result = $conn->query("SELECT DISTINCT difficulty_level FROM recipes ORDER BY FIELD(difficulty_level, 'Easy', 'Medium', 'Hard')");
$difficulties = $difficulties_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe Collection - FoodFusion</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/recipe_collection.css">
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
    <section class="hero-recipes text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Recipe Collection</h1>
            <p class="lead mb-4">Discover delicious recipes from around the world. Filter by cuisine, diet, or difficulty.</p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form method="GET" action="">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control form-control-lg" 
                                   name="search" 
                                   placeholder="Search recipes by name, ingredient, or description..."
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                            <button class="btn btn-danger btn-lg" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Filters Section -->
    <section class="py-4 bg-light">
        <div class="container">
            <div class="filter-card">
                <h4 class="mb-4"><i class="fas fa-filter me-2"></i>Filter Recipes</h4>
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Cuisine Type</label>
                        <select class="form-select" name="cuisine">
                            <option value="">All Cuisines</option>
                            <?php foreach ($cuisines as $cuisine): ?>
                                <option value="<?php echo $cuisine['cuisine_type']; ?>"
                                    <?php echo ($cuisine_filter == $cuisine['cuisine_type']) ? 'selected' : ''; ?>>
                                    <?php echo $cuisine['cuisine_type']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Dietary Preference</label>
                        <select class="form-select" name="diet">
                            <option value="">All Diets</option>
                            <?php foreach ($diets as $diet): ?>
                                <option value="<?php echo $diet['dietary_preference']; ?>"
                                    <?php echo ($diet_filter == $diet['dietary_preference']) ? 'selected' : ''; ?>>
                                    <?php echo $diet['dietary_preference']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Difficulty Level</label>
                        <select class="form-select" name="difficulty">
                            <option value="">All Levels</option>
                            <?php foreach ($difficulties as $difficulty): ?>
                                <option value="<?php echo $difficulty['difficulty_level']; ?>"
                                    <?php echo ($difficulty_filter == $difficulty['difficulty_level']) ? 'selected' : ''; ?>>
                                    <?php echo $difficulty['difficulty_level']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-1 d-flex align-items-end">
                        <div class="d-grid w-100">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php if ($cuisine_filter || $diet_filter || $difficulty_filter): ?>
                <div class="mt-4">
                    <strong>Active Filters:</strong>
                    <?php if ($cuisine_filter): ?>
                        <span class="badge bg-danger me-2">Cuisine: <?php echo $cuisine_filter; ?></span>
                    <?php endif; ?>
                    <?php if ($diet_filter): ?>
                        <span class="badge bg-success me-2">Diet: <?php echo $diet_filter; ?></span>
                    <?php endif; ?>
                    <?php if ($difficulty_filter): ?>
                        <span class="badge bg-warning me-2">Difficulty: <?php echo $difficulty_filter; ?></span>
                    <?php endif; ?>
                    <a href="recipe_collection.php" class="btn btn-sm btn-outline-secondary ms-2">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Recipes Grid -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h3>Our Recipe Collection</h3>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        <?php echo count($recipes); ?> recipe<?php echo count($recipes) != 1 ? 's' : ''; ?> found
                    </span>
                </div>
            </div>
            
            <?php if (count($recipes) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($recipes as $recipe): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card recipe-card h-100">
                                <img src="<?php echo !empty($recipe['image_url']) ? $recipe['image_url'] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'; ?>" 
                                     class="card-img-top recipe-img" alt="<?php echo $recipe['title']; ?>">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo htmlspecialchars($recipe['title']); ?></h5>
                                    <p class="card-text flex-grow-1"><?php echo substr($recipe['description'], 0, 100) . '...'; ?></p>
                                    
                                    <div class="mb-3">
                                        <?php if ($recipe['cuisine_type']): ?>
                                            <span class="badge badge-cuisine me-1"><?php echo $recipe['cuisine_type']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($recipe['dietary_preference']): ?>
                                            <span class="badge badge-diet me-1"><?php echo $recipe['dietary_preference']; ?></span>
                                        <?php endif; ?>
                                        <?php if ($recipe['difficulty_level']): ?>
                                            <span class="badge badge-difficulty"><?php echo $recipe['difficulty_level']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="recipe-meta mb-3">
                                        <i class="fas fa-user me-1"></i> By <?php echo $recipe['first_name'] . ' ' . $recipe['last_name']; ?>
                                        <br>
                                        <i class="fas fa-clock me-1"></i> <?php echo $recipe['prep_time'] + $recipe['cook_time']; ?> mins
                                        <i class="fas fa-utensils ms-3 me-1"></i> <?php echo $recipe['servings']; ?> servings
                                    </div>
                                    
                                    <div class="mt-auto">
                                        <a href="#" class="btn btn-outline-danger w-100" data-bs-toggle="modal" 
                                           data-bs-target="#recipeModal<?php echo $recipe['recipe_id']; ?>">
                                            <i class="fas fa-book-open me-2"></i>View Recipe
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recipe Modal -->
                        <div class="modal fade" id="recipeModal<?php echo $recipe['recipe_id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title"><?php echo htmlspecialchars($recipe['title']); ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <img src="<?php echo !empty($recipe['image_url']) ? $recipe['image_url'] : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'; ?>" 
                                                     class="img-fluid rounded" alt="<?php echo $recipe['title']; ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Recipe Details</h6>
                                                <p><strong>Cuisine:</strong> <?php echo $recipe['cuisine_type']; ?></p>
                                                <p><strong>Diet:</strong> <?php echo $recipe['dietary_preference']; ?></p>
                                                <p><strong>Difficulty:</strong> <?php echo $recipe['difficulty_level']; ?></p>
                                                <p><strong>Prep Time:</strong> <?php echo $recipe['prep_time']; ?> mins</p>
                                                <p><strong>Cook Time:</strong> <?php echo $recipe['cook_time']; ?> mins</p>
                                                <p><strong>Servings:</strong> <?php echo $recipe['servings']; ?></p>
                                                <p><strong>By:</strong> <?php echo $recipe['first_name'] . ' ' . $recipe['last_name']; ?></p>
                                            </div>
                                        </div>
                                        
                                        <h6>Description</h6>
                                        <p><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Ingredients</h6>
                                                <div class="card">
                                                    <div class="card-body">
                                                        <?php echo nl2br(htmlspecialchars($recipe['ingredients'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Instructions</h6>
                                                <div class="card">
                                                    <div class="card-body">
                                                        <?php echo nl2br(htmlspecialchars($recipe['instructions'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <button class="btn btn-danger">
                                                <i class="fas fa-heart me-2"></i>Save Recipe
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <h4>No recipes found</h4>
                    <p class="mb-4">Try adjusting your filters or search term</p>
                    <a href="recipe_collection.php" class="btn btn-danger">
                        <i class="fas fa-redo me-2"></i>Reset Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Browse by Category</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-globe-asia fa-3x text-danger mb-3"></i>
                            <h4>Cuisine Types</h4>
                            <div class="mt-3">
                                <?php foreach (array_slice($cuisines, 0, 5) as $cuisine): ?>
                                    <a href="recipe_collection.php?cuisine=<?php echo urlencode($cuisine['cuisine_type']); ?>" 
                                       class="btn btn-outline-danger btn-sm m-1">
                                        <?php echo $cuisine['cuisine_type']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-leaf fa-3x text-success mb-3"></i>
                            <h4>Dietary Preferences</h4>
                            <div class="mt-3">
                                <?php foreach (array_slice($diets, 0, 5) as $diet): ?>
                                    <a href="recipe_collection.php?diet=<?php echo urlencode($diet['dietary_preference']); ?>" 
                                       class="btn btn-outline-success btn-sm m-1">
                                        <?php echo $diet['dietary_preference']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-tachometer-alt fa-3x text-warning mb-3"></i>
                            <h4>Difficulty Levels</h4>
                            <div class="mt-3">
                                <?php foreach ($difficulties as $difficulty): ?>
                                    <a href="recipe_collection.php?difficulty=<?php echo urlencode($difficulty['difficulty_level']); ?>" 
                                       class="btn btn-outline-warning btn-sm m-1">
                                        <?php echo $difficulty['difficulty_level']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-danger text-white text-center">
        <div class="container">
            <h2 class="mb-4">Want to Share Your Own Recipe?</h2>
            <p class="lead mb-4">Join our community and share your culinary creations with food lovers worldwide.</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="#" class="btn btn-light btn-lg">
                    <i class="fas fa-plus-circle me-2"></i>Add New Recipe
                </a>
            <?php else: ?>
                <a href="index.php" class="btn btn-light btn-lg me-3">
                    <i class="fas fa-user-plus me-2"></i>Sign Up to Contribute
                </a>
                <a href="index.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
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
                        <li><a href="recipe_collection.php" class="text-white-50 text-decoration-none">Recipe Collection</a></li>
                        <li><a href="about.php" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li><a href="contact.php" class="text-white-50 text-decoration-none">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Popular Cuisines</h5>
                    <div class="d-flex flex-wrap">
                        <?php foreach (array_slice($cuisines, 0, 6) as $cuisine): ?>
                            <a href="recipe_collection.php?cuisine=<?php echo urlencode($cuisine['cuisine_type']); ?>" 
                               class="text-white-50 text-decoration-none me-3 mb-1">
                                <?php echo $cuisine['cuisine_type']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
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
                    <h5>Recipe Collection Privacy</h5>
                    <p>Recipes shared on our platform are visible to all users. User information is only displayed with permission.</p>
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
                    <h5>Recipe Filters Cookies</h5>
                    <p>We use cookies to remember your recipe filters and preferences for a better browsing experience.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/recipe_collection.js"></script>
</body>
</html>

<?php
$conn->close();
?>