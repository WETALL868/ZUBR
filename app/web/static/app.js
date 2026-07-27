// Минимальный набор клиентских скриптов.

// Адрес страницы без служебных параметров сообщения.
function urlWithoutFlash() {
  var url = new URL(window.location.href);
  url.searchParams.delete('msg');
  url.searchParams.delete('kind');
  return url.pathname + (url.search ? url.search : '') + url.hash;
}

// 1. Сообщение о результате действия не должно оставаться в адресной строке:
//    иначе оно снова появится при обновлении страницы и будет выглядеть
//    как незавершённая операция.
(function () {
  var params = new URLSearchParams(window.location.search);
  if (!params.has('msg') && !params.has('kind')) return;
  window.history.replaceState({}, document.title, urlWithoutFlash());
})();

// 2. Автообновление страницы, пока выполняется фоновая задача.
//    После завершения открываем страницу уже без старого сообщения.
(function () {
  var indicator = document.querySelector('[data-job]');
  if (!indicator) return;
  var target = urlWithoutFlash();
  var timer = setInterval(function () {
    fetch('/api/jobs')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var stillRunning = Object.keys(data).some(function (k) { return data[k].running; });
        if (!stillRunning) {
          clearInterval(timer);
          window.location.replace(target);
        }
      })
      .catch(function () { clearInterval(timer); });
  }, 3000);
})();

// 3. Подтверждение перед долгими операциями.
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (event) {
    if (!window.confirm(form.getAttribute('data-confirm'))) {
      event.preventDefault();
    }
  });
});

// 4. Сброс фильтров.
document.querySelectorAll('[data-reset-filters]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    window.location.href = window.location.pathname;
  });
});
