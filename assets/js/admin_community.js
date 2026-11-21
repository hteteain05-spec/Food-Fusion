$(document).ready(function () {
  $("#postsTable").DataTable({
    pageLength: 25,
    order: [[4, "desc"]],
    language: {
      search: "Search within table:",
      lengthMenu: "Show _MENU_ posts per page",
      info: "Showing _START_ to _END_ of _TOTAL_ posts",
    },
  });

  $("#selectAll").on("change", function () {
    $(".post-checkbox").prop("checked", this.checked);
  });

  $("#bulkForm").on("submit", function (e) {
    const selectedPosts = [];
    $(".post-checkbox:checked").each(function () {
      selectedPosts.push($(this).val());
    });

    if (selectedPosts.length === 0) {
      alert("Please select at least one post to delete.");
      e.preventDefault();
      return false;
    }

    selectedPosts.forEach(function (postId) {
      $("#bulkForm").append(
        '<input type="hidden" name="selected_posts[]" value="' + postId + '">',
      );
    });

    return confirm(
      "Are you sure you want to delete " + selectedPosts.length + " post(s)?",
    );
  });

  $("#viewPostModal").on("show.bs.modal", function (event) {
    const button = $(event.relatedTarget);
    const postId = button.data("id");
    const title = button.data("title");
    const content = button.data("content");
    const tips = button.data("tips");
    const experience = button.data("experience");
    const photo = button.data("photo");
    const author = button.data("author");
    const email = button.data("email");
    const date = button.data("date");
    const status = button.data("status");
    const likes = button.data("likes");

    $("#modalPostTitle").text(title);
    $("#modalPostContent").text(content);
    $("#modalPostTips").text(tips || "No tips provided");
    $("#modalPostExperience").text(experience || "No experience shared");
    $("#modalPostAuthor").text(author);
    $("#modalPostEmail").text(email);
    $("#modalPostDate").text(date);
    $("#modalPostLikes").html(
      '<i class="fas fa-heart text-danger"></i> ' + likes,
    );
    $("#modalDeleteLink").attr(
      "href",
      "admin_community.php?delete_id=" + postId,
    );

    const statusBadge =
      status === "Approved"
        ? '<span class="badge badge-approved">' + status + "</span>"
        : '<span class="badge badge-rejected">' + status + "</span>";
    $("#modalPostStatus").html(statusBadge);

    const imageContainer = $("#modalPostImage");
    if (photo && photo !== "NULL" && photo.trim() !== "") {
      imageContainer.html(
        '<img src="' + photo + '" class="post-image-modal" alt="Recipe Image">',
      );
    } else {
      imageContainer.html(
        '<p class="text-muted"><i class="fas fa-image me-2"></i>No image available</p>',
      );
    }

    $("#modalPostTipsSection").toggle(tips && tips.trim() !== "");
    $("#modalPostExperienceSection").toggle(
      experience && experience.trim() !== "",
    );
  });

  setTimeout(() => $(".alert").alert("close"), 5000);
});
