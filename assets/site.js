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
   * 3.5. Общее поведение больших окон
   *
   * Одинаково работает и для схемы проезда, и для просмотра документа:
   * Escape закрывает окно средствами самого <dialog>, нажатие по
   * затемнённому фону — вручную, фокус возвращается на элемент,
   * с которого окно открыли.
   * ================================================================ */

  /**
   * @param {HTMLDialogElement} dialog
   */
  const setupDialog = (dialog) => {
    for (const closer of dialog.querySelectorAll('[data-close-modal]')) {
      closer.addEventListener('click', () => dialog.close());
    }

    // Нажатие по затемнённому фону: клик приходит на сам <dialog>,
    // а не на его содержимое, поэтому сравниваем цель события.
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) dialog.close();
    });

    // Запасной путь для браузеров без showModal(): там Escape
    // и фон обрабатываются вручную.
    document.addEventListener('keydown', (event) => {
      if (
        event.key === 'Escape' &&
        dialog.hasAttribute('open') &&
        dialog.classList.contains('modal-fallback')
      ) {
        event.preventDefault();
        closeDialogElement(dialog);
      }
    });
  };

  /**
   * @param {HTMLDialogElement} dialog
   */
  const openDialogElement = (dialog) => {
    if (typeof dialog.showModal === 'function') {
      dialog.showModal();
    } else {
      dialog.classList.add('modal-fallback');
      dialog.setAttribute('open', '');
    }
  };

  /**
   * @param {HTMLDialogElement} dialog
   */
  const closeDialogElement = (dialog) => {
    if (typeof dialog.close === 'function' && dialog.open) dialog.close();
    else dialog.removeAttribute('open');
    dialog.classList.remove('modal-fallback');
  };

  /* ================================================================ *
   * 3.6. Схема проезда крупнее
   * ================================================================ */

  const setupMap = () => {
    const dialog = /** @type {HTMLDialogElement | null} */ (document.querySelector('#map-modal'));
    const openers = document.querySelectorAll('[data-open-map]');
    if (!dialog || !openers.length) return;

    setupDialog(dialog);

    for (const opener of openers) {
      opener.addEventListener('click', () => {
        openDialogElement(dialog);
        const close = dialog.querySelector('[data-close-modal]');
        if (close instanceof HTMLElement) close.focus();
      });
    }

    // Штатный <dialog> сам возвращает фокус на элемент, с которого его
    // открыли; в запасном режиме это делается вручную.
    dialog.addEventListener('close', () => {
      const opener = document.querySelector('[data-open-map]');
      if (opener instanceof HTMLElement) opener.focus();
    });
  };

  /* ================================================================ *
   * 3.7. Просмотр документа
   * ================================================================ */

  const setupDocumentViewer = () => {
    const dialog = /** @type {HTMLDialogElement | null} */ (
      document.querySelector('#document-viewer')
    );
    const buttons = document.querySelectorAll('[data-view-document]');
    if (!dialog || !buttons.length) return;

    const title = dialog.querySelector('#document-viewer-title');
    const body = dialog.querySelector('[data-viewer-body]');
    const actions = dialog.querySelector('[data-viewer-actions]');
    const closeButton = dialog.querySelector('[data-close-modal]');

    /**
     * Ссылки действий создаются при открытии документа и удаляются
     * при закрытии: держать в разметке заготовки с href="#" нельзя,
     * это переход в никуда.
     * @param {string} file
     */
    const buildActions = (file) => {
      if (!actions) return;
      for (const stale of actions.querySelectorAll('a')) stale.remove();

      const download = document.createElement('a');
      download.href = file;
      download.setAttribute('download', '');
      download.dataset.viewerDownload = '';
      download.textContent = 'Скачать';

      const tab = document.createElement('a');
      tab.href = file;
      tab.target = '_blank';
      tab.rel = 'noopener';
      tab.dataset.viewerTab = '';
      tab.textContent = 'Открыть в новой вкладке';

      actions.prepend(download, tab);
    };

    /** Элемент, с которого открыли окно: на него возвращается фокус. */
    let lastOpener = null;

    setupDialog(dialog);

    dialog.addEventListener('close', () => {
      // Тяжёлый PDF выгружается, чтобы не висел в памяти после закрытия.
      if (body) body.innerHTML = '';
      if (actions) for (const stale of actions.querySelectorAll('a')) stale.remove();
      if (lastOpener instanceof HTMLElement) lastOpener.focus();
    });

    /**
     * Сообщение вместо просмотра: когда браузер не умеет показывать PDF
     * внутри страницы или файл не открылся.
     * @param {string} heading
     * @param {string} text
     * @param {string} file
     */
    const showNotice = (heading, text, file) => {
      if (!body) return;
      body.innerHTML = '';

      const notice = document.createElement('p');
      notice.className = 'viewer-notice';

      const strong = document.createElement('strong');
      strong.textContent = heading;
      notice.append(strong, document.createTextNode(text));

      const link = document.createElement('a');
      link.className = 'button button-gold';
      link.href = file;
      link.target = '_blank';
      link.rel = 'noopener';
      link.textContent = 'Открыть в новой вкладке';
      notice.append(document.createElement('br'), link);

      body.append(notice);
    };

    /**
     * Может ли браузер показать PDF прямо в окне.
     *
     * Chrome для Android и большинство мобильных браузеров этого
     * не умеют: вместо документа появляется пустая рамка или файл
     * молча уходит в загрузки. Поэтому на телефонах и планшетах
     * документ всегда показывается страницами-картинками.
     */
    const canEmbedPdf = () => {
      if (window.matchMedia('(max-width: 820px), (pointer: coarse)').matches) return false;
      return navigator.pdfViewerEnabled !== false;
    };

    /**
     * Постраничный просмотр картинками — то, что видит телефон.
     * Страницы подгружаются по мере прокрутки, поэтому открытие
     * документа стоит одной страницы, а не всех двадцати четырёх.
     *
     * @param {Element} button кнопка «Посмотреть» со сведениями о страницах
     * @param {string} file адрес исходного PDF
     * @returns {boolean} удалось ли построить просмотр
     */
    const showPages = (button, file) => {
      const dir = button.getAttribute('data-pages-dir');
      const total = Number(button.getAttribute('data-pages'));
      if (!body || !dir || !Number.isFinite(total) || total < 1) return false;

      const width = Number(button.getAttribute('data-page-width')) || 1000;
      const height = Number(button.getAttribute('data-page-height')) || 1414;

      body.innerHTML = '';

      const list = document.createElement('div');
      list.className = 'viewer-pages';

      for (let number = 1; number <= total; number += 1) {
        const figure = document.createElement('figure');
        figure.className = 'viewer-page';

        const image = document.createElement('img');
        image.src = `${dir}/${String(number).padStart(2, '0')}.webp`;
        image.alt = `Страница ${number} из ${total}`;
        image.width = width;
        image.height = height;
        // Первая страница нужна сразу, остальные — по мере прокрутки.
        image.loading = number === 1 ? 'eager' : 'lazy';
        image.decoding = 'async';

        if (number === 1) {
          // Если не открылась даже первая страница, показывать пустое
          // окно нельзя: посетитель должен увидеть, что делать дальше.
          image.addEventListener('error', () => {
            showNotice(
              'Документ не открылся',
              ' Страницы документа не загрузились. Попробуйте скачать файл или сообщите администратору.',
              file,
            );
          });
        }

        const caption = document.createElement('figcaption');
        caption.textContent = `Страница ${number} из ${total}`;

        figure.append(image, caption);
        list.append(figure);
      }

      body.append(list);

      /*
       * «Открыть в новой вкладке» здесь лишняя: документ уже показан
       * страницами, а мобильный браузер по этой ссылке всё равно
       * скачал бы PDF — то же, что и «Скачать». На узком экране
       * длинная надпись занимала треть верхней полосы.
       */
      actions?.querySelector('[data-viewer-tab]')?.remove();

      return true;
    };

    for (const button of buttons) {
      button.addEventListener('click', async () => {
        const file = button.getAttribute('data-file');
        const name = button.getAttribute('data-title') || 'Документ';
        if (!file) return;

        lastOpener = button;
        if (title) title.textContent = name;
        buildActions(file);
        if (body) body.innerHTML = '';

        openDialogElement(dialog);
        if (closeButton instanceof HTMLElement) closeButton.focus();

        // Файл проверяется до показа: встроенный просмотр не сообщает
        // об ошибке сам, и посетитель увидел бы пустое серое окно.
        let available = false;
        try {
          const response = await fetch(file, { method: 'HEAD' });
          available = response.ok && (response.headers.get('content-type') || '').includes('pdf');
          if (!response.ok) {
            showNotice(
              'Документ не открылся',
              ` Сервер ответил кодом ${response.status}. Попробуйте скачать файл или сообщите администратору.`,
              file,
            );
            return;
          }
        } catch {
          showNotice(
            'Документ не открылся',
            ' Не удалось связаться с сервером. Проверьте подключение и попробуйте ещё раз.',
            file,
          );
          return;
        }

        // Телефон и планшет получают документ страницами-картинками:
        // встроенную рамку с PDF они не отрисовывают.
        if (!canEmbedPdf()) {
          if (showPages(button, file)) return;

          // Картинок страниц нет (документ добавили, но не выполнили
          // npm run documents:pages) — остаётся отдельная вкладка.
          showNotice(
            'Просмотр внутри страницы недоступен',
            ' Браузер не умеет показывать PDF на странице. Документ откроется отдельной вкладкой.',
            file,
          );
          return;
        }

        if (!available || !body) return;

        const frame = document.createElement('iframe');
        frame.className = 'viewer-frame';
        // Документ открывается с первой страницы и вписывается в окно
        // целиком: FitH растягивал страницу по ширине, и посетитель
        // видел увеличенный угол вместо всего листа.
        frame.src = `${file}#page=1&view=Fit`;
        frame.title = name;
        body.innerHTML = '';
        body.append(frame);
      });
    }
  };

  /* ================================================================ *
   * 4. Отправка обращения
   * ================================================================ */

  /** Предел размера вложения — тот же, что проверяет сервер. */
  const MAX_ATTACHMENT_BYTES = 5 * 1024 * 1024;

  /**
   * Текст для случая, когда сервер ответил не JSON. Техническое
   * «Unexpected token '<'» посетителю показывать нельзя.
   */
  const UNEXPECTED_ANSWER =
    'Не удалось отправить обращение. Сервер вернул некорректный ответ. ' +
    'Попробуйте ещё раз или сообщите администратору.';

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

    /**
     * Проверка вложения на стороне браузера — до отправки, чтобы не гонять
     * лишние мегабайты по мобильной сети. Те же правила повторно проверяет
     * сервер: на клиентскую проверку полагаться нельзя.
     */
    const checkAttachment = () => {
      const field = /** @type {HTMLInputElement | null} */ (
        form.querySelector('input[type="file"][name="attachment"]')
      );
      const file = field && field.files && field.files[0];
      if (!file) return null;

      const allowed = ['application/pdf', 'image/jpeg', 'image/png'];
      const byExtension = /\.(pdf|jpe?g|png)$/i.test(file.name);
      if (file.type && !allowed.includes(file.type) && !byExtension) {
        return 'Разрешены только PDF, JPG и PNG.';
      }
      if (file.size > MAX_ATTACHMENT_BYTES) {
        const size = (file.size / 1024 / 1024).toFixed(1);
        return `Файл весит ${size} МБ. Размер вложения не должен превышать 5 МБ.`;
      }
      if (file.size === 0) return 'Выбранный файл пустой.';
      return null;
    };

    /**
     * Разбор ответа сервера.
     *
     * Вызывать response.json() безусловно нельзя: если сервер по какой-то
     * причине вернёт HTML (страница ошибки хостинга, перенаправление на
     * вход), разбор упадёт с техническим текстом вида
     * «Unexpected token '<'», непонятным посетителю.
     *
     * @param {Response} response
     * @returns {Promise<{ ok?: boolean, appealNumber?: string, publicId?: string, error?: string }>}
     */
    const readAnswer = async (response) => {
      const type = (response.headers.get('content-type') || '').toLowerCase();
      const body = (await response.text()).trim();

      if (!type.includes('application/json')) {
        // Сервер ответил не тем форматом — показываем понятную причину
        // и оставляем подробности в консоли для разбора.
        console.error(
          `Ответ от ${response.url}: статус ${response.status}, тип «${type || 'не указан'}».`,
          body.slice(0, 300),
        );
        throw new Error(UNEXPECTED_ANSWER);
      }

      if (body === '') throw new Error(UNEXPECTED_ANSWER);

      try {
        return JSON.parse(body);
      } catch {
        console.error(`Не удалось разобрать ответ от ${response.url}:`, body.slice(0, 300));
        throw new Error(UNEXPECTED_ANSWER);
      }
    };

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      // Защита от двойной отправки: повторные нажатия игнорируются,
      // пока не пришёл ответ сервера.
      if (sending) return;
      if (!form.reportValidity()) return;

      const previousAlert = form.querySelector('.simple-alert');
      if (previousAlert) previousAlert.remove();

      const attachmentProblem = checkAttachment();
      if (attachmentProblem) {
        showAlert(attachmentProblem, 'error');
        return;
      }

      sending = true;
      const previousLabel = button ? button.textContent : '';
      if (button) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = 'Отправляем…';
      }

      try {
        // Content-Type для multipart/form-data выставляет сам браузер:
        // он добавляет границу разделителя, вручную его задавать нельзя.
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { Accept: 'application/json' },
        });

        const payload = await readAnswer(response);
        const number = payload.appealNumber || payload.publicId;

        // Окно успеха показывается только после реального сохранения.
        if (!response.ok || !payload.ok || !number) {
          throw new Error(payload.error || 'Не удалось отправить обращение.');
        }

        // Форма очищается только после подтверждённого сохранения на сервере.
        form.reset();
        openDialog(number);
      } catch (error) {
        // При ошибке введённые данные остаются на месте: посетитель
        // может исправить одно поле и отправить снова.
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
    setupMap();
    setupDocumentViewer();
    setupAppealForm();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
