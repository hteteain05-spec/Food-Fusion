<?php
// culinary_resources.php - Culinary Resources Page
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

// Get culinary resources
$sql = "SELECT * FROM resources WHERE resource_type = 'culinary' AND is_active = 1 ORDER BY uploaded_at DESC";
$result = $conn->query($sql);
$resources = $result->fetch_all(MYSQLI_ASSOC);

// Get categories
$categories_sql = "SELECT DISTINCT category FROM resources WHERE resource_type = 'culinary' AND category IS NOT NULL";
$categories_result = $conn->query($categories_sql);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Get popular downloads
$popular_sql = "SELECT * FROM resources WHERE resource_type = 'culinary' AND is_active = 1 
                ORDER BY download_count DESC LIMIT 5";
$popular_result = $conn->query($popular_sql);
$popular_resources = $popular_result->fetch_all(MYSQLI_ASSOC);

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
if($is_logged_in){
    $username = $_SESSION['user_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Culinary Resources - FoodFusion</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-culinary {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), 
                        url('https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
        }
        
        .resource-card {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .resource-card:hover {
            transform: translateY(-5px);
        }
        
        .pdf-icon {
            color: #e74c3c;
        }
        
        .video-icon {
            color: #3498db;
        }
        
        .image-icon {
            color: #2ecc71;
        }
        
        .document-icon {
            color: #f39c12;
        }
        
        .btn-download {
            background: linear-gradient(45deg, #ff6b6b, #ff8e53);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .btn-watch {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-watch:hover {
            background: #2980b9;
            color: white;
        }
        
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 8px;
        }
        
        .video-container video,
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .resource-preview {
            height: 180px;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .stats-card {
            background: linear-gradient(45deg, #ff6b6b, #ff8e53);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .category-badge {
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .category-badge:hover {
            transform: scale(1.05);
        }
        
        /* Style for modal */
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            background: linear-gradient(45deg, #3498db, #2ecc71);
            color: white;
            border-bottom: none;
        }
        
        .btn-close-white {
            filter: brightness(0) invert(1);
        }
    </style>
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
    <section class="hero-culinary text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Culinary Resources</h1>
            <p class="lead mb-4">Download recipe cards, watch cooking tutorials, and master kitchen techniques.</p>
        </div>
    </section>

    <!-- Quick Stats -->
    <section class="py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="stats-card text-center">
                        <div class="stats-number">
                            <?php echo count($resources); ?>
                        </div>
                        <p>Resources Available</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card text-center" style="background: linear-gradient(45deg, #3498db, #2ecc71);">
                        <div class="stats-number">
                            <?php 
                                $total_downloads = 0;
                                foreach ($resources as $resource) {
                                    $total_downloads += $resource['download_count'];
                                }
                                echo $total_downloads;
                            ?>
                        </div>
                        <p>Total Downloads</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card text-center" style="background: linear-gradient(45deg, #9b59b6, #e74c3c);">
                        <div class="stats-number">
                            <?php 
                                $video_count = 0;
                                foreach ($resources as $resource) {
                                    if ($resource['file_type'] == 'video') $video_count++;
                                }
                                echo $video_count;
                            ?>
                        </div>
                        <p>Video Tutorials</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card text-center" style="background: linear-gradient(45deg, #f39c12, #d35400);">
                        <div class="stats-number">
                            <?php 
                                $recipe_card_count = 0;
                                foreach ($resources as $resource) {
                                    if ($resource['category'] == 'Recipe Cards') $recipe_card_count++;
                                }
                                echo $recipe_card_count;
                            ?>
                        </div>
                        <p>Recipe Cards</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <!-- Left Column: Resources -->
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3>All Culinary Resources</h3>
                        <div class="dropdown">
                            <button class="btn btn-outline-danger dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-filter me-2"></i>Filter by Type
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-filter="all">All Types</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="pdf">PDF Documents</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="video">Video Tutorials</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="image">Images</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Categories -->
                    <div class="mb-4">
                        <h5 class="mb-3">Browse by Category</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-danger category-badge" data-category="all">All Categories</span>
                            <?php foreach ($categories as $category): ?>
                                <span class="badge bg-secondary category-badge" 
                                      data-category="<?php echo htmlspecialchars($category['category']); ?>">
                                    <?php echo htmlspecialchars($category['category']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Resources Grid -->
                    <div class="row g-4" id="resourcesGrid">
                        <?php if (count($resources) > 0): ?>
                            <?php foreach ($resources as $resource): ?>
                                <div class="col-md-6" data-type="<?php echo $resource['file_type']; ?>" 
                                     data-category="<?php echo htmlspecialchars($resource['category']); ?>">
                                    <div class="card resource-card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="card-title"><?php echo htmlspecialchars($resource['title']); ?></h5>
                                                    <span class="badge 
                                                        <?php 
                                                            switch($resource['file_type']) {
                                                                case 'pdf': echo 'bg-danger'; break;
                                                                case 'video': echo 'bg-primary'; break;
                                                                case 'image': echo 'bg-success'; break;
                                                                default: echo 'bg-warning';
                                                            }
                                                        ?>">
                                                        <?php echo strtoupper($resource['file_type']); ?>
                                                    </span>
                                                    <?php if ($resource['category']): ?>
                                                        <span class="badge bg-secondary ms-1"><?php echo htmlspecialchars($resource['category']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="resource-icon 
                                                    <?php 
                                                        switch($resource['file_type']) {
                                                            case 'pdf': echo 'pdf-icon'; break;
                                                            case 'video': echo 'video-icon'; break;
                                                            case 'image': echo 'image-icon'; break;
                                                            default: echo 'document-icon';
                                                        }
                                                    ?>">
                                                    <?php 
                                                        switch($resource['file_type']) {
                                                            case 'pdf': echo '<i class="fas fa-file-pdf fa-2x"></i>'; break;
                                                            case 'video': echo '<i class="fas fa-video fa-2x"></i>'; break;
                                                            case 'image': echo '<i class="fas fa-image fa-2x"></i>'; break;
                                                            default: echo '<i class="fas fa-file-alt fa-2x"></i>';
                                                        }
                                                    ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Resource Preview -->
                                            <div class="resource-preview">
                                                <?php if ($resource['file_type'] == 'video'): ?>
                                                    <video style="width: 100%; height: 100%; object-fit: cover;">
                                                        <source src="<?php echo htmlspecialchars($resource['file_url']); ?>" type="video/mp4">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                <?php else: ?>
                                                    <img src="Images/document-preview.png" style="width: 80px; opacity: 0.7;" alt="Document Preview">
                                                <?php endif; ?>
                                            </div>
                                            
                                            <p class="card-text"><?php echo htmlspecialchars($resource['description']); ?></p>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <div class="text-muted small">
                                                    <i class="fas fa-download me-1"></i> <?php echo $resource['download_count']; ?> downloads
                                                    <br>
                                                    <i class="fas fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($resource['uploaded_at'])); ?>
                                                </div>
                                                <div>
                                                    <?php if ($resource['file_type'] == 'video'): ?>
                                                        <button class="btn-watch btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#videoModal<?php echo $resource['resource_id']; ?>">
                                                            <i class="fas fa-play me-1"></i>Watch
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($is_logged_in): ?>
                                                        <!-- Simple download link like your friend's code -->
                                                        <a href="<?php echo htmlspecialchars($resource['file_url']); ?>" 
                                                           class="btn-download btn-sm"
                                                           download>
                                                            <i class="fas fa-download me-1"></i>Download
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-danger btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#loginRequiredModal">
                                                            <i class="fas fa-download me-1"></i>Download
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Video Modal (for watching videos) -->
                                    <?php if ($resource['file_type'] == 'video'): ?>
                                    <div class="modal fade" id="videoModal<?php echo $resource['resource_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><?php echo htmlspecialchars($resource['title']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="video-container">
                                                        <?php if (strpos($resource['file_url'], 'youtube.com') !== false || strpos($resource['file_url'], 'youtu.be') !== false): ?>
                                                            <?php
                                                            $video_id = '';
                                                            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', 
                                                                           $resource['file_url'], $matches)) {
                                                                $video_id = $matches[1];
                                                            }
                                                            ?>
                                                            <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" 
                                                                    frameborder="0" 
                                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                                    allowfullscreen>
                                                            </iframe>
                                                        <?php else: ?>
                                                            <video controls style="width: 100%; height: 100%;">
                                                                <source src="<?php echo htmlspecialchars($resource['file_url']); ?>" 
                                                                        type="video/mp4">
                                                                Your browser does not support the video tag.
                                                            </video>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="mt-3">
                                                        <h6>Description</h6>
                                                        <p><?php echo htmlspecialchars($resource['description']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <?php if ($is_logged_in): ?>
                                                        <a href="<?php echo htmlspecialchars($resource['file_url']); ?>" 
                                                           class="btn-download"
                                                           download>
                                                            <i class="fas fa-download me-2"></i>Download Video
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-utensil-spoon fa-4x text-muted mb-4"></i>
                                    <h4>No resources available yet</h4>
                                    <p class="mb-4">Check back soon for culinary resources!</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column: Sidebar -->
                <div class="col-lg-4">
                    <!-- Most Popular Downloads -->
                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-fire me-2"></i>Most Popular Downloads</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($popular_resources) > 0): ?>
                                <?php foreach ($popular_resources as $popular): ?>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="resource-icon me-3 
                                            <?php 
                                                switch($popular['file_type']) {
                                                    case 'pdf': echo 'pdf-icon'; break;
                                                    case 'video': echo 'video-icon'; break;
                                                    case 'image': echo 'image-icon'; break;
                                                    default: echo 'document-icon';
                                                }
                                            ?>">
                                            <?php 
                                                switch($popular['file_type']) {
                                                    case 'pdf': echo '<i class="fas fa-file-pdf"></i>'; break;
                                                    case 'video': echo '<i class="fas fa-video"></i>'; break;
                                                    case 'image': echo '<i class="fas fa-image"></i>'; break;
                                                    default: echo '<i class="fas fa-file-alt"></i>';
                                                }
                                            ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($popular['title']); ?></h6>
                                            <p class="text-muted mb-0 small">
                                                <i class="fas fa-download me-1"></i> <?php echo $popular['download_count']; ?> downloads
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">No downloads yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recipe Cards Collection -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-book me-2"></i>Recipe Cards Collection</h5>
                        </div>
                        <div class="card-body">
                            <p>Download and print beautiful recipe cards for your kitchen.</p>
                            <div class="d-grid gap-2">
                                <?php 
                                    $recipe_cards = array_filter($resources, function($resource) {
                                        return $resource['category'] == 'Recipe Cards';
                                    });
                                    $recipe_cards = array_slice($recipe_cards, 0, 3);
                                ?>
                                <?php foreach ($recipe_cards as $card): ?>
                                    <?php if ($is_logged_in): ?>
                                        <a href="<?php echo htmlspecialchars($card['file_url']); ?>" 
                                           class="btn btn-outline-success btn-sm text-start"
                                           download>
                                            <i class="fas fa-file-pdf me-2"></i> <?php echo htmlspecialchars($card['title']); ?>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-outline-success btn-sm text-start" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#loginRequiredModal">
                                            <i class="fas fa-file-pdf me-2"></i> <?php echo htmlspecialchars($card['title']); ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (count($recipe_cards) == 0): ?>
                                    <p class="text-muted small">No recipe cards available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cooking Techniques -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Essential Techniques</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Knife Skills Masterclass</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Sauce Making Basics</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Baking Fundamentals</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Meat Cooking Temperatures</li>
                                <li><i class="fas fa-check text-success me-2"></i>Vegetable Preparation Guide</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Video Tutorials -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Featured Video Tutorials</h2>
            <div class="row g-4">
                <?php 
                    $video_tutorials = array_filter($resources, function($resource) {
                        return $resource['file_type'] == 'video';
                    });
                    $video_tutorials = array_slice($video_tutorials, 0, 3);
                ?>
                <?php if (count($video_tutorials) > 0): ?>
                    <?php foreach ($video_tutorials as $video): ?>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="video-container">
                                    <?php if (strpos($video['file_url'], 'youtube.com') !== false || strpos($video['file_url'], 'youtu.be') !== false): ?>
                                        <?php
                                        $video_id = '';
                                        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', 
                                                       $video['file_url'], $matches)) {
                                            $video_id = $matches[1];
                                        }
                                        ?>
                                        <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" 
                                                frameborder="0" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                allowfullscreen>
                                        </iframe>
                                    <?php else: ?>
                                        <video controls style="width: 100%; height: 100%;">
                                            <source src="<?php echo htmlspecialchars($video['file_url']); ?>" 
                                                    type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h5><?php echo htmlspecialchars($video['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($video['description']); ?></p>
                                    <div class="d-flex justify-content-between">
                                        <span class="badge bg-primary">Video Tutorial</span>
                                        <?php if ($is_logged_in): ?>
                                            <a href="<?php echo htmlspecialchars($video['file_url']); ?>" 
                                               class="btn btn-sm btn-outline-primary"
                                               download>
                                                <i class="fas fa-download me-1"></i>Download
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">No video tutorials available yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Download Stats -->
    <section class="py-5 bg-danger text-white">
        <div class="container text-center">
            <h2 class="mb-4">Join Thousands of Home Cooks</h2>
            <p class="lead mb-4">Our culinary resources have helped home cooks improve their skills and confidence in the kitchen.</p>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h3>10,000+</h3>
                    <p>Active Learners</p>
                </div>
                <div class="col-md-3 mb-4">
                    <i class="fas fa-download fa-3x mb-3"></i>
                    <h3>50,000+</h3>
                    <p>Resources Downloaded</p>
                </div>
                <div class="col-md-3 mb-4">
                    <i class="fas fa-star fa-3x mb-3"></i>
                    <h3>4.8/5</h3>
                    <p>User Rating</p>
                </div>
                <div class="col-md-3 mb-4">
                    <i class="fas fa-clock fa-3x mb-3"></i>
                    <h3>500+</h3>
                    <p>Hours of Content</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Required Modal -->
    <div class="modal fade" id="loginRequiredModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Login Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You need to be logged in to download resources. Please login or sign up for a free account.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="userLogin.php" class="btn btn-danger">
                        <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><i class="fas fa-utensils me-2"></i>FoodFusion</h5>
                    <p>Master the art of cooking with our comprehensive culinary resources.</p>
                    <div class="social-icons mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Resource Categories</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50 text-decoration-none">Recipe Cards</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Video Tutorials</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Cooking Guides</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Kitchen Hacks</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Download Stats</h5>
                    <?php 
                        $total_resources = count($resources);
                        $total_downloads = 0;
                        $video_count = 0;
                        foreach ($resources as $resource) {
                            $total_downloads += $resource['download_count'];
                            if ($resource['file_type'] == 'video') $video_count++;
                        }
                    ?>
                    <p class="mb-1"><?php echo $total_resources; ?> Resources Available</p>
                    <p class="mb-1"><?php echo $total_downloads; ?> Total Downloads</p>
                    <p class="mb-1"><?php echo $video_count; ?> Video Tutorials</p>
                    <p><?php echo count($categories); ?> Categories</p>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 FoodFusion Culinary Resources. All rights reserved.</p>
                <p class="mb-0">
                    <a href="#" class="text-white-50 text-decoration-none me-3">Terms of Use</a>
                    <a href="#" class="text-white-50 text-decoration-none">Privacy Policy</a>
                </p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Filter resources by type and category
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('[data-filter]');
            const categoryBadges = document.querySelectorAll('.category-badge');
            const resourceCards = document.querySelectorAll('#resourcesGrid .col-md-6');
            
            let currentFilter = 'all';
            let currentCategory = 'all';
            
            // Type filter
            filterButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentFilter = this.getAttribute('data-filter');
                    applyFilters();
                });
            });
            
            // Category filter
            categoryBadges.forEach(badge => {
                badge.addEventListener('click', function() {
                    currentCategory = this.getAttribute('data-category');
                    applyFilters();
                    
                    // Update active badge
                    categoryBadges.forEach(b => b.classList.remove('bg-danger'));
                    categoryBadges.forEach(b => b.classList.add('bg-secondary'));
                    this.classList.remove('bg-secondary');
                    this.classList.add('bg-danger');
                });
            });
            
            function applyFilters() {
                resourceCards.forEach(card => {
                    const type = card.getAttribute('data-type');
                    const category = card.getAttribute('data-category');
                    
                    let typeMatch = currentFilter === 'all' || type === currentFilter;
                    let categoryMatch = currentCategory === 'all' || category === currentCategory;
                    
                    if (typeMatch && categoryMatch) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>