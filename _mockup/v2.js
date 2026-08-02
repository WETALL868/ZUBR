/* ===========================================================================
   ВАРИАНТ 2. Пошаговое оформление.

   Форма та же самая — она просто показывается по одному разделу за раз:
   покупатель, доставка, оплата, подтверждение. Ни одно поле не удалено и не
   перенесено в другой раздел, поэтому отправка заказа, расчёт стоимости и
   проверки на сервере работают ровно как раньше.

   Проверка перед переходом к следующему шагу берёт те же правила, что и
   сервер, — из <script type="application/json" data-co-rules>. Второго набора
   правил в проекте не появляется.
   =========================================================================== */

(function () {
  "use strict";

  var form = document.querySelector(".checkout-form");
  if (!form) return;

  var rules = {};
  try {
    rules = JSON.parse(document.querySelector("[data-co-rules]").textContent || "{}");
  } catch (e) {}

  var sections = Array.prototype.slice.call(form.querySelectorAll(":scope > .co-section"));
  var summary = form.querySelector(".co-summary");
  var consents = form.querySelector(".co-consents");
  var leadOnly = form.querySelector("[data-lead-only]");

  var steps = [
    { title: "Покупатель", nodes: [sections[0]] },
    { title: "Доставка", nodes: [sections[1]] },
    { title: "Оплата", nodes: [sections[2]] },
    { title: "Итог", nodes: [leadOnly, consents, summary].filter(Boolean) },
  ];

  /* ------------------------------------------------------------- разметка */

  var nav = document.createElement("ol");
  nav.className = "co-steps";
  steps.forEach(function (step, i) {
    var li = document.createElement("li");
    li.className = "co-step";
    li.innerHTML =
      '<span class="co-step-num">' + (i + 1) + "</span>" +
      '<span class="co-step-name">' + step.title + "</span>";
    li.addEventListener("click", function () {
      /* Назад можно вернуться свободно, вперёд — только через проверку. */
      if (i <= current) go(i);
    });
    nav.appendChild(li);
  });
  form.insertBefore(nav, form.firstChild);

  var bar = document.createElement("div");
  bar.className = "co-stepbar";
  bar.innerHTML =
    '<button type="button" class="button ghost co-back">Назад</button>' +
    '<span class="co-stepbar-total" data-step-total></span>' +
    '<button type="button" class="button primary co-next">Далее</button>';
  form.appendChild(bar);

  var back = bar.querySelector(".co-back");
  var next = bar.querySelector(".co-next");
  var barTotal = bar.querySelector("[data-step-total]");

  /* Сводка выбранного — показывается на последнем шаге. */
  var recap = document.createElement("dl");
  recap.className = "co-recap";
  if (summary) {
    summary.parentNode.insertBefore(recap, summary);
    steps[steps.length - 1].nodes.unshift(recap);
  }

  /* --------------------------------------------------------------- логика */

  var current = 0;

  function visibleRequired(node) {
    if (!node) return [];
    return Array.prototype.slice
      .call(node.querySelectorAll("[data-co-required]"))
      .filter(function (el) {
        if (el.disabled) return false;
        return el.offsetParent !== null || el.getClientRects().length > 0;
      });
  }

  function fieldError(el) {
    var name = el.dataset.coField;
    var rule = rules[name];
    var value = (el.value || "").trim();
    if (!value) return "Заполните поле «" + ((rule && rule.label) || name) + "».";
    if (rule && rule.pattern && !new RegExp(rule.pattern).test(value)) {
      return rule.message || "Проверьте формат.";
    }
    return "";
  }

  function showErrorFor(name, text) {
    var box = form.querySelector('[data-co-error-for="' + name + '"]');
    if (box) box.textContent = text;
  }

  function showError(el, text) {
    showErrorFor(el.dataset.coField, text);
    el.classList.toggle("is-invalid", !!text);
  }

  function validate(stepIndex) {
    var ok = true;
    var first = null;

    steps[stepIndex].nodes.forEach(function (node) {
      visibleRequired(node).forEach(function (el) {
        var msg = fieldError(el);
        showError(el, msg);
        if (msg && ok) {
          ok = false;
          first = el;
        }
      });
    });

    if (stepIndex === 1) {
      var addrBox = form.querySelector("[data-delivery-address-field]");
      var addr = form.querySelector("[data-delivery-address]");
      if (addrBox && !addrBox.hidden && addr && !addr.value.trim()) {
        showErrorFor("address", "Укажите адрес доставки.");
        if (ok) { ok = false; first = addr; }
      } else {
        showErrorFor("address", "");
      }

      var pvzBox = form.querySelector("[data-pvz-field]");
      var pvz = form.querySelector("[data-pvz]");
      if (pvzBox && !pvzBox.hidden && pvz && !pvz.value) {
        pvz.classList.add("is-invalid");
        if (ok) { ok = false; first = pvz; }
      } else if (pvz) {
        pvz.classList.remove("is-invalid");
      }
    }

    if (stepIndex === 2) {
      var paid = form.querySelector("[data-payment-cards] input:checked");
      showErrorFor("payment_code", paid ? "" : "Выберите способ оплаты.");
      if (!paid && ok) {
        ok = false;
        first = form.querySelector("[data-payment-cards] input");
      }
    }

    if (first && first.focus) first.focus();
    return ok;
  }

  function go(index) {
    current = Math.max(0, Math.min(steps.length - 1, index));

    steps.forEach(function (step, i) {
      step.nodes.forEach(function (node) {
        if (node) node.classList.toggle("co-step-hidden", i !== current);
      });
      nav.children[i].classList.toggle("is-current", i === current);
      nav.children[i].classList.toggle("is-done", i < current);
    });

    back.disabled = current === 0;
    next.hidden = current === steps.length - 1;
    bar.classList.toggle("is-last", current === steps.length - 1);

    if (current === steps.length - 1) buildRecap();
    syncTotal();
  }

  function value(name) {
    var el = form.querySelector('[data-co-field="' + name + '"]');
    return el && !el.disabled ? (el.value || "").trim() : "";
  }

  function buildRecap() {
    var legal = form.querySelector('[data-customer-type][value="legal"]').checked;
    var who = legal
      ? [value("legal_company") || "Организация", "ИНН " + (value("legal_inn") || "—"), value("legal_phone")]
      : [
          [value("first_name"), value("last_name")].filter(Boolean).join(" ") || "—",
          value("phone"),
          value("email"),
        ];

    var deliveryInput = form.querySelector("[data-delivery-cards] input:checked");
    var paymentInput = form.querySelector("[data-payment-cards] input:checked");
    var addrBox = form.querySelector("[data-delivery-address-field]");
    var addr = form.querySelector("[data-delivery-address]");
    var pvz = form.querySelector("[data-pvz]");

    var rows = [
      ["Покупатель", (legal ? "Юр. лицо · " : "Физ. лицо · ") + who.filter(Boolean).join(" · ")],
      [
        "Доставка",
        [
          deliveryInput ? deliveryInput.value : "не выбрана",
          pvz && pvz.value ? pvz.value : "",
          addrBox && !addrBox.hidden && addr ? addr.value : "",
        ]
          .filter(Boolean)
          .join(" · "),
      ],
      ["Оплата", paymentInput ? paymentInput.dataset.paymentTitle : "не выбрана"],
    ];

    recap.innerHTML = "";
    rows.forEach(function (row) {
      var wrap = document.createElement("div");
      var dt = document.createElement("dt");
      var dd = document.createElement("dd");
      dt.textContent = row[0];
      dd.textContent = row[1];
      wrap.appendChild(dt);
      wrap.appendChild(dd);
      recap.appendChild(wrap);
    });
  }

  function syncTotal() {
    var grand = form.querySelector("[data-cart-grand-total]");
    barTotal.textContent = grand ? "Итого: " + grand.textContent : "";
  }

  next.addEventListener("click", function () {
    if (validate(current)) go(current + 1);
  });
  back.addEventListener("click", function () {
    go(current - 1);
  });
  form.addEventListener("change", syncTotal);
  document.addEventListener("mockup:delivery", syncTotal);

  go(0);
})();
