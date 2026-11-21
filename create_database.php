<?php
// create_database.php
// Run this file in your browser to create the database and tables

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>FoodFusion Database Setup</h2>";
echo "<pre>";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP password is empty
$database = 'foodfusion_db';

try {
    // Create connection
    $conn = new mysqli($host, $username, $password);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected to MySQL successfully.\n";
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $database 
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "Database '$database' created successfully or already exists.\n";
    } else {
        echo "Error creating database: " . $conn->error . "\n";
    }
    
    // Select the database
    $conn->select_db($database);
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        failed_login_attempts INT DEFAULT 0,
        account_locked_until TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT TRUE,
        user_role ENUM('user', 'admin') DEFAULT 'user'
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'users' created successfully.\n";
    } else {
        echo "Error creating users table: " . $conn->error . "\n";
    }
    
    // Create recipes table
    $sql = "CREATE TABLE IF NOT EXISTS recipes (
        recipe_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        ingredients TEXT NOT NULL,
        instructions TEXT NOT NULL,
        cuisine_type VARCHAR(50),
        dietary_preference VARCHAR(50),
        difficulty_level ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Medium',
        prep_time INT,
        cook_time INT,
        servings INT,
        image_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        views_count INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'recipes' created successfully.\n";
    } else {
        echo "Error creating recipes table: " . $conn->error . "\n";
    }
    
    // Create categories table
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        category_id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'categories' created successfully.\n";
    } else {
        echo "Error creating categories table: " . $conn->error . "\n";
    }
    
    // Create recipe_categories junction table (many-to-many)
    $sql = "CREATE TABLE IF NOT EXISTS recipe_categories (
        recipe_id INT,
        category_id INT,
        PRIMARY KEY (recipe_id, category_id),
        FOREIGN KEY (recipe_id) REFERENCES recipes(recipe_id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'recipe_categories' created successfully.\n";
    } else {
        echo "Error creating recipe_categories table: " . $conn->error . "\n";
    }
    
    // Create community_cookbook table
    $sql = "CREATE TABLE IF NOT EXISTS community_cookbook (
        contribution_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        recipe_title VARCHAR(200) NOT NULL,
        recipe_content TEXT NOT NULL,
        tips TEXT,
        experience TEXT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        likes_count INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'community_cookbook' created successfully.\n";
    } else {
        echo "Error creating community_cookbook table: " . $conn->error . "\n";
    }
    
    // Create contact_messages table
    $sql = "CREATE TABLE IF NOT EXISTS contact_messages (
        message_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        message_type ENUM('inquiry', 'recipe_request', 'feedback', 'other') DEFAULT 'inquiry',
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_read BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'contact_messages' created successfully.\n";
    } else {
        echo "Error creating contact_messages table: " . $conn->error . "\n";
    }
    
    // Create resources table (for culinary and educational resources)
    $sql = "CREATE TABLE IF NOT EXISTS resources (
        resource_id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        file_url VARCHAR(500) NOT NULL,
        file_type ENUM('pdf', 'video', 'image', 'document') NOT NULL,
        resource_type ENUM('culinary', 'educational') NOT NULL,
        category VARCHAR(100),
        download_count INT DEFAULT 0,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'resources' created successfully.\n";
    } else {
        echo "Error creating resources table: " . $conn->error . "\n";
    }
    
    // Create events table (for cooking events carousel)
    $sql = "CREATE TABLE IF NOT EXISTS events (
        event_id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        event_time TIME,
        location VARCHAR(200),
        image_url VARCHAR(500),
        max_participants INT,
        registered_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'events' created successfully.\n";
    } else {
        echo "Error creating events table: " . $conn->error . "\n";
    }
    
    // Insert sample categories
    $categories = [
        ['Italian', 'Traditional Italian cuisine'],
        ['Mexican', 'Spicy Mexican dishes'],
        ['Vegetarian', 'Plant-based recipes'],
        ['Desserts', 'Sweet treats and desserts'],
        ['Quick & Easy', 'Recipes under 30 minutes']
    ];
    
    foreach ($categories as $category) {
        $name = $conn->real_escape_string($category[0]);
        $desc = $conn->real_escape_string($category[1]);
        
        $sql = "INSERT IGNORE INTO categories (name, description) 
                VALUES ('$name', '$desc')";
        $conn->query($sql);
    }
    
    echo "Sample categories inserted.\n";
    
    // Insert sample events
    $events = [
        ['Italian Cooking Masterclass', 'Learn authentic Italian cooking techniques', '2024-12-15', '14:00:00', 'London Cooking School', 'italian_event.jpg', 20],
        ['Vegan Baking Workshop', 'Plant-based baking for beginners', '2024-12-20', '10:00:00', 'Online Event', 'vegan_baking.jpg', 50],
        ['Christmas Feast Preparation', 'Prepare the perfect Christmas dinner', '2024-12-10', '16:00:00', 'Community Center', 'christmas_feast.jpg', 30]
    ];
    
    foreach ($events as $event) {
        $title = $conn->real_escape_string($event[0]);
        $desc = $conn->real_escape_string($event[1]);
        $date = $event[2];
        $time = $event[3];
        $location = $conn->real_escape_string($event[4]);
        $image = $conn->real_escape_string($event[5]);
        $max = $event[6];
        
        $sql = "INSERT INTO events (title, description, event_date, event_time, location, image_url, max_participants) 
                VALUES ('$title', '$desc', '$date', '$time', '$location', '$image', $max)";
        $conn->query($sql);
    }
    
    echo "Sample events inserted.\n";
    
    // Insert sample resources
    $resources = [
        ['Basic Knife Skills Guide', 'Learn essential knife techniques', 'knife_skills.pdf', 'pdf', 'culinary', 'Tutorials'],
        ['Energy Saving Cooking Tips', 'Cook efficiently and save energy', 'energy_tips.pdf', 'pdf', 'educational', 'Tips'],
        ['Pasta Making Tutorial', 'Step-by-step pasta making video', 'pasta_tutorial.mp4', 'video', 'culinary', 'Video Tutorials']
    ];
    
    foreach ($resources as $resource) {
        $title = $conn->real_escape_string($resource[0]);
        $desc = $conn->real_escape_string($resource[1]);
        $file = $conn->real_escape_string($resource[2]);
        $type = $conn->real_escape_string($resource[3]);
        $rtype = $conn->real_escape_string($resource[4]);
        $cat = $conn->real_escape_string($resource[5]);
        
        $sql = "INSERT INTO resources (title, description, file_url, file_type, resource_type, category) 
                VALUES ('$title', '$desc', '$file', '$type', '$rtype', '$cat')";
        $conn->query($sql);
    }
    
    echo "Sample resources inserted.\n";
    
    // Create admin user (password: Admin123!)
    $admin_email = 'admin@foodfusion.com';
    $admin_pass = password_hash('Admin123!', PASSWORD_DEFAULT);
    
    $sql = "INSERT IGNORE INTO users (first_    name, last_name, email, password_hash, user_role) 
            VALUES ('Admin', 'User', '$admin_email', '$admin_pass', 'admin')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Admin user created.\n";
        echo "Admin Email: $admin_email\n";
        echo "Admin Password: Admin123!\n";
    }
    
    $conn->close();
    
    echo "\n========================================\n";
    echo "DATABASE SETUP COMPLETED SUCCESSFULLY!\n";
    echo "========================================\n";
    echo "\nYou can now access the database with:\n";
    echo "Database: $database\n";
    echo "Username: $username\n";
    echo "Password: $password\n";
    
} catch (Exception $e) {
    echo "Error during database setup: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>