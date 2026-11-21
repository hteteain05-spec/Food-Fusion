document.addEventListener("DOMContentLoaded", function () {
  const filterSelects = document.querySelectorAll(
    'select[name="cuisine"], select[name="diet"], select[name="difficulty"]',
  );
  filterSelects.forEach((select) => {
    select.addEventListener("change", function () {
      this.form.submit();
    });
  });
});
