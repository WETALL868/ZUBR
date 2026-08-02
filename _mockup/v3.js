/* ===========================================================================
   ВАРИАНТ 3. Складывающиеся разделы.

   Разделы «Покупатель», «Доставка» и «Оплата» сворачиваются в одну строку с
   кратким итогом того, что выбрано. Открыт всегда один — но, в отличие от
   пошагового варианта, порядок свободный: можно вернуться в любой раздел
   одним нажатием, не проходя шаги заново.

   Разметка формы не меняется: заголовки разделов превращаются в кнопки,
   содержимое остаётся тем же самым. Проверки берутся из тех же правил, что
   применяет сервер.
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
  var panels = [];

  ["Покупатель", "Доставка", "Оплата"].forEach(function (name, i) {
    var section = sections[i];
    if (!section) return;

    var title = section.querySelector(".co-title");
    if (!title) return;

    /* Заголовок превращаем в кнопку: то же слово на том же месте, но по нему
       теперь можно свернуть и развернуть раздел. */
    var head = document.createElement("button");
    head.type = "button";
    head.className = "co-acc-head";
    head.setAttribute("aria-expanded", "false");
    head.innerHTML =
      '<span class="co-acc-name">' + name + "</span>" +
      '<span class="co-acc-recap" data-acc-recap></span>' +
      '<span class="co-acc-caret" aria-hidden="true"></span>';

    var body = document.createElement("div");
    body.className = "co-acc-body";
    while (title.nextSibling) body.appendChild(title.nextSibling);

    var done = document.createElement("button");
    done.type = "button";
    done.className = "button primary co-acc-done";
    done.textContent = i === 2 ? "Готово" : "Продолжить";
    body.appendChild(done);

    section.replaceChild(head, title);
    section.appendChild(body);
    section.classList.add("co-acc");

    var panel = { section: section, head: head, body: body, recap: head.querySelector("[data-acc-recap]"), index: i };
    panels.push(panel);

    head.addEventListener("click", function () {
      open(panel.index === current ? -1 : panel.index);
    });

    done.addEventListener("click", function () {
      if (validate(panel)) open(panel.index + 1 < panels.length ? panel.index + 1 : -1);
    });
  });

  var current = -1;

  function open(index) {
    current = index;
    panels.forEach(function (p) {
      var isOpen = p.index === index;
      p.section.classList.toggle("is-open", isOpen);
      p.head.setAttribute("aria-expanded", isOpen ? "true" : "false");
      p.body.hidden = !isOpen;
      if (!isOpen) recap(p);
    });
  }

  /* ------------------------------------------------------------- проверки */

  function visibleRequired(node) {
    return Array.prototype.slice
      .call(node.querySelectorAll("[data-co-required]"))
      .filter(function (el) {
        return !el.disabled && (el.offsetParent !== null || el.getClientRects().length > 0);
      });
  }

  function showErrorFor(name, text) {
    var box = form.querySelector('[data-co-error-for="' + name + '"]');
    if (box) box.textContent = text;
  }

  function validate(panel) {
    var ok = true;
    var first = null;

    visibleRequired(panel.body).forEach(function (el) {
      var rule = rules[el.dataset.coField];
      var v = (el.value || "").trim();
      var msg = "";
      if (!v) msg = "Заполните поле «" + ((rule && rule.label) || el.dataset.coField) + "».";
      else if (rule && rule.pattern && !new RegExp(rule.pattern).test(v)) msg = rule.message || "Проверьте формат.";
      showErrorFor(el.dataset.coField, msg);
      el.classList.toggle("is-invalid", !!msg);
      if (msg && ok) { ok = false; first = el; }
    });

    if (panel.index === 1) {
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

    if (panel.index === 2) {
      var paid = form.querySelector("[data-payment-cards] input:checked");
      showErrorFor("payment_code", paid ? "" : "Выберите способ оплаты.");
      if (!paid && ok) ok = false;
    }

    if (first && first.focus) first.focus();
    return ok;
  }

  /* --------------------------------------------------------- краткий итог */

  function value(name) {
    var el = form.querySelector('[data-co-field="' + name + '"]');
    return el && !el.disabled ? (el.value || "").trim() : "";
  }

  function recap(panel) {
    var text = "";

    if (panel.index === 0) {
      var legal = form.querySelector('[data-customer-type][value="legal"]').checked;
      text = legal
        ? ["Юр. лицо", value("legal_company"), value("legal_inn") && "ИНН " + value("legal_inn"), value("legal_phone")]
            .filter(Boolean).join(" · ")
        : ["Физ. лицо", [value("first_name"), value("last_name")].filter(Boolean).join(" "), value("phone")]
            .filter(Boolean).join(" · ");
    }

    if (panel.index === 1) {
      var d = form.querySelector("[data-delivery-cards] input:checked");
      var pvz = form.querySelector("[data-pvz]");
      var addrBox = form.querySelector("[data-delivery-address-field]");
      var addr = form.querySelector("[data-delivery-address]");
      var price = form.querySelector("[data-delivery-summary-price]");
      text = [
        d ? d.value : "",
        price ? price.textContent.trim() : "",
        pvz && pvz.value ? pvz.value : "",
        addrBox && !addrBox.hidden && addr ? addr.value.trim() : "",
      ].filter(Boolean).join(" · ");
    }

    if (panel.index === 2) {
      var pay = form.querySelector("[data-payment-cards] input:checked");
      text = pay ? pay.dataset.paymentTitle : "";
    }

    panel.recap.textContent = text || "не заполнено";
    panel.section.classList.toggle("is-filled", !!text);
  }

  form.addEventListener("change", function () {
    panels.forEach(function (p) { if (p.index !== current) recap(p); });
  });
  document.addEventListener("mockup:delivery", function () {
    panels.forEach(function (p) { if (p.index !== current) recap(p); });
  });

  /* Начинаем с открытого первого раздела: остальные свёрнуты в строки. */
  setTimeout(function () { open(0); }, 0);
})();
