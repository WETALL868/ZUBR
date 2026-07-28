/*
 * Панель работает и без JavaScript: любая форма отправляется обычным POST.
 * Здесь только удобства — вкладки, подтверждение удаления и подстановка
 * адреса из названия.
 */
(function () {
  "use strict";

  /* Вкладки карточки товара: переключение без перезагрузки. */
  document.querySelectorAll("[data-tabs]").forEach(function (group) {
    var buttons = group.querySelectorAll("[data-tab]");
    var panels = document.querySelectorAll("[data-panel]");

    buttons.forEach(function (button) {
      button.addEventListener("click", function () {
        var name = button.getAttribute("data-tab");
        buttons.forEach(function (b) { b.classList.toggle("active", b === button); });
        panels.forEach(function (p) { p.hidden = p.getAttribute("data-panel") !== name; });
        try { history.replaceState(null, "", "#" + name); } catch (e) { /* не важно */ }
      });
    });

    var wanted = location.hash.replace("#", "");
    if (wanted) {
      var target = group.querySelector('[data-tab="' + wanted + '"]');
      if (target) { target.click(); }
    }
  });

  /* Удаление и другие необратимые действия спрашивают подтверждение. */
  document.querySelectorAll("[data-confirm]").forEach(function (element) {
    element.addEventListener("click", function (event) {
      if (!window.confirm(element.getAttribute("data-confirm"))) {
        event.preventDefault();
      }
    });
  });

  /*
   * Адрес страницы подставляется из названия, но только пока поле пустое:
   * у существующего товара менять адрес автоматически нельзя — по нему уже
   * ходят ссылки и поисковые системы.
   */
  var source = document.querySelector("[data-slug-source]");
  var target = document.querySelector("[data-slug-target]");
  if (source && target && target.value === "") {
    source.addEventListener("input", function () {
      if (target.dataset.touched === "1") { return; }
      target.value = translit(source.value);
    });
    target.addEventListener("input", function () { target.dataset.touched = "1"; });
  }

  var MAP = {
    а:"a",б:"b",в:"v",г:"g",д:"d",е:"e",ё:"e",ж:"zh",з:"z",и:"i",й:"y",к:"k",л:"l",
    м:"m",н:"n",о:"o",п:"p",р:"r",с:"s",т:"t",у:"u",ф:"f",х:"h",ц:"c",ч:"ch",ш:"sh",
    щ:"sch",ъ:"",ы:"y",ь:"",э:"e",ю:"yu",я:"ya"
  };

  function translit(text) {
    return text
      .toLowerCase()
      .split("")
      .map(function (ch) { return MAP.hasOwnProperty(ch) ? MAP[ch] : ch; })
      .join("")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "");
  }
})();
