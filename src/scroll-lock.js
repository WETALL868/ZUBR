/**
 * Блокировка прокрутки страницы под модальными окнами.
 *
 * ЗАЧЕМ ЭТО ПОЯВИЛОСЬ. Раньше окна просто вешали на <body> класс с
 * `overflow: hidden`. На телефоне это не работает: прокручивается не <body>,
 * а корневой элемент, и на iOS Safari `overflow: hidden` у body вообще не
 * останавливает прокрутку. В результате при открытом окне палец прокручивал
 * невидимую страницу под ним, а после закрытия пользователь оказывался совсем
 * в другом месте каталога — со стороны это выглядело как зависание.
 *
 * Что делает этот модуль:
 *   - запоминает текущую позицию прокрутки;
 *   - переводит body в position: fixed со сдвигом на эту позицию, из-за чего
 *     прокрутка физически невозможна на любом браузере, включая iOS Safari;
 *   - при снятии блокировки возвращает страницу ровно туда, где она была.
 *
 * Блокировка со счётчиком: корзина, быстрый просмотр, галерея и окно успешного
 * заказа могут открываться друг поверх друга. Разблокировка происходит только
 * когда закрылось последнее окно. releaseAll() — аварийный сброс.
 */
(function () {
  "use strict";

  var holders = [];
  var savedY = 0;
  var locked = false;

  function applyLock() {
    if (locked) {
      return;
    }
    savedY = window.scrollY || window.pageYOffset || 0;
    document.body.style.top = "-" + savedY + "px";
    document.body.classList.add("scroll-locked");
    locked = true;
  }

  function releaseLock() {
    if (!locked) {
      return;
    }
    document.body.classList.remove("scroll-locked");
    document.body.style.top = "";
    locked = false;
    // Возвращаем страницу на прежнее место мгновенно: html имеет
    // scroll-behavior: smooth, и без "instant" страница уезжала бы с анимацией.
    window.scrollTo({ top: savedY, left: 0, behavior: "instant" });
  }

  var ScrollLock = {
    /** Заблокировать прокрутку от имени окна с именем name. */
    lock: function (name) {
      if (holders.indexOf(name) === -1) {
        holders.push(name);
      }
      applyLock();
    },

    /** Снять блокировку от имени окна name. Разблокирует, если оно последнее. */
    unlock: function (name) {
      var i = holders.indexOf(name);
      if (i !== -1) {
        holders.splice(i, 1);
      }
      if (!holders.length) {
        releaseLock();
      }
    },

    /** Аварийный сброс: снять всё, что бы ни оставалось. */
    releaseAll: function () {
      holders = [];
      releaseLock();
    },

    isLocked: function () {
      return locked;
    },

    holders: function () {
      return holders.slice();
    },
  };

  window.ScrollLock = ScrollLock;

  // -------------------------------------------------------------------------
  // Страховки: ни при каких обстоятельствах страница не должна остаться
  // заблокированной. Каждый из этих случаев когда-то приводил к "зависанию".
  // -------------------------------------------------------------------------

  // Возврат назад из bfcache (iOS Safari почти всегда отдаёт страницу оттуда):
  // окна уже закрыты разметкой, но класс блокировки мог сохраниться в снимке.
  window.addEventListener("pageshow", function (event) {
    if (event.persisted && locked) {
      ScrollLock.releaseAll();
    }
  });

  // Уход со страницы по ссылке: следующая страница не должна унаследовать
  // заблокированный body, если браузер восстановит её из кэша.
  window.addEventListener("pagehide", function () {
    if (locked) {
      document.body.classList.remove("scroll-locked");
      document.body.style.top = "";
    }
  });

  // Поворот телефона и смена размера окна: пересчитываем сдвиг, иначе после
  // поворота страница возвращается не на то место.
  window.addEventListener("orientationchange", function () {
    if (locked) {
      document.body.style.top = "-" + savedY + "px";
    }
  });
})();
