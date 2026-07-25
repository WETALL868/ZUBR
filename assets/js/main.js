(function () {
  "use strict";

  /* --------------------------------------------------------------------
     Configuration
     Set LEAD_ENDPOINT to your backend / CRM handler (must accept POST JSON).
     While it is null the form validates and reports success locally without
     sending anything — replace it before going live.
     -------------------------------------------------------------------- */
  var LEAD_ENDPOINT = null; // e.g. "/api/lead"

  var $ = function (sel) { return document.querySelector(sel); };

  var yearEl = document.getElementById("year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  /* ---------- Mobile navigation ---------- */
  var burger = document.getElementById("burger");
  var mobileNav = document.getElementById("mobile-nav");

  function setNavState(open) {
    mobileNav.classList.toggle("is-open", open);
    mobileNav.setAttribute("aria-hidden", open ? "false" : "true");
    burger.setAttribute("aria-expanded", open ? "true" : "false");
    burger.setAttribute("aria-label", open ? "Закрыть меню" : "Открыть меню");
    document.body.style.overflow = open ? "hidden" : "";
  }

  function closeMobileNav(returnFocus) {
    if (!mobileNav.classList.contains("is-open")) return;
    setNavState(false);
    if (returnFocus) burger.focus();
  }

  burger.addEventListener("click", function () {
    var willOpen = !mobileNav.classList.contains("is-open");
    setNavState(willOpen);
    if (willOpen) {
      var firstLink = mobileNav.querySelector("a");
      if (firstLink) firstLink.focus();
    }
  });

  mobileNav.querySelectorAll("a").forEach(function (link) {
    link.addEventListener("click", function () { closeMobileNav(false); });
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") closeMobileNav(true);
  });

  // Keep state sane if the viewport grows past the desktop breakpoint.
  var desktopQuery = window.matchMedia("(min-width: 1020px)");
  var onBreakpoint = function (e) { if (e.matches) closeMobileNav(false); };
  if (desktopQuery.addEventListener) desktopQuery.addEventListener("change", onBreakpoint);
  else if (desktopQuery.addListener) desktopQuery.addListener(onBreakpoint);

  setNavState(false);

  /* ---------- Cookie consent banner ----------
     Consent choice is stored in localStorage and never asked again once set.
     "Decline" still permits strictly necessary cookies (session/UI state) and
     only opts the visitor out of analytics — see loadMetrica() below. */
  var COOKIE_CONSENT_KEY = "lancor_cookie_consent";
  var cookieBanner = document.getElementById("cookie-banner");

  function getCookieConsent() {
    try { return localStorage.getItem(COOKIE_CONSENT_KEY); } catch (e) { return null; }
  }

  /* Yandex.Metrica loader. Does nothing until a counter id is provided via
     window.__ymCounterId (see index.html <head>), and only ever runs after the
     visitor has accepted cookies. */
  var metricaLoaded = false;
  function loadMetrica() {
    if (metricaLoaded) return;
    var id = window.__ymCounterId;
    if (!id) return; // no counter configured yet
    metricaLoaded = true;

    (function (m, e, t, r, i, k, a) {
      m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments); };
      m[i].l = 1 * new Date();
      for (var j = 0; j < e.scripts.length; j++) { if (e.scripts[j].src === r) return; }
      k = e.createElement(t); a = e.getElementsByTagName(t)[0];
      k.async = 1; k.src = r; a.parentNode.insertBefore(k, a);
    })(window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

    window.ym(id, "init", {
      clickmap: true,
      trackLinks: true,
      accurateTrackBounce: true,
      webvisor: true
    });
  }

  if (cookieBanner) {
    var storedConsent = getCookieConsent();

    if (!storedConsent) {
      document.body.classList.add("has-cookie-banner");
      // Defer to the next frame so the slide-up transition actually plays.
      requestAnimationFrame(function () {
        requestAnimationFrame(function () { cookieBanner.classList.add("is-visible"); });
      });
    } else if (storedConsent === "accepted") {
      loadMetrica();
    }

    var dismissCookieBanner = function (value) {
      try { localStorage.setItem(COOKIE_CONSENT_KEY, value); } catch (e) { /* private mode / storage disabled */ }
      cookieBanner.classList.remove("is-visible");
      document.body.classList.remove("has-cookie-banner");
      if (value === "accepted") loadMetrica();
    };

    var cookieAccept = document.getElementById("cookie-accept");
    var cookieDecline = document.getElementById("cookie-decline");
    if (cookieAccept) cookieAccept.addEventListener("click", function () { dismissCookieBanner("accepted"); });
    if (cookieDecline) cookieDecline.addEventListener("click", function () { dismissCookieBanner("declined"); });
  }

  /* ---------- CTA intent tracking (which button brought them to the form) ---------- */
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

  /* ---------- Sticky mobile CTA ----------
     Driven by IntersectionObserver instead of a scroll handler: reading
     getBoundingClientRect() on every scroll event forces a synchronous
     layout and costs INP on low-end phones. */
  var mobileCta = document.getElementById("mobile-cta");
  var hero = document.querySelector(".hero");
  var contactSection = document.getElementById("contact");

  if (mobileCta && hero && contactSection && "IntersectionObserver" in window) {
    var pastHero = false;
    var nearContact = false;
    var sync = function () { mobileCta.classList.toggle("is-visible", pastHero && !nearContact); };

    new IntersectionObserver(function (entries) {
      pastHero = !entries[0].isIntersecting;
      sync();
    }, { threshold: 0 }).observe(hero);

    new IntersectionObserver(function (entries) {
      nearContact = entries[0].isIntersecting;
      sync();
    }, { rootMargin: "0px 0px -40% 0px", threshold: 0 }).observe(contactSection);
  }

  /* ---------- Lead form ---------- */
  var form = document.getElementById("lead-form");
  if (!form) return;

  var fields = form.elements;
  var successMsg = document.getElementById("form-success");
  var errorMsg = document.getElementById("form-error");
  var submitBtn = form.querySelector("button[type=submit]");
  var submitLabel = submitBtn ? submitBtn.querySelector(".btn__label") : null;
  var defaultLabel = submitLabel ? submitLabel.textContent : "";

  function hideNotes() {
    successMsg.classList.remove("is-visible");
    errorMsg.classList.remove("is-visible");
  }

  function setSending(sending) {
    form.classList.toggle("is-sending", sending);
    if (submitBtn) submitBtn.disabled = sending;
    if (submitLabel) submitLabel.textContent = sending ? "Отправляем…" : defaultLabel;
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    hideNotes();

    // Honeypot: real users never see or fill this field.
    if (fields.contact_reference && fields.contact_reference.value) return;

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    var payload = {
      name: fields.name.value.trim(),
      phone: fields.phone.value.trim(),
      seats: fields.seats.value.trim(),
      task: fields.task.value.trim(),
      intent: fields.intent.value,
      page: location.href
    };

    if (!LEAD_ENDPOINT) {
      // No backend wired up yet — confirm locally so the UI stays testable.
      setSending(false);
      successMsg.classList.add("is-visible");
      form.reset();
      return;
    }

    setSending(true);

    fetch(LEAD_ENDPOINT, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    })
      .then(function (res) {
        if (!res.ok) throw new Error("HTTP " + res.status);
        setSending(false);
        successMsg.classList.add("is-visible");
        form.reset();
        if (window.ym && window.__ymCounterId) {
          window.ym(window.__ymCounterId, "reachGoal", "lead_form_submit");
        }
      })
      .catch(function () {
        setSending(false);
        errorMsg.classList.add("is-visible");
      });
  });
})();
