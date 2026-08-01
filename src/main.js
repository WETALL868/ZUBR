const form = document.querySelector(".lead-form");
const status = document.querySelector(".form-status");
const submitButton = form?.querySelector('button[type="submit"]');
const cookieBanner = document.querySelector(".cookie-banner");
const cookieAccept = document.querySelector(".cookie-accept");
const successDialog = document.querySelector(".order-success");
const successNumber = successDialog?.querySelector(".order-success-number strong");
const successClose = successDialog?.querySelector(".order-success-close");
const successOk = successDialog?.querySelector(".order-success-ok");
const formRenderedAt = document.querySelector("[data-form-rendered-at]");

// Stamped from JS (not from server-rendered HTML) so the value survives page
// caching: the server rejects submissions that arrive implausibly fast, which
// filters out naive form-spam bots without adding a captcha for real users.
if (formRenderedAt) {
  formRenderedAt.value = String(Date.now());
}

function hasCookieConsent() {
  try {
    return localStorage.getItem("xeon-cookie-consent") === "accepted";
  } catch {
    return false;
  }
}

function saveCookieConsent() {
  try {
    localStorage.setItem("xeon-cookie-consent", "accepted");
  } catch {
    // The banner still closes if browser storage is unavailable.
  }
}

/*
 * Баннер cookie висит поверх страницы, поэтому он обязан освобождать под себя
 * место внизу документа. Без этого на телефоне он закрывал весь подвал —
 * ссылки на политику, соглашение и реквизиты, — а на форме заказа прятал
 * первые поля: посетитель видел заголовок «Оставьте заявку» и пустоту под ним.
 *
 * Высоту меряем, а не задаём числом: она зависит от ширины экрана и от
 * длины текста, которые владелец может поменять в панели.
 */
function updateCookieOffset() {
  const visible = cookieBanner && !cookieBanner.hidden
    && getComputedStyle(cookieBanner).display !== "none";

  document.documentElement.classList.toggle("has-cookie-banner", !!visible);
  document.documentElement.style.setProperty(
    "--cookie-banner-height",
    visible ? `${Math.ceil(cookieBanner.getBoundingClientRect().height)}px` : "0px",
  );
}

if (cookieBanner && hasCookieConsent()) {
  cookieBanner.hidden = true;
}

if (cookieBanner) {
  updateCookieOffset();

  // Текст переносится по-разному при повороте экрана и при смене размера
  // шрифта в браузере, поэтому высоту пересчитываем, а не запоминаем.
  if (typeof ResizeObserver === "function") {
    new ResizeObserver(updateCookieOffset).observe(cookieBanner);
  } else {
    window.addEventListener("resize", updateCookieOffset, { passive: true });
  }
}

cookieAccept?.addEventListener("click", () => {
  saveCookieConsent();
  if (cookieBanner) {
    cookieBanner.hidden = true;
  }
  updateCookieOffset();
});

function formatPhone(value) {
  let digits = String(value || "").replace(/\D/g, "");

  // Восьмёрка в начале — тот же российский код страны, что и семёрка.
  if (digits.startsWith("8")) {
    digits = `7${digits.slice(1)}`;
  }

  // Снимаем код страны: в поле он показывается отдельно, как «+7».
  if (digits.startsWith("7")) {
    digits = digits.slice(1);
  }

  // Код страны может оказаться в поле дважды. При переводе курсора в поле
  // мы сами подставляем «+7», а номер, вставленный из буфера обмена, почти
  // всегда уже начинается с +7 или с 8 — получается «+7 +7 999 …».
  // Раньше лишний код оставался в номере: он сдвигал цифры вправо, а
  // последняя не помещалась в десять знаков и пропадала — «+7 999 123-45-67»
  // превращался в «+7 (799) 912-34-56». Отдел продаж получал чужой номер и
  // не мог перезвонить, причём покупатель этого не видел.
  while (digits.length > 10 && (digits[0] === "7" || digits[0] === "8")) {
    digits = digits.slice(1);
  }

  digits = digits.slice(0, 10);

  const parts = [
    digits.slice(0, 3),
    digits.slice(3, 6),
    digits.slice(6, 8),
    digits.slice(8, 10),
  ];

  let formatted = "+7";
  if (parts[0]) {
    formatted += ` (${parts[0]}`;
  }
  if (parts[0]?.length === 3) {
    formatted += ")";
  }
  if (parts[1]) {
    formatted += ` ${parts[1]}`;
  }
  if (parts[2]) {
    formatted += `-${parts[2]}`;
  }
  if (parts[3]) {
    formatted += `-${parts[3]}`;
  }

  return formatted;
}

/*
 * Маска телефона вешается на КАЖДОЕ поле телефона, а не на первое найденное.
 * Полей теперь два: одно у физического лица, другое у юридического. Раньше
 * маску получало только первое, и юрлицо вводило номер вручную, а потом
 * получало отказ по формату от сервера.
 */
document.querySelectorAll("[data-phone-mask]").forEach((input) => {
  /*
   * Курсор всегда уводим в конец строки.
   *
   * При переводе в пустое поле мы подставляем «+7». Браузер при этом
   * оставляет курсор в начале, и первая же набранная цифра встаёт ПЕРЕД
   * подсказкой: получается «4+7», маска читает это как номер, начинающийся
   * с четвёрки, и семёрка из подсказки уезжает внутрь номера. Покупатель
   * набирал 499 322-13-11, а в заказ попадало +7 (479) 932-21-31 — чужой
   * номер, по которому не перезвонить.
   *
   * Маска и так переписывает значение целиком, поэтому держать курсор
   * где-то в середине всё равно бессмысленно.
   */
  const caretToEnd = () => {
    try {
      const end = input.value.length;
      input.setSelectionRange(end, end);
    } catch {
      // У некоторых типов полей выделения нет — не беда.
    }
  };

  input.addEventListener("focus", () => {
    if (!input.value) {
      input.value = "+7";
    }
    caretToEnd();
  });

  input.addEventListener("input", () => {
    input.value = formatPhone(input.value);
    caretToEnd();
  });
});

function showOrderSuccess(orderId) {
  if (!successDialog || !successNumber) {
    return;
  }

  successNumber.textContent = orderId;
  successDialog.classList.add("is-open");
  successDialog.setAttribute("aria-hidden", "false");
  document.body.classList.add("success-open");
  window.ScrollLock?.lock("success");
  successOk?.focus({ preventScroll: true });
}

function closeOrderSuccess() {
  if (!successDialog) {
    return;
  }

  successDialog.classList.remove("is-open");
  successDialog.setAttribute("aria-hidden", "true");
  document.body.classList.remove("success-open");
  window.ScrollLock?.unlock("success");
}

function trackMetrikaGoal(goalName, params = {}) {
  if (typeof window.ym === "function") {
    window.ym(110948351, "reachGoal", goalName, params);
  }
}

successClose?.addEventListener("click", closeOrderSuccess);
successOk?.addEventListener("click", closeOrderSuccess);
successDialog?.addEventListener("click", (event) => {
  if (event.target === successDialog) {
    closeOrderSuccess();
  }
});

async function postOrder(endpoint, payload) {
  const response = await fetch(endpoint, {
    method: "POST",
    headers: { "content-type": "application/json" },
    body: JSON.stringify(payload),
  });
  const contentType = response.headers.get("content-type") || "";
  const result = contentType.includes("application/json") ? await response.json().catch(() => ({})) : {};

  if (!response.ok || !result.ok) {
    const error = new Error(result.message || "Не получилось отправить заказ. Попробуйте позже или позвоните нам.");
    error.status = response.status;
    error.isJson = contentType.includes("application/json");
    throw error;
  }

  return result;
}

async function sendOrder(payload) {
  const endpoints = ["/api/order", "/api/order.php"];
  let lastError;

  for (const endpoint of endpoints) {
    try {
      return await postOrder(endpoint, payload);
    } catch (error) {
      lastError = error;
      if (endpoint === endpoints[endpoints.length - 1]) {
        throw error;
      }
      if (error.isJson && error.status && error.status !== 404 && error.status !== 405) {
        throw error;
      }
    }
  }

  throw lastError || new Error("Не получилось отправить заказ. Попробуйте позже или позвоните нам.");
}

form?.addEventListener("submit", async (event) => {
  event.preventDefault();
  prepareCartForSubmit();

  // Телефон приводим к единому виду до проверки: покупатель мог вставить
  // номер из буфера в любом написании.
  form.querySelectorAll("[data-phone-mask]").forEach((input) => {
    if (!input.disabled && input.value) {
      input.value = formatPhone(input.value);
    }
  });

  /*
   * Проверяем сами, а не через reportValidity().
   *
   * Встроенная проверка браузера показывает подсказку в маленьком облачке
   * над полем, по одному за раз, и не умеет ни подсветить поле, ни написать
   * сообщение рядом с ним. Задача требует именно этого — и ещё прокрутки к
   * первому незаполненному полю.
   */
  const problems = validateCheckout();
  if (problems.length) {
    if (status) {
      status.textContent = problems.length === 1
        ? problems[0].message
        : `Проверьте заполнение: ${problems.length} ${problems.length < 5 ? "поля" : "полей"} требуют внимания.`;
    }
    focusFirstProblem(problems);
    return;
  }

  const data = new FormData(form);
  const payload = Object.fromEntries(data.entries());

  // Название способа оплаты нужно менеджеру в письме: код «invoice» ему
  // ничего не говорит, а «Безналичный расчет для организации» говорит всё.
  const paymentChecked = form.querySelector('input[name="payment_code"]:checked');
  if (paymentChecked) {
    payload.payment = paymentChecked.dataset.paymentTitle || paymentChecked.value;
  }

  status.textContent = "";
  if (submitButton) {
    submitButton.disabled = true;
    submitButton.textContent = "Отправляем...";
  }

  try {
    const result = await sendOrder(payload);
    const orderId = result.orderId || "XS-NEW";

    /*
     * Запоминаем введённое ДО очистки формы и после неё уже не трогаем
     * запись. Иначе постоянный покупатель, оформивший заказ, при следующем
     * заходе получал бы пустые поля: очищенная форма перезаписывала бы
     * сохранённое, и весь смысл запоминания пропадал.
     */
    rememberCheckout();
    form.reset();
    // reset() снимает и тип покупателя, и согласия — возвращаем форму в то
    // состояние, в котором её увидит следующий покупатель.
    applyCustomerType();
    cart = [];
    saveCart();
    renderCart();
    closeCart();
    trackMetrikaGoal("order_success", { order_id: orderId });
    showOrderSuccess(orderId);
  } catch (error) {
    status.textContent = error.message || "Не получилось отправить заказ. Попробуйте позже или позвоните нам.";
  } finally {
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.textContent = "Подтвердить заказ";
    }
  }
});

const viewer = document.querySelector(".photo-viewer");
const viewerImage = viewer?.querySelector(".product-dialog-media img");
const viewerTitle = viewer?.querySelector("#product-dialog-title");
const viewerPrice = viewer?.querySelector(".product-dialog-price");
const viewerLead = viewer?.querySelector(".product-dialog-lead");
const viewerSpecs = viewer?.querySelector(".product-dialog-specs");
const viewerTags = viewer?.querySelector(".product-dialog-tags");
const viewerOrder = viewer?.querySelector(".product-dialog-order");
const viewerFullLink = viewer?.querySelector("[data-product-full-link]");
const viewerStock = viewer?.querySelector("[data-viewer-stock]");
const viewerStockLabel = viewer?.querySelector("[data-viewer-stock-label]");
const viewerStockNote = viewer?.querySelector("[data-viewer-stock-note]");
const viewerClose = viewer?.querySelector(".photo-viewer-close");
const productCards = document.querySelectorAll(".model-card:not(.request-card)");
const requestLinks = document.querySelectorAll(".request-link");
const orderSelect = document.querySelector('select[name="goal"]');
const quantityInput = document.querySelector("[data-order-quantity]");
const cartDrawer = document.querySelector(".cart-drawer");
const cartBackdrop = document.querySelector(".cart-backdrop");
const cartOpenButtons = document.querySelectorAll("[data-cart-open]");
const cartCloseButtons = document.querySelectorAll("[data-cart-close]");
const cartList = document.querySelector("[data-cart-list]");
const cartEmpty = document.querySelector("[data-cart-empty]");
const cartCount = document.querySelector("[data-cart-count]");
const cartHeaderTotal = document.querySelector("[data-cart-header-total]");
const cartCheckout = document.querySelector("[data-cart-checkout]");
const cartError = document.querySelector("[data-cart-error]");
const deliveryOptions = document.querySelectorAll('input[name="cartDelivery"]');
const deliveryRegion = document.querySelector("[data-delivery-region]");
const deliveryCity = document.querySelector("[data-delivery-city]");
const deliveryAddress = document.querySelector("[data-delivery-address]");
const deliveryAddressField = document.querySelector("[data-delivery-address-field]");
const deliveryComment = document.querySelector("[data-delivery-comment]");
const deliveryOptionBox = document.querySelector("[data-delivery-option]");
const deliveryOptionTitle = document.querySelector("[data-delivery-option-title]");
const deliveryNote = document.querySelector("[data-delivery-note]");
const deliverySummaryPrice = document.querySelector("[data-delivery-summary-price]");
const deliverySummaryTermRow = document.querySelector("[data-delivery-summary-term-row]");
const deliverySummaryTerm = document.querySelector("[data-delivery-summary-term]");
const cityRequiredMark = document.querySelector("[data-city-req]");
const orderCartSummary = document.querySelector("[data-order-cart-summary]");
const orderCartLines = document.querySelector("[data-order-cart-lines]");
const cartItemsInput = document.querySelector("[data-cart-items]");
const cartTotalInput = document.querySelector("[data-cart-total]");
const cartDiscountInput = document.querySelector("[data-cart-discount]");
const deliveryMethodInput = document.querySelector("[data-delivery-method]");
const deliveryPriceInput = document.querySelector("[data-delivery-price]");
const deliveryTermInput = document.querySelector("[data-delivery-term-hidden]");
const deliveryRegionInput = document.querySelector("[data-delivery-region-hidden]");
const deliveryCityInput = document.querySelector("[data-delivery-city-hidden]");
const deliveryAddressInput = document.querySelector("[data-delivery-address-hidden]");
const deliveryCommentInput = document.querySelector("[data-delivery-comment-hidden]");
const orderTotalInput = document.querySelector("[data-order-total]");

// ---------------------------------------------------------------- оформление
const checkoutForm = document.querySelector(".checkout-form");
const customerTypeInputs = document.querySelectorAll("[data-customer-type]");
const customerFieldsets = document.querySelectorAll("[data-customer-fields]");
const paymentCardBoxes = document.querySelectorAll(".co-card-payment");
const leadOnlySection = document.querySelector("[data-lead-only]");
const commentToggle = document.querySelector("[data-comment-toggle]");
const commentBox = document.querySelector("[data-comment-box], #co-comment-box");
const cartSubtotalNodes = document.querySelectorAll("[data-cart-subtotal]");
const cartDeliveryTotalNodes = document.querySelectorAll("[data-cart-delivery-total]");
const cartGrandTotalNodes = document.querySelectorAll("[data-cart-grand-total]");
const discountRow = document.querySelector("[data-discount-row]");
const discountValue = document.querySelector("[data-cart-discount-total]");
const totalLabel = document.querySelector("[data-total-label]");

/*
 * Правила проверки приезжают с сервера вместе со страницей — тем же
 * массивом, по которому сервер потом проверяет пришедший заказ. Держать их
 * ещё и здесь, в коде, значило бы завести вторую копию, которая рано или
 * поздно разойдётся с первой: сайт принимал бы то, что сервер отвергает.
 */
const fieldRules = (() => {
  try {
    return JSON.parse(document.querySelector("[data-co-rules]")?.textContent || "{}");
  } catch {
    return {};
  }
})();
let lastFocusedTrigger = null;
let activeProductTitle = "";
let activeProductCard = null;
let cart = loadCart();

// Scroll save/restore across navigation now lives in src/scroll-restore.js,
// shared by every page (home, category pages, product pages) instead of
// being duplicated here with a single unscoped key.

function clearNode(node) {
  while (node?.firstChild) {
    node.removeChild(node.firstChild);
  }
}

function setOrderModel(title) {
  if (!orderSelect || !title) {
    return;
  }

  const normalizedTitle = title.trim().toLowerCase();
  const match = Array.from(orderSelect.options).find(
    (option) => option.textContent.trim().toLowerCase() === normalizedTitle,
  );

  if (match) {
    orderSelect.value = match.textContent.trim();
  }
}

function closeViewer({ restoreFocus = true } = {}) {
  if (!viewer) {
    return;
  }

  viewer.classList.remove("is-open");
  viewer.setAttribute("aria-hidden", "true");
  document.body.classList.remove("viewer-open");
  window.ScrollLock?.unlock("viewer");
  // Адрес страницы возвращается к чистому виду. Иначе после закрытия в URL
  // оставался #e5-2699-v4, и любое событие hashchange (кнопка «назад»,
  // перезагрузка, поделиться ссылкой) снова открывало это же окно.
  clearProductHash();
  if (viewerImage) {
    viewerImage.removeAttribute("src");
    viewerImage.alt = "";
  }
  if (viewerTitle) {
    viewerTitle.textContent = "";
  }
  if (viewerPrice) {
    viewerPrice.textContent = "";
  }
  if (viewerLead) {
    viewerLead.textContent = "";
  }
  clearNode(viewerSpecs);
  clearNode(viewerTags);
  activeProductTitle = "";

  if (restoreFocus) {
    lastFocusedTrigger?.focus?.({ preventScroll: true });
  }
}

function getHashSlug(hash = window.location.hash) {
  const rawHash = String(hash || "").replace(/^#/, "");
  if (!rawHash) {
    return "";
  }

  try {
    return decodeURIComponent(rawHash).trim().toLowerCase();
  } catch {
    return rawHash.trim().toLowerCase();
  }
}

function getProductSlugFromQuery(search = window.location.search) {
  try {
    return new URLSearchParams(search).get("product")?.trim().toLowerCase() || "";
  } catch {
    return "";
  }
}

function getProductCardFromHash(hash = window.location.hash) {
  const slug = getHashSlug(hash) || getProductSlugFromQuery();
  if (!slug) {
    return null;
  }

  return Array.from(productCards).find((card) => card.id?.toLowerCase() === slug) || null;
}

function updateProductHash(productId) {
  if (!productId || window.location.hash === `#${productId}`) {
    return;
  }

  try {
    window.history.replaceState(null, "", `${window.location.pathname}${window.location.search}#${productId}`);
  } catch {
    window.location.hash = productId;
  }
}

function clearProductHash() {
  if (!window.location.hash) {
    return;
  }

  try {
    window.history.replaceState(null, "", `${window.location.pathname}${window.location.search}`);
  } catch {
    // replaceState может быть недоступен — тогда просто оставляем адрес как есть,
    // это не мешает работе окна.
  }
}

function openProductViewer(card, trigger, { updateHash = true } = {}) {
  const image = card.querySelector(".model-photo img");
  const title = card.querySelector("h3")?.textContent?.trim() || image?.alt || "Процессор Xeon";
  const priceNode = card.querySelector(".model-price");
  const lead = card.querySelector("p:not(.model-price)")?.textContent?.trim() || "";
  const specs = card.querySelector(".model-specs");
  const tags = card.querySelector(".model-tags");

  if (!viewer || !viewerImage || !viewerTitle || !viewerPrice || !viewerLead || !viewerSpecs || !viewerTags || !image) {
    return;
  }

  lastFocusedTrigger = trigger || document.activeElement;
  activeProductTitle = title;
  activeProductCard = card;
  viewerImage.src = image.currentSrc || image.src;
  viewerImage.alt = image.alt;
  viewerTitle.textContent = title;
  // Копируем узел целиком, чтобы зачёркнутая старая цена осталась зачёркнутой.
  clearNode(viewerPrice);
  priceNode?.childNodes.forEach((node) => viewerPrice.appendChild(node.cloneNode(true)));
  viewerLead.textContent = lead;

  /*
   * Наличие в быстром просмотре.
   *
   * Покупатель часто вообще не открывает страницу товара — кладёт в корзину
   * прямо отсюда. Значит, «Под заказ» и срок поставки он должен видеть
   * здесь же, а не узнавать от менеджера после оплаты.
   */
  if (viewerStock && viewerStockLabel && viewerStockNote) {
    const stockCode = card.dataset.stock || "in_stock";
    const stockLabel = card.dataset.stockLabel || "";
    const stockNote = card.dataset.stockNote || "";

    viewerStock.className = `product-stock stock-${stockCode}`;
    viewerStock.hidden = stockLabel === "";
    viewerStockLabel.textContent = stockLabel;
    viewerStockNote.textContent = stockCode === "in_stock" ? "" : stockNote;
    viewerStockNote.hidden = viewerStockNote.textContent === "";
  }

  clearNode(viewerSpecs);
  clearNode(viewerTags);

  specs?.querySelectorAll("div").forEach((row) => {
    viewerSpecs.appendChild(row.cloneNode(true));
  });

  tags?.querySelectorAll("li").forEach((tag) => {
    viewerTags.appendChild(tag.cloneNode(true));
  });

  if (viewerFullLink && card.id) {
    viewerFullLink.href = `/products/${card.id}/`;
  }

  viewer.classList.add("is-open");
  viewer.setAttribute("aria-hidden", "false");
  document.body.classList.add("viewer-open");
  window.ScrollLock?.lock("viewer");
  if (updateHash) {
    updateProductHash(card.id);
  }
  viewerClose?.focus({ preventScroll: true });
}

function openProductFromHash({ closeStaleViewer = false } = {}) {
  const card = getProductCardFromHash();
  if (card) {
    openProductViewer(card, card.querySelector(".model-photo") || card, { updateHash: false });
    return true;
  }

  if (closeStaleViewer && viewer?.classList.contains("is-open")) {
    closeViewer({ restoreFocus: false });
  }

  return false;
}

function loadCart() {
  try {
    const saved = JSON.parse(localStorage.getItem("comp-uter-cart") || "[]");
    if (!Array.isArray(saved)) {
      return [];
    }

    // Normalise every field coming back from storage: the cart is rendered
    // into the DOM and posted to the order API, so it must not carry
    // arbitrary types or unbounded strings.
    return saved
      .filter((item) => item && typeof item === "object" && item.id && item.title)
      .slice(0, 50)
      .map((item) => ({
        id: String(item.id).replace(/[^a-zA-Z0-9_-]/g, "").slice(0, 64),
        title: String(item.title).slice(0, 200),
        price: Math.max(0, Number(item.price) || 0),
        oldPrice: Math.max(0, Number(item.oldPrice) || 0),
        priceText: String(item.priceText ?? "").slice(0, 40),
        unit: String(item.unit ?? "шт.").slice(0, 16),
        image: String(item.image ?? "").slice(0, 300),
        qty: Math.min(999, Math.max(1, parseInt(item.qty, 10) || 1)),
      }))
      .filter((item) => item.id && item.price > 0);
  } catch {
    return [];
  }
}

function saveCart() {
  try {
    localStorage.setItem("comp-uter-cart", JSON.stringify(cart));
  } catch {
    // The cart still works during the current visit if browser storage is unavailable.
  }
}

function formatMoney(value) {
  const amount = Math.max(0, Number(value) || 0);
  return `${new Intl.NumberFormat("ru-RU", { maximumFractionDigits: 2 }).format(amount)}\u00a0₽`;
}

// Цена берётся из data-price, который сервер печатает из /data/prices.json,
// а не из текста карточки: так цена в корзине не может разойтись с ценой на
// странице (пробелы, знак рубля и «Цена по запросу» больше ни на что не влияют).
function getCardPrice(card) {
  const raw = card.dataset.price;
  if (raw === undefined || raw === "") {
    return 0;
  }

  const price = Number(String(raw).replace(",", "."));
  return Number.isFinite(price) && price > 0 ? price : 0;
}

function getProductFromCard(card) {
  const image = card.querySelector(".model-photo img");
  const title = card.querySelector("h3")?.textContent?.trim() || image?.alt || "Xeon";
  const price = getCardPrice(card);
  const oldRaw = Number(String(card.dataset.oldPrice ?? "").replace(",", "."));
  // Старая цена имеет смысл, только если она выше нынешней. Сервер считает
  // выгоду так же и по своей базе — здесь это лишь для показа в форме.
  const oldPrice = Number.isFinite(oldRaw) && oldRaw > price ? oldRaw : 0;

  return {
    id: card.id,
    title,
    price,
    oldPrice,
    priceText: price > 0 ? formatMoney(price) : "",
    unit: card.dataset.unit || "шт.",
    image: image?.getAttribute("src") || image?.currentSrc || "",
  };
}

function getCartQuantity() {
  return cart.reduce((sum, item) => sum + item.qty, 0);
}

function getCartSubtotal() {
  return cart.reduce((sum, item) => sum + item.price * item.qty, 0);
}

/** Выгода против зачёркнутой цены. Нет старых цен — нет и строки «Скидка». */
function getCartDiscount() {
  return cart.reduce(
    (sum, item) => sum + (item.oldPrice > item.price ? (item.oldPrice - item.price) * item.qty : 0),
    0,
  );
}

/**
 * Выбранный способ доставки.
 *
 * price === null означает «по тарифам службы» — это не ноль и не бесплатно,
 * поэтому итог в таком случае показывается как «сумма товаров + доставка».
 *
 * Правило «бесплатно от суммы» применяется и здесь, и на сервере. Здесь —
 * чтобы покупатель видел ноль сразу; там — потому что решает всё равно
 * сервер, а не браузер.
 */
function getDeliverySelection() {
  const selected = Array.from(deliveryOptions).find((option) => option.checked);

  if (!selected) {
    return { chosen: false, name: "", method: "", price: null, needsDetails: false, term: "" };
  }

  const rawPrice = selected.dataset.deliveryPrice ?? "";
  const freeFromRaw = selected.dataset.deliveryFreeFrom ?? "";
  const freeFrom = freeFromRaw === "" ? null : Number(freeFromRaw);
  let price = rawPrice === "" ? null : Number(rawPrice) || 0;

  if (price !== null && freeFrom !== null && Number.isFinite(freeFrom) && getCartSubtotal() >= freeFrom) {
    price = 0;
  }

  return {
    chosen: true,
    name: selected.value,
    method: selected.dataset.deliveryTitle || selected.value,
    price,
    needsDetails: selected.dataset.deliveryDetails === "true",
    term: selected.dataset.deliveryTerm || "",
  };
}

function getOrderTotal() {
  const subtotal = getCartSubtotal();
  const delivery = getDeliverySelection();
  return delivery.price === null ? null : subtotal + delivery.price;
}

// Cart rows are built with DOM APIs rather than an innerHTML template on
// purpose: item titles/images/ids come back from localStorage, which is
// attacker-writable in shared-browser and malicious-extension scenarios.
// textContent/setAttribute keep that data inert.
function buildCartRow(item) {
  const row = document.createElement("article");
  row.className = "cart-item";
  row.dataset.cartItem = item.id;

  const image = document.createElement("img");
  // Reject javascript:/data: URLs — only same-origin or http(s) images.
  const safeSrc = /^(https?:)?\/\//i.test(item.image) || item.image.startsWith("/") ? item.image : "";
  if (safeSrc) {
    image.src = safeSrc;
  }
  image.alt = "";
  image.loading = "lazy";

  const copy = document.createElement("div");
  copy.className = "cart-item-copy";

  const title = document.createElement("strong");
  title.textContent = item.title;

  const unitPrice = document.createElement("span");
  unitPrice.textContent = `${formatMoney(item.price)} за ${item.unit || "шт."}`;

  const actions = document.createElement("div");
  actions.className = "cart-item-actions";

  const qtyBox = document.createElement("div");
  qtyBox.className = "cart-qty";
  qtyBox.setAttribute("aria-label", `Количество: ${item.title}`);

  const decrease = document.createElement("button");
  decrease.type = "button";
  decrease.dataset.cartDecrease = item.id;
  decrease.setAttribute("aria-label", "Уменьшить количество");
  decrease.textContent = "−";

  const qty = document.createElement("span");
  qty.textContent = String(item.qty);

  const increase = document.createElement("button");
  increase.type = "button";
  increase.dataset.cartIncrease = item.id;
  increase.setAttribute("aria-label", "Увеличить количество");
  increase.textContent = "+";

  const remove = document.createElement("button");
  remove.className = "cart-remove";
  remove.type = "button";
  remove.dataset.cartRemove = item.id;
  remove.textContent = "Удалить";

  qtyBox.append(decrease, qty, increase);
  actions.append(qtyBox, remove);
  copy.append(title, unitPrice, actions);
  row.append(image, copy);
  return row;
}

function renderCart() {
  const subtotal = getCartSubtotal();
  const count = getCartQuantity();

  if (cartCount) {
    cartCount.textContent = String(count);
  }
  if (cartHeaderTotal) {
    cartHeaderTotal.textContent = formatMoney(subtotal);
  }
  if (cartEmpty) {
    cartEmpty.hidden = cart.length > 0;
  }

  clearNode(cartList);
  cart.forEach((item) => {
    cartList?.appendChild(buildCartRow(item));
  });

  renderCheckout();
  syncCartFields();
}

/* ==========================================================================
   Оформление заказа
   ========================================================================== */

// Отмечаем, что способ оплаты выбрали руками: дальше подставлять свой
// вариант при каждом переключении типа покупателя нельзя.
let paymentTouched = false;

/** Выбранный тип покупателя: individual или legal. */
function getCustomerType() {
  const checked = Array.from(customerTypeInputs).find((input) => input.checked);
  return checked?.value === "legal" ? "legal" : "individual";
}

/**
 * Показывает поля выбранного типа покупателя и убирает поля другого.
 *
 * Скрытый набор помечается disabled, а не просто прячется. Это важно:
 * отключённые поля браузер не отправляет и не проверяет, поэтому пустой ИНН
 * в скрытой форме юрлица не мешает физлицу оформить заказ. Значения при этом
 * никуда не деваются — переключился обратно и всё на месте.
 */
function applyCustomerType() {
  const type = getCustomerType();

  customerFieldsets.forEach((set) => {
    const mine = set.dataset.customerFields === type;
    set.hidden = !mine;
    set.disabled = !mine;
    if (!mine) {
      set.querySelectorAll("[data-co-field]").forEach((field) => clearFieldError(field.name));
    }
  });

  document.querySelectorAll(".co-switch-option").forEach((option) => {
    option.classList.toggle("is-active", option.querySelector("input")?.checked === true);
  });

  renderPaymentMethods(type);
}

/**
 * Оставляет только те способы оплаты, которые доступны этому покупателю.
 *
 * Способ «для организаций» физлицу не показывается, и наоборот. Если
 * выбранный способ пропал из списка, выбирается тот, что помечен как
 * основной для этого типа покупателя, — чтобы форма никогда не оставалась
 * без выбранной оплаты.
 */
function renderPaymentMethods(type) {
  if (!paymentCardBoxes.length) {
    return;
  }

  let selectedStillVisible = false;
  const visible = [];

  paymentCardBoxes.forEach((box) => {
    const audience = box.dataset.paymentAudience || "both";
    const suits = audience === "both" || audience === type;
    const input = box.querySelector("input");

    box.hidden = !suits;
    if (input) {
      input.disabled = !suits;
      if (suits) {
        visible.push(box);
        if (input.checked) {
          selectedStillVisible = true;
        }
      } else if (input.checked) {
        input.checked = false;
      }
    }
  });

  /*
   * Пока покупатель сам не выбрал оплату, ставим ту, что магазин пометил
   * основной для этого типа: организация чаще всего платит по счёту, и
   * заставлять её каждый раз это отмечать незачем. Как только выбор сделан
   * руками, мы его больше не трогаем — даже при переключении типа, если
   * выбранный способ по-прежнему доступен.
   */
  const useDefault = !paymentTouched || !selectedStillVisible;

  if (useDefault && visible.length) {
    const preferred = visible.find((box) =>
      type === "legal" ? box.dataset.paymentDefaultLegal === "1" : box.dataset.paymentDefaultIndividual === "1",
    );
    visible.forEach((box) => {
      const input = box.querySelector("input");
      if (input) {
        input.checked = false;
      }
    });
    const input = (preferred || visible[0]).querySelector("input");
    if (input) {
      input.checked = true;
    }
  }

  paymentCardBoxes.forEach((box) => {
    box.classList.toggle("is-active", box.querySelector("input")?.checked === true);
  });
  clearFieldError("payment_code");
}

/** Пересобирает блок доставки: вариант, стоимость, срок, поле адреса. */
function renderDeliveryBlock() {
  const delivery = getDeliverySelection();
  const subtotal = getCartSubtotal();

  document.querySelectorAll("[data-delivery-cards] .co-card").forEach((card) => {
    const input = card.querySelector("input");
    card.classList.toggle("is-active", input?.checked === true);

    // «Бесплатно от суммы» показываем прямо на карточке: покупатель должен
    // видеть ноль до того, как выберет способ, а не после.
    const priceNode = card.querySelector("[data-delivery-card-price]");
    const raw = input?.dataset.deliveryPrice ?? "";
    const freeFromRaw = input?.dataset.deliveryFreeFrom ?? "";
    const freeFrom = freeFromRaw === "" ? null : Number(freeFromRaw);
    if (priceNode && raw !== "") {
      const base = Number(raw) || 0;
      const free = freeFrom !== null && Number.isFinite(freeFrom) && subtotal >= freeFrom;
      priceNode.textContent = free || base === 0 ? "бесплатно" : formatMoney(base);
    }
  });

  if (deliveryOptionBox) {
    deliveryOptionBox.hidden = !delivery.chosen;
  }
  if (deliveryOptionTitle) {
    deliveryOptionTitle.textContent = delivery.method || "";
  }
  if (deliveryNote) {
    deliveryNote.textContent = delivery.needsDetails
      ? "Доставка производится до подъезда, до шлагбаума или до другого удобного для вас места, куда может доехать транспорт."
      : "";
    deliveryNote.hidden = !delivery.needsDetails;
  }
  if (deliverySummaryPrice) {
    deliverySummaryPrice.textContent = !delivery.chosen
      ? "не выбрано"
      : delivery.price === null
        ? "по тарифам службы доставки"
        : formatMoney(delivery.price);
  }
  if (deliverySummaryTermRow) {
    deliverySummaryTermRow.hidden = !delivery.chosen || delivery.term === "";
  }
  if (deliverySummaryTerm) {
    deliverySummaryTerm.textContent = delivery.term;
  }
  if (deliveryAddressField) {
    deliveryAddressField.hidden = !delivery.needsDetails;
    if (!delivery.needsDetails) {
      clearFieldError("address");
    }
  }
  if (cityRequiredMark) {
    cityRequiredMark.hidden = !delivery.needsDetails;
  }
}

/** Суммы внизу формы и в корзине. */
function renderTotals() {
  const subtotal = getCartSubtotal();
  const delivery = getDeliverySelection();
  const orderTotal = getOrderTotal();
  const discount = getCartDiscount();

  cartSubtotalNodes.forEach((node) => {
    node.textContent = formatMoney(subtotal);
  });
  cartDeliveryTotalNodes.forEach((node) => {
    node.textContent = !delivery.chosen
      ? "не выбрано"
      : delivery.price === null
        ? "по тарифам службы доставки"
        : formatMoney(delivery.price);
  });
  cartGrandTotalNodes.forEach((node) => {
    node.textContent = orderTotal === null ? formatMoney(subtotal) : formatMoney(orderTotal);
  });

  if (totalLabel) {
    // Пока стоимость доставки неизвестна, итог честнее назвать неполным,
    // чем показать сумму товаров как окончательную.
    totalLabel.textContent = orderTotal === null ? "Итого без учёта доставки" : "Итого";
  }
  if (discountRow) {
    discountRow.hidden = discount <= 0;
  }
  if (discountValue) {
    discountValue.textContent = formatMoney(discount);
  }
}

/** Всё оформление разом: доставка, оплата, суммы, режим формы. */
function renderCheckout() {
  if (!checkoutForm) {
    return;
  }

  // Пустая корзина — это заявка «подберите мне», а не покупка: тогда нужны
  // поля «что подобрать», и они же становятся обязательными.
  if (leadOnlySection) {
    leadOnlySection.hidden = cart.length > 0;
  }
  if (orderSelect) {
    orderSelect.disabled = cart.length > 0;
    if (cart.length > 0) {
      orderSelect.value = "Заказ из корзины";
    }
  }
  const categorySelect = document.querySelector("[data-order-category]");
  if (categorySelect) {
    categorySelect.disabled = cart.length > 0;
  }

  renderDeliveryBlock();
  renderTotals();
}

/* ----------------------------------------------------------- проверка полей */

function fieldNode(name) {
  return checkoutForm?.querySelector(`[data-co-field="${name}"]`)
    || checkoutForm?.querySelector(`[name="${name}"]`)
    || null;
}

function setFieldError(name, message) {
  const box = checkoutForm?.querySelector(`[data-co-error-for="${name}"]`);
  if (box) {
    box.textContent = message;
  }
  const node = fieldNode(name);
  node?.classList.toggle("is-invalid", message !== "");
  if (node && message !== "") {
    node.setAttribute("aria-invalid", "true");
  } else {
    node?.removeAttribute("aria-invalid");
  }
}

function clearFieldError(name) {
  setFieldError(name, "");
}

/** Проверяет одно поле по правилу с сервера. Возвращает текст ошибки или "". */
function checkField(name) {
  const rule = fieldRules[name];
  const node = fieldNode(name);
  if (!rule || !node || node.disabled || node.closest("fieldset")?.disabled) {
    return "";
  }

  const value = String(node.value || "").trim();

  if (value === "") {
    return rule.required ? `Заполните поле «${rule.label}».` : "";
  }
  if (rule.pattern && !new RegExp(rule.pattern, "u").test(value)) {
    return rule.message;
  }
  return "";
}

/**
 * Проверяет форму целиком.
 *
 * @returns {{name:string,message:string,node:Element}[]} ошибки по порядку полей
 */
function validateCheckout() {
  const problems = [];
  const type = getCustomerType();

  Object.keys(fieldRules).forEach((name) => {
    if (!fieldRules[name].types.includes(type)) {
      return;
    }
    const message = checkField(name);
    setFieldError(name, message);
    if (message) {
      problems.push({ name, message, node: fieldNode(name) });
    }
  });

  // Доставка: город и адрес обязательны только там, где без них не доехать.
  const delivery = getDeliverySelection();
  const cityValue = deliveryCity?.value.trim() || "";
  const addressValue = deliveryAddress?.value.trim() || "";

  if (cart.length && delivery.needsDetails && cityValue === "") {
    setFieldError("city", "Укажите город доставки.");
    problems.push({ name: "city", message: "Укажите город доставки.", node: deliveryCity });
  } else {
    clearFieldError("city");
  }

  if (cart.length && delivery.needsDetails && addressValue === "") {
    setFieldError("address", "Укажите улицу, дом и квартиру.");
    problems.push({ name: "address", message: "Укажите улицу, дом и квартиру.", node: deliveryAddress });
  } else if (!delivery.needsDetails || addressValue !== "") {
    clearFieldError("address");
  }

  // Способ оплаты: если варианты вообще есть, один должен быть выбран.
  const paymentInputs = Array.from(checkoutForm?.querySelectorAll('input[name="payment_code"]') || [])
    .filter((input) => !input.disabled);
  if (paymentInputs.length && !paymentInputs.some((input) => input.checked)) {
    const message = "Выберите способ оплаты.";
    setFieldError("payment_code", message);
    problems.push({ name: "payment_code", message, node: paymentInputs[0] });
  } else {
    clearFieldError("payment_code");
  }

  // Заявка без корзины обязана сказать, о чём она.
  if (!cart.length && orderSelect && !orderSelect.disabled && orderSelect.value === "") {
    const message = "Выберите модель или задачу.";
    setFieldError("goal", message);
    problems.push({ name: "goal", message, node: orderSelect });
  } else {
    clearFieldError("goal");
  }

  // Согласия.
  const consents = Array.from(checkoutForm?.querySelectorAll("[data-co-consent]") || []);
  const missing = consents.filter((box) => !box.checked);
  if (missing.length) {
    const message = missing.length === consents.length
      ? "Подтвердите согласие с пользовательским соглашением и политикой обработки персональных данных."
      : "Подтвердите оба согласия — без них заказ оформить нельзя.";
    setFieldError("consents", message);
    problems.push({ name: "consents", message, node: missing[0] });
  } else {
    clearFieldError("consents");
  }

  return problems;
}

/** Подводит покупателя к первому незаполненному полю и подсвечивает его. */
function focusFirstProblem(problems) {
  const first = problems[0];
  if (!first?.node) {
    return;
  }

  // Поле может лежать в свёрнутом блоке комментария — раскрываем.
  if (commentBox && commentBox.contains(first.node) && commentBox.hidden) {
    toggleComment(true);
  }

  const box = first.node.closest(".co-field, .co-section, .consent-label") || first.node;
  const top = box.getBoundingClientRect().top + window.scrollY - 120;
  window.scrollTo({ top: Math.max(0, top), behavior: "smooth" });

  // Фокус ставим после прокрутки: иначе браузер прокрутит ещё раз, по-своему.
  window.setTimeout(() => {
    try {
      first.node.focus({ preventScroll: true });
    } catch {
      // Скрытый или отключённый элемент сфокусировать нельзя — не беда.
    }
  }, 350);
}

/* --------------------------------------------- запоминание введённого */

const CHECKOUT_STORAGE_KEY = "comp-uter-checkout";

/*
 * Личного кабинета на сайте нет, поэтому подставлять данные «авторизованного
 * пользователя» неоткуда. Но покупатель, который вернулся за вторым заказом,
 * не должен снова набирать ИНН и банковские реквизиты — поэтому форма
 * помнит то, что он ввёл сам, и только в его же браузере. На сервер это
 * ничего не отправляет.
 */
function rememberCheckout() {
  if (!checkoutForm) {
    return;
  }

  const data = { customer_type: getCustomerType() };
  checkoutForm.querySelectorAll("[data-co-field]").forEach((field) => {
    if (field.value) {
      data[field.name] = String(field.value).slice(0, 300);
    }
  });
  [
    ["region", deliveryRegion],
    ["city", deliveryCity],
    ["address", deliveryAddress],
  ].forEach(([key, node]) => {
    if (node?.value) {
      data[key] = String(node.value).slice(0, 300);
    }
  });

  try {
    localStorage.setItem(CHECKOUT_STORAGE_KEY, JSON.stringify(data));
  } catch {
    // Заказ оформляется и без запоминания.
  }
}

function restoreCheckout() {
  if (!checkoutForm) {
    return;
  }

  let data = {};
  try {
    data = JSON.parse(localStorage.getItem(CHECKOUT_STORAGE_KEY) || "{}");
  } catch {
    return;
  }
  if (!data || typeof data !== "object") {
    return;
  }

  if (data.customer_type === "legal") {
    const legal = Array.from(customerTypeInputs).find((input) => input.value === "legal");
    if (legal) {
      legal.checked = true;
    }
  }

  checkoutForm.querySelectorAll("[data-co-field]").forEach((field) => {
    const saved = data[field.name];
    if (typeof saved === "string" && saved !== "") {
      field.value = saved;
    }
  });
  if (typeof data.region === "string" && deliveryRegion) {
    deliveryRegion.value = data.region;
  }
  if (typeof data.city === "string" && deliveryCity) {
    deliveryCity.value = data.city;
  }
  if (typeof data.address === "string" && deliveryAddress) {
    deliveryAddress.value = data.address;
  }
}

/* ------------------------------------------------ комментарий к заказу */

function toggleComment(open) {
  if (!commentBox || !commentToggle) {
    return;
  }
  const next = open === undefined ? commentBox.hidden : open;
  commentBox.hidden = !next;
  commentToggle.setAttribute("aria-expanded", String(next));
}

function addToCart(card, trigger) {
  const product = getProductFromCard(card);
  if (!product.id || !product.price) {
    return;
  }

  const existing = cart.find((item) => item.id === product.id);
  if (existing) {
    existing.qty += 1;
  } else {
    cart.push({ ...product, qty: 1 });
  }

  saveCart();
  renderCart();
  if (trigger) {
    const originalText = trigger.textContent;
    trigger.textContent = "Добавлено";
    window.setTimeout(() => {
      trigger.textContent = originalText;
    }, 900);
  }
  openCart();
}

function updateCartQuantity(productId, delta) {
  const item = cart.find((entry) => entry.id === productId);
  if (!item) {
    return;
  }

  item.qty += delta;
  if (item.qty <= 0) {
    cart = cart.filter((entry) => entry.id !== productId);
  }

  saveCart();
  renderCart();
}

function removeCartItem(productId) {
  cart = cart.filter((entry) => entry.id !== productId);
  saveCart();
  renderCart();
}

function openCart() {
  if (!cartDrawer) {
    return;
  }

  cartDrawer.classList.add("is-open");
  cartDrawer.setAttribute("aria-hidden", "false");
  if (cartBackdrop) {
    cartBackdrop.hidden = false;
  }
  document.body.classList.add("cart-open");
  window.ScrollLock?.lock("cart");
  cartCheckout?.focus({ preventScroll: true });
}

function closeCart() {
  if (!cartDrawer) {
    return;
  }

  cartDrawer.classList.remove("is-open");
  cartDrawer.setAttribute("aria-hidden", "true");
  if (cartBackdrop) {
    cartBackdrop.hidden = true;
  }
  document.body.classList.remove("cart-open");
  window.ScrollLock?.unlock("cart");
}

function buildCartSummaryLines() {
  const delivery = getDeliverySelection();
  const region = deliveryRegion?.value.trim() || "";
  const city = deliveryCity?.value.trim() || "";
  const address = delivery.needsDetails ? deliveryAddress?.value.trim() || "" : "";
  const comment = deliveryComment?.value.trim() || "";
  const subtotal = getCartSubtotal();
  const orderTotal = getOrderTotal();
  const discount = getCartDiscount();
  const lines = cart.map((item) => `${item.title}: ${item.qty} шт. × ${formatMoney(item.price)} = ${formatMoney(item.price * item.qty)}`);

  lines.push(`Товары: ${formatMoney(subtotal)}`);
  if (discount > 0) {
    lines.push(`Скидка: ${formatMoney(discount)}`);
  }
  if (delivery.chosen) {
    lines.push(`Доставка: ${delivery.method}${delivery.price === null ? " · по тарифам службы доставки" : ` · ${formatMoney(delivery.price)}`}`);
  }
  if (delivery.term) {
    lines.push(`Срок доставки: ${delivery.term}`);
  }
  if (region) {
    lines.push(`Регион: ${region}`);
  }
  if (city) {
    lines.push(`Город: ${city}`);
  }
  if (address) {
    lines.push(`Адрес/ПВЗ: ${address}`);
  }
  if (comment) {
    lines.push(`Пожелание по доставке: ${comment}`);
  }
  lines.push(`Итого: ${orderTotal === null ? `${formatMoney(subtotal)} + доставка` : formatMoney(orderTotal)}`);

  return lines;
}

function syncCartFields() {
  const delivery = getDeliverySelection();
  const subtotal = getCartSubtotal();
  const orderTotal = getOrderTotal();
  const region = deliveryRegion?.value.trim() || "";
  const city = deliveryCity?.value.trim() || "";
  const address = delivery.needsDetails ? deliveryAddress?.value.trim() || "" : "";
  const comment = deliveryComment?.value.trim() || "";

  if (cartItemsInput) {
    cartItemsInput.value = cart.length ? JSON.stringify(cart) : "";
  }
  if (cartTotalInput) {
    cartTotalInput.value = cart.length ? String(subtotal) : "";
  }
  if (cartDiscountInput) {
    cartDiscountInput.value = cart.length ? String(getCartDiscount()) : "";
  }
  if (deliveryMethodInput) {
    deliveryMethodInput.value = cart.length ? delivery.name || delivery.method : "";
  }
  if (deliveryPriceInput) {
    deliveryPriceInput.value = cart.length && delivery.price !== null ? String(delivery.price) : "";
  }
  if (deliveryTermInput) {
    deliveryTermInput.value = cart.length ? delivery.term : "";
  }
  if (deliveryRegionInput) {
    deliveryRegionInput.value = cart.length ? region : "";
  }
  if (deliveryCityInput) {
    deliveryCityInput.value = cart.length ? city : "";
  }
  if (deliveryAddressInput) {
    deliveryAddressInput.value = cart.length ? address : "";
  }
  if (deliveryCommentInput) {
    deliveryCommentInput.value = cart.length ? comment : "";
  }
  if (orderTotalInput) {
    orderTotalInput.value = cart.length && orderTotal !== null ? String(orderTotal) : "";
  }
  if (quantityInput) {
    quantityInput.value = cart.length ? String(getCartQuantity()) : "";
  }

  if (orderCartSummary && orderCartLines) {
    orderCartSummary.hidden = cart.length === 0;
    clearNode(orderCartLines);
    if (cart.length) {
      buildCartSummaryLines().forEach((line, index) => {
        const row = document.createElement(index < cart.length ? "span" : "strong");
        row.textContent = line;
        orderCartLines.appendChild(row);
      });
    }
  }
}

/*
 * Проверка перед переходом к оформлению.
 *
 * Раньше здесь же спрашивали город и адрес: они были в самой корзине.
 * Теперь доставка выбирается в форме заказа, и корзине остаётся проверить
 * единственное, что относится к ней самой, — что в ней что-то есть.
 */
function validateCartForCheckout() {
  if (!cart.length) {
    if (cartError) {
      cartError.textContent = "Добавьте хотя бы один товар в корзину.";
    }
    openCart();
    return false;
  }

  if (cartError) {
    cartError.textContent = "";
  }
  return true;
}

function prepareCartForSubmit() {
  if (cart.length && orderSelect) {
    orderSelect.value = "Заказ из корзины";
  }
  syncCartFields();
  return true;
}

function moveCartToOrderForm() {
  if (!validateCartForCheckout()) {
    return;
  }

  prepareCartForSubmit();
  closeCart();

  // Category pages (/processors/, /drives/) show the same cart drawer but have
  // no order form of their own — send the visitor to the form on the homepage.
  // The cart itself lives in localStorage, so nothing is lost in the jump.
  if (!form) {
    window.location.href = "/#order";
    return;
  }

  // Прокручиваем к самому разделу заказа, а не к тегу <form> внутри него.
  //
  // Раньше здесь были две прокрутки с разными целями: смена хеша везёт
  // браузер к разделу id="order", а scrollIntoView — к форме id="email",
  // которая лежит ниже заголовка раздела. Две плавные прокрутки шли
  // одновременно, побеждала вторая, и покупатель проезжал мимо заголовка:
  // на компьютере на 86 пикселей, на телефоне — на 466, то есть заголовок
  // «Оставьте заявку…» оставался выше экрана.
  //
  // Прокрутку всё равно делаем руками: если в адресе уже стоит #order
  // (покупатель пришёл к форме, потом открыл корзину), присвоение того же
  // хеша ничего не сдвинет.
  const orderSection = document.getElementById("order") || form;
  window.location.hash = "order";
  orderSection.scrollIntoView({ behavior: "smooth", block: "start" });
}

productCards.forEach((card) => {
  const specs = card.querySelector(".model-specs");
  const photo = card.querySelector(".model-photo");

  if (specs && !card.querySelector(".model-detail-button")) {
    const detailButton = document.createElement("button");
    detailButton.className = "model-detail-button";
    detailButton.type = "button";
    detailButton.textContent = "Посмотреть все";
    detailButton.addEventListener("click", (event) => {
      event.stopPropagation();
      openProductViewer(card, detailButton);
    });
    specs.after(detailButton);
  }

  if (card.dataset.orderable !== "false" && !card.querySelector(".model-cart-button")) {
    const cartButton = document.createElement("button");
    cartButton.className = "model-cart-button";
    cartButton.type = "button";
    cartButton.textContent = "В корзину";
    cartButton.addEventListener("click", (event) => {
      event.stopPropagation();
      addToCart(card, cartButton);
    });
    card.querySelector(".model-detail-button")?.after(cartButton);
  }

  card.addEventListener("click", (event) => {
    if (event.target.closest("a, button, input, select, textarea")) {
      return;
    }

    const trigger = event.target.closest(".model-photo") || card;
    openProductViewer(card, trigger);
  });

  photo?.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      openProductViewer(card, photo);
    }
  });
});

requestLinks.forEach((link) => {
  link.addEventListener("click", () => {
    if (orderSelect && link.dataset.goal) {
      orderSelect.value = link.dataset.goal;
    }
  });
});

viewerOrder?.addEventListener("click", () => {
  if (activeProductCard) {
    addToCart(activeProductCard, viewerOrder);
  } else {
    setOrderModel(activeProductTitle);
    window.location.hash = "order";
  }
  closeViewer({ restoreFocus: false });
});

cartOpenButtons.forEach((button) => {
  button.addEventListener("click", openCart);
});

cartCloseButtons.forEach((button) => {
  button.addEventListener("click", closeCart);
});

cartList?.addEventListener("click", (event) => {
  const decrease = event.target.closest("[data-cart-decrease]");
  const increase = event.target.closest("[data-cart-increase]");
  const remove = event.target.closest("[data-cart-remove]");

  if (decrease) {
    updateCartQuantity(decrease.dataset.cartDecrease, -1);
  }
  if (increase) {
    updateCartQuantity(increase.dataset.cartIncrease, 1);
  }
  if (remove) {
    removeCartItem(remove.dataset.cartRemove);
  }
});

deliveryOptions.forEach((option) => {
  option.addEventListener("change", () => {
    renderCheckout();
    syncCartFields();
    clearFieldError("address");
    clearFieldError("city");
  });
});

[deliveryRegion, deliveryCity, deliveryAddress, deliveryComment].forEach((field) => {
  field?.addEventListener("input", () => {
    syncCartFields();
    rememberCheckout();
  });
});

cartCheckout?.addEventListener("click", moveCartToOrderForm);

/* ------------------------------------------------- события формы заказа */

customerTypeInputs.forEach((input) => {
  input.addEventListener("change", () => {
    applyCustomerType();
    rememberCheckout();
    if (status) {
      status.textContent = "";
    }
  });
});

paymentCardBoxes.forEach((box) => {
  box.querySelector("input")?.addEventListener("change", () => {
    paymentTouched = true;
    paymentCardBoxes.forEach((other) => {
      other.classList.toggle("is-active", other.querySelector("input")?.checked === true);
    });
    clearFieldError("payment_code");
  });
});

// Ошибку у поля убираем, как только покупатель его поправил: держать
// красную подпись под уже исправленным полем — значит спорить с человеком.
checkoutForm?.querySelectorAll("[data-co-field]").forEach((field) => {
  field.addEventListener("input", () => {
    if (field.classList.contains("is-invalid")) {
      setFieldError(field.name, checkField(field.name));
    }
  });
  field.addEventListener("change", rememberCheckout);
  field.addEventListener("blur", () => {
    if (field.value.trim() !== "") {
      setFieldError(field.name, checkField(field.name));
    }
  });
});

checkoutForm?.querySelectorAll("[data-co-consent]").forEach((box) => {
  box.addEventListener("change", () => clearFieldError("consents"));
});

commentToggle?.addEventListener("click", () => toggleComment());

if (checkoutForm) {
  restoreCheckout();
  applyCustomerType();
}

renderCart();
openProductFromHash();
window.addEventListener("hashchange", () => {
  openProductFromHash({ closeStaleViewer: true });
});

// Фотография в быстром просмотре открывается на весь экран поверх окна.
// Галерея лежит выше по z-index, а блокировка прокрутки считает открытые окна,
// поэтому закрытие галереи не разблокирует страницу под ещё открытым окном.
viewerImage?.addEventListener("click", () => {
  const src = viewerImage.currentSrc || viewerImage.getAttribute("src");
  if (src && window.ProductGallery) {
    window.ProductGallery.open([{ src, alt: viewerImage.alt || activeProductTitle }]);
  }
});

viewerClose?.addEventListener("click", closeViewer);
viewer?.addEventListener("click", (event) => {
  if (event.target === viewer) {
    closeViewer();
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape" && viewer?.classList.contains("is-open")) {
    closeViewer();
  }
  if (event.key === "Escape" && cartDrawer?.classList.contains("is-open")) {
    closeCart();
  }
  if (event.key === "Escape" && successDialog?.classList.contains("is-open")) {
    closeOrderSuccess();
  }
});
