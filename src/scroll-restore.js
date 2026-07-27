/**
 * Scroll position across real browser navigation.
 *
 * This site is a static multi-page app: every URL (home, /processors/,
 * /drives/, /products/<slug>/, future category pages) is a distinct HTML
 * document loaded via normal full-page navigation. There is no client-side
 * router and no History API pushState-based routing, so "navigating" always
 * means either a fresh document load (link click, typed URL, reload) or a
 * genuine browser Back/Forward.
 *
 * Rule: a fresh navigation always opens at the top. A real Back/Forward
 * restores the scroll position that page had before the user left it. The
 * two are told apart with the Navigation Timing API, not with guesswork, and
 * the key is scoped per-pathname so scroll state from one page can never
 * leak into another (this is what broke when /processors/ and /drives/ both
 * started sharing the same script — they wrote a single unscoped key).
 *
 * Out of scope on purpose: the hash quick-view modal uses
 * history.replaceState, which never fires a navigation or pageshow event, so
 * opening/closing it is untouched by anything here.
 */
(function () {
  "use strict";

  if ("scrollRestoration" in history) {
    // Take manual control as early as possible so the browser's own
    // automatic restoration for this entry never races with, or duplicates,
    // the restore below.
    history.scrollRestoration = "manual";
  }

  var STORAGE_KEY = "comp-uter-scroll:" + location.pathname;

  function saveScroll() {
    try {
      sessionStorage.setItem(STORAGE_KEY, String(window.scrollY));
    } catch (error) {
      // Storage can be unavailable (private mode) — scroll restore is a
      // nice-to-have, not required.
    }
  }

  var saveScheduled = false;
  window.addEventListener(
    "scroll",
    function () {
      if (saveScheduled) {
        return;
      }
      saveScheduled = true;
      window.requestAnimationFrame(function () {
        saveScroll();
        saveScheduled = false;
      });
    },
    { passive: true },
  );

  function restoreScroll() {
    var saved;
    try {
      saved = sessionStorage.getItem(STORAGE_KEY);
    } catch (error) {
      return;
    }

    var y = Number(saved);
    if (!saved || !isFinite(y) || y <= 0) {
      return;
    }

    window.scrollTo({ top: y, left: 0, behavior: "instant" });
  }

  function isBackForwardNavigation() {
    var entries =
      typeof performance !== "undefined" && performance.getEntriesByType
        ? performance.getEntriesByType("navigation")
        : [];

    if (entries.length) {
      return entries[0].type === "back_forward";
    }

    // Fallback for older engines without Navigation Timing Level 2.
    return !!(
      typeof performance !== "undefined" &&
      performance.navigation &&
      performance.navigation.type === 2
    );
  }

  window.addEventListener("pageshow", function (event) {
    // Served from the back/forward cache: the whole page (including its
    // live scroll position) was frozen and thawed as-is — nothing to do,
    // and calling scrollTo here would only fight the browser's own result.
    if (event.persisted) {
      return;
    }

    // Any other load: fresh navigation, reload, or a Back/Forward that
    // missed the bfcache and had to re-fetch the document. Only the last
    // case should restore anything.
    if (!isBackForwardNavigation()) {
      return;
    }

    // Wait for images to finish so a large saved offset lands correctly
    // against the page's final height.
    if (document.readyState === "complete") {
      restoreScroll();
    } else {
      window.addEventListener("load", restoreScroll, { once: true });
    }
  });
})();
