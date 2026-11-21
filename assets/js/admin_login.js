document
  .getElementById("togglePassword")
  .addEventListener("click", function () {
    const passwordInput = document.getElementById("password");
    const icon = this.querySelector("i");

    if (passwordInput.type === "password") {
      passwordInput.type = "text";
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
    } else {
      passwordInput.type = "password";
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
    }
  });

document.addEventListener("DOMContentLoaded", function () {
  document.getElementById("email").focus();
});

setTimeout(function () {
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach(function (alert) {
    const bsAlert = new bootstrap.Alert(alert);
    bsAlert.close();
  });
}, 5000);

document.addEventListener("keypress", function (e) {
  if (e.key === "Enter" && !e.target.matches("textarea")) {
    e.preventDefault();
    document.querySelector('button[name="admin_login"]').click();
  }
});
