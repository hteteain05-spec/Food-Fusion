<?php
// admin_recipes.php - Recipe Management for Admin
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

/* DEBUG: Check connection */
echo "<!-- Database connected successfully -->";

// Handle recipe deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_sql = "DELETE FROM recipes WHERE recipe_id = ?";
    $stmt = $conn->prepare($delete_sql);
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "Recipe deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting recipe: " . $stmt->error;
            $message_type = "danger";
        }
        $stmt->close();
    } else {
        $message = "Error preparing delete statement: " . $conn->error;
        $message_type = "danger";
    }
}

// Handle recipe status toggle
if (isset($_GET['toggle_views'])) {
    $toggle_id = intval($_GET['toggle_views']);
    $toggle_sql = "UPDATE recipes SET views_count = 0 WHERE recipe_id = ?";
    $stmt = $conn->prepare($toggle_sql);
    if ($stmt) {
        $stmt->bind_param("i", $toggle_id);
        $stmt->execute();
        $message = "Recipe views reset to 0!";
        $message_type = "success";
        $stmt->close();
    }
}

/* DEBUG: Check database structure */
$tables_check = $conn->query("SHOW TABLES");
echo "<!-- Tables in database: ";
while($row = $tables_check->fetch_array()) {
    echo $row[0] . " ";
}
echo " -->";

// Check if recipes table is empty and add a test recipe
$check_empty = $conn->query("SELECT COUNT(*) as count FROM recipes");
if ($check_empty) {
    $count = $check_empty->fetch_assoc()['count'];
    echo "<!-- Recipes count: $count -->";
    
    if ($count == 0) {
        // Check recipes table structure
        $recipes_structure = $conn->query("DESCRIBE recipes");
        echo "<!-- Recipes table structure: ";
        while($row = $recipes_structure->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ") ";
        }
        echo " -->";
        
        // Add a test recipe
        $test_sql = "INSERT INTO recipes (
                        title, 
                        description, 
                        cuisine_type, 
                        difficulty_level, 
                        prep_time, 
                        cook_time,
                        servings,
                        views_count, 
                        created_at
                    ) VALUES (
                        'Classic Spaghetti Carbonara',
                        'A traditional Italian pasta dish with eggs, cheese, pancetta, and pepper',
                        'Italian',
                        'Medium',
                        15,
                        20,
                        4,
                        10,
                        NOW()
                    )";
        
        if ($conn->query($test_sql)) {
            echo "<!-- Test recipe added successfully -->";
            $message = "Test recipe added to empty database!";
            $message_type = "info";
        } else {
            echo "<!-- Failed to add test recipe: " . $conn->error . " -->";
            $message = "Error: Could not add test recipe - " . $conn->error;
            $message_type = "warning";
        }
    }
}

// Initialize stats array with default values
$stats = [
    'total_recipes' => 0,
    'easy' => 0,
    'medium' => 0,
    'hard' => 0,
    'total_views' => 0,
    'italian' => 0,
    'chinese' => 0,
    'indian' => 0,
    'mexican' => 0
];

// Get recipe statistics
$stats_sql = "
SELECT
    COUNT(*) AS total_recipes,
    SUM(CASE WHEN difficulty_level = 'Easy' THEN 1 ELSE 0 END) AS easy,
    SUM(CASE WHEN difficulty_level = 'Medium' THEN 1 ELSE 0 END) AS medium,
    SUM(CASE WHEN difficulty_level = 'Hard' THEN 1 ELSE 0 END) AS hard,
    IFNULL(SUM(views_count), 0) AS total_views,
    SUM(CASE WHEN cuisine_type LIKE '%Italian%' THEN 1 ELSE 0 END) AS italian,
    SUM(CASE WHEN cuisine_type LIKE '%Chinese%' THEN 1 ELSE 0 END) AS chinese,
    SUM(CASE WHEN cuisine_type LIKE '%Indian%' THEN 1 ELSE 0 END) AS indian,
    SUM(CASE WHEN cuisine_type LIKE '%Mexican%' THEN 1 ELSE 0 END) AS mexican
FROM recipes
";
    
$stats_result = $conn->query($stats_sql);

if ($stats_result) {
    $stats = $stats_result->fetch_assoc();
    $stats = array_merge([
        'total_recipes' => 0,
        'easy' => 0,
        'medium' => 0,
        'hard' => 0,
        'total_views' => 0,
        'italian' => 0,
        'chinese' => 0,
        'indian' => 0,
        'mexican' => 0
    ], $stats);
    echo "<!-- Stats fetched: " . json_encode($stats) . " -->";
} else {
    $message = "Error fetching statistics: " . $conn->error;
    $message_type = "warning";
    echo "<!-- Stats error: " . $conn->error . " -->";
}

// Get all recipes with user information
$recipes_sql = "
SELECT
    r.recipe_id,
    r.title,
    r.description,
    r.cuisine_type,
    r.dietary_preference,
    r.difficulty_level,
    r.prep_time,
    r.cook_time,
    r.servings,
    r.image_url,
    r.views_count,
    r.created_at,
    IFNULL(u.username, 'Admin') AS username,
    IFNULL(u.email, '') AS email
FROM recipes r
LEFT JOIN users u ON r.user_id = u.user_id
ORDER BY r.recipe_id DESC
";

/* DEBUG: Check the SQL query */
echo "<!-- Main SQL Query: " . htmlspecialchars($recipes_sql) . " -->";

$recipes_result = $conn->query($recipes_sql);

/* DEBUG: Test with simpler query */
$simple_query = $conn->query("SELECT COUNT(*) as simple_count FROM recipes");
if ($simple_query) {
    $simple_count = $simple_query->fetch_assoc()['simple_count'];
    echo "<!-- Simple count query: $simple_count recipes -->";
}

if (!$recipes_result) {
    $message = "Error fetching recipes: " . $conn->error;
    $message_type = "danger";
    echo "<!-- SQL Error: " . htmlspecialchars($conn->error) . " -->";
    
    // Try simpler query
    $simple_recipes_sql = "SELECT * FROM recipes ORDER BY recipe_id DESC";
    $recipes_result = $conn->query($simple_recipes_sql);
    echo "<!-- Trying simple query... -->";
    if ($recipes_result) {
        echo "<!-- Simple query successful, found: " . $recipes_result->num_rows . " rows -->";
    }
} else {
    echo "<!-- Main query successful, found: " . $recipes_result->num_rows . " rows -->";
    
    /* DEBUG: Check data structure */
    if ($recipes_result->num_rows > 0) {
        $test_row = $recipes_result->fetch_assoc();
        echo "<!-- First recipe data: " . htmlspecialchars(json_encode($test_row)) . " -->";
        // Reset pointer back to beginning
        $recipes_result->data_seek(0);
    } else {
        echo "<!-- No recipes found in main query -->";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodFusion - Recipe Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #a71d2a;
        }
        
        .recipe-management-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stats-icon.total { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .stats-icon.views { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .stats-icon.easy { background: rgba(25, 135, 84, 0.1); color: #198754; }
        .stats-icon.hard { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stats-icon.medium { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
        
        .recipe-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .table thead {
            background: #f8f9fa;
        }
        
        .table th {
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        
        .recipe-image {
            width: 80px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #dee2e6;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .badge-views {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .badge-cuisine {
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            border: 1px solid #0d6efd;
        }
        
        .badge-easy {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
            border: 1px solid #198754;
        }
        
        .badge-medium {
            background: rgba(111, 66, 193, 0.1);
            color: #6f42c1;
            border: 1px solid #6f42c1;
        }
        
        .badge-hard {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid #ffc107;
        }
        
        .action-btn {
            padding: 5px 12px;
            border-radius: 6px;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
            margin: 2px;
        }
        
        .view-btn {
            background: rgba(13, 110, 253, 0.1);
            border: 1px solid #0d6efd;
            color: #0d6efd;
        }
        
        .view-btn:hover {
            background: #0d6efd;
            color: white;
        }
        
        .edit-btn {
            background: rgba(32, 201, 151, 0.1);
            border: 1px solid #20c997;
            color: #20c997;
        }
        
        .edit-btn:hover {
            background: #20c997;
            color: white;
        }
        
        .delete-btn {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
            color: #dc3545;
        }
        
        .delete-btn:hover {
            background: #dc3545;
            color: white;
        }
        
        .reset-btn {
            background: rgba(108, 117, 125, 0.1);
            border: 1px solid #6c757d;
            color: #6c757d;
        }
        
        .reset-btn:hover {
            background: #6c757d;
            color: white;
        }
        
        .add-recipe-btn {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .add-recipe-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            padding-left: 40px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .no-recipes {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        .no-recipes i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .recipe-title {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .views-count {
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .time-info {
            font-size: 0.85em;
            color: #6c757d;
        }
        
        .debug-info {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 15px 0;
            font-size: 0.9em;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="recipe-management-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-book me-3"></i>Recipe Collection Management</h1>
                    <p class="mb-0">Manage all recipes, create new ones, and organize the FoodFusion recipe collection</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="add_recipe.php" class="add-recipe-btn">
                        <i class="fas fa-plus-circle me-2"></i>Add New Recipe
                    </a>
                </div>
            </div>
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
                        <a class="nav-link active" href="admin_recipes.php">
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
        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon total">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3><?php echo number_format($stats['total_recipes']); ?></h3>
                    <p class="text-muted mb-0">Total Recipes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon views">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3><?php echo number_format($stats['total_views']); ?></h3>
                    <p class="text-muted mb-0">Total Views</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon easy">
                        <i class="fas fa-smile"></i>
                    </div>
                    <h3><?php echo number_format($stats['easy']); ?></h3>
                    <p class="text-muted mb-0">Easy Recipes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon medium">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <h3><?php echo number_format($stats['medium']); ?></h3>
                    <p class="text-muted mb-0">Medium Recipes</p>
                </div>
            </div>
        </div>

        <!-- Cuisine Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-pizza-slice"></i>
                    </div>
                    <h3><?php echo number_format($stats['italian']); ?></h3>
                    <p class="text-muted mb-0">Italian Recipes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-dragon"></i>
                    </div>
                    <h3><?php echo number_format($stats['chinese']); ?></h3>
                    <p class="text-muted mb-0">Chinese Recipes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fas fa-pepper-hot"></i>
                    </div>
                    <h3><?php echo number_format($stats['indian']); ?></h3>
                    <p class="text-muted mb-0">Indian Recipes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                        <i class="fas fa-pepper-hot"></i>
                    </div>
                    <h3><?php echo number_format($stats['hard']); ?></h3>
                    <p class="text-muted mb-0">Hard Recipes</p>
                </div>
            </div>
        </div>

        <!-- Recipe Table -->
        <div class="recipe-table">
            <div class="table-responsive">
                <table class="table table-hover" id="recipesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Recipe Title</th>
                            <th>Cuisine</th>
                            <th>Difficulty</th>
                            <th>Views</th>
                            <th>Time</th>
                            <th>Author</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($recipes_result) && $recipes_result && $recipes_result->num_rows > 0): ?>
                            <?php while ($recipe = $recipes_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $recipe['recipe_id']; ?></td>
                                    <td>
                                        <?php if (!empty($recipe['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($recipe['image_url']); ?>" 
                                                 alt="Recipe Image" 
                                                 class="recipe-image">
                                        <?php else: ?>
                                            <img src="https://via.placeholder.com/80x60/eee/999?text=No+Image" 
                                                 alt="No Image" 
                                                 class="recipe-image">
                                        <?php endif; ?>
                                    </td>
                                    <td class="recipe-title">
                                        <strong><?php echo htmlspecialchars($recipe['title']); ?></strong>
                                        <?php if (!empty($recipe['description'])): ?>
                                            <small class="text-muted d-block"><?php echo substr(htmlspecialchars($recipe['description']), 0, 50); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-cuisine">
                                            <?php echo htmlspecialchars($recipe['cuisine_type'] ?? 'Not specified'); ?>
                                        </span>
                                        <?php if (!empty($recipe['dietary_preference'])): ?>
                                            <small class="d-block text-muted"><?php echo htmlspecialchars($recipe['dietary_preference']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $difficulty = $recipe['difficulty_level'] ?? 'Medium';
                                        $difficulty_class = 'badge-' . strtolower($difficulty);
                                        ?>
                                        <span class="badge <?php echo $difficulty_class; ?>">
                                            <?php echo htmlspecialchars($difficulty); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="views-count"><?php echo number_format($recipe['views_count']); ?></span>
                                        <div class="mt-1">
                                            <a href="?toggle_views=<?php echo $recipe['recipe_id']; ?>" 
                                               class="badge reset-btn" 
                                               onclick="return confirm('Reset views to 0?')"
                                               title="Reset views">
                                                <i class="fas fa-redo"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($recipe['prep_time']): ?>
                                            <div class="time-info">
                                                <i class="fas fa-clock"></i> Prep: <?php echo $recipe['prep_time']; ?>m
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($recipe['cook_time']): ?>
                                            <div class="time-info">
                                                <i class="fas fa-fire"></i> Cook: <?php echo $recipe['cook_time']; ?>m
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($recipe['servings']): ?>
                                            <div class="time-info">
                                                <i class="fas fa-users"></i> Serves: <?php echo $recipe['servings']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $author_name = !empty($recipe['username']) ? $recipe['username'] : 'Admin';
                                        $initials = strtoupper(substr($author_name, 0, 2));
                                        ?>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 35px; height: 35px; font-size: 0.8em;">
                                                <?php echo $initials; ?>
                                            </div>
                                            <div class="ms-2">
                                                <div style="font-size: 0.9em;"><?php echo htmlspecialchars($author_name); ?></div>
                                                <small class="text-muted" style="font-size: 0.75em;"><?php echo htmlspecialchars($recipe['email'] ?? ''); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $created_date = date('M d, Y', strtotime($recipe['created_at']));
                                        echo $created_date;
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_recipe.php?id=<?php echo $recipe['recipe_id']; ?>" 
                                               class="action-btn edit-btn"
                                               title="Edit Recipe">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete_id=<?php echo $recipe['recipe_id']; ?>" 
                                               class="action-btn delete-btn"
                                               onclick="return confirm('Are you sure you want to delete this recipe?')"
                                               title="Delete Recipe">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="no-recipes">
                                        <i class="fas fa-book-open fa-3x mb-3 text-muted"></i>
                                        <h4 class="text-muted">No Recipes Found</h4>
                                        <p class="mb-3">The recipes table is empty or there was an error loading recipes.</p>
                                        <div class="mt-4">
                                            <a href="add_recipe.php" class="btn btn-success me-2">
                                                <i class="fas fa-plus-circle me-2"></i>Add First Recipe
                                            </a>
                                            <button onclick="location.reload()" class="btn btn-outline-secondary">
                                                <i class="fas fa-redo me-2"></i>Refresh Page
                                            </button>
                                        </div>
                                        <?php if (!isset($recipes_result) || !$recipes_result): ?>
                                            <div class="alert alert-warning mt-3">
                                                <small><i class="fas fa-exclamation-triangle me-1"></i> 
                                                    SQL Error: <?php echo $conn->error ?? 'Unknown error'; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable only if there are rows
            if ($('#recipesTable tbody tr td').length > 1 && !$('#recipesTable tbody tr td').hasClass('text-center')) {
                $('#recipesTable').DataTable({
                    "pageLength": 10,
                    "order": [[0, "desc"]],
                    "language": {
                        "search": "Search recipes:",
                        "lengthMenu": "Show _MENU_ recipes per page",
                        "info": "Showing _START_ to _END_ of _TOTAL_ recipes"
                    }
                });
            }
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
            
           
        });
    </script>
</body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>