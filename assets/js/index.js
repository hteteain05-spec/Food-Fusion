function hideCookieBanner() {
  document.querySelector(".cookie-consent").style.display = "none";
}

const modalAction = document.body.dataset.modalAction;
const successMessage = document.body.dataset.successMessage;

if (modalAction === "register") {
  window.onload = function () {
    new bootstrap.Modal(document.getElementById("registerModal")).show();
  };
} else if (modalAction === "login") {
  window.onload = function () {
    new bootstrap.Modal(document.getElementById("loginModal")).show();
  };
}

if (successMessage) {
  window.onload = function () {
    alert(successMessage);
    const registerModal = bootstrap.Modal.getInstance(
      document.getElementById("registerModal"),
    );
    const loginModal = bootstrap.Modal.getInstance(
      document.getElementById("loginModal"),
    );
    if (registerModal) registerModal.hide();
    if (loginModal) loginModal.hide();
    setTimeout(function () {
      window.location.reload();
    }, 1000);
  };
}
