/**
 * Интерфейсные скрипты сайта.
 *
 * Правила:
 *  • сайт полностью работает без JavaScript — здесь только улучшения;
 *  • ни одной цены в этом файле: все расчёты приходят с сервера;
 *  • аналитика не загружается, пока пользователь не дал согласие.
 */
(function () {
    'use strict';

    var CONSENT_KEY = 'cookie-consent';

    /* ── Мобильное меню ──────────────────────────────────────────────── */

    var menu = document.getElementById('mobile-menu');
    var burger = document.querySelector('[data-menu-open]');
    var menuClose = document.querySelector('[data-menu-close]');
    var lastFocused = null;

    function openMenu() {
        if (!menu) return;
        lastFocused = document.activeElement;
        menu.setAttribute('open', '');
        document.body.style.overflow = 'hidden';
        var first = menu.querySelector('a, button');
        if (first) first.focus();
    }

    function closeMenu() {
        if (!menu) return;
        menu.removeAttribute('open');
        document.body.style.overflow = '';
        if (burger) burger.focus();
        else if (lastFocused && lastFocused.focus) lastFocused.focus();
    }

    if (burger) burger.addEventListener('click', openMenu);
    if (menuClose) menuClose.addEventListener('click', closeMenu);

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (menu && menu.hasAttribute('open')) closeMenu();
        var dialog = document.querySelector('dialog[open]');
        if (dialog && typeof dialog.close === 'function') dialog.close();
    });

    /* ── Диалог обратного звонка ─────────────────────────────────────── */

    var dialog = document.getElementById('callback-dialog');

    document.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-dialog-open]');
        if (opener && dialog && typeof dialog.showModal === 'function') {
            event.preventDefault();
            if (menu && menu.hasAttribute('open')) closeMenu();
            dialog.showModal();
            return;
        }

        var closer = event.target.closest('[data-dialog-close]');
        if (closer && dialog) {
            event.preventDefault();
            dialog.close();
        }
    });

    if (dialog) {
        // Клик по подложке закрывает окно.
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) dialog.close();
        });
        dialog.addEventListener('close', function () {
            document.body.style.overflow = '';
        });
    }

    /* ── Маска телефона ──────────────────────────────────────────────── */

    function formatPhone(value) {
        var digits = value.replace(/\D/g, '');

        if (digits.length === 0) return '';
        if (digits[0] === '8') digits = '7' + digits.slice(1);
        if (digits[0] !== '7') digits = '7' + digits;
        digits = digits.slice(0, 11);

        var out = '+7';
        if (digits.length > 1) out += ' (' + digits.slice(1, 4);
        if (digits.length >= 4) out += ') ' + digits.slice(4, 7);
        if (digits.length >= 8) out += '-' + digits.slice(7, 9);
        if (digits.length >= 10) out += '-' + digits.slice(9, 11);

        return out;
    }

    document.querySelectorAll('input[type="tel"]').forEach(function (input) {
        input.addEventListener('input', function () {
            var start = input.selectionStart === input.value.length;
            input.value = formatPhone(input.value);
            if (start) input.setSelectionRange(input.value.length, input.value.length);
        });
        input.addEventListener('focus', function () {
            if (!input.value) input.value = '+7 (';
        });
        input.addEventListener('blur', function () {
            if (input.value.replace(/\D/g, '').length < 2) input.value = '';
        });
    });

    /* ── Отправка форм ───────────────────────────────────────────────── */

    document.querySelectorAll('form[data-ajax]').forEach(function (form) {
        var status = form.querySelector('[data-form-status]');
        var button = form.querySelector('[type="submit"]');

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            clearErrors(form);
            setStatus(status, '', null);

            if (button) {
                button.disabled = true;
                button.dataset.label = button.textContent;
                button.textContent = 'Отправляем…';
            }

            var data = new FormData(form);
            var calc = window.zubrCalcPayload && form.dataset.withCalc === '1' ? window.zubrCalcPayload() : null;
            if (calc) data.set('calc_json', JSON.stringify(calc));

            fetch(form.action, {
                method: 'POST',
                body: data,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (response) { return response.json().catch(function () { return null; }); })
                .then(function (result) {
                    if (result && result.ok) {
                        setStatus(status, result.message + ' Номер заявки: ' + result.lead_id + '.', 'ok');
                        form.reset();
                        trackGoal('form_' + (form.dataset.form || 'lead'));
                        return;
                    }

                    var errors = (result && result.errors) || { form: 'Не удалось отправить заявку. Позвоните нам, пожалуйста.' };
                    showErrors(form, errors);
                    setStatus(status, errors.form || 'Проверьте, пожалуйста, отмеченные поля.', 'error');
                })
                .catch(function () {
                    setStatus(status, 'Сеть недоступна. Позвоните нам или попробуйте позже.', 'error');
                })
                .finally(function () {
                    if (button) {
                        button.disabled = false;
                        button.textContent = button.dataset.label || 'Отправить';
                    }
                });
        });
    });

    function clearErrors(form) {
        form.querySelectorAll('[data-error-for]').forEach(function (node) { node.textContent = ''; });
        form.querySelectorAll('[aria-invalid]').forEach(function (node) { node.removeAttribute('aria-invalid'); });
    }

    function showErrors(form, errors) {
        var firstInvalid = null;

        Object.keys(errors).forEach(function (field) {
            var holder = form.querySelector('[data-error-for="' + field + '"]');
            if (holder) holder.textContent = errors[field];

            var input = form.querySelector('[name="' + field + '"]');
            if (field === 'consent_personal') input = form.querySelector('[name="consent_personal"]');
            if (input) {
                input.setAttribute('aria-invalid', 'true');
                if (!firstInvalid) firstInvalid = input;
            }
        });

        if (firstInvalid && firstInvalid.focus) firstInvalid.focus();
    }

    function setStatus(node, message, type) {
        if (!node) return;
        node.textContent = message;
        node.className = 'form__status' + (type ? ' form__status--' + type : '');
        node.hidden = !message;
    }

    /* ── Поиск по списку территорий ──────────────────────────────────── */

    var geoSearch = document.querySelector('[data-geo-search]');

    if (geoSearch) {
        var geoItems = Array.prototype.slice.call(document.querySelectorAll('[data-geo-item]'));
        var geoCounter = document.querySelector('[data-geo-count]');

        geoSearch.addEventListener('input', function () {
            var query = geoSearch.value.trim().toLowerCase();
            var shown = 0;

            geoItems.forEach(function (item) {
                var match = !query || item.dataset.geoItem.indexOf(query) !== -1;
                item.hidden = !match;
                if (match) shown++;
            });

            document.querySelectorAll('[data-geo-group]').forEach(function (group) {
                var visible = group.querySelectorAll('[data-geo-item]:not([hidden])').length;
                group.hidden = visible === 0;
            });

            if (geoCounter) {
                geoCounter.textContent = query ? 'Найдено: ' + shown : '';
            }
        });
    }

    /* ── Cookie-баннер и аналитика ───────────────────────────────────── */

    var banner = document.getElementById('cookie-banner');

    function readConsent() {
        try {
            var raw = localStorage.getItem(CONSENT_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (error) {
            return null;
        }
    }

    function saveConsent(consent) {
        consent.version = banner ? banner.dataset.version : '1.0';
        consent.date = new Date().toISOString();
        try {
            localStorage.setItem(CONSENT_KEY, JSON.stringify(consent));
        } catch (error) { /* приватный режим — просто не запоминаем */ }
        applyConsent(consent);
    }

    function applyConsent(consent) {
        if (banner) banner.hidden = true;
        if (consent && consent.analytics) loadAnalytics();
    }

    var analyticsLoaded = false;

    function loadAnalytics() {
        if (analyticsLoaded) return;

        var config = window.zubrAnalytics;
        if (!config || !config.metrika) return;

        analyticsLoaded = true;

        // Метрика подключается только здесь — после явного согласия.
        (function (m, e, t, r, i, k, a) {
            m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments); };
            m[i].l = 1 * new Date();
            k = e.createElement(t); a = e.getElementsByTagName(t)[0];
            k.async = 1; k.src = r; a.parentNode.insertBefore(k, a);
        })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js', 'ym');

        window.ym(config.metrika, 'init', {
            clickmap: true,
            trackLinks: true,
            accurateTrackBounce: true,
            webvisor: !!config.webvisor
        });
    }

    function trackGoal(goal) {
        var consent = readConsent();
        if (!consent || !consent.analytics) return;
        if (window.ym && window.zubrAnalytics && window.zubrAnalytics.metrika) {
            window.ym(window.zubrAnalytics.metrika, 'reachGoal', goal);
        }
    }

    window.zubrTrackGoal = trackGoal;

    if (banner) {
        var saved = readConsent();

        if (saved && saved.version === banner.dataset.version) {
            applyConsent(saved);
        } else {
            banner.hidden = false;
        }

        banner.querySelector('[data-cookie-all]').addEventListener('click', function () {
            saveConsent({ necessary: true, analytics: true, marketing: true });
        });

        banner.querySelector('[data-cookie-necessary]').addEventListener('click', function () {
            saveConsent({ necessary: true, analytics: false, marketing: false });
        });

        var settingsToggle = banner.querySelector('[data-cookie-settings]');
        var settingsBox = banner.querySelector('[data-cookie-settings-box]');

        if (settingsToggle && settingsBox) {
            settingsToggle.addEventListener('click', function () {
                settingsBox.hidden = !settingsBox.hidden;
                settingsToggle.setAttribute('aria-expanded', String(!settingsBox.hidden));
            });

            var saveButton = settingsBox.querySelector('[data-cookie-save]');
            if (saveButton) {
                saveButton.addEventListener('click', function () {
                    saveConsent({
                        necessary: true,
                        analytics: settingsBox.querySelector('[name="analytics"]').checked,
                        marketing: settingsBox.querySelector('[name="marketing"]').checked
                    });
                });
            }
        }
    }

    // Ссылка «Настройки cookie» в подвале — вернуть баннер.
    document.querySelectorAll('[data-cookie-reopen]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            if (banner) {
                banner.hidden = false;
                banner.scrollIntoView({ block: 'center' });
            }
        });
    });

    /* ── Цели аналитики на контактах ─────────────────────────────────── */

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href^="tel:"]');
        if (link) trackGoal('call_click');

        var messenger = event.target.closest('[data-messenger]');
        if (messenger) trackGoal('messenger_' + messenger.dataset.messenger);
    });
}());
