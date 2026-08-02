/*
 * Клиентские сценарии сайта СНП «Новая Искань».
 *
 * Сайт отдаётся как статические страницы, поэтому весь интерактив собран
 * в одном небольшом файле без внешних библиотек:
 *   1. Мобильное меню
 *   2. Переключатели категорий (документы и новости)
 *   3. Cookie-уведомление
 *   4. Отправка обращения и окно с номером
 *
 * Разметка всех блоков присутствует в HTML и без скрипта: карточки
 * документов и новостей видны, форма отправляется обычной отправкой.
 * Скрипт только улучшает поведение.
 */
(() => {
  'use strict';

  /* ================================================================ *
   * 1. Мобильное меню
   * ================================================================ */

  const setupMobileMenu = () => {
    const menu = document.querySelector('.mobile-menu');
    if (!menu) return;

    const summary = menu.querySelector('summary');
    const panel = menu.querySelector('nav');
    if (!summary || !panel) return;

    let backdrop = null;

    const isOpen = () => menu.hasAttribute('open');

    const close = ({ returnFocus = true } = {}) => {
      document.body.classList.remove('menu-open');
      if (backdrop) {
        backdrop.remove();
        backdrop = null;
      }
      summary.setAttribute('aria-expanded', 'false');
      summary.setAttribute('aria-label', 'Открыть меню');
      if (isOpen()) menu.removeAttribute('open');
      if (returnFocus) summary.focus();
    };

    const open = () => {
      document.body.classList.add('menu-open');
      summary.setAttribute('aria-expanded', 'true');
      summary.setAttribute('aria-label', 'Закрыть меню');

      backdrop = document.createElement('button');
      backdrop.type = 'button';
      backdrop.className = 'menu-backdrop';
      backdrop.setAttribute('aria-label', 'Закрыть меню');
      // Нажатие вне панели закрывает меню.
      backdrop.addEventListener('click', () => close());
      menu.appendChild(backdrop);

      // Кнопка закрытия добавляется один раз и живёт внутри панели.
      if (!panel.querySelector('.menu-close')) {
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'menu-close';
        closeButton.setAttribute('aria-label', 'Закрыть меню');
        closeButton.innerHTML = '&times;';
        closeButton.addEventListener('click', () => close());
        panel.prepend(closeButton);
      }

      const firstLink = panel.querySelector('a');
      if (firstLink) firstLink.focus();
    };

    if (!panel.id) panel.id = 'mobile-nav';
    summary.setAttribute('aria-expanded', 'false');
    summary.setAttribute('aria-controls', panel.id);

    menu.addEventListener('toggle', () => {
      if (isOpen()) open();
      else close({ returnFocus: false });
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && isOpen()) {
        event.preventDefault();
        close();
      }
    });

    // Переход по пункту меню закрывает панель — в том числе для ссылок-якорей,
    // когда страница не перезагружается.
    panel.addEventListener('click', (event) => {
      if (event.target instanceof Element && event.target.closest('a')) {
        close({ returnFocus: false });
      }
    });

    // Если экран расширили до десктопного, меню не должно остаться открытым.
    const wide = window.matchMedia('(min-width: 821px)');
    const onWide = (event) => {
      if (event.matches) close({ returnFocus: false });
    };
    if (typeof wide.addEventListener === 'function') wide.addEventListener('change', onWide);
  };

  /* ================================================================ *
   * 2. Переключатели категорий
   *
   * Работают и на странице документов, и на странице новостей.
   * Выбранная категория пишется в адрес страницы (?category=roads),
   * поэтому её можно скопировать, открыть заново и вернуться кнопкой
   * «Назад» браузера.
   * ================================================================ */

  const ALL = 'all';

  /**
   * @param {HTMLElement} group
   */
  const setupFilterGroup = (group) => {
    const param = group.dataset.filterParam || 'category';
    const buttons = /** @type {HTMLButtonElement[]} */ ([
      ...group.querySelectorAll('button[data-category]'),
    ]);
    const items = [...group.querySelectorAll('[data-item-category]')];
    if (!buttons.length || !items.length) return;

    const summary = group.querySelector('[data-filter-summary]');
    const emptyNotice = group.querySelector('[data-filter-empty]');
    const known = new Set(buttons.map((button) => button.dataset.category));

    /**
     * Приводит значение из адреса к существующей категории.
     * Неизвестное значение не ломает страницу — показывается полный список.
     * @param {string | null} value
     * @returns {string}
     */
    const normalise = (value) => {
      if (!value) return ALL;
      const lower = value.toLowerCase();
      return known.has(lower) ? lower : ALL;
    };

    /**
     * @param {string} category
     */
    const apply = (category) => {
      let shown = 0;

      for (const item of items) {
        const itemCategories = (item.getAttribute('data-item-category') || '')
          .split(/\s+/)
          .filter(Boolean);
        const visible = category === ALL || itemCategories.includes(category);
        item.toggleAttribute('hidden', !visible);
        if (visible) shown += 1;
      }

      for (const button of buttons) {
        const selected = button.dataset.category === category;
        button.classList.toggle('selected', selected);
        button.setAttribute('aria-pressed', selected ? 'true' : 'false');
      }

      // Пустая категория: вместо голого экрана показывается пояснение.
      if (emptyNotice) emptyNotice.toggleAttribute('hidden', shown !== 0);

      if (summary) {
        const active = buttons.find((button) => button.dataset.category === category);
        const name = active ? (active.textContent || '').trim() : 'Все документы';
        summary.textContent =
          shown === 0
            ? `В категории «${name}» пока нет материалов.`
            : `Показано материалов: ${shown}${category === ALL ? '' : ` · категория «${name}»`}`;
      }

      group.dataset.activeCategory = category;
    };

    const readFromLocation = () =>
      normalise(new URLSearchParams(window.location.search).get(param));

    for (const button of buttons) {
      button.addEventListener('click', () => {
        const category = button.dataset.category || ALL;
        apply(category);

        // Адрес обновляется без перезагрузки; «Назад» возвращает прошлую категорию.
        const url = new URL(window.location.href);
        if (category === ALL) url.searchParams.delete(param);
        else url.searchParams.set(param, category);
        window.history.pushState({ [param]: category }, '', url);
      });
    }

    window.addEventListener('popstate', () => apply(readFromLocation()));

    apply(readFromLocation());
  };

  /* ================================================================ *
   * 3. Cookie-уведомление
   * ================================================================ */

  const COOKIE_KEY = 'novaya-iskan-cookie-choice';

  const setupCookieBanner = () => {
    const banner = document.querySelector('.cookie-banner');
    if (!banner) return;

    let stored = null;
    try {
      stored = window.localStorage.getItem(COOKIE_KEY);
    } catch {
      // Приватный режим браузера: уведомление просто показывается снова.
      stored = null;
    }

    if (stored) return;

    banner.removeAttribute('hidden');

    /**
     * @param {string} choice
     */
    const remember = (choice) => {
      try {
        window.localStorage.setItem(COOKIE_KEY, choice);
      } catch {
        // Сохранить выбор не удалось — не повод оставлять баннер на экране.
      }
      banner.setAttribute('hidden', '');
    };

    for (const button of banner.querySelectorAll('[data-cookie-choice]')) {
      button.addEventListener('click', () =>
        remember(button.getAttribute('data-cookie-choice') || 'accepted'),
      );
    }
  };

  /* ================================================================ *
   * 4. Отправка обращения
   * ================================================================ */

  const setupAppealForm = () => {
    const form = /** @type {HTMLFormElement | null} */ (document.querySelector('.appeal-form'));
    if (!form) return;

    const dialog = /** @type {HTMLDialogElement | null} */ (
      document.querySelector('#appeal-success')
    );
    const numberSlot = document.querySelector('[data-appeal-number]');
    const button = /** @type {HTMLButtonElement | null} */ (
      form.querySelector("button[type='submit'], .submit-button")
    );

    let sending = false;

    /**
     * @param {string} message
     * @param {'error' | 'success'} kind
     */
    const showAlert = (message, kind) => {
      const previous = form.querySelector('.simple-alert');
      if (previous) previous.remove();

      const notice = document.createElement('p');
      notice.className = `simple-alert simple-alert-${kind}`;
      notice.setAttribute('role', kind === 'error' ? 'alert' : 'status');
      notice.textContent = message;
      form.prepend(notice);
      notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    const closeDialog = () => {
      if (!dialog) return;
      if (typeof dialog.close === 'function' && dialog.open) dialog.close();
      else dialog.removeAttribute('open');
      dialog.classList.remove('modal-fallback');
    };

    /**
     * Окно открывается только с номером, полученным от сервера.
     * @param {string} publicId
     */
    const openDialog = (publicId) => {
      if (!dialog) {
        showAlert(`Обращение зарегистрировано. Номер: ${publicId}`, 'success');
        return;
      }

      if (numberSlot) numberSlot.textContent = publicId;

      if (typeof dialog.showModal === 'function') {
        dialog.showModal();
      } else {
        // Запасной путь для браузера без поддержки showModal().
        dialog.classList.add('modal-fallback');
        dialog.setAttribute('open', '');
      }

      const closeButton = dialog.querySelector('button');
      if (closeButton) closeButton.focus();
    };

    if (dialog) {
      for (const closer of dialog.querySelectorAll('[data-close-modal]')) {
        closer.addEventListener('click', closeDialog);
      }

      // Штатный <dialog> закрывается по Escape сам; в запасном режиме — вручную.
      document.addEventListener('keydown', (event) => {
        if (
          event.key === 'Escape' &&
          dialog.hasAttribute('open') &&
          dialog.classList.contains('modal-fallback')
        ) {
          event.preventDefault();
          closeDialog();
        }
      });
    }

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      // Защита от двойной отправки: повторные нажатия игнорируются,
      // пока не пришёл ответ сервера.
      if (sending) return;
      if (!form.reportValidity()) return;

      sending = true;
      const previousLabel = button ? button.textContent : '';
      if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = 'Отправка…';
      }

      const previousAlert = form.querySelector('.simple-alert');
      if (previousAlert) previousAlert.remove();

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { Accept: 'application/json' },
        });

        /** @type {{ ok?: boolean, publicId?: string, error?: string }} */
        let payload = {};
        try {
          payload = await response.json();
        } catch {
          throw new Error('Сервер вернул неожиданный ответ. Попробуйте позже.');
        }

        // Окно успеха показывается только после реального сохранения.
        if (!response.ok || !payload.ok || !payload.publicId) {
          throw new Error(payload.error || 'Не удалось отправить обращение.');
        }

        // Форма очищается только после подтверждённого сохранения на сервере.
        form.reset();
        openDialog(payload.publicId);
      } catch (error) {
        showAlert(
          error instanceof Error ? error.message : 'Не удалось отправить обращение.',
          'error',
        );
      } finally {
        sending = false;
        if (button) {
          button.disabled = false;
          button.removeAttribute('aria-busy');
          button.textContent = previousLabel;
        }
      }
    });
  };

  /* ================================================================ *
   * Запуск
   * ================================================================ */

  const start = () => {
    setupMobileMenu();
    for (const group of document.querySelectorAll('[data-filter-group]')) {
      setupFilterGroup(/** @type {HTMLElement} */ (group));
    }
    setupCookieBanner();
    setupAppealForm();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
