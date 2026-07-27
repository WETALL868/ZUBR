document.querySelectorAll("[data-article-toggle]").forEach((button) => {
  const article = button.closest(".cpu-article");
  const body = article?.querySelector("[data-article-body]");
  const label = button.querySelector("[data-toggle-label]");
  if (!body || !label) {
    return;
  }

  const collapsedHeight = getComputedStyle(body).maxHeight;

  button.addEventListener("click", () => {
    const expanded = body.classList.toggle("is-expanded");
    button.setAttribute("aria-expanded", String(expanded));
    label.textContent = expanded ? "Скрыть описание" : "Показать полностью";
    body.style.maxHeight = expanded ? `${body.scrollHeight}px` : collapsedHeight;

    if (!expanded) {
      article.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
  });
});
