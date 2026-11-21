document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form");
  if (form) {
    form.addEventListener("submit", function (e) {
      const privacyCheck = document.getElementById("privacyCheck");
      if (!privacyCheck.checked) {
        e.preventDefault();
        alert("Please agree to the Privacy Policy before submitting.");
        privacyCheck.focus();
      }
    });
  }
});
