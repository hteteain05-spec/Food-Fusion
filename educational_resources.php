<?php
// educational_resources.php - Educational Resources Page
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

// Get educational resources
$sql = "SELECT * FROM resources WHERE resource_type = 'educational' AND is_active = 1 ORDER BY uploaded_at DESC";
$result = $conn->query($sql);
$resources = $result->fetch_all(MYSQLI_ASSOC);

// Get categories for educational resources
$categories_sql = "SELECT DISTINCT category FROM resources WHERE resource_type = 'educational' AND category IS NOT NULL";
$categories_result = $conn->query($categories_sql);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Get popular educational downloads
$popular_sql = "SELECT * FROM resources WHERE resource_type = 'educational' AND is_active = 1 
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
    <title>Educational Resources - FoodFusion</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-educational {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), 
                        url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80');
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
        
        .presentation-icon {
            color: #9b59b6;
        }
        
        .document-icon {
            color: #f39c12;
        }
        
        .course-icon {
            color: #2ecc71;
        }
        
        .btn-download {
            background: linear-gradient(45deg, #3498db, #2ecc71);
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
        
        .btn-enroll {
            background: linear-gradient(45deg, #9b59b6, #e74c3c);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-enroll:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .stats-card {
            background: linear-gradient(45deg, #3498db, #2ecc71);
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
        
        .course-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            height: 100%;
        }
        
        .module-progress {
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            height: 10px;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .module-progress-bar {
            background: white;
            height: 100%;
            border-radius: 20px;
        }
        
        .quiz-card {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
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
    <section class="hero-educational text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Educational Resources</h1>
            <p class="lead mb-4">Enhance your culinary knowledge with courses, guides, and professional training materials.</p>
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
                        <p>Educational Resources</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card text-center" style="background: linear-gradient(45deg, #9b59b6, #e74c3c);">
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
                    <div class="stats-card text-center" style="background: linear-gradient(45deg, #f39c12, #d35400);">
                        <div class="stats-number">
                            <?php 
                                $course_count = 0;
                                foreach ($resources as $resource) {
                                    if ($resource['category'] == 'Courses') $course_count++;
                                }
                                echo $course_count;
                            ?>
                        </div>
                        <p>Online Courses</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card text-center" style="background: linear-gradient(45deg, #2ecc71, #27ae60);">
                        <div class="stats-number">
                            <?php 
                                $certification_count = 0;
                                foreach ($resources as $resource) {
                                    if ($resource['category'] == 'Certifications') $certification_count++;
                                }
                                echo $certification_count;
                            ?>
                        </div>
                        <p>Certifications</p>
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
                        <h3>Learning Materials</h3>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-filter me-2"></i>Filter by Type
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-filter="all">All Types</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="pdf">PDF Guides</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="video">Video Lectures</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="presentation">Presentations</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="course">Online Courses</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Categories -->
                    <div class="mb-4">
                        <h5 class="mb-3">Browse by Category</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-primary category-badge" data-category="all">All Categories</span>
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
                                                                case 'presentation': echo 'bg-purple'; break;
                                                                case 'course': echo 'bg-success'; break;
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
                                                            case 'presentation': echo 'presentation-icon'; break;
                                                            case 'course': echo 'course-icon'; break;
                                                            default: echo 'document-icon';
                                                        }
                                                    ?>">
                                                    <?php 
                                                        switch($resource['file_type']) {
                                                            case 'pdf': echo '<i class="fas fa-file-pdf fa-2x"></i>'; break;
                                                            case 'video': echo '<i class="fas fa-video fa-2x"></i>'; break;
                                                            case 'presentation': echo '<i class="fas fa-desktop fa-2x"></i>'; break;
                                                            case 'course': echo '<i class="fas fa-graduation-cap fa-2x"></i>'; break;
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
                                                <?php elseif ($resource['file_type'] == 'course'): ?>
                                                    <i class="fas fa-graduation-cap fa-4x text-white"></i>
                                                <?php elseif ($resource['file_type'] == 'presentation'): ?>
                                                    <i class="fas fa-desktop fa-4x text-white"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-book fa-4x text-white"></i>
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
                                                        <button class="btn btn-primary btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#videoModal<?php echo $resource['resource_id']; ?>">
                                                            <i class="fas fa-play me-1"></i>Watch
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($is_logged_in): ?>
                                                        <?php if ($resource['file_type'] == 'course'): ?>
                                                            <a href="#" 
                                                               class="btn-enroll btn-sm">
                                                                <i class="fas fa-sign-in-alt me-1"></i>Enroll Now
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="<?php echo htmlspecialchars($resource['file_url']); ?>" 
                                                               class="btn-download btn-sm"
                                                               download>
                                                                <i class="fas fa-download me-1"></i>Download
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <button class="btn btn-primary btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#loginRequiredModal">
                                                            <i class="fas fa-download me-1"></i>Download
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Video Modal (for watching video lectures) -->
                                    <?php if ($resource['file_type'] == 'video'): ?>
                                    <div class="modal fade" id="videoModal<?php echo $resource['resource_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title"><?php echo htmlspecialchars($resource['title']); ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                                                        <h6>Lecture Description</h6>
                                                        <p><?php echo htmlspecialchars($resource['description']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <?php if ($is_logged_in): ?>
                                                        <a href="<?php echo htmlspecialchars($resource['file_url']); ?>" 
                                                           class="btn-download"
                                                           download>
                                                            <i class="fas fa-download me-2"></i>Download Lecture
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
                                    <i class="fas fa-graduation-cap fa-4x text-muted mb-4"></i>
                                    <h4>No educational resources available yet</h4>
                                    <p class="mb-4">Check back soon for learning materials!</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column: Sidebar -->
                <div class="col-lg-4">
                    <!-- Most Popular Downloads -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-fire me-2"></i>Most Popular Learning Materials</h5>
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
                                                    case 'presentation': echo 'presentation-icon'; break;
                                                    case 'course': echo 'course-icon'; break;
                                                    default: echo 'document-icon';
                                                }
                                            ?>">
                                            <?php 
                                                switch($popular['file_type']) {
                                                    case 'pdf': echo '<i class="fas fa-file-pdf"></i>'; break;
                                                    case 'video': echo '<i class="fas fa-video"></i>'; break;
                                                    case 'presentation': echo '<i class="fas fa-desktop"></i>'; break;
                                                    case 'course': echo '<i class="fas fa-graduation-cap"></i>'; break;
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
                    
                    <!-- Featured Courses -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Featured Courses</h5>
                        </div>
                        <div class="card-body">
                            <?php 
                                $featured_courses = array_filter($resources, function($resource) {
                                    return $resource['category'] == 'Courses';
                                });
                                $featured_courses = array_slice($featured_courses, 0, 3);
                            ?>
                            <?php if (count($featured_courses) > 0): ?>
                                <?php foreach ($featured_courses as $course): ?>
                                    <div class="mb-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($course['title']); ?></h6>
                                        <p class="text-muted small mb-2"><?php echo substr(htmlspecialchars($course['description']), 0, 60) . '...'; ?></p>
                                        <?php if ($is_logged_in): ?>
                                            <a href="#" class="btn btn-success btn-sm w-100">
                                                <i class="fas fa-sign-in-alt me-1"></i>Enroll Now
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-success btn-sm w-100" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#loginRequiredModal">
                                                <i class="fas fa-sign-in-alt me-1"></i>Enroll Now
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small">No courses available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Learning Path -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0"><i class="fas fa-road me-2"></i>Learning Path</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Beginner Cooking Basics</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Intermediate Techniques</li>
                                <li class="mb-2"><i class="fas fa-circle text-warning me-2"></i>Advanced Culinary Arts</li>
                                <li class="mb-2"><i class="far fa-circle text-muted me-2"></i>Professional Certification</li>
                                <li><i class="far fa-circle text-muted me-2"></i>Specialized Cuisines</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Courses Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Featured Online Courses</h2>
            <div class="row g-4">
                <?php 
                    $online_courses = array_filter($resources, function($resource) {
                        return $resource['file_type'] == 'course';
                    });
                    $online_courses = array_slice($online_courses, 0, 3);
                ?>
                <?php if (count($online_courses) > 0): ?>
                    <?php foreach ($online_courses as $course): ?>
                        <div class="col-md-4">
                            <div class="course-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h4 class="mb-0"><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <span class="badge bg-light text-dark">New</span>
                                </div>
                                <p class="mb-4"><?php echo substr(htmlspecialchars($course['description']), 0, 100) . '...'; ?></p>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Course Progress</small>
                                        <small>0%</small>
                                    </div>
                                    <div class="module-progress">
                                        <div class="module-progress-bar" style="width: 0%"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small><i class="fas fa-clock me-1"></i> 8 hours</small>
                                    <small class="ms-3"><i class="fas fa-modules me-1"></i> 12 modules</small>
                                </div>
                                
                                <?php if ($is_logged_in): ?>
                                    <a href="#" class="btn btn-light w-100">
                                        <i class="fas fa-play-circle me-2"></i>Start Course
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-light w-100" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#loginRequiredModal">
                                        <i class="fas fa-play-circle me-2"></i>Start Course
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-md-4">
                        <div class="course-card">
                            <h4 class="mb-3">Culinary Fundamentals</h4>
                            <p class="mb-4">Learn the basics of cooking techniques, knife skills, and kitchen safety.</p>
                            <a href="#" class="btn btn-light w-100">Coming Soon</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="course-card">
                            <h4 class="mb-3">Nutrition Science</h4>
                            <p class="mb-4">Understand the science behind nutrition and healthy cooking practices.</p>
                            <a href="#" class="btn btn-light w-100">Coming Soon</a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="course-card">
                            <h4 class="mb-3">Food Safety & Hygiene</h4>
                            <p class="mb-4">Essential knowledge for safe food handling and preparation.</p>
                            <a href="#" class="btn btn-light w-100">Coming Soon</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Knowledge Hub -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Knowledge Hub</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-book fa-3x text-primary mb-3"></i>
                            <h4>Study Guides</h4>
                            <p>Comprehensive guides covering various culinary topics and techniques.</p>
                            <div class="mt-3">
                                <?php 
                                    $guides = array_filter($resources, function($resource) {
                                        return $resource['category'] == 'Study Guides';
                                    });
                                    $guide_count = count($guides);
                                ?>
                                <span class="badge bg-primary"><?php echo $guide_count; ?> Guides</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-chalkboard-teacher fa-3x text-success mb-3"></i>
                            <h4>Webinars</h4>
                            <p>Live and recorded webinars with industry experts and master chefs.</p>
                            <div class="mt-3">
                                <?php 
                                    $webinars = array_filter($resources, function($resource) {
                                        return $resource['category'] == 'Webinars';
                                    });
                                    $webinar_count = count($webinars);
                                ?>
                                <span class="badge bg-success"><?php echo $webinar_count; ?> Webinars</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-certificate fa-3x text-warning mb-3"></i>
                            <h4>Certifications</h4>
                            <p>Prepare for professional culinary certifications and exams.</p>
                            <div class="mt-3">
                                <?php 
                                    $certs = array_filter($resources, function($resource) {
                                        return $resource['category'] == 'Certifications';
                                    });
                                    $cert_count = count($certs);
                                ?>
                                <span class="badge bg-warning"><?php echo $cert_count; ?> Programs</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Learning Statistics -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="mb-4">Join Our Learning Community</h2>
            <p class="lead mb-4">Thousands of students are enhancing their culinary knowledge with our educational resources.</p>
            <div class="row">
                <div class="col-md-3 mb-4">
                    <i class="fas fa-user-graduate fa-3x mb-3"></i>
                    <h3>5,000+</h3>
                    <p>Active Students</p>
                </div>
                <div class="col-md-3 mb-4">
                    <i class="fas fa-book-open fa-3x mb-3"></i>
                    <h3>200+</h3>
                    <p>Learning Materials</p>
                </div>
                <div class="col-md-3 mb-4">
                    <i class="fas fa-certificate fa-3x mb-3"></i>
                    <h3>150+</h3>
                    <p>Certifications Awarded</p>
                </div>
                <div class="col-md-3 mb-4">
                    <i class="fas fa-chalkboard-teacher fa-3x mb-3"></i>
                    <h3>50+</h3>
                    <p>Expert Instructors</p>
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
                    <p>You need to be logged in to access educational resources and enroll in courses. Please login or sign up for a free account.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="userLogin.php" class="btn btn-primary">
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
                    <h5><i class="fas fa-graduation-cap me-2"></i>FoodFusion Education</h5>
                    <p>Advance your culinary career with our comprehensive educational resources and courses.</p>
                    <div class="social-icons mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Learning Categories</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white-50 text-decoration-none">Online Courses</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Certifications</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Study Guides</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Webinars</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Learning Stats</h5>
                    <?php 
                        $total_resources = count($resources);
                        $total_downloads = 0;
                        $course_count = 0;
                        foreach ($resources as $resource) {
                            $total_downloads += $resource['download_count'];
                            if ($resource['category'] == 'Courses') $course_count++;
                        }
                    ?>
                    <p class="mb-1"><?php echo $total_resources; ?> Educational Resources</p>
                    <p class="mb-1"><?php echo $total_downloads; ?> Total Downloads</p>
                    <p class="mb-1"><?php echo $course_count; ?> Online Courses</p>
                    <p><?php echo count($categories); ?> Learning Categories</p>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 FoodFusion Educational Resources. All rights reserved.</p>
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
                    categoryBadges.forEach(b => b.classList.remove('bg-primary'));
                    categoryBadges.forEach(b => b.classList.add('bg-secondary'));
                    this.classList.remove('bg-secondary');
                    this.classList.add('bg-primary');
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