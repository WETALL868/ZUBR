/**
 * Поведение отдельной страницы товара.
 *
 * 1. «Вернуться в каталог» — возвращает на предыдущую страницу, если она есть,
 *    чтобы не терять позицию прокрутки в каталоге.
 * 2. Главная фотография товара открывается на весь экран в общей галерее
 *    (src/gallery.js) — том же компоненте, что и в каталоге.
 */
document.querySelectorAll("[data-back-to-catalog]").forEach((link) => {
  link.addEventListener("click", (event) => {
    if (window.history.length > 1) {
      event.preventDefault();
      window.history.back();
    }
  });
});

const productMedia = document.querySelector(".product-page .product-dialog-media");
const productImage = productMedia?.querySelector("img");

if (productMedia && productImage && window.ProductGallery) {
  // Массив на будущее: когда у товара появится вторая и третья фотография,
  // достаточно передать их сюда — свайпы, стрелки и точки уже готовы.
  const images = [
    {
      src: productImage.getAttribute("data-full") || productImage.currentSrc || productImage.getAttribute("src"),
      alt: productImage.alt || "",
    },
  ];

  productMedia.setAttribute("role", "button");
  productMedia.setAttribute("tabindex", "0");
  productMedia.setAttribute("aria-label", "Открыть фотографию на весь экран");

  const openGallery = () => window.ProductGallery.open(images, 0);

  productMedia.addEventListener("click", openGallery);
  productMedia.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      openGallery();
    }
  });
}
