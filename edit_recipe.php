<?php
// edit_recipe.php
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
$recipe = null;

// Get recipe ID from URL
$recipe_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch existing recipe data
if ($recipe_id > 0) {
    $sql = "SELECT * FROM recipes WHERE recipe_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $recipe = $result->fetch_assoc();
    } else {
        $message = "Recipe not found!";
        $message_type = "danger";
        $recipe_id = 0;
    }
    $stmt->close();
} else {
    $message = "Invalid recipe ID!";
    $message_type = "danger";
}

// Handle form submit for updating
if ($_SERVER["REQUEST_METHOD"] === "POST" && $recipe_id > 0) {
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
        // Update recipe in database
        $sql = "UPDATE recipes SET 
                title = ?, 
                description = ?, 
                ingredients = ?, 
                instructions = ?, 
                cuisine_type = ?, 
                dietary_preference = ?, 
                difficulty_level = ?, 
                prep_time = ?, 
                cook_time = ?, 
                servings = ?, 
                image_url = ?,
                updated_at = NOW()
                WHERE recipe_id = ?";

        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param(
            "sssssssiissi",
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
            $image_url,
            $recipe_id
        );

        if ($stmt->execute()) {
            // Update local recipe data for form
            $recipe = [
                'title' => $title,
                'description' => $description,
                'ingredients' => $ingredients,
                'instructions' => $instructions,
                'cuisine_type' => $cuisine_type,
                'dietary_preference' => $dietary_preference,
                'difficulty_level' => $difficulty_level,
                'prep_time' => $prep_time,
                'cook_time' => $cook_time,
                'servings' => $servings,
                'image_url' => $image_url
            ];
            
            $message = "Recipe updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating recipe: " . $stmt->error;
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
    <title>Edit Recipe - FoodFusion Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/edit_recipe.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="admin_recipes.php">
                <i class="fas fa-arrow-left me-2"></i>Back to Recipes
            </a>
        </div>
    </nav>

    <div class="container">
        <?php if (!$recipe && $recipe_id > 0): ?>
            <div class="alert alert-danger mt-4">
                <i class="fas fa-exclamation-circle me-2"></i>
                Recipe not found. It may have been deleted.
            </div>
            <div class="text-center mt-4">
                <a href="admin_recipes.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Recipes List
                </a>
            </div>
        <?php elseif ($recipe_id == 0): ?>
            <div class="alert alert-danger mt-4">
                <i class="fas fa-exclamation-circle me-2"></i>
                Invalid recipe ID specified.
            </div>
            <div class="text-center mt-4">
                <a href="admin_recipes.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Recipes List
                </a>
            </div>
        <?php else: ?>
            <div class="recipe-card card mt-4">
                <div class="card-header text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Edit Recipe: 
                        <span class="text-warning"><?php echo htmlspecialchars($recipe['title']); ?></span>
                    </h4>
                    <small>Recipe ID: #<?php echo $recipe_id; ?></small>
                </div>

                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Current Image Preview -->
                    <?php if (!empty($recipe['image_url'])): ?>
                        <div class="mb-4">
                            <label class="form-label">Current Image:</label>
                            <div class="recipe-preview">
                                <img src="<?php echo htmlspecialchars($recipe['image_url']); ?>" 
                                     alt="Current Recipe Image" 
                                     class="current-image mb-2">
                                <div>
                                    <small class="text-muted">
                                        <i class="fas fa-link me-1"></i>
                                        <?php echo htmlspecialchars($recipe['image_url']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <!-- Hidden field to preserve recipe ID -->
                        <input type="hidden" name="recipe_id" value="<?php echo $recipe_id; ?>">

                        <div class="mb-4">
                            <label class="form-label required">Recipe Title</label>
                            <input type="text" 
                                   name="title" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($recipe['title']); ?>" 
                                   required
                                   placeholder="Enter recipe title">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Description</label>
                            <textarea name="description" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="Brief description of the recipe"><?php echo htmlspecialchars($recipe['description']); ?></textarea>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label required">Ingredients</label>
                                <textarea name="ingredients" 
                                          class="form-control" 
                                          rows="8"
                                          required
                                          placeholder="List ingredients with quantities (one per line)"><?php echo htmlspecialchars($recipe['ingredients']); ?></textarea>
                                <small class="text-muted">One ingredient per line</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Instructions</label>
                                <textarea name="instructions" 
                                          class="form-control" 
                                          rows="8"
                                          required
                                          placeholder="Step-by-step cooking instructions"><?php echo htmlspecialchars($recipe['instructions']); ?></textarea>
                                <small class="text-muted">Numbered steps work best</small>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Cuisine Type</label>
                                <input type="text" 
                                       name="cuisine_type" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($recipe['cuisine_type'] ?? ''); ?>"
                                       placeholder="e.g., Italian, Chinese">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Dietary Preference</label>
                                <input type="text" 
                                       name="dietary_preference" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($recipe['dietary_preference'] ?? ''); ?>"
                                       placeholder="e.g., Vegetarian, Gluten-Free">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Difficulty Level</label>
                                <select name="difficulty_level" class="form-select">
                                    <option value="Easy" <?php echo ($recipe['difficulty_level'] ?? 'Medium') == 'Easy' ? 'selected' : ''; ?>>Easy</option>
                                    <option value="Medium" <?php echo ($recipe['difficulty_level'] ?? 'Medium') == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="Hard" <?php echo ($recipe['difficulty_level'] ?? 'Medium') == 'Hard' ? 'selected' : ''; ?>>Hard</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Prep Time (minutes)</label>
                                <input type="number" 
                                       name="prep_time" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($recipe['prep_time'] ?? ''); ?>"
                                       placeholder="e.g., 15"
                                       min="0">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Cook Time (minutes)</label>
                                <input type="number" 
                                       name="cook_time" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($recipe['cook_time'] ?? ''); ?>"
                                       placeholder="e.g., 30"
                                       min="0">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Servings</label>
                                <input type="number" 
                                       name="servings" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($recipe['servings'] ?? ''); ?>"
                                       placeholder="e.g., 4"
                                       min="1">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Image URL</label>
                            <input type="url" 
                                   name="image_url" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($recipe['image_url'] ?? ''); ?>"
                                   placeholder="https://example.com/image.jpg">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Enter a direct link to the recipe image
                            </small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-4 mt-4">
                            <div>
                                <a href="admin_recipes.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <a href="../view_recipe.php?id=<?php echo $recipe_id; ?>" 
                                   class="btn btn-info ms-2" 
                                   target="_blank">
                                    <i class="fas fa-eye me-2"></i>View Recipe
                                </a>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-update">
                                    <i class="fas fa-save me-2"></i>Update Recipe
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-footer bg-light">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-calendar-plus me-1"></i>
                                Created: <?php echo date('F j, Y', strtotime($recipe['created_at'])); ?>
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                <i class="fas fa-eye me-1"></i>
                                Views: <?php echo number_format($recipe['views_count'] ?? 0); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/edit_recipe.js"></script>
</body>
</html>