<?php
// cookbook.php - Community Cookbook Page (Fixed for filename cookbook.php)
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

$check_table_sql = "SHOW TABLES LIKE 'recipe_likes'";
$table_result = $conn->query($check_table_sql);
if ($table_result->num_rows == 0) {

    $create_table_sql = "CREATE TABLE recipe_likes (
        like_id INT AUTO_INCREMENT PRIMARY KEY,
        contribution_id INT NOT NULL,
        user_id INT NOT NULL,
        liked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_contribution_id (contribution_id),
        INDEX idx_user_id (user_id),
        UNIQUE KEY unique_like (contribution_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!$conn->query($create_table_sql)) {
        error_log("Error creating recipe_likes table: " . $conn->error);
    }
}

// Handle file uploads
$upload_dir = "uploads/recipes/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Allowed file types
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$max_file_size = 2 * 1024 * 1024; // 2MB

// Initialize variables
$message = "";
$message_type = "";
$edit_mode = false;
$edit_id = 0;

// Handle Recipe Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $message = "Please login to submit a recipe!";
        $message_type = "danger";
    } else {
        if (isset($_POST['submit_recipe'])) {
            $title = $_POST['title'];
            $content = $_POST['content'];
            $tips = $_POST['tips'] ?? '';
            $experience = $_POST['experience'] ?? '';
            $user_id = $_SESSION['user_id'];
            $recipe_photo = null;
            
            // Handle photo upload
            if (!empty($_FILES['recipe_photo']['name'])) {
                $file_name = $_FILES['recipe_photo']['name'];
                $file_tmp = $_FILES['recipe_photo']['tmp_name'];
                $file_size = $_FILES['recipe_photo']['size'];
                $file_type = $_FILES['recipe_photo']['type'];
                
                // Validate file
                if (in_array($file_type, $allowed_types)) {
                    if ($file_size <= $max_file_size) {
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $new_filename = uniqid('recipe_', true) . '.' . $file_ext;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            $recipe_photo = $upload_path;
                        }
                    } else {
                        $message = "File size too large. Maximum 2MB allowed.";
                        $message_type = "danger";
                    }
                } else {
                    $message = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
                    $message_type = "danger";
                }
            } 
            // Handle photo URL
            elseif (!empty($_POST['photo_url'])) {
                $photo_url = filter_var($_POST['photo_url'], FILTER_VALIDATE_URL);
                if ($photo_url) {
                    $recipe_photo = $photo_url;
                }
            }
            
            if (empty($title) || empty($content)) {
                $message = "Title and recipe content are required!";
                $message_type = "danger";
            } else {
                $sql = "INSERT INTO community_cookbook (user_id, recipe_title, recipe_content, recipe_photo, tips, experience, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'approved')";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssss", $user_id, $title, $content, $recipe_photo, $tips, $experience);
                
                if ($stmt->execute()) {
                    $message = "Recipe submitted successfully!";
                    $message_type = "success";
                    $_POST = array(); // Clear form
                } else {
                    $message = "Error submitting recipe: " . $conn->error;
                    $message_type = "danger";
                }
                $stmt->close();
            }
        }
        
        // Handle Recipe Update
        if (isset($_POST['update_recipe'])) {
            $recipe_id = $_POST['recipe_id'];
            $title = $_POST['title'];
            $content = $_POST['content'];
            $tips = $_POST['tips'] ?? '';
            $experience = $_POST['experience'] ?? '';
            $user_id = $_SESSION['user_id'];
            $recipe_photo = null;
            
            // Handle photo upload for update
            if (!empty($_FILES['recipe_photo']['name'])) {
                $file_name = $_FILES['recipe_photo']['name'];
                $file_tmp = $_FILES['recipe_photo']['tmp_name'];
                $file_size = $_FILES['recipe_photo']['size'];
                $file_type = $_FILES['recipe_photo']['type'];
                
                if (in_array($file_type, $allowed_types)) {
                    if ($file_size <= $max_file_size) {
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $new_filename = uniqid('recipe_', true) . '.' . $file_ext;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            $recipe_photo = $upload_path;
                        }
                    }
                }
            } 
            elseif (!empty($_POST['photo_url'])) {
                $photo_url = filter_var($_POST['photo_url'], FILTER_VALIDATE_URL);
                if ($photo_url) {
                    $recipe_photo = $photo_url;
                }
            }
            
            // Verify user owns this recipe
            $check_sql = "SELECT * FROM community_cookbook WHERE contribution_id = ? AND user_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $recipe_id, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // If no new photo, keep the old one
                if ($recipe_photo === null) {
                    $current_recipe = $check_result->fetch_assoc();
                    $recipe_photo = $current_recipe['recipe_photo'];
                }
                
                $update_sql = "UPDATE community_cookbook SET recipe_title = ?, recipe_content = ?, recipe_photo = ?, tips = ?, experience = ? WHERE contribution_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssssi", $title, $content, $recipe_photo, $tips, $experience, $recipe_id);
                
                if ($update_stmt->execute()) {
                    $message = "Recipe updated successfully!";
                    $message_type = "success";
                    $edit_mode = false;
                } else {
                    $message = "Error updating recipe: " . $conn->error;
                    $message_type = "danger";
                }
                $update_stmt->close();
            } else {
                $message = "You are not authorized to edit this recipe.";
                $message_type = "danger";
            }
            $check_stmt->close();
        }
    }
}

// Handle Edit Request
if (isset($_GET['edit']) && isset($_SESSION['user_id'])) {
    $recipe_id = $_GET['edit'];
    $user_id = $_SESSION['user_id'];
    
    // Verify user owns this recipe
    $sql = "SELECT * FROM community_cookbook WHERE contribution_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $recipe_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $edit_mode = true;
        $edit_data = $result->fetch_assoc();
        $edit_id = $recipe_id;
    } else {
        $message = "You are not authorized to edit this recipe.";
        $message_type = "danger";
    }
    $stmt->close();
}

// Handle Delete Request - FIXED FOR cookbook.php
if (isset($_GET['delete']) && isset($_SESSION['user_id'])) {
    $recipe_id = $_GET['delete'];
    $user_id = $_SESSION['user_id'];
    
    // Verify user owns this recipe
    $check_sql = "SELECT * FROM community_cookbook WHERE contribution_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $recipe_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // First, delete related likes
        $delete_likes_sql = "DELETE FROM recipe_likes WHERE contribution_id = ?";
        $delete_likes_stmt = $conn->prepare($delete_likes_sql);
        $delete_likes_stmt->bind_param("i", $recipe_id);
        $delete_likes_stmt->execute();
        $delete_likes_stmt->close();
        
        // Now delete the recipe
        $delete_sql = "DELETE FROM community_cookbook WHERE contribution_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $recipe_id);
        
        if ($delete_stmt->execute()) {
            // Set success message
            $_SESSION['delete_success'] = "Recipe deleted successfully!";
        } else {
            $_SESSION['delete_error'] = "Error deleting recipe: " . $conn->error;
        }
        $delete_stmt->close();
    } else {
        $_SESSION['delete_error'] = "You are not authorized to delete this recipe.";
    }
    $check_stmt->close();
    
    // Redirect after delete - FIXED: Redirect to cookbook.php (not community_cookbook.php)
    header("Location: cookbook.php");
    exit();
}

// Handle Like - FIXED FOR cookbook.php
if (isset($_GET['like']) && isset($_SESSION['user_id'])) {
    $contribution_id = intval($_GET['like']);
    $user_id = $_SESSION['user_id'];
    
    // Check if user already liked
    $check_sql = "SELECT * FROM recipe_likes WHERE contribution_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $contribution_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        // Add like
        $like_sql = "INSERT INTO recipe_likes (contribution_id, user_id) VALUES (?, ?)";
        $like_stmt = $conn->prepare($like_sql);
        $like_stmt->bind_param("ii", $contribution_id, $user_id);
        
        if ($like_stmt->execute()) {
            // Update likes count in community_cookbook table
            $update_sql = "UPDATE community_cookbook SET likes_count = likes_count + 1 WHERE contribution_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $contribution_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $_SESSION['like_success'] = "Recipe liked!";
        } else {
            $_SESSION['like_error'] = "Error liking recipe: " . $conn->error;
        }
        $like_stmt->close();
    } else {
        // User already liked, so unlike
        $unlike_sql = "DELETE FROM recipe_likes WHERE contribution_id = ? AND user_id = ?";
        $unlike_stmt = $conn->prepare($unlike_sql);
        $unlike_stmt->bind_param("ii", $contribution_id, $user_id);
        
        if ($unlike_stmt->execute()) {
            // Update likes count in community_cookbook table
            $update_sql = "UPDATE community_cookbook SET likes_count = GREATEST(likes_count - 1, 0) WHERE contribution_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $contribution_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $_SESSION['like_success'] = "Recipe unliked!";
        } else {
            $_SESSION['like_error'] = "Error unliking recipe: " . $conn->error;
        }
        $unlike_stmt->close();
    }
    $check_stmt->close();
    
    // Redirect back to same page - FIXED: Use current page name
    header("Location: cookbook.php" . (isset($_GET['edit']) ? '?edit=' . $_GET['edit'] : ''));
    exit();
}

// Get community contributions with user info (all approved recipes)
$sql = "SELECT cc.*, u.first_name, u.last_name, u.email,
        (SELECT COUNT(*) FROM recipe_likes WHERE contribution_id = cc.contribution_id) as total_likes,
        EXISTS(SELECT 1 FROM recipe_likes WHERE contribution_id = cc.contribution_id AND user_id = ?) as user_liked
        FROM community_cookbook cc
        LEFT JOIN users u ON cc.user_id = u.user_id
        WHERE cc.status = 'approved'
        ORDER BY cc.submitted_at DESC";

$stmt = $conn->prepare($sql);
$user_id_param = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$stmt->bind_param("i", $user_id_param);
$stmt->execute();
$result = $stmt->get_result();
$contributions = ($result && $result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

// Get user's own recipes (for editing/deleting)
$user_recipes = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_sql = "SELECT cc.*, 
                (SELECT COUNT(*) FROM recipe_likes WHERE contribution_id = cc.contribution_id) as total_likes,
                EXISTS(SELECT 1 FROM recipe_likes WHERE contribution_id = cc.contribution_id AND user_id = ?) as user_liked
                FROM community_cookbook cc 
                WHERE cc.user_id = ? 
                ORDER BY cc.submitted_at DESC";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("ii", $user_id, $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_recipes = ($user_result->num_rows > 0) ? $user_result->fetch_all(MYSQLI_ASSOC) : [];
    $user_stmt->close();
}

// Get top contributors
$top_sql = "SELECT u.first_name, u.last_name, COUNT(cc.contribution_id) as recipe_count
            FROM users u
            LEFT JOIN community_cookbook cc ON u.user_id = cc.user_id AND cc.status = 'approved'
            GROUP BY u.user_id
            HAVING recipe_count > 0
            ORDER BY recipe_count DESC
            LIMIT 5";

$top_result = $conn->query($top_sql);
$top_contributors = ($top_result && $top_result->num_rows > 0) ? $top_result->fetch_all(MYSQLI_ASSOC) : [];

// Check for messages from session
if (isset($_SESSION['like_success'])) {
    $message = $_SESSION['like_success'];
    $message_type = "success";
    unset($_SESSION['like_success']);
}

if (isset($_SESSION['like_error'])) {
    $message = $_SESSION['like_error'];
    $message_type = "danger";
    unset($_SESSION['like_error']);
}

if (isset($_SESSION['delete_success'])) {
    $message = $_SESSION['delete_success'];
    $message_type = "success";
    unset($_SESSION['delete_success']);
}

if (isset($_SESSION['delete_error'])) {
    $message = $_SESSION['delete_error'];
    $message_type = "danger";
    unset($_SESSION['delete_error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Cookbook - FoodFusion</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/cookbook.css">
</head>
<body data-logged-in="<?php echo isset($_SESSION['user_id']) ? '1' : '0'; ?>" data-edit-mode="<?php echo $edit_mode ? '1' : '0'; ?>" data-edit-param="<?php echo isset($_GET['edit']) ? '1' : '0'; ?>">
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
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="menuDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bars me-1"></i>Menu
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="menuDropdown">
                            <li><a class="dropdown-item" href="recipe_collection.php"><i class="fas fa-book me-2"></i>Recipe Collection</a></li>
                            <li><a class="dropdown-item active" href="cookbook.php"><i class="fas fa-users me-2"></i>Community Cookbook</a></li>
                            <li><a class="dropdown-item" href="culinary_resources.php"><i class="fas fa-utensil-spoon me-2"></i>Culinary Resources</a></li>
                            <li><a class="dropdown-item" href="educational_resources.php"><i class="fas fa-graduation-cap me-2"></i>Educational Resources</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="about.php"><i class="fas fa-address-card me-1"></i>About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php"><i class="fas fa-phone me-1"></i>Contact Us</a>
                    </li>
                </ul>
                
                <div class="d-flex">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown user-dropdown">
                            <a class="btn btn-light dropdown-toggle" href="#" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-2"></i><?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="#my-recipes"><i class="fas fa-utensils me-2"></i>My Recipes</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="index.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
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
    <section class="hero-community text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Community Cookbook</h1>
            <p class="lead mb-4">Share your favorite recipes, cooking tips, and culinary experiences with our food-loving community!</p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="<?php echo $edit_mode ? '' : '#submitRecipeModal'; ?>" 
                        onclick="<?php echo $edit_mode ? 'openEditModal()' : ''; ?>">
                    <i class="fas fa-plus-circle me-2"></i><?php echo $edit_mode ? 'Edit Recipe' : 'Share Your Recipe'; ?>
                </button>
            <?php else: ?>
                <a href="index.php" class="btn btn-light btn-lg me-3">
                    <i class="fas fa-user-plus me-2"></i>Join to Contribute
                </a>
                <a href="index.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Community Stats -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="community-stats text-center">
                        <div class="stats-number"><?php echo count($contributions); ?></div>
                        <h5>Recipes Shared</h5>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="community-stats text-center">
                        <?php 
                            $total_likes = 0;
                            foreach ($contributions as $contribution) {
                                $total_likes += $contribution['total_likes'];
                            }
                        ?>
                        <div class="stats-number"><?php echo $total_likes; ?></div>
                        <h5>Total Likes</h5>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="community-stats text-center">
                        <?php 
                            $unique_authors = array_unique(array_column($contributions, 'user_id'));
                        ?>
                        <div class="stats-number"><?php echo count($unique_authors); ?></div>
                        <h5>Community Chefs</h5>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="community-stats text-center">
                        <div class="stats-number"><?php echo isset($_SESSION['user_id']) ? count($user_recipes) : '0'; ?></div>
                        <h5>Your Recipes</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-5 bg-light">
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Tabs for My Recipes and Community Recipes -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <ul class="nav nav-tabs mb-4" id="recipeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="community-tab" data-bs-toggle="tab" data-bs-target="#community" type="button" role="tab">
                        <i class="fas fa-users me-2"></i>Community Recipes
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="myrecipes-tab" data-bs-toggle="tab" data-bs-target="#my-recipes" type="button" role="tab">
                        <i class="fas fa-utensils me-2"></i>My Recipes (<?php echo count($user_recipes); ?>)
                    </button>
                </li>
            </ul>
            <?php endif; ?>
            
            <div class="tab-content" id="recipeTabsContent">
                <!-- Community Recipes Tab -->
                <div class="tab-pane fade show active" id="community" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3>Latest Community Recipes</h3>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="<?php echo $edit_mode ? '' : '#submitRecipeModal'; ?>"
                                            onclick="<?php echo $edit_mode ? 'openEditModal()' : ''; ?>">
                                        <i class="fas fa-plus me-2"></i><?php echo $edit_mode ? 'Edit Recipe' : 'Add Recipe'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (count($contributions) > 0): ?>
                                <div class="recipe-grid-popup">
                                    <?php foreach ($contributions as $contribution): ?>
                                        <div class="card recipe-card-popup" onclick="openRecipePopup(<?php echo htmlspecialchars(json_encode($contribution), ENT_QUOTES, 'UTF-8'); ?>)">
                                            <?php if (!empty($contribution['recipe_photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($contribution['recipe_photo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($contribution['recipe_title']); ?>" 
                                                 class="recipe-img-popup">
                                            <?php else: ?>
                                            <img src="https://images.unsplash.com/photo-1565958011703-44f9829ba187?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" 
                                                 alt="Recipe Image" 
                                                 class="recipe-img-popup">
                                            <?php endif; ?>
                                            <div class="recipe-content-popup">
                                                <h3 class="recipe-title-popup"><?php echo htmlspecialchars($contribution['recipe_title']); ?></h3>
                                                <p class="recipe-author-popup">
                                                    By <?php echo $contribution['first_name'] . ' ' . $contribution['last_name']; ?> - 
                                                    <?php echo date('F j, Y', strtotime($contribution['submitted_at'])); ?>
                                                </p>
                                                <p class="mb-3 flex-grow-1">
                                                    <?php 
                                                    $content = htmlspecialchars($contribution['recipe_content']);
                                                    echo nl2br(substr($content, 0, 150)) . (strlen($content) > 150 ? '...' : '');
                                                    ?>
                                                </p>
                                                <div class="recipe-meta-popup">
                                                    <a href="?like=<?php echo $contribution['contribution_id']; ?>" 
                                                       class="like-btn-popup text-decoration-none <?php echo ($contribution['user_liked'] == 1) ? 'liked' : ''; ?> <?php echo (isset($_SESSION['user_id'])) ? '' : 'disabled'; ?>"
                                                       onclick="event.stopPropagation(); <?php echo (isset($_SESSION['user_id'])) ? '' : 'event.preventDefault(); alert(\'Please login to like recipes!\');'; ?>">
                                                        <span class="like-icon">❤</span> Like
                                                    </a>
                                                    <span class="likes-count-popup"><?php echo $contribution['total_likes']; ?> Likes</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-utensils fa-4x text-muted mb-4"></i>
                                    <h4>No recipes yet</h4>
                                    <p class="mb-4">Be the first to share your recipe with the community!</p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="<?php echo $edit_mode ? '' : '#submitRecipeModal'; ?>"
                                                onclick="<?php echo $edit_mode ? 'openEditModal()' : ''; ?>">
                                            <i class="fas fa-plus me-2"></i>Share Your First Recipe
                                        </button>
                                    <?php else: ?>
                                        <a href="index.php" class="btn btn-danger">
                                            <i class="fas fa-user-plus me-2"></i>Join Community
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Right Column: Community Sidebar -->
                        <div class="col-lg-4">
                            <!-- Top Contributors -->
                            <div class="top-contributor">
                                <h4 class="text-white mb-4"><i class="fas fa-crown me-2"></i>Top Contributors</h4>
                                <?php if (count($top_contributors) > 0): ?>
                                    <?php foreach ($top_contributors as $index => $contributor): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="user-avatar me-3" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                                <?php echo strtoupper(substr($contributor['first_name'], 0, 1) . substr($contributor['last_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h6 class="text-white mb-0"><?php echo $contributor['first_name'] . ' ' . $contributor['last_name']; ?></h6>
                                                <p class="text-white-50 mb-0"><?php echo $contributor['recipe_count']; ?> recipes shared</p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-white-50">Be the first top contributor!</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Community Guidelines -->
                            <div class="card mb-4">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0"><i class="fas fa-book me-2"></i>Community Guidelines</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Be respectful to other members</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Share original recipes</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Include clear instructions</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Credit sources when applicable</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>No spam or advertising</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Have fun and be creative!</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- My Recipes Tab (Only for logged-in users) -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="tab-pane fade" id="my-recipes" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3><i class="fas fa-utensils me-2"></i>My Recipes</h3>
                                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#submitRecipeModal">
                                    <i class="fas fa-plus me-2"></i>Add New Recipe
                                </button>
                            </div>
                            
                            <?php if (count($user_recipes) > 0): ?>
                                <div class="row">
                                    <?php foreach ($user_recipes as $recipe): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card recipe-card-popup" onclick="openRecipePopup(<?php echo htmlspecialchars(json_encode($recipe), ENT_QUOTES, 'UTF-8'); ?>)">
                                                <div class="position-relative">
                                                    <div class="action-buttons">
                                                        <div class="btn-group-vertical">
                                                            <a href="?edit=<?php echo $recipe['contribution_id']; ?>" 
                                                               class="btn btn-primary action-btn"
                                                               onclick="event.stopPropagation();"
                                                               title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="cookbook.php?delete=<?php echo $recipe['contribution_id']; ?>" 
                                                               class="btn btn-danger action-btn"
                                                               onclick="event.stopPropagation(); return confirmDelete(event, <?php echo $recipe['contribution_id']; ?>);"
                                                               title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if (!empty($recipe['recipe_photo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($recipe['recipe_photo']); ?>" 
                                                         alt="<?php echo htmlspecialchars($recipe['recipe_title']); ?>" 
                                                         class="recipe-img-popup">
                                                    <?php else: ?>
                                                    <img src="https://images.unsplash.com/photo-1565958011703-44f9829ba187?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" 
                                                         alt="Recipe Image" 
                                                         class="recipe-img-popup">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="recipe-content-popup">
                                                    <h3 class="recipe-title-popup"><?php echo htmlspecialchars($recipe['recipe_title']); ?></h3>
                                                    <p class="recipe-author-popup">
                                                        Submitted: <?php echo date('F j, Y', strtotime($recipe['submitted_at'])); ?>
                                                    </p>
                                                    <p class="mb-3 flex-grow-1">
                                                        <?php 
                                                        $content = htmlspecialchars($recipe['recipe_content']);
                                                        echo nl2br(substr($content, 0, 150)) . (strlen($content) > 150 ? '...' : '');
                                                        ?>
                                                    </p>
                                                    <div class="recipe-meta-popup">
                                                        <span class="likes-count-popup">
                                                            <i class="fas fa-heart me-1"></i>
                                                            <?php echo $recipe['total_likes']; ?> Likes
                                                        </span>
                                                        <span class="badge bg-success">Published</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-utensils fa-4x text-muted mb-4"></i>
                                    <h4>You haven't shared any recipes yet</h4>
                                    <p class="mb-4">Start sharing your culinary creations with the community!</p>
                                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#submitRecipeModal">
                                        <i class="fas fa-plus me-2"></i>Share Your First Recipe
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Recipe Popup Modal -->
    <div class="popup-overlay" id="recipePopup">
        <div class="popup-content">
            <button class="close-popup" onclick="closePopup()">×</button>
            <img id="popupImage" src="" alt="Recipe Image" class="popup-img">
            <h2 id="popupTitle" class="popup-title"></h2>
            <p id="popupAuthor" class="popup-author"></p>
            <div class="popup-body">
                <div id="popupDescription"></div>
                <div id="popupTips" style="display: none;"></div>
                <div id="popupExperience" style="display: none;"></div>
                <div class="recipe-meta-popup" style="margin-top: 30px;">
                    <a href="#" id="popupLikeBtn" class="like-btn-popup" onclick="likeRecipe(event)">
                        <span class="like-icon">❤</span> Like
                    </a>
                    <span class="likes-count-popup" id="popupLikesCount">0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Recipe Modal -->
    <div class="modal fade" id="submitRecipeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        <?php echo $edit_mode ? 'Edit Recipe' : 'Share Your Recipe'; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <p class="text-muted mb-4">
                            <?php echo $edit_mode ? 'Update your recipe below:' : 'Share your favorite recipe with the FoodFusion community. Be sure to include clear instructions and helpful tips!'; ?>
                        </p>
                        
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="recipe_id" value="<?php echo $edit_id; ?>">
                            <input type="hidden" name="update_recipe" value="1">
                        <?php else: ?>
                            <input type="hidden" name="submit_recipe" value="1">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Recipe Title *</label>
                            <input type="text" class="form-control" name="title" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($edit_data['recipe_title']) : (isset($_POST['title']) ? $_POST['title'] : ''); ?>" 
                                   required placeholder="e.g., My Grandmother's Special Curry">
                        </div>
                        
                        <!-- Photo Upload Field -->
                        <div class="mb-3">
                            <label class="form-label">Recipe Photo (Optional)</label>
                            <div class="input-group">
                                <input type="file" class="form-control" name="recipe_photo" id="recipePhoto" 
                                       accept="image/*" onchange="previewImage(event)">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePhotoLink()">
                                    <i class="fas fa-link me-1"></i>Use URL Instead
                                </button>
                            </div>
                            <div id="photoUrlGroup" class="mt-2" style="display: none;">
                                <label class="form-label">Or enter image URL:</label>
                                <input type="url" class="form-control" name="photo_url" 
                                       placeholder="https://example.com/recipe-photo.jpg"
                                       value="<?php echo $edit_mode && filter_var($edit_data['recipe_photo'], FILTER_VALIDATE_URL) ? htmlspecialchars($edit_data['recipe_photo']) : ''; ?>">
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Upload JPG, PNG, or GIF (max 2MB) or paste an image URL</small>
                            </div>
                        </div>
                        
                        <!-- Image Preview -->
                        <div class="mb-3" id="imagePreviewContainer" style="display: <?php echo ($edit_mode && !empty($edit_data['recipe_photo'])) ? 'block' : 'none'; ?>;">
                            <label class="form-label">Photo Preview:</label>
                            <div class="border rounded p-2 text-center">
                                <img id="imagePreview" src="<?php echo $edit_mode && !empty($edit_data['recipe_photo']) ? htmlspecialchars($edit_data['recipe_photo']) : ''; ?>" 
                                     alt="Preview" class="img-fluid" style="max-height: 200px;">
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeImage()">
                                    <i class="fas fa-trash me-1"></i>Remove Image
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Recipe Content *</label>
                            <textarea class="form-control" name="content" rows="8" 
                                      required placeholder="Please include ingredients and step-by-step instructions..."><?php echo $edit_mode ? htmlspecialchars($edit_data['recipe_content']) : (isset($_POST['content']) ? $_POST['content'] : ''); ?></textarea>
                            <small class="text-muted">Format: List ingredients first, then instructions</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Cooking Tips & Tricks</label>
                            <textarea class="form-control" name="tips" rows="4" 
                                      placeholder="Any special techniques, substitutions, or tips that would help others..."><?php echo $edit_mode ? htmlspecialchars($edit_data['tips']) : (isset($_POST['tips']) ? $_POST['tips'] : ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Your Experience with this Recipe</label>
                            <textarea class="form-control" name="experience" rows="4" 
                                      placeholder="Why is this recipe special to you? Any stories or memories associated with it..."><?php echo $edit_mode ? htmlspecialchars($edit_data['experience']) : (isset($_POST['experience']) ? $_POST['experience'] : ''); ?></textarea>
                        </div>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Recipes are published immediately for the community to enjoy!
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-paper-plane me-2"></i>
                            <?php echo $edit_mode ? 'Update Recipe' : 'Submit Recipe'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><i class="fas fa-utensils me-2"></i>FoodFusion</h5>
                    <p>A community-driven platform for food lovers to share, learn, and connect.</p>
                    <div class="social-icons mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Community Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="cookbook.php" class="text-white-50 text-decoration-none">Community Cookbook</a></li>
                        <li><a href="recipe_collection.php" class="text-white-50 text-decoration-none">Recipe Collection</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Cooking Challenges</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Member Spotlight</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Community Stats</h5>
                    <p class="mb-1"><?php echo count($contributions); ?> Recipes Shared</p>
                    <p class="mb-1"><?php echo count($unique_authors); ?> Active Contributors</p>
                    <p class="mb-1"><?php echo $total_likes; ?> Community Likes</p>
                    <p>24/7 Active Support</p>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 FoodFusion Community. All rights reserved.</p>
                <p class="mb-0">
                    <a href="#" class="text-white-50 text-decoration-none me-3" data-bs-toggle="modal" data-bs-target="#privacyModal">Community Guidelines</a>
                    <a href="#" class="text-white-50 text-decoration-none" data-bs-toggle="modal" data-bs-target="#cookieModal">Privacy Policy</a>
                </p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/cookbook.js"></script>
</body>
</html>

<?php
$conn->close();
?>