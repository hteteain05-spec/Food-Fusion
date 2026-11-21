$(document).ready(function () {
  $("#usersTable").DataTable({
    pageLength: 25,
    order: [[5, "desc"]],
    language: {
      search: "Search within table:",
      lengthMenu: "Show _MENU_ users per page",
      info: "Showing _START_ to _END_ of _TOTAL_ users",
    },
  });

  $("#selectAll").on("change", function () {
    $(".user-checkbox").prop("checked", this.checked);
  });

  $("#bulkForm").on("submit", function (e) {
    const selectedUsers = [];
    $(".user-checkbox:checked").each(function () {
      selectedUsers.push($(this).val());
    });

    if (selectedUsers.length === 0) {
      alert("Please select at least one user to delete.");
      e.preventDefault();
      return false;
    }

    selectedUsers.forEach(function (userId) {
      $("#bulkForm").append(
        '<input type="hidden" name="selected_users[]" value="' + userId + '">',
      );
    });

    return true;
  });

  setTimeout(() => $(".alert").alert("close"), 5000);
});
