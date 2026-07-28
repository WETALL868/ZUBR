const form = document.querySelector(".lead-form");
const status = document.querySelector(".form-status");
const phoneInput = document.querySelector("[data-phone-mask]");
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

if (cookieBanner && hasCookieConsent()) {
  cookieBanner.hidden = true;
}

cookieAccept?.addEventListener("click", () => {
  saveCookieConsent();
  if (cookieBanner) {
    cookieBanner.hidden = true;
  }
});

function formatPhone(value) {
  let digits = String(value || "").replace(/\D/g, "");
  if (digits.startsWith("8")) {
    digits = `7${digits.slice(1)}`;
  }
  if (digits.startsWith("7")) {
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

phoneInput?.addEventListener("focus", () => {
  if (!phoneInput.value) {
    phoneInput.value = "+7";
  }
});

phoneInput?.addEventListener("input", () => {
  phoneInput.value = formatPhone(phoneInput.value);
});

function showOrderSuccess(orderId) {
  if (!successDialog || !successNumber) {
    return;
  }

  successNumber.textContent = orderId;
  successDialog.classList.add("is-open");
  successDialog.setAttribute("aria-hidden", "false");
  document.body.classList.add("success-open");
  successOk?.focus();
}

function closeOrderSuccess() {
  if (!successDialog) {
    return;
  }

  successDialog.classList.remove("is-open");
  successDialog.setAttribute("aria-hidden", "true");
  document.body.classList.remove("success-open");
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
  if (!prepareCartForSubmit()) {
    return;
  }
  if (phoneInput) {
    phoneInput.value = formatPhone(phoneInput.value);
  }
  if (!form.reportValidity()) {
    return;
  }

  const data = new FormData(form);
  const payload = Object.fromEntries(data.entries());

  status.textContent = "";
  if (submitButton) {
    submitButton.disabled = true;
    submitButton.textContent = "Отправляем...";
  }

  try {
    const result = await sendOrder(payload);
    const orderId = result.orderId || "XS-NEW";
    form.reset();
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
      submitButton.textContent = "Оформить заказ";
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
const viewerClose = viewer?.querySelector(".photo-viewer-close");
const productCards = document.querySelectorAll(".model-card:not(.request-card)");
const requestLinks = document.querySelectorAll(".request-link");
const orderSelect = document.querySelector('select[name="goal"]');
const quantityInput = form?.querySelector('input[name="quantity"]');
const cartDrawer = document.querySelector(".cart-drawer");
const cartBackdrop = document.querySelector(".cart-backdrop");
const cartOpenButtons = document.querySelectorAll("[data-cart-open]");
const cartCloseButtons = document.querySelectorAll("[data-cart-close]");
const cartList = document.querySelector("[data-cart-list]");
const cartEmpty = document.querySelector("[data-cart-empty]");
const cartCount = document.querySelector("[data-cart-count]");
const cartHeaderTotal = document.querySelector("[data-cart-header-total]");
const cartSubtotal = document.querySelector("[data-cart-subtotal]");
const cartDeliveryTotal = document.querySelector("[data-cart-delivery-total]");
const cartGrandTotal = document.querySelector("[data-cart-grand-total]");
const cartCheckout = document.querySelector("[data-cart-checkout]");
const cartError = document.querySelector("[data-cart-error]");
const deliveryOptions = document.querySelectorAll('input[name="cartDelivery"]');
const deliveryFields = document.querySelector("[data-delivery-fields]");
const deliveryCity = document.querySelector("[data-delivery-city]");
const deliveryAddress = document.querySelector("[data-delivery-address]");
const deliveryComment = document.querySelector("[data-delivery-comment]");
const orderCartSummary = document.querySelector("[data-order-cart-summary]");
const orderCartLines = document.querySelector("[data-order-cart-lines]");
const cartItemsInput = document.querySelector("[data-cart-items]");
const cartTotalInput = document.querySelector("[data-cart-total]");
const deliveryMethodInput = document.querySelector("[data-delivery-method]");
const deliveryPriceInput = document.querySelector("[data-delivery-price]");
const deliveryCityInput = document.querySelector("[data-delivery-city-hidden]");
const deliveryAddressInput = document.querySelector("[data-delivery-address-hidden]");
const deliveryCommentInput = document.querySelector("[data-delivery-comment-hidden]");
const orderTotalInput = document.querySelector("[data-order-total]");
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
    lastFocusedTrigger?.focus?.();
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
  if (updateHash) {
    updateProductHash(card.id);
  }
  viewerClose?.focus();
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

  return {
    id: card.id,
    title,
    price,
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

function getDeliverySelection() {
  const selected = Array.from(deliveryOptions).find((option) => option.checked);
  const rawPrice = selected?.dataset.deliveryPrice ?? "0";
  const hasFixedPrice = rawPrice !== "";

  return {
    method: selected?.dataset.deliveryTitle || selected?.value || "Самовывоз",
    price: hasFixedPrice ? Number(rawPrice) || 0 : null,
    needsDetails: selected?.dataset.deliveryDetails === "true",
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
  const delivery = getDeliverySelection();
  const orderTotal = getOrderTotal();
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
  if (cartSubtotal) {
    cartSubtotal.textContent = formatMoney(subtotal);
  }
  if (cartDeliveryTotal) {
    cartDeliveryTotal.textContent = delivery.price === null ? "По тарифам службы доставки" : formatMoney(delivery.price);
  }
  if (cartGrandTotal) {
    cartGrandTotal.textContent = orderTotal === null ? `${formatMoney(subtotal)} + доставка` : formatMoney(orderTotal);
  }
  if (deliveryFields) {
    deliveryFields.classList.toggle("is-visible", delivery.needsDetails);
  }

  clearNode(cartList);
  cart.forEach((item) => {
    cartList?.appendChild(buildCartRow(item));
  });

  syncCartFields();
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
  cartCheckout?.focus();
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
}

function buildCartSummaryLines() {
  const delivery = getDeliverySelection();
  const city = delivery.needsDetails ? deliveryCity?.value.trim() || "" : "";
  const address = delivery.needsDetails ? deliveryAddress?.value.trim() || "" : "";
  const comment = delivery.needsDetails ? deliveryComment?.value.trim() || "" : "";
  const subtotal = getCartSubtotal();
  const orderTotal = getOrderTotal();
  const lines = cart.map((item) => `${item.title}: ${item.qty} шт. × ${formatMoney(item.price)} = ${formatMoney(item.price * item.qty)}`);

  lines.push(`Товары: ${formatMoney(subtotal)}`);
  lines.push(`Доставка: ${delivery.method}${delivery.price === null ? " · по тарифам службы доставки" : ` · ${formatMoney(delivery.price)}`}`);
  if (city) {
    lines.push(`Город: ${city}`);
  }
  if (address) {
    lines.push(`Адрес/ПВЗ: ${address}`);
  }
  if (comment) {
    lines.push(`Комментарий по доставке: ${comment}`);
  }
  lines.push(`Итого: ${orderTotal === null ? `${formatMoney(subtotal)} + доставка` : formatMoney(orderTotal)}`);

  return lines;
}

function syncCartFields() {
  const delivery = getDeliverySelection();
  const subtotal = getCartSubtotal();
  const orderTotal = getOrderTotal();
  const city = delivery.needsDetails ? deliveryCity?.value.trim() || "" : "";
  const address = delivery.needsDetails ? deliveryAddress?.value.trim() || "" : "";
  const comment = delivery.needsDetails ? deliveryComment?.value.trim() || "" : "";

  if (cartItemsInput) {
    cartItemsInput.value = cart.length ? JSON.stringify(cart) : "";
  }
  if (cartTotalInput) {
    cartTotalInput.value = cart.length ? String(subtotal) : "";
  }
  if (deliveryMethodInput) {
    deliveryMethodInput.value = cart.length ? delivery.method : "";
  }
  if (deliveryPriceInput) {
    deliveryPriceInput.value = cart.length && delivery.price !== null ? String(delivery.price) : "";
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

function validateCartForCheckout() {
  if (!cart.length) {
    if (cartError) {
      cartError.textContent = "Добавьте хотя бы один товар в корзину.";
    }
    openCart();
    return false;
  }

  const delivery = getDeliverySelection();
  if (delivery.needsDetails && !deliveryCity?.value.trim()) {
    if (cartError) {
      cartError.textContent = "Укажите город для доставки.";
    }
    openCart();
    deliveryCity?.focus();
    return false;
  }

  if (delivery.needsDetails && !deliveryAddress?.value.trim()) {
    if (cartError) {
      cartError.textContent = "Укажите адрес, пункт выдачи или удобный способ получения.";
    }
    openCart();
    deliveryAddress?.focus();
    return false;
  }

  if (cartError) {
    cartError.textContent = "";
  }
  return true;
}

function prepareCartForSubmit() {
  if (!cart.length) {
    syncCartFields();
    return true;
  }

  if (!validateCartForCheckout()) {
    return false;
  }

  if (orderSelect) {
    orderSelect.value = "Заказ из корзины";
  }
  if (quantityInput) {
    quantityInput.value = String(getCartQuantity());
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

  window.location.hash = "order";
  form.scrollIntoView({ behavior: "smooth", block: "start" });
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
  option.addEventListener("change", renderCart);
});

[deliveryCity, deliveryAddress, deliveryComment].forEach((field) => {
  field?.addEventListener("input", syncCartFields);
});

cartCheckout?.addEventListener("click", moveCartToOrderForm);

renderCart();
openProductFromHash();
window.addEventListener("hashchange", () => {
  openProductFromHash({ closeStaleViewer: true });
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
