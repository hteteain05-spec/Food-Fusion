<?php
// add_recipe.php
session_start();

// Admin login check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "foodfusion_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    $cuisine_type = $_POST['cuisine_type'] ?: NULL;
    $dietary_preference = $_POST['dietary_preference'] ?: NULL;
    $difficulty_level = $_POST['difficulty_level'] ?? 'Medium';
    $prep_time = $_POST['prep_time'] ?: NULL;
    $cook_time = $_POST['cook_time'] ?: NULL;
    $servings = $_POST['servings'] ?: NULL;
    $image_url = $_POST['image_url'] ?: NULL;

    if (empty($title) || empty($ingredients) || empty($instructions)) {
        $message = "Please fill in all required fields.";
        $message_type = "danger";
    } else {

        $sql = "INSERT INTO recipes 
        (user_id, title, description, ingredients, instructions, cuisine_type, dietary_preference, difficulty_level, prep_time, cook_time, servings, image_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $admin_id = $_SESSION['admin_id'] ?? 1;

        $stmt->bind_param(
            "isssssssiiss",
            $admin_id,
            $title,
            $description,
            $ingredients,
            $instructions,
            $cuisine_type,
            $dietary_preference,
            $difficulty_level,
            $prep_time,
            $cook_time,
            $servings,
            $image_url
        );

        if ($stmt->execute()) {
            $message = "Recipe added successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . $stmt->error;
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
    <title>Add Recipe - FoodFusion Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Recipe</h4>
        </div>

        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Recipe Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ingredients *</label>
                    <textarea name="ingredients" class="form-control" rows="4" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Instructions *</label>
                    <textarea name="instructions" class="form-control" rows="5" required></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cuisine Type</label>
                        <input type="text" name="cuisine_type" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Dietary Preference</label>
                        <input type="text" name="dietary_preference" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Difficulty</label>
                        <select name="difficulty_level" class="form-select">
                            <option value="Easy">Easy</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="Hard">Hard</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Prep Time (min)</label>
                        <input type="number" name="prep_time" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cook Time (min)</label>
                        <input type="number" name="cook_time" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Servings</label>
                        <input type="number" name="servings" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Image URL</label>
                    <input type="url" name="image_url" class="form-control">
                </div>

                <div class="d-flex justify-content-between">
                    <a href="admin_recipes.php" class="btn btn-secondary">Back</a>
                    <button type="submit" class="btn btn-success">Add Recipe</button>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>
