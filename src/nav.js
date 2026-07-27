document.querySelectorAll("[data-nav-toggle]").forEach((toggle) => {
  const panelId = toggle.getAttribute("aria-controls");
  const panel = panelId ? document.getElementById(panelId) : null;
  if (!panel) {
    return;
  }

  function openPanel() {
    panel.classList.add("is-open");
    toggle.setAttribute("aria-expanded", "true");
  }

  function closePanel() {
    panel.classList.remove("is-open");
    toggle.setAttribute("aria-expanded", "false");
  }

  toggle.addEventListener("click", () => {
    if (panel.classList.contains("is-open")) {
      closePanel();
    } else {
      openPanel();
    }
  });

  panel.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", closePanel);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && panel.classList.contains("is-open")) {
      closePanel();
      toggle.focus();
    }
  });

  document.addEventListener("click", (event) => {
    if (!panel.classList.contains("is-open")) {
      return;
    }
    if (panel.contains(event.target) || toggle.contains(event.target)) {
      return;
    }
    closePanel();
  });

  window.addEventListener("resize", () => {
    // Must match the CSS breakpoint where the hamburger stops being used,
    // otherwise the panel keeps an .is-open class the layout no longer honours.
    if (window.innerWidth > 1060 && panel.classList.contains("is-open")) {
      closePanel();
    }
  });
});

// ---------------------------------------------------------------------------
// Catalog dropdown.
//
// CSS already opens it on :hover and :focus-within, which covers mouse and
// keyboard. This adds the explicit toggle needed on touch screens (no hover)
// and inside the mobile panel, where the dropdown renders as an accordion.
// ---------------------------------------------------------------------------
document.querySelectorAll("[data-nav-group]").forEach((group) => {
  const trigger = group.querySelector("[data-nav-group-trigger]");
  const menu = group.querySelector("[data-nav-group-menu]");
  if (!trigger || !menu) {
    return;
  }

  function close() {
    group.classList.remove("is-open");
    trigger.setAttribute("aria-expanded", "false");
  }

  function open() {
    group.classList.add("is-open");
    trigger.setAttribute("aria-expanded", "true");
  }

  trigger.addEventListener("click", (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (group.classList.contains("is-open")) {
      close();
    } else {
      open();
    }
  });

  menu.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", close);
  });

  document.addEventListener("click", (event) => {
    if (group.classList.contains("is-open") && !group.contains(event.target)) {
      close();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && group.classList.contains("is-open")) {
      close();
      trigger.focus();
    }
  });
});
