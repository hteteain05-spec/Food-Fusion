$(document).ready(function () {
  $("table").DataTable({
    pageLength: 25,
    order: [[7, "desc"]],
    language: {
      search: "Filter messages:",
      lengthMenu: "Show _MENU_ messages",
      info: "Showing _START_ to _END_ of _TOTAL_ messages",
      paginate: {
        previous: "<i class='fas fa-chevron-left'></i>",
        next: "<i class='fas fa-chevron-right'></i>",
      },
    },
    dom: '<"top"f>rt<"bottom"lip><"clear">',
  });
});

function toggleSelectAll(source) {
  const checkboxes = document.getElementsByClassName("message-checkbox");
  for (let checkbox of checkboxes) checkbox.checked = source.checked;
}

function selectAll() {
  const checkboxes = document.getElementsByClassName("message-checkbox");
  for (let checkbox of checkboxes) checkbox.checked = true;
  document.getElementById("selectAllCheckbox").checked = true;
}

function deselectAll() {
  const checkboxes = document.getElementsByClassName("message-checkbox");
  for (let checkbox of checkboxes) checkbox.checked = false;
  document.getElementById("selectAllCheckbox").checked = false;
}

function confirmBulkAction() {
  const action = document.getElementById("bulkAction").value;
  const checkboxes = document.getElementsByClassName("message-checkbox");
  let checkedCount = 0;
  for (let checkbox of checkboxes) if (checkbox.checked) checkedCount++;

  if (checkedCount === 0) {
    alert("Please select at least one message.");
    return false;
  }
  if (!action) {
    alert("Please select a bulk action.");
    return false;
  }
  if (action === "delete") {
    return confirm(
      `Are you sure you want to delete ${checkedCount} selected message(s)? This action cannot be undone.`,
    );
  }
  return true;
}

function exportMessages() {
  const filters = [];
  const status = document.body.dataset.statusFilter;
  const type = document.body.dataset.typeFilter;
  const search = document.body.dataset.searchTerm;

  if (status !== "all") filters.push(`status=${status}`);
  if (type !== "all") filters.push(`type=${type}`);
  if (search) filters.push(`search=${encodeURIComponent(search)}`);

  alert(
    "Export feature would generate CSV/Excel file with current filters:\n" +
      "Status: " +
      (status === "all" ? "All" : status) +
      "\n" +
      "Type: " +
      (type === "all" ? "All" : type) +
      "\n" +
      "Search: " +
      (search || "None"),
  );
}

setInterval(function () {
  const unreadCount = Number(document.body.dataset.unreadCount || 0);
  const unreadBadge = document.querySelector(".stats-card:nth-child(2) h3");
  if (
    unreadBadge &&
    parseInt(unreadBadge.textContent.replace(/,/g, "")) != unreadCount
  ) {
    location.reload();
  }
}, 30000);
