(function () {
  document
    .querySelectorAll(".site-readiness-check-trigger")
    .forEach(function (button) {
      button.addEventListener("click", function () {
        var expanded = button.getAttribute("aria-expanded") === "true";
        var panel = document.getElementById(
          button.getAttribute("aria-controls"),
        );
        button.setAttribute("aria-expanded", !expanded);
        panel.hidden = expanded;
      });
    });

  var toggle = document.querySelector(".site-readiness-passed-toggle");
  if (!toggle) {
    return;
  }

  toggle.addEventListener("click", function () {
    var accordion = toggle.nextElementSibling;
    var expanded = toggle.getAttribute("aria-expanded") === "true";
    toggle.setAttribute("aria-expanded", !expanded);
    accordion.classList.toggle("hidden");
    toggle
      .querySelector(".dashicons")
      .classList.toggle("dashicons-arrow-down-alt2");
    toggle
      .querySelector(".dashicons")
      .classList.toggle("dashicons-arrow-up-alt2");
  });
})();
