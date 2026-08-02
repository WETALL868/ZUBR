/* ===========================================================================
   МАКЕТ. Общая часть поведения для всех трёх вариантов.

   Здесь только то, что нужно в любом варианте:
     • поле пункта выдачи, которое показывается ТОЛЬКО при доставке до ПВЗ;
     • подпись выбранного способа доставки прямо на кнопке «Подтвердить»;
     • страховка на случай, если браузер не применил правило [hidden].

   Расчёты стоимости, отправка заказа и проверки полей не трогаются: всё это
   по-прежнему делает src/main.js и сервер.
   =========================================================================== */

(function () {
  "use strict";

  var form = document.querySelector(".checkout-form");
  if (!form) return;

  var cards = form.querySelector("[data-delivery-cards]");
  var addressField = form.querySelector("[data-delivery-address-field]");
  if (!cards) return;

  /* ------------------------------------------------------- поле пункта выдачи
     В рабочей базе способ доставки до ПВЗ помечается отдельным признаком.
     В макете определяем его по названию — этого достаточно, чтобы показать,
     как поле появляется и исчезает. */

  function isPickupPoint(input) {
    var t = (input.value + " " + (input.dataset.deliveryTitle || "")).toLowerCase();
    return t.indexOf("пвз") >= 0 || t.indexOf("пункт выдачи") >= 0;
  }

  var pvz = document.createElement("div");
  pvz.className = "co-field co-field-full";
  pvz.setAttribute("data-pvz-field", "");
  pvz.hidden = true;
  pvz.innerHTML =
    '<label for="co-pvz">Пункт выдачи <span class="co-req" aria-hidden="true">*</span></label>' +
    '<select id="co-pvz" data-pvz>' +
    '<option value="">Выберите пункт выдачи</option>' +
    '<option>СДЭК · Москва, Варшавское шоссе, 26</option>' +
    '<option>СДЭК · Москва, ул. Профсоюзная, 93</option>' +
    '<option>Boxberry · Москва, Каширское шоссе, 61</option>' +
    '<option>Boxberry · Подольск, ул. Кирова, 39</option>' +
    "</select>" +
    '<p class="co-hint">Список подставится из службы доставки по указанному городу.</p>';

  if (addressField && addressField.parentNode) {
    addressField.parentNode.insertBefore(pvz, addressField);
  } else {
    cards.parentNode.appendChild(pvz);
  }

  /* --------------------------------------------------------------- обновление */

  function selected() {
    return cards.querySelector("input[type=radio]:checked");
  }

  function apply() {
    var input = selected();
    if (!input) return;

    var pickupPoint = isPickupPoint(input);
    var needsAddress = input.dataset.deliveryDetails === "true";

    pvz.hidden = !pickupPoint;

    /* Адрес улицы для ПВЗ не нужен: адрес пункта известен службе доставки. */
    if (addressField) {
      addressField.hidden = !needsAddress || pickupPoint;
    }

    document.dispatchEvent(new CustomEvent("mockup:delivery", {
      detail: { title: input.value, pickupPoint: pickupPoint, needsAddress: needsAddress },
    }));
  }

  cards.addEventListener("change", apply);
  form.addEventListener("change", function (e) {
    if (e.target && e.target.name === "cartDelivery") apply();
  });

  /* main.js перерисовывает блок доставки после своих расчётов — встаём после него. */
  setTimeout(apply, 0);
  setTimeout(apply, 250);
})();
