// Минимальный набор клиентских скриптов.

// 1. Автообновление страницы, пока выполняется фоновая задача.
(function () {
  var indicator = document.querySelector('[data-job]');
  if (!indicator) return;
  var timer = setInterval(function () {
    fetch('/api/jobs')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var stillRunning = Object.keys(data).some(function (k) { return data[k].running; });
        if (!stillRunning) {
          clearInterval(timer);
          window.location.reload();
        }
      })
      .catch(function () { clearInterval(timer); });
  }, 3000);
})();

// 2. Подтверждение перед долгими операциями.
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (event) {
    if (!window.confirm(form.getAttribute('data-confirm'))) {
      event.preventDefault();
    }
  });
});

// 3. Сброс фильтров.
document.querySelectorAll('[data-reset-filters]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    window.location.href = window.location.pathname;
  });
});
