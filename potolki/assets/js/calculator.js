/**
 * Калькулятор сметы.
 *
 * Важно: в этом файле нет ни одной цены. Скрипт только собирает введённые
 * параметры, отправляет их на /api/calculate.php и показывает то, что
 * посчитал сервер. Поэтому подменить стоимость из браузера невозможно,
 * а прайс существует ровно в одном месте — config/prices.php.
 *
 * Без JavaScript тот же расчёт выполняется обычной отправкой формы.
 */
(function () {
    'use strict';

    var form = document.getElementById('calc-form');
    if (!form) return;

    var roomsBox = form.querySelector('[data-rooms]');
    var template = document.getElementById('room-template');
    var summary = document.getElementById('calc-summary');
    var addButton = form.querySelector('[data-add-room]');
    var endpoint = form.dataset.endpoint || '/api/calculate.php';
    var timer = null;
    var lastPayload = null;

    /* ── Режимы ──────────────────────────────────────────────────────── */

    var tabs = form.querySelectorAll('[data-mode]');

    function setMode(mode) {
        form.dataset.currentMode = mode;

        tabs.forEach(function (tab) {
            tab.setAttribute('aria-selected', String(tab.dataset.mode === mode));
        });

        form.querySelectorAll('[data-visible-in]').forEach(function (node) {
            node.hidden = node.dataset.visibleIn.split(' ').indexOf(mode) === -1;
        });

        if (mode !== 'rooms') {
            // В быстром и подробном режимах считаем одно помещение.
            var extra = roomsBox.querySelectorAll('[data-room]');
            for (var i = 1; i < extra.length; i++) extra[i].hidden = true;
        } else {
            roomsBox.querySelectorAll('[data-room]').forEach(function (room) { room.hidden = false; });
        }

        schedule();
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () { setMode(tab.dataset.mode); });
    });

    /* ── Помещения ───────────────────────────────────────────────────── */

    function roomNodes() {
        return Array.prototype.slice.call(roomsBox.querySelectorAll('[data-room]')).filter(function (node) {
            return !node.hidden;
        });
    }

    function renumber() {
        roomsBox.querySelectorAll('[data-room]').forEach(function (room, index) {
            var label = room.querySelector('[data-room-number]');
            if (label) label.textContent = 'Помещение ' + (index + 1);

            room.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace(/rooms\[\d+\]/, 'rooms[' + index + ']');
            });

            var remove = room.querySelector('[data-remove-room]');
            if (remove) remove.hidden = index === 0 && roomsBox.querySelectorAll('[data-room]').length === 1;
        });
    }

    function addRoom(copyFrom) {
        if (!template) return;

        var count = roomsBox.querySelectorAll('[data-room]').length;
        if (count >= 20) return;

        var clone = template.content.firstElementChild.cloneNode(true);
        roomsBox.appendChild(clone);

        if (copyFrom) {
            var source = copyFrom.querySelectorAll('[name]');
            var target = clone.querySelectorAll('[name]');
            for (var i = 0; i < source.length && i < target.length; i++) {
                if (source[i].type === 'checkbox') target[i].checked = source[i].checked;
                else target[i].value = source[i].value;
            }
        }

        renumber();

        var title = clone.querySelector('[name$="[title]"]');
        if (title) title.focus();

        schedule();
    }

    if (addButton) {
        addButton.addEventListener('click', function () {
            var rooms = roomsBox.querySelectorAll('[data-room]');
            addRoom(rooms[rooms.length - 1]);
        });
    }

    form.addEventListener('click', function (event) {
        var remove = event.target.closest('[data-remove-room]');
        if (remove) {
            event.preventDefault();
            var room = remove.closest('[data-room]');
            if (roomsBox.querySelectorAll('[data-room]').length > 1) {
                room.remove();
                renumber();
                schedule();
            }
            return;
        }

        var copy = event.target.closest('[data-copy-room]');
        if (copy) {
            event.preventDefault();
            addRoom(copy.closest('[data-room]'));
        }
    });

    /* ── Сбор данных ─────────────────────────────────────────────────── */

    function collect() {
        var rooms = [];

        roomNodes().forEach(function (node) {
            var room = {};
            node.querySelectorAll('[name]').forEach(function (field) {
                var match = field.name.match(/\[(\w+)\]$/);
                if (!match) return;
                var key = match[1];
                if (field.type === 'checkbox') room[key] = field.checked ? 1 : 0;
                else if (field.value !== '') room[key] = field.value;
            });
            rooms.push(room);
        });

        var distance = form.querySelector('[name="distance_km"]');

        return {
            rooms: rooms,
            distance_km: distance ? distance.value || 0 : 0
        };
    }

    // Форма заявки прикладывает к письму актуальный расчёт.
    window.zubrCalcPayload = collect;

    /* ── Запрос к серверу ────────────────────────────────────────────── */

    function schedule() {
        if (timer) window.clearTimeout(timer);
        timer = window.setTimeout(calculate, 350);
    }

    /** Есть ли хоть одно помещение с заданными размерами. */
    function hasDimensions(payload) {
        return payload.rooms.some(function (room) {
            return room.area || (room.length && room.width);
        });
    }

    function calculate() {
        var payload = collect();

        // Пока размеры не введены, сервер запрашивать незачем: он вернёт
        // ошибку, а пользователь получит красную строку на пустой форме.
        if (!payload.rooms.length || !hasDimensions(payload)) {
            showHint();
            return;
        }

        lastPayload = payload;

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (payload !== lastPayload) return;
                render(result);
            })
            .catch(function () {
                if (summary) {
                    summary.querySelector('[data-summary-body]').innerHTML =
                        '<p class="calc__disclaimer">Не удалось получить расчёт. Проверьте соединение или позвоните нам — посчитаем вместе.</p>';
                }
            });
    }

    form.addEventListener('input', schedule);
    form.addEventListener('change', schedule);

    /* ── Отрисовка результата ────────────────────────────────────────── */

    function money(value) {
        return new Intl.NumberFormat('ru-RU').format(Math.round(value)) + ' ₽';
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function line(title, value, extra) {
        var row = el('div', 'calc__line');
        var left = el('span', null, title);
        if (extra) left.appendChild(el('small', null, extra));
        row.appendChild(left);
        row.appendChild(el('span', null, value));
        return row;
    }

    /** Подсказка вместо пустого итога, пока размеры не заданы. */
    function showHint() {
        if (!summary) return;
        var total = summary.querySelector('[data-summary-total]');
        var range = summary.querySelector('[data-summary-range]');
        var body = summary.querySelector('[data-summary-body]');
        if (total) total.textContent = '—';
        if (range) range.textContent = 'Укажите длину и ширину помещения — расчёт появится здесь.';
        if (body) body.innerHTML = '';
    }

    function render(result) {
        if (!summary) return;

        var body = summary.querySelector('[data-summary-body]');
        var totalNode = summary.querySelector('[data-summary-total]');
        var rangeNode = summary.querySelector('[data-summary-range]');

        body.innerHTML = '';

        if (!result.ok) {
            totalNode.textContent = '—';
            rangeNode.textContent = '';
            var list = el('div', 'calc__warnings');
            Object.keys(result.errors || {}).forEach(function (key) {
                list.appendChild(el('div', null, result.errors[key]));
            });
            body.appendChild(list);
            return;
        }

        var mode = form.dataset.currentMode || 'detailed';

        result.rooms.forEach(function (room) {
            body.appendChild(line(
                room.title,
                money(room.subtotal),
                room.area + ' м², периметр ' + room.perimeter + ' пог. м'
            ));

            if (mode !== 'quick') {
                room.lines.forEach(function (item) {
                    body.appendChild(line(
                        item.title,
                        money(item.sum),
                        item.qty + ' ' + item.unit + ' × ' + money(item.rate)
                    ));
                });
            }
        });

        if (result.travel && result.travel.sum > 0) {
            body.appendChild(line('Выезд', money(result.travel.sum), result.travel.billable_km + ' км сверх бесплатной зоны'));
        }

        var totals = result.totals;

        if (totals.discount > 0) {
            body.appendChild(line('Скидка ' + totals.discount_percent + ' %', '−' + money(totals.discount)));
        }

        if (totals.minimum_applied) {
            body.appendChild(line('Минимальная сумма заказа', money(totals.minimum_order), 'Сумма работ вышла меньше минимальной'));
        }

        var totalRow = line('Итого', money(totals.total));
        totalRow.className = 'calc__line calc__line--total';
        body.appendChild(totalRow);

        totalNode.textContent = mode === 'quick'
            ? money(result.range.min) + ' – ' + money(result.range.max)
            : money(totals.total);

        rangeNode.textContent = mode === 'quick'
            ? 'Ориентировочный диапазон ±' + result.range.uncertainty_percent + ' %. Подробный расчёт даст точную смету.'
            : 'Срок изготовления ' + result.terms.production_min + '–' + result.terms.production_max + ' рабочих дней, монтаж около ' + result.terms.install_hours + ' ч.';

        if (result.warnings && result.warnings.length) {
            var warnings = el('div', 'calc__warnings');
            result.warnings.forEach(function (text) { warnings.appendChild(el('div', null, text)); });
            body.appendChild(warnings);
        }

        if (result.individual_quote) {
            var note = el('div', 'calc__warnings');
            note.appendChild(el('div', null, 'По этим параметрам нужен индивидуальный расчёт после замера — цифра выше ориентировочная.'));
            body.appendChild(note);
        }

        var number = summary.querySelector('[data-summary-number]');
        if (number) number.textContent = 'Номер расчёта: ' + result.quote_number;

        var hidden = form.querySelector('[name="calc_json"]');
        if (hidden) hidden.value = JSON.stringify(lastPayload);

        if (window.zubrTrackGoal) window.zubrTrackGoal('calculator_result');
    }

    /* ── Печать сметы ────────────────────────────────────────────────── */

    var printButton = document.querySelector('[data-print-estimate]');
    if (printButton) {
        printButton.addEventListener('click', function () {
            if (window.zubrTrackGoal) window.zubrTrackGoal('estimate_print');
            window.print();
        });
    }

    /* ── Старт ───────────────────────────────────────────────────────── */

    renumber();
    setMode(form.dataset.startMode || 'quick');
}());
