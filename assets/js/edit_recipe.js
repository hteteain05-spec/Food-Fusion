document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form");
  if (form) {
    form.addEventListener("keypress", function (e) {
      if (e.key === "Enter") e.preventDefault();
    });
  }
});
