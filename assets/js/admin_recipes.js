$(document).ready(function () {
  if (
    $("#recipesTable tbody tr td").length > 1 &&
    !$("#recipesTable tbody tr td").hasClass("text-center")
  ) {
    $("#recipesTable").DataTable({
      pageLength: 10,
      order: [[0, "desc"]],
      language: {
        search: "Search recipes:",
        lengthMenu: "Show _MENU_ recipes per page",
        info: "Showing _START_ to _END_ of _TOTAL_ recipes",
      },
    });
  }

  setTimeout(function () {
    $(".alert").alert("close");
  }, 5000);
});
