(function () {
  "use strict";

  document.getElementById("year").textContent = new Date().getFullYear();

  /* ---------- Mobile navigation ---------- */
  var burger = document.getElementById("burger");
  var mobileNav = document.getElementById("mobile-nav");

  function closeMobileNav() {
    mobileNav.classList.remove("is-open");
    burger.setAttribute("aria-expanded", "false");
    burger.setAttribute("aria-label", "Открыть меню");
    document.body.style.overflow = "";
  }
  function openMobileNav() {
    mobileNav.classList.add("is-open");
    burger.setAttribute("aria-expanded", "true");
    burger.setAttribute("aria-label", "Закрыть меню");
    document.body.style.overflow = "hidden";
  }

  burger.addEventListener("click", function () {
    var isOpen = mobileNav.classList.contains("is-open");
    if (isOpen) closeMobileNav(); else openMobileNav();
  });

  mobileNav.querySelectorAll("a").forEach(function (link) {
    link.addEventListener("click", closeMobileNav);
  });

  /* ---------- CTA intent tracking (hero / header buttons -> hidden field) ---------- */
  var intentField = document.getElementById("f-intent");
  document.querySelectorAll("[data-intent]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (intentField) intentField.value = btn.getAttribute("data-intent");
    });
  });

  /* ---------- Scroll reveal ---------- */
  var revealEls = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window) {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
    );
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add("is-visible"); });
  }

  /* ---------- Sticky mobile CTA visibility ---------- */
  var mobileCta = document.getElementById("mobile-cta");
  var hero = document.querySelector(".hero");
  var contactSection = document.getElementById("contact");

  function updateMobileCta() {
    var heroBottom = hero.getBoundingClientRect().bottom;
    var contactTop = contactSection.getBoundingClientRect().top;
    var viewportH = window.innerHeight;
    var pastHero = heroBottom < 0;
    var reachedContact = contactTop < viewportH * 0.6;
    mobileCta.classList.toggle("is-visible", pastHero && !reachedContact);
  }
  window.addEventListener("scroll", updateMobileCta, { passive: true });
  updateMobileCta();

  /* ---------- Lead form (client-side; wire to backend/CRM as needed) ---------- */
  var form = document.getElementById("lead-form");
  var successMsg = document.getElementById("form-success");

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    if (form.company.value) return; // honeypot triggered, silently drop

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    var submitBtn = form.querySelector("button[type=submit]");
    submitBtn.disabled = true;

    var payload = {
      name: form.name.value.trim(),
      phone: form.phone.value.trim(),
      seats: form.seats.value.trim(),
      task: form.task.value.trim(),
      intent: form.intent.value
    };

    // TODO: replace with a real endpoint, e.g.:
    // fetch("/api/lead", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) })
    console.log("Lead submitted:", payload);

    successMsg.classList.add("is-visible");
    form.reset();
    submitBtn.disabled = false;
  });
})();
