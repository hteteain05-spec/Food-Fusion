document.addEventListener("DOMContentLoaded", function () {
  const filterButtons = document.querySelectorAll("[data-filter]");
  const categoryBadges = document.querySelectorAll(".category-badge");
  const resourceCards = document.querySelectorAll("#resourcesGrid .col-md-6");

  let currentFilter = "all";
  let currentCategory = "all";

  filterButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      currentFilter = this.getAttribute("data-filter");
      applyFilters();
    });
  });

  categoryBadges.forEach((badge) => {
    badge.addEventListener("click", function () {
      currentCategory = this.getAttribute("data-category");
      applyFilters();
      categoryBadges.forEach((b) => b.classList.remove("bg-danger"));
      categoryBadges.forEach((b) => b.classList.add("bg-secondary"));
      this.classList.remove("bg-secondary");
      this.classList.add("bg-danger");
    });
  });

  function applyFilters() {
    resourceCards.forEach((card) => {
      const type = card.getAttribute("data-type");
      const category = card.getAttribute("data-category");
      const typeMatch = currentFilter === "all" || type === currentFilter;
      const categoryMatch =
        currentCategory === "all" || category === currentCategory;
      card.style.display = typeMatch && categoryMatch ? "block" : "none";
    });
  }
});
