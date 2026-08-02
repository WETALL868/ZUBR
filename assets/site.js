(() => {
  const form = document.querySelector(".appeal-form");
  if (!form) return;

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const button = form.querySelector("button[type='submit'], .submit-button");
    const oldText = button ? button.textContent : "";
    const oldAlert = form.querySelector(".simple-alert");
    if (oldAlert) oldAlert.remove();

    if (button) {
      button.disabled = true;
      button.textContent = "Отправка…";
    }

    try {
      const response = await fetch(form.action, { method: "POST", body: new FormData(form) });
      const payload = await response.json();
      if (!response.ok || !payload.ok) throw new Error(payload.error || "Не удалось отправить обращение.");

      const notice = document.createElement("p");
      notice.className = "simple-alert simple-alert-success";
      notice.setAttribute("role", "status");
      notice.textContent = `Обращение зарегистрировано. Номер: ${payload.publicId}`;
      form.prepend(notice);
      form.reset();
      notice.scrollIntoView({ behavior: "smooth", block: "center" });
    } catch (error) {
      const notice = document.createElement("p");
      notice.className = "simple-alert simple-alert-error";
      notice.setAttribute("role", "alert");
      notice.textContent = error instanceof Error ? error.message : "Не удалось отправить обращение.";
      form.prepend(notice);
    } finally {
      if (button) {
        button.disabled = false;
        button.textContent = oldText;
      }
    }
  });
})();
