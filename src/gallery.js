/**
 * ProductGallery — единый просмотр фотографий товара с увеличением.
 *
 * Один компонент на весь сайт: его используют и карточки каталога, и
 * отдельные страницы товаров. Разметку создаёт сам, поэтому подключается
 * на любой странице одним тегом <script> без правки HTML.
 *
 * Как открыть:
 *     ProductGallery.open([{ src: "/public/images/products/e5-2690-v4.jpg",
 *                            alt: "Процессор Intel Xeon E5-2690 V4" }], 0);
 *
 * Что умеет:
 *   - показывает фото целиком, по центру, без обрезки и без растягивания;
 *   - закрытие: крестик, тап по фону, свайп вниз, Escape, кнопка «назад»;
 *   - несколько фото: свайп влево/вправо, стрелки, точки-индикатор,
 *     предзагрузка соседних кадров;
 *   - на телефоне работает масштабирование щипком (touch-action: pinch-zoom);
 *   - прокрутка страницы блокируется через ScrollLock и восстанавливается
 *     ровно на прежнем месте.
 *
 * Сейчас у товара одна фотография, но принимает массив — чтобы добавить
 * вторую и третью, достаточно передать их в open(), ничего не переделывая.
 */
(function () {
  "use strict";

  var LOCK_NAME = "gallery";
  var root = null;
  var els = {};
  var images = [];
  var index = 0;
  var lastFocused = null;
  var touch = { x: 0, y: 0, active: false };

  function build() {
    if (root) {
      return;
    }

    root = document.createElement("div");
    root.className = "pgallery";
    root.setAttribute("aria-hidden", "true");
    root.setAttribute("role", "dialog");
    root.setAttribute("aria-modal", "true");
    root.setAttribute("aria-label", "Фотография товара");
    root.hidden = true;

    // Верхняя панель с крестиком — отдельной полосой НАД фотографией, чтобы
    // кнопка никогда не перекрывала товар. Отступы учитывают вырез и
    // системные элементы телефона через env(safe-area-inset-*).
    var bar = document.createElement("div");
    bar.className = "pgallery-bar";

    els.counter = document.createElement("span");
    els.counter.className = "pgallery-counter";

    els.close = document.createElement("button");
    els.close.type = "button";
    els.close.className = "pgallery-close";
    els.close.setAttribute("aria-label", "Закрыть фотографию");
    els.close.innerHTML = "&times;";

    bar.append(els.counter, els.close);

    els.stage = document.createElement("div");
    els.stage.className = "pgallery-stage";

    els.image = document.createElement("img");
    els.image.className = "pgallery-image";
    els.image.decoding = "async";
    els.stage.appendChild(els.image);

    els.prev = document.createElement("button");
    els.prev.type = "button";
    els.prev.className = "pgallery-nav pgallery-prev";
    els.prev.setAttribute("aria-label", "Предыдущая фотография");
    els.prev.innerHTML = "&#8249;";

    els.next = document.createElement("button");
    els.next.type = "button";
    els.next.className = "pgallery-nav pgallery-next";
    els.next.setAttribute("aria-label", "Следующая фотография");
    els.next.innerHTML = "&#8250;";

    els.dots = document.createElement("div");
    els.dots.className = "pgallery-dots";

    root.append(bar, els.prev, els.stage, els.next, els.dots);
    document.body.appendChild(root);

    bindEvents();
  }

  function bindEvents() {
    els.close.addEventListener("click", close);
    els.prev.addEventListener("click", function () { show(index - 1); });
    els.next.addEventListener("click", function () { show(index + 1); });

    // Тап мимо фотографии закрывает — куда угодно, кроме самой картинки и
    // элементов управления. Проверять «строго фон» было мало: на телефоне
    // фона вокруг фотографии остаётся несколько пикселей, и попасть по нему
    // пальцем практически невозможно.
    root.addEventListener("click", function (event) {
      if (event.target.closest(".pgallery-image, .pgallery-close, .pgallery-nav, .pgallery-dot")) {
        return;
      }
      close();
    });

    document.addEventListener("keydown", function (event) {
      if (root.hidden) {
        return;
      }
      if (event.key === "Escape") {
        event.preventDefault();
        close();
      } else if (event.key === "ArrowLeft") {
        show(index - 1);
      } else if (event.key === "ArrowRight") {
        show(index + 1);
      }
    });

    // Свайпы: влево/вправо — соседнее фото, вниз — закрыть.
    els.stage.addEventListener("touchstart", function (event) {
      if (event.touches.length !== 1) {
        touch.active = false; // два пальца — это масштабирование, не свайп
        return;
      }
      touch.x = event.touches[0].clientX;
      touch.y = event.touches[0].clientY;
      touch.active = true;
    }, { passive: true });

    els.stage.addEventListener("touchend", function (event) {
      if (!touch.active || !event.changedTouches.length) {
        return;
      }
      touch.active = false;
      var dx = event.changedTouches[0].clientX - touch.x;
      var dy = event.changedTouches[0].clientY - touch.y;

      if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) {
        show(index + (dx < 0 ? 1 : -1));
      } else if (dy > 90 && Math.abs(dy) > Math.abs(dx)) {
        close();
      }
    }, { passive: true });

    // Кнопка «назад» в браузере закрывает галерею, а не уводит со страницы.
    window.addEventListener("popstate", function () {
      if (!root.hidden) {
        close({ fromHistory: true });
      }
    });

    // Возврат из кэша браузера (частый случай на iOS): галерея не должна
    // остаться открытой поверх страницы.
    window.addEventListener("pageshow", function (event) {
      if (event.persisted && !root.hidden) {
        close({ silent: true });
      }
    });
  }

  function preload(i) {
    if (i < 0 || i >= images.length) {
      return;
    }
    var img = new Image();
    img.src = images[i].src;
  }

  function show(i) {
    if (!images.length) {
      return;
    }
    // По кругу: с последнего кадра свайп влево возвращает на первый.
    index = (i + images.length) % images.length;
    var item = images[index];

    els.image.src = item.src;
    els.image.alt = item.alt || "";

    var many = images.length > 1;
    els.prev.hidden = !many;
    els.next.hidden = !many;
    els.dots.hidden = !many;
    els.counter.textContent = many ? index + 1 + " / " + images.length : "";

    if (many) {
      els.dots.textContent = "";
      images.forEach(function (_, n) {
        var dot = document.createElement("button");
        dot.type = "button";
        dot.className = "pgallery-dot" + (n === index ? " is-active" : "");
        dot.setAttribute("aria-label", "Фотография " + (n + 1));
        dot.addEventListener("click", function () { show(n); });
        els.dots.appendChild(dot);
      });
      preload(index + 1);
      preload(index - 1);
    }
  }

  function open(list, startIndex) {
    var items = (Array.isArray(list) ? list : [list])
      .map(function (item) {
        return typeof item === "string" ? { src: item, alt: "" } : item;
      })
      .filter(function (item) { return item && item.src; });

    if (!items.length) {
      return;
    }

    build();
    images = items;
    lastFocused = document.activeElement;

    show(startIndex || 0);

    root.hidden = false;
    root.setAttribute("aria-hidden", "false");
    // Класс вешается следующим кадром, чтобы сработал плавный переход.
    window.requestAnimationFrame(function () {
      root.classList.add("is-open");
    });

    if (window.ScrollLock) {
      window.ScrollLock.lock(LOCK_NAME);
    }
    // preventScroll обязателен: обычный focus() прокручивает страницу к
    // элементу, и после закрытия галереи она оказывалась не на своём месте.
    els.close.focus({ preventScroll: true });
  }

  function close(options) {
    if (!root || root.hidden) {
      return;
    }

    root.classList.remove("is-open");
    root.hidden = true;
    root.setAttribute("aria-hidden", "true");
    els.image.removeAttribute("src");
    els.image.alt = "";

    if (window.ScrollLock) {
      window.ScrollLock.unlock(LOCK_NAME);
    }

    if (!(options && options.silent) && lastFocused && lastFocused.focus) {
      lastFocused.focus({ preventScroll: true });
    }
    lastFocused = null;
  }

  window.ProductGallery = {
    open: open,
    close: close,
    isOpen: function () { return !!root && !root.hidden; },
  };
})();
