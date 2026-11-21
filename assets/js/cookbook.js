// Recipe Popup Functions
let currentRecipeId = null;
let currentRecipeData = null;

function openRecipePopup(recipe) {
  currentRecipeId = recipe.contribution_id;
  currentRecipeData = recipe;

  const popup = document.getElementById("recipePopup");
  document.getElementById("popupTitle").textContent = recipe.recipe_title;

  if (recipe.first_name && recipe.last_name) {
    document.getElementById("popupAuthor").textContent =
      `By ${recipe.first_name} ${recipe.last_name} - ${new Date(recipe.submitted_at).toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" })}`;
  } else {
    document.getElementById("popupAuthor").textContent =
      `Submitted: ${new Date(recipe.submitted_at).toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" })}`;
  }

  document.getElementById("popupDescription").innerHTML = formatRecipeContent(
    recipe.recipe_content,
  );

  const popupImage = document.getElementById("popupImage");
  popupImage.src =
    recipe.recipe_photo && recipe.recipe_photo.trim() !== ""
      ? recipe.recipe_photo
      : "https://images.unsplash.com/photo-1565958011703-44f9829ba187?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80";
  popupImage.style.display = "block";

  const tipsDiv = document.getElementById("popupTips");
  if (recipe.tips && recipe.tips.trim() !== "") {
    tipsDiv.innerHTML = `<h3><i class="fas fa-lightbulb me-2 text-warning"></i>Cooking Tips</h3><p>${recipe.tips.replace(/\n/g, "<br>")}</p>`;
    tipsDiv.style.display = "block";
  } else {
    tipsDiv.style.display = "none";
  }

  const expDiv = document.getElementById("popupExperience");
  if (recipe.experience && recipe.experience.trim() !== "") {
    expDiv.innerHTML = `<h3><i class="fas fa-star me-2 text-info"></i>My Experience</h3><p>${recipe.experience.replace(/\n/g, "<br>")}</p>`;
    expDiv.style.display = "block";
  } else {
    expDiv.style.display = "none";
  }

  document.getElementById("popupLikesCount").textContent =
    recipe.total_likes || 0;

  const likeBtn = document.getElementById("popupLikeBtn");
  likeBtn.href = `?like=${recipe.contribution_id}`;
  likeBtn.classList.toggle("liked", recipe.user_liked == 1);

  popup.style.display = "flex";
  document.body.style.overflow = "hidden";
}

function closePopup() {
  const popup = document.getElementById("recipePopup");
  popup.style.display = "none";
  document.body.style.overflow = "auto";
  currentRecipeId = null;
  currentRecipeData = null;
}

function formatRecipeContent(content) {
  if (!content) return "<p>No recipe content available.</p>";

  const lines = content.split("\n");
  let formatted = "";
  let inIngredients = false;
  let inInstructions = false;

  for (let line of lines) {
    line = line.trim();
    if (line === "") continue;

    if (line.toLowerCase().includes("ingredients")) {
      if (inInstructions) formatted += "</ol>";
      formatted += "<h3>Ingredients</h3><ul>";
      inIngredients = true;
      inInstructions = false;
    } else if (
      line.toLowerCase().includes("instructions") ||
      line.toLowerCase().includes("directions") ||
      line.toLowerCase().includes("steps")
    ) {
      if (inIngredients) formatted += "</ul>";
      if (inInstructions) formatted += "</ol>";
      formatted += "<h3>Instructions</h3><ol>";
      inIngredients = false;
      inInstructions = true;
    } else if (inIngredients) {
      formatted += `<li>${line}</li>`;
    } else if (inInstructions) {
      formatted += `<li>${line}</li>`;
    } else {
      if (inIngredients) {
        formatted += "</ul>";
        inIngredients = false;
      }
      if (inInstructions) {
        formatted += "</ol>";
        inInstructions = false;
      }
      formatted += `<p>${line}</p>`;
    }
  }

  if (inIngredients) formatted += "</ul>";
  if (inInstructions) formatted += "</ol>";
  return formatted;
}

function likeRecipe(event) {
  event.preventDefault();
  event.stopPropagation();

  if (document.body.dataset.loggedIn !== "1") {
    alert("Please login to like recipes!");
    return false;
  }

  const likeBtn = event.currentTarget;
  const likesCount = document.getElementById("popupLikesCount");

  if (likeBtn.classList.contains("liked")) {
    likeBtn.classList.remove("liked");
    let count = parseInt(likesCount.textContent);
    likesCount.textContent = Math.max(0, count - 1);
  } else {
    likeBtn.classList.add("liked");
    let count = parseInt(likesCount.textContent);
    likesCount.textContent = count + 1;
  }

  window.location.href = likeBtn.href;
  return false;
}

function confirmDelete(event, recipeId) {
  event.preventDefault();
  event.stopPropagation();

  if (
    confirm(
      "Are you sure you want to delete this recipe? This action cannot be undone.",
    )
  ) {
    window.location.href = "cookbook.php?delete=" + recipeId;
  }
  return false;
}

const recipePopup = document.getElementById("recipePopup");
if (recipePopup) {
  recipePopup.addEventListener("click", function (e) {
    if (e.target === this) closePopup();
  });
}

document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") closePopup();
});

function openEditModal() {
  const modal = new bootstrap.Modal(
    document.getElementById("submitRecipeModal"),
  );
  modal.show();
}

if (document.body.dataset.editMode === "1") {
  document.addEventListener("DOMContentLoaded", openEditModal);
}

function togglePhotoLink() {
  const fileInput = document.getElementById("recipePhoto");
  const urlGroup = document.getElementById("photoUrlGroup");
  if (urlGroup.style.display === "none") {
    urlGroup.style.display = "block";
    fileInput.required = false;
  } else {
    urlGroup.style.display = "none";
    fileInput.required = true;
  }
}

function previewImage(event) {
  const input = event.target;
  const preview = document.getElementById("imagePreview");
  const previewContainer = document.getElementById("imagePreviewContainer");

  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      previewContainer.style.display = "block";
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    previewContainer.style.display = "none";
  }
}

function removeImage() {
  const preview = document.getElementById("imagePreview");
  const previewContainer = document.getElementById("imagePreviewContainer");
  const fileInput = document.getElementById("recipePhoto");

  preview.src = "";
  previewContainer.style.display = "none";
  fileInput.value = "";

  const urlInput = document.querySelector('input[name="photo_url"]');
  if (urlInput) urlInput.value = "";
}

const submitModal = document.getElementById("submitRecipeModal");
if (submitModal) {
  submitModal.addEventListener("shown.bs.modal", function () {
    this.querySelector('input[name="title"]').focus();
  });

  submitModal.addEventListener("hidden.bs.modal", function () {
    removeImage();
    const urlGroup = document.getElementById("photoUrlGroup");
    if (urlGroup) urlGroup.style.display = "none";
  });
}

if (document.body.dataset.editParam === "1") {
  document.addEventListener("DOMContentLoaded", function () {
    const myRecipesTab = document.getElementById("myrecipes-tab");
    if (myRecipesTab) {
      const tab = new bootstrap.Tab(myRecipesTab);
      tab.show();
    }
  });
}
