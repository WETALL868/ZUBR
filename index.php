<?php require_once __DIR__ . '/src/prices.php'; require_once __DIR__ . '/src/images.php'; ?><!doctype html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <script src="/src/scroll-restore.js?v=1"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="yandex-verification" content="624ceca67ba02e2a" />
    <meta
      name="description"
      content="Серверные процессоры Intel Xeon, HDD и SSD для серверов, NAS, RAID-массивов и рабочих станций. Подбор оборудования, проверка совместимости, доставка по России."
    />
    <meta
      name="keywords"
      content="серверные процессоры, Intel Xeon E5, серверные диски, HDD для сервера, диски для NAS, диски для RAID, накопители для рабочих станций, подбор оборудования, проверка совместимости"
    />
    <meta property="og:title" content="Серверные процессоры и диски для серверов, NAS и рабочих станций" />
    <meta
      property="og:description"
      content="Процессоры Intel Xeon, серверные HDD и SSD для серверов, NAS и RAID. Подбор оборудования под вашу систему, проверка совместимости, документы для организаций."
    />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://comp-uter.ru/" />
    <meta property="og:site_name" content="Comp-Uter" />
    <meta property="og:image" content="https://comp-uter.ru/public/assets/xeon-hero.webp" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Серверные процессоры и диски для серверов, NAS и рабочих станций" />
    <meta
      name="twitter:description"
      content="Серверные процессоры Intel Xeon, HDD и SSD для серверов, NAS и RAID-массивов. Подбор и проверка совместимости."
    />
    <meta name="twitter:image" content="https://comp-uter.ru/public/assets/xeon-hero.webp" />
    <link rel="canonical" href="https://comp-uter.ru/" />
    <link rel="sitemap" type="application/xml" href="/sitemap.xml" />
    <link rel="icon" href="/favicon.ico" sizes="any" />
    <title>Серверные процессоры и диски для серверов и NAS | Comp-Uter</title>
    <link rel="preconnect" href="https://mc.yandex.ru" />
    <link rel="dns-prefetch" href="https://mc.yandex.ru" />
    <link rel="preload" as="image" href="/public/assets/xeon-hero.webp" fetchpriority="high" />
    <style>
:root {
  color-scheme: dark;
  --ink: #f6f7f2;
  --muted: #b7beb8;
  --soft: #dfe6dd;
  --panel: #111916;
  --panel-2: #18221f;
  --panel-3: #24251e;
  --line: rgba(255, 255, 255, 0.14);
  --accent: #4ff4e0;
  --accent-2: #ffd05a;
  --accent-3: #86d85f;
  --hot: #4aa7ff;
  --violet: #8d69ff;
  --dark: #070907;
  --max: 1180px;
  font-family:
    Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}
* {
  box-sizing: border-box;
}
html {
  max-width: 100%;
  overflow-x: hidden;
  scroll-behavior: smooth;
}
body {
  max-width: 100%;
  overflow-x: hidden;
  margin: 0;
  background:
    linear-gradient(180deg, rgba(7, 9, 7, 0.96), rgba(7, 9, 7, 0.98)),
    var(--dark);
  color: var(--ink);
}
a {
  color: inherit;
  text-decoration: none;
}
.site-header {
  position: fixed;
  z-index: 20;
  top: 0;
  left: 0;
  right: 0;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: clamp(12px, 1.6vw, 24px);
  padding: 18px clamp(20px, 4vw, 52px);
  background:
    linear-gradient(180deg, rgba(13, 18, 16, 0.94), rgba(7, 9, 7, 0.78)),
    rgba(7, 9, 7, 0.9);
  border-bottom: 1px solid rgba(79, 244, 224, 0.24);
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.36);
  backdrop-filter: blur(18px);
}
.brand,
.site-header nav,
.hero-actions,
.contact-methods {
  display: flex;
  align-items: center;
}
.brand {
  gap: 10px;
  font-size: 17px;
  font-weight: 850;
}
.brand-image {
  display: block;
  overflow: visible;
  border: 0;
  background: transparent;
  box-shadow: none;
}
.brand-image img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter:
    drop-shadow(0 0 14px rgba(0, 128, 255, 0.26))
    drop-shadow(0 10px 18px rgba(0, 0, 0, 0.34));
}
.brand-image-wordmark {
  width: clamp(210px, 18vw, 290px);
  height: 56px;
  padding: 0;
  border-radius: 0;
}
.brand-image-full {
  width: min(460px, calc(100vw - 40px));
  aspect-ratio: 1400 / 392;
  border-radius: 0;
}
.brand-mark {
  position: relative;
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  border: 1px solid rgba(143, 255, 240, 0.82);
  border-radius: 7px;
  background:
    linear-gradient(135deg, rgba(79, 244, 224, 0.18), rgba(255, 208, 90, 0.12)),
    #10201c;
  box-shadow:
    0 0 24px rgba(79, 244, 224, 0.42),
    inset 0 0 0 5px rgba(82, 214, 195, 0.12),
    inset 0 0 0 10px rgba(7, 9, 7, 0.32);
}
.brand-mark::before,
.brand-mark::after {
  position: absolute;
  content: "";
  pointer-events: none;
}
.brand-mark::before {
  inset: 7px;
  border: 1px solid rgba(255, 208, 90, 0.72);
  border-radius: 4px;
  background:
    radial-gradient(circle at 50% 50%, rgba(143, 255, 240, 0.28), transparent 42%),
    rgba(7, 9, 7, 0.7);
}
.brand-mark::after {
  inset: -5px;
  border-radius: 10px;
  background:
    linear-gradient(90deg, transparent 0 4px, rgba(255, 208, 90, 0.9) 4px 6px, transparent 6px 11px) top / 11px 5px repeat-x,
    linear-gradient(90deg, transparent 0 4px, rgba(255, 208, 90, 0.9) 4px 6px, transparent 6px 11px) bottom / 11px 5px repeat-x,
    linear-gradient(0deg, transparent 0 4px, rgba(255, 208, 90, 0.9) 4px 6px, transparent 6px 11px) left / 5px 11px repeat-y,
    linear-gradient(0deg, transparent 0 4px, rgba(255, 208, 90, 0.9) 4px 6px, transparent 6px 11px) right / 5px 11px repeat-y;
  opacity: 0.9;
}
.site-header nav {
  gap: 10px;
  color: var(--ink);
  font-size: 16px;
  font-weight: 850;
}
.site-header nav a {
  display: inline-flex;
  align-items: center;
  min-height: 42px;
  padding: 10px 13px;
  
  white-space: nowrap;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 7px;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.025)),
    rgba(17, 25, 22, 0.46);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
}
.site-header nav a:hover,
.kit-card a:hover,
.contact-methods a:hover {
  color: var(--accent);
  border-color: rgba(79, 244, 224, 0.45);
  box-shadow:
    0 0 26px rgba(79, 244, 224, 0.18),
    inset 0 1px 0 rgba(255, 255, 255, 0.18);
}
.nav-group {
  position: relative;
  display: inline-flex;
}
.site-header nav .nav-group-trigger {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  min-height: 42px;
  padding: 10px 13px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 7px;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.025)),
    rgba(17, 25, 22, 0.46);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
  color: inherit;
  font: inherit;
  white-space: nowrap;
  cursor: pointer;
}
.nav-group-caret {
  width: 0;
  height: 0;
  border-left: 4px solid transparent;
  border-right: 4px solid transparent;
  border-top: 5px solid currentColor;
  opacity: 0.75;
  transition: transform 0.18s ease;
}
.nav-group:hover .nav-group-caret,
.nav-group:focus-within .nav-group-caret,
.nav-group.is-open .nav-group-caret {
  transform: rotate(180deg);
}
.nav-group-menu {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  z-index: 30;
  display: grid;
  gap: 4px;
  min-width: 244px;
  padding: 8px;
  border: 1px solid rgba(79, 244, 224, 0.28);
  border-radius: 10px;
  background:
    linear-gradient(180deg, rgba(16, 24, 21, 0.98), rgba(9, 13, 11, 0.98));
  box-shadow: 0 24px 48px rgba(0, 0, 0, 0.48);
  opacity: 0;
  visibility: hidden;
  transform: translateY(-6px);
  transition: opacity 0.16s ease, transform 0.16s ease, visibility 0.16s;
}
.nav-group:hover .nav-group-menu,
.nav-group:focus-within .nav-group-menu,
.nav-group.is-open .nav-group-menu {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}
.site-header nav .nav-group-menu a {
  min-height: 40px;
  width: 100%;
  justify-content: flex-start;
  white-space: nowrap;
  font-size: 14px;
}
.header-contacts {
  display: grid;
  gap: 4px;
  min-width: max-content;
  justify-items: end;
  padding: 8px 14px;
  border: 1px solid rgba(79, 244, 224, 0.18);
  border-radius: 8px;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.055), rgba(255, 255, 255, 0.015)),
    rgba(9, 18, 17, 0.62);
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.08),
    0 12px 30px rgba(0, 0, 0, 0.2);
  line-height: 1;
}
.header-contacts span {
  color: var(--accent-2);
  font-size: 10px;
  font-weight: 950;
  text-transform: uppercase;
}
.header-phone {
  color: var(--ink);
  font-size: 16px;
  font-weight: 950;
}
.header-mail {
  color: var(--accent);
  font-size: 12px;
  font-weight: 850;
}
.header-contacts a:hover {
  color: var(--accent-2);
}
.header-action {
  min-height: 46px;
  padding: 13px 20px;
  border: 1px solid rgba(255, 208, 90, 0.68);
  border-radius: 8px;
  background:
    linear-gradient(180deg, #ffe08c, #d99d24 48%, #9a6a14);
  color: #170f02;
  font-size: 15px;
  font-weight: 950;
  box-shadow:
    0 12px 0 #5c3b06,
    0 18px 34px rgba(217, 164, 65, 0.26),
    inset 0 2px 0 rgba(255, 255, 255, 0.42);
  transform: translateY(-5px);
}
.header-action:hover {
  filter: brightness(1.08);
  transform: translateY(-7px);
}
.header-action:active {
  box-shadow:
    0 5px 0 #5c3b06,
    0 10px 24px rgba(217, 164, 65, 0.22),
    inset 0 2px 0 rgba(255, 255, 255, 0.28);
  transform: translateY(1px);
}
.header-cart {
  min-height: 46px;
  display: grid;
  grid-template-columns: auto auto;
  align-items: center;
  column-gap: 8px;
  row-gap: 2px;
  padding: 8px 12px;
  border: 1px solid rgba(79, 244, 224, 0.36);
  border-radius: 8px;
  background:
    linear-gradient(180deg, rgba(79, 244, 224, 0.16), rgba(79, 244, 224, 0.04)),
    rgba(7, 13, 11, 0.82);
  color: var(--ink);
  cursor: pointer;
  font: inherit;
  box-shadow:
    0 12px 28px rgba(0, 0, 0, 0.22),
    inset 0 1px 0 rgba(255, 255, 255, 0.12);
}
.header-cart:hover,
.header-cart:focus-visible {
  border-color: var(--accent);
  box-shadow:
    0 0 28px rgba(79, 244, 224, 0.18),
    inset 0 1px 0 rgba(255, 255, 255, 0.18);
  outline: none;
}
.header-cart span {
  font-size: 13px;
  font-weight: 950;
}
.header-cart strong {
  min-width: 24px;
  min-height: 24px;
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: var(--accent);
  color: #06110f;
  font-size: 13px;
  font-weight: 950;
}
.header-cart em {
  grid-column: 1 / -1;
  color: var(--accent-2);
  font-size: 12px;
  font-style: normal;
  font-weight: 850;
}
.nav-panel {
  display: contents;
}
.site-header nav {
  order: 1;
}
.header-contacts {
  order: 2;
}
.header-cart {
  order: 3;
}
.header-action {
  order: 4;
}
.nav-toggle {
  display: none;
  order: 5;
  width: 44px;
  height: 44px;
  flex: 0 0 44px;
  padding: 0;
  border: 1px solid rgba(79, 244, 224, 0.36);
  border-radius: 8px;
  background: rgba(7, 13, 11, 0.82);
  cursor: pointer;
}
.nav-toggle span,
.nav-toggle span::before,
.nav-toggle span::after {
  position: relative;
  display: block;
  width: 20px;
  height: 2px;
  margin: 0 auto;
  border-radius: 2px;
  background: var(--ink);
  transition: transform 160ms ease, opacity 160ms ease;
}
.nav-toggle span::before,
.nav-toggle span::after {
  position: absolute;
  content: "";
  left: 0;
}
.nav-toggle span::before {
  top: -6px;
}
.nav-toggle span::after {
  top: 6px;
}
.nav-toggle[aria-expanded="true"] span {
  background: transparent;
}
.nav-toggle[aria-expanded="true"] span::before {
  top: 0;
  transform: rotate(45deg);
}
.nav-toggle[aria-expanded="true"] span::after {
  top: 0;
  transform: rotate(-45deg);
}
.hero {
  position: relative;
  min-height: 94vh;
  display: grid;
  align-items: center;
  overflow: hidden;
  padding: 112px clamp(20px, 5vw, 72px) 64px;
}
.hero::before,
.hero::after {
  position: absolute;
  z-index: 1;
  content: "";
  pointer-events: none;
}
.hero::before {
  width: min(52vw, 760px);
  height: min(52vw, 760px);
  right: clamp(-160px, -8vw, -60px);
  top: 12%;
  border-radius: 50%;
  background:
    radial-gradient(circle, rgba(79, 244, 224, 0.44) 0%, rgba(74, 167, 255, 0.24) 32%, rgba(141, 105, 255, 0.1) 55%, transparent 72%);
  filter: blur(18px);
  mix-blend-mode: screen;
}
.hero::after {
  right: 0;
  top: 12%;
  width: 56%;
  height: 64%;
  background:
    linear-gradient(110deg, transparent 0%, rgba(79, 244, 224, 0.16) 42%, rgba(255, 208, 90, 0.12) 58%, transparent 72%);
  transform: skewX(-14deg);
  filter: blur(10px);
  opacity: 0.82;
}
.hero-image,
.hero-overlay {
  position: absolute;
  inset: 0;
}
.hero-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  filter: saturate(1.24) contrast(1.12) brightness(1.06);
}
.hero-overlay {
  background:
    radial-gradient(circle at 75% 40%, rgba(79, 244, 224, 0.18) 0%, rgba(74, 167, 255, 0.12) 28%, rgba(7, 9, 7, 0) 56%),
    linear-gradient(90deg, rgba(7, 9, 7, 0.98) 0%, rgba(7, 9, 7, 0.76) 38%, rgba(7, 9, 7, 0.18) 100%),
    linear-gradient(0deg, var(--dark) 0%, rgba(7, 9, 7, 0) 32%);
}
.hero-content {
  position: relative;
  z-index: 2;
  width: min(740px, 100%);
}
.eyebrow,
.section-label {
  margin: 0 0 14px;
  color: var(--accent-2);
  font-size: 13px;
  font-weight: 850;
  letter-spacing: 0;
  text-transform: uppercase;
}
.eyebrow {
  width: fit-content;
  padding: 8px 11px;
  border: 1px solid rgba(255, 208, 90, 0.26);
  border-radius: 7px;
  background: rgba(36, 37, 30, 0.62);
  box-shadow:
    0 0 26px rgba(255, 208, 90, 0.12),
    inset 0 1px 0 rgba(255, 255, 255, 0.08);
}
.hero-copy {
  max-width: 650px;
  color: var(--soft);
  font-size: 20px;
  line-height: 1.55;
  text-shadow: 0 2px 14px rgba(0, 0, 0, 0.55);
}
.hero-actions {
  flex-wrap: wrap;
  gap: 12px;
  margin: 32px 0;
}
.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 58px;
  padding: 18px 26px;
  border: 1px solid transparent;
  border-radius: 9px;
  font-size: 16px;
  font-weight: 950;
  cursor: pointer;
  transition:
    transform 160ms ease,
    box-shadow 160ms ease,
    filter 160ms ease;
}
.button.primary {
  border-color: rgba(255, 255, 255, 0.18);
  background:
    linear-gradient(180deg, #92fff1 0%, #4ff4e0 42%, #1aa995 100%);
  color: #02110f;
  box-shadow:
    0 12px 0 #0a6257,
    0 22px 42px rgba(79, 244, 224, 0.28),
    inset 0 2px 0 rgba(255, 255, 255, 0.5),
    inset 0 -12px 20px rgba(0, 0, 0, 0.12);
  transform: translateY(-5px);
}
.button.secondary {
  border-color: rgba(255, 208, 90, 0.48);
  background:
    linear-gradient(180deg, rgba(255, 224, 140, 0.2), rgba(255, 208, 90, 0.08)),
    rgba(255, 255, 255, 0.08);
  color: var(--ink);
  box-shadow:
    0 10px 0 #35260b,
    0 18px 34px rgba(255, 208, 90, 0.14),
    inset 0 2px 0 rgba(255, 255, 255, 0.16);
  transform: translateY(-5px);
}
.button:hover {
  filter: brightness(1.08) saturate(1.06);
  transform: translateY(-8px);
}
.button:active {
  transform: translateY(1px);
}
.button.primary:active {
  box-shadow:
    0 5px 0 #0a6257,
    0 10px 24px rgba(79, 244, 224, 0.22),
    inset 0 1px 0 rgba(255, 255, 255, 0.32);
}
.button.secondary:active {
  box-shadow:
    0 4px 0 #35260b,
    0 8px 22px rgba(255, 208, 90, 0.12),
    inset 0 1px 0 rgba(255, 255, 255, 0.12);
}
.hero-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  max-width: 720px;
  margin: 0;
}
.hero-stats div,
.trust-strip div,
.story-grid article,
.kit-card,
.test-column div,
.location-list div {
  border: 1px solid var(--line);
  border-radius: 8px;
  background: rgba(17, 25, 22, 0.82);
}
.hero-stats div {
  padding: 16px;
  box-shadow:
    0 14px 34px rgba(0, 0, 0, 0.28),
    inset 0 1px 0 rgba(255, 255, 255, 0.08);
}
.hero-stats dt {
  font-size: 28px;
  font-weight: 900;
}
.hero-stats dd {
  margin: 4px 0 0;
  color: var(--muted);
}
@media (min-width: 1061px) and (max-width: 1700px) {
  .site-header {
    gap: 10px;
    padding: 14px 20px;
  }
  .brand-image-wordmark {
    width: clamp(150px, 13vw, 190px);
    height: 40px;
  }
  .site-header nav {
    gap: 4px;
    font-size: 13px;
  }
  .site-header nav a {
    min-height: 38px;
    padding: 8px 9px;
    white-space: nowrap;
  }
  .header-contacts {
    gap: 2px;
    padding: 6px 10px;
  }
  .header-phone {
    font-size: 14px;
  }
  .header-mail,
  .header-contacts span {
    font-size: 10px;
  }
  .header-cart {
    min-height: 40px;
    padding: 6px 9px;
    column-gap: 6px;
    font-size: 13px;
  }
  .header-action {
    min-height: 40px;
    padding: 10px 14px;
    font-size: 13px;
    white-space: nowrap;
    box-shadow:
      0 8px 0 #5c3b06,
      0 14px 26px rgba(217, 164, 65, 0.24),
      inset 0 2px 0 rgba(255, 255, 255, 0.42);
    transform: translateY(-3px);
  }
}}
@media (min-width: 1061px) and (max-width: 1300px) {
  .header-mail,
  .header-contacts span {
    display: none;
  }
  .site-header {
    gap: 8px;
    padding: 14px 14px;
  }
  .site-header nav {
    gap: 2px;
    font-size: 12px;
  }
  .site-header nav a {
    padding: 8px 7px;
  }
  .header-contacts {
    padding: 6px 8px;
  }
  .header-action {
    padding: 10px 11px;
  }
}}
body.viewer-open {
  overflow: hidden;
}
body.cart-open {
  overflow: hidden;
}
body.success-open {
  overflow: hidden;
}
input,
select,
textarea {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: 6px;
  background: #070d0b;
  color: var(--ink);
  font: inherit;
  padding: 13px 14px;
}
textarea {
  resize: vertical;
}
button:disabled {
  cursor: wait;
  filter: grayscale(0.2) brightness(0.84);
}
.consent-label a,
.privacy-section a,
.cookie-banner a,
.site-footer a {
  color: var(--accent);
  font-weight: 850;
}
.cookie-banner {
  position: fixed;
  z-index: 40;
  right: clamp(16px, 3vw, 34px);
  bottom: clamp(16px, 3vw, 34px);
  width: min(520px, calc(100vw - 32px));
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 18px;
  align-items: center;
  padding: 18px;
  border: 1px solid rgba(82, 214, 195, 0.34);
  border-radius: 10px;
  background:
    linear-gradient(135deg, rgba(24, 34, 31, 0.96), rgba(7, 9, 7, 0.96)),
    var(--panel);
  color: var(--soft);
  box-shadow:
    0 24px 70px rgba(0, 0, 0, 0.5),
    inset 0 1px 0 rgba(255, 255, 255, 0.08);
}
.cookie-banner[hidden] {
  display: none;
}
.cookie-banner strong,
.cookie-banner p {
  margin: 0;
}
.cookie-banner p {
  margin-top: 6px;
  color: var(--muted);
  font-size: 14px;
  line-height: 1.45;
}
.cookie-accept {
  min-height: 48px;
  padding-inline: 18px;
}
@media (max-width: 1060px) {
  .site-header {
    flex-wrap: nowrap;
    padding: 12px 16px;
    gap: 10px;
  }
  .brand-image-wordmark {
    width: clamp(150px, 42vw, 200px);
    height: 40px;
  }
  .header-cart {
    min-height: 40px;
    padding: 6px 10px;
  }
  .header-cart em {
    display: none;
  }
  .nav-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  .nav-panel {
    display: none;
    position: fixed;
    z-index: 19;
    top: 68px;
    left: 0;
    right: 0;
    flex-direction: column;
    align-items: stretch;
    gap: 14px;
    padding: 18px 16px 24px;
    max-height: calc(100dvh - 68px);
    overflow-y: auto;
    background: linear-gradient(180deg, rgba(13, 18, 16, 0.98), rgba(7, 9, 7, 0.98));
    border-bottom: 1px solid rgba(79, 244, 224, 0.24);
    box-shadow: 0 24px 50px rgba(0, 0, 0, 0.5);
  }
  .nav-panel.is-open {
    display: flex;
  }
  .site-header nav {
    width: 100%;
    flex-direction: column;
    align-items: stretch;
    gap: 6px;
    font-size: 16px;
  }
  .site-header nav a {
    width: 100%;
    justify-content: flex-start;
  }
  .nav-group {
    display: block;
    width: 100%;
  }
  .site-header nav .nav-group-trigger {
    width: 100%;
    justify-content: space-between;
  }
  .nav-group-menu {
    position: static;
    display: none;
    min-width: 0;
    margin: 6px 0 0;
    padding: 6px 0 0 12px;
    border: 0;
    border-left: 2px solid rgba(79, 244, 224, 0.3);
    border-radius: 0;
    background: none;
    box-shadow: none;
    opacity: 1;
    visibility: visible;
    transform: none;
    transition: none;
  }
  .nav-group.is-open .nav-group-menu {
    display: grid;
  }
  .nav-group:hover .nav-group-menu {
    display: none;
  }
  .nav-group.is-open:hover .nav-group-menu {
    display: grid;
  }
  .header-contacts {
    width: 100%;
    min-width: 0;
    justify-items: start;
    padding: 12px 14px;
  }
  .header-action {
    width: 100%;
  }
  .hero {
    min-height: 900px;
    align-items: end;
    padding-top: 108px;
  }
  .hero-overlay {
    background:
      radial-gradient(circle at 70% 18%, rgba(79, 244, 224, 0.26) 0%, rgba(74, 167, 255, 0.14) 32%, transparent 58%),
      linear-gradient(0deg, var(--dark) 0%, rgba(7, 9, 7, 0.95) 56%, rgba(7, 9, 7, 0.25) 100%),
      linear-gradient(90deg, rgba(7, 9, 7, 0.9), rgba(7, 9, 7, 0.4));
  }
  .hero-stats,
  .trust-strip,
  .seo-panel,
  .guide-grid,
  .story-grid,
  .kit-grid,
  .requisites-grid,
  .privacy-grid,
  .site-footer,
  .feature-band,
  .corporate-panel,
  .location-band,
  .order-section {
    grid-template-columns: 1fr;
  }
  input,
  select,
  textarea {
    font-size: 16px;
  }
}}
@media (max-width: 560px) {
  .site-header {
    padding: 12px 14px;
  }
  .brand {
    font-size: 15px;
  }
  .brand-image-wordmark {
    width: clamp(140px, 46vw, 190px);
    height: 36px;
  }
  .header-contacts {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    width: 100%;
    padding: 9px 10px;
  }
  .header-contacts span {
    display: none;
  }
  .header-phone {
    font-size: 14px;
  }
  .header-mail {
    font-size: 12px;
  }
  .header-action {
    min-height: 46px;
  }
  .header-cart {
    min-height: 38px;
    padding: 6px 9px;
  }
  .header-cart span {
    font-size: 12px;
  }
  .site-header nav {
    font-size: 15px;
  }
  .hero {
    min-height: 940px;
    padding: 100px 16px 48px;
  }
  .button {
    width: 100%;
    min-height: 56px;
  }
  .hero-copy {
    font-size: 18px;
  }
  .cookie-banner {
    grid-template-columns: 1fr;
  }
}}
    </style>
    <link rel="preload" href="/src/styles.css?v=2" as="style" onload="this.onload=null;this.rel='stylesheet'" />
    <noscript><link rel="stylesheet" href="/src/styles.css?v=2" /></noscript>
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "name": "Процессоры Intel Xeon E5 и жесткие диски Seagate в наличии",
        "description": "Процессоры Intel Xeon E5 и серверные жесткие диски Seagate для рабочих станций, X99-сборок, NAS, RAID-массивов и организаций.",
        "itemListElement": [
          { "@type": "ListItem", "position": 1, "name": "Процессор Intel Xeon E5-2699 V4", "url": "https://comp-uter.ru/products/e5-2699-v4/" },
          { "@type": "ListItem", "position": 2, "name": "Процессор Intel Xeon E5-2699 V3", "url": "https://comp-uter.ru/products/e5-2699-v3/" },
          { "@type": "ListItem", "position": 3, "name": "Процессор Intel Xeon E5-2697 V4", "url": "https://comp-uter.ru/products/e5-2697-v4/" },
          { "@type": "ListItem", "position": 4, "name": "Процессор Intel Xeon E5-2696 V3", "url": "https://comp-uter.ru/products/e5-2696-v3/" },
          { "@type": "ListItem", "position": 5, "name": "Процессор Intel Xeon E5-2690 V4", "url": "https://comp-uter.ru/products/e5-2690-v4/" },
          { "@type": "ListItem", "position": 6, "name": "Процессор Intel Xeon E5-2673 V3", "url": "https://comp-uter.ru/products/e5-2673-v3/" },
          { "@type": "ListItem", "position": 7, "name": "Процессор Intel Xeon E5-2667 V3", "url": "https://comp-uter.ru/products/e5-2667-v3/" },
          { "@type": "ListItem", "position": 8, "name": "Жесткий диск Seagate Exos X10 10TB ST10000NM0016", "url": "https://comp-uter.ru/products/st10000nm0016/" },
          { "@type": "ListItem", "position": 9, "name": "Жесткий диск Seagate Exos 7E8 6TB ST6000NM0115", "url": "https://comp-uter.ru/products/st6000nm0115/" },
          { "@type": "ListItem", "position": 10, "name": "Жесткий диск Seagate Constellation ES.3 4TB ST4000NM0033", "url": "https://comp-uter.ru/products/st4000nm0033/" },
          { "@type": "ListItem", "position": 11, "name": "Жесткий диск Seagate Enterprise Capacity 3.5 HDD v5 3TB ST3000NM0005", "url": "https://comp-uter.ru/products/st3000nm0005/" },
          { "@type": "ListItem", "position": 12, "name": "Жесткий диск Seagate Constellation ES.3 3TB ST3000NM0033", "url": "https://comp-uter.ru/products/st3000nm0033/" }
        ]
      }
    </script>
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "Comp-Uter",
        "legalName": "ИП Михайловский Виталий Геннадьевич",
        "url": "https://comp-uter.ru/",
        "description": "Поставка серверных процессоров Intel Xeon и жестких дисков Seagate в России: подбор оборудования, проверка совместимости, работа с организациями.",
        "email": "info@comp-uter.ru",
        "telephone": "+74993221311",
        "taxID": "772703413513",
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "Болотниковская ул., д.5к3",
          "addressLocality": "Москва",
          "postalCode": "117556",
          "addressCountry": "RU"
        },
        "openingHours": "Mo-Fr 09:00-18:00"
      }
    </script>
    <script type="application/ld+json">{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[{"@type":"Question","name":"Как проверить совместимость процессора с сервером или платой?","acceptedAnswer":{"@type":"Answer","text":"Сначала сверьте сокет — все процессоры в нашем каталоге используют LGA 2011-3. Затем проверьте список поддерживаемых процессоров на сайте производителя вашей материнской платы и обновите BIOS до актуальной версии. Если списка под рукой нет, напишите нам модель платы или сервера — сверим вместе."}},{"@type":"Question","name":"Как подобрать Intel Xeon по количеству ядер?","acceptedAnswer":{"@type":"Answer","text":"Ориентируйтесь на характер нагрузки. Для рендера, виртуализации и задач, которые хорошо распараллеливаются, выигрывают модели с 18–22 ядрами. Если приложение почти не использует больше шести–восьми потоков, заметнее будет высокая частота на ядро, а не количество ядер."}},{"@type":"Question","name":"Чем серверный жесткий диск отличается от обычного?","acceptedAnswer":{"@type":"Answer","text":"Корпоративные серии рассчитаны на круглосуточную работу и постоянную нагрузку, имеют более высокую заявленную наработку на отказ и устойчивость к вибрации в многодисковых корзинах. Обычный настольный диск проектируется под периодический бытовой режим и в массиве из нескольких дисков ведет себя хуже."}},{"@type":"Question","name":"Подойдет ли серверный диск для NAS?","acceptedAnswer":{"@type":"Answer","text":"Да. Все диски в каталоге подключаются по SATA III и выполнены в форм-факторе 3.5 дюйма, поэтому устанавливаются в большинство NAS-корпусов. Перед покупкой стоит свериться со списком совместимости вашей модели NAS — особенно по максимальному поддерживаемому объему диска."}},{"@type":"Question","name":"Можно ли использовать в RAID диски разного объема?","acceptedAnswer":{"@type":"Answer","text":"Технически можно, но в большинстве уровней RAID массив будет считать все диски по объему наименьшего, и разница окажется потерянной. Для предсказуемого результата в одном массиве лучше использовать одинаковые модели одного объема."}},{"@type":"Question","name":"Как проверить совместимость диска с RAID-контроллером?","acceptedAnswer":{"@type":"Answer","text":"Проверьте, что контроллер поддерживает интерфейс SATA III и нужный вам объем диска — у части старых контроллеров есть ограничение на максимальную емкость. Отдельные серверные платформы также ведут список проверенных моделей накопителей. Напишите нам модель контроллера или сервера — поможем сверить."}},{"@type":"Question","name":"Помогаете ли вы подобрать комплектующие?","acceptedAnswer":{"@type":"Answer","text":"Да, подбор бесплатный. Напишите модель сервера, материнской платы или NAS, текущую конфигурацию и задачу — предложим подходящие позиции из наличия и объясним выбор. Для организаций подготовим спецификацию и счет."}}]}</script>
  </head>
  <body>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
      (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
      })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=110948351', 'ym');

      ym(110948351, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/110948351" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->

    <header class="site-header">
      <a class="brand" href="#top" aria-label="Comp-Uter">
        <span class="brand-image brand-image-wordmark">
          <img src="/public/assets/comp-uter-logo-wordmark.webp" alt="" />
        </span>
      </a>
      <div class="nav-panel" id="site-nav-panel" data-nav-panel>
        <nav aria-label="Основная навигация">
          <div class="nav-group" data-nav-group>
            <button
              class="nav-group-trigger"
              type="button"
              data-nav-group-trigger
              aria-expanded="false"
              aria-controls="catalog-menu"
            >
              Каталог
              <span class="nav-group-caret" aria-hidden="true"></span>
            </button>
            <div class="nav-group-menu" id="catalog-menu" data-nav-group-menu>
              <a href="/processors/">Серверные процессоры</a>
              <a href="/drives/">Диски и накопители</a>
            </div>
          </div>
          <a href="#selection">Подбор</a>
          <a href="#testing">Совместимость</a>
          <a href="#delivery">Доставка</a>
          <a href="#contact">Контакты</a>
        </nav>
        <div class="header-contacts" aria-label="Контакты отдела продаж">
          <span>Отдел продаж</span>
          <a class="header-phone" href="tel:+74993221311">+7 (499) 322-13-11</a>
          <a class="header-mail" href="mailto:info@comp-uter.ru">info@comp-uter.ru</a>
        </div>
        <a class="header-action" href="#order">Заказать</a>
      </div>
      <button class="header-cart" type="button" data-cart-open aria-label="Открыть корзину">
        <span>Корзина</span>
        <strong data-cart-count>0</strong>
        <em data-cart-header-total>0 ₽</em>
      </button>
      <button
        class="nav-toggle"
        type="button"
        data-nav-toggle
        aria-expanded="false"
        aria-controls="site-nav-panel"
        aria-label="Открыть меню"
      >
        <span></span>
      </button>
    </header>

    <div class="cart-backdrop" data-cart-close hidden></div>
    <aside class="cart-drawer" aria-hidden="true" aria-labelledby="cart-title">
      <div class="cart-drawer-head">
        <div>
          <p class="section-label">Корзина</p>
          <h2 id="cart-title">Ваш заказ</h2>
        </div>
        <button class="cart-close" type="button" data-cart-close aria-label="Закрыть корзину">×</button>
      </div>
      <p class="cart-empty" data-cart-empty>Корзина пока пустая. Добавьте процессор или накопитель из каталога, чтобы оформить заказ на сайте.</p>
      <div class="cart-list" data-cart-list></div>
      <section class="cart-delivery" aria-labelledby="cart-delivery-title">
        <h3 id="cart-delivery-title">Способ получения</h3>
        <label class="delivery-option">
          <input type="radio" name="cartDelivery" value="Самовывоз" data-delivery-price="<?= prices_delivery_attr('самовывоз') ?>" data-delivery-title="Самовывоз: Москва, м. Варшавская, Болотниковская ул., д.5к3" checked />
          <span>
            <strong>Самовывоз</strong>
            <em>Москва, м. Варшавская, Болотниковская ул., д.5к3 · бесплатно</em>
          </span>
        </label>
        <label class="delivery-option">
          <input type="radio" name="cartDelivery" value="Доставка по Москве" data-delivery-price="<?= prices_delivery_attr('москва') ?>" data-delivery-title="Доставка по Москве" data-delivery-details="true" />
          <span>
            <strong>Доставка по Москве</strong>
            <em>Курьерская доставка по адресу · <?= prices_delivery_money('москва') ?></em>
          </span>
        </label>
        <label class="delivery-option">
          <input type="radio" name="cartDelivery" value="Доставка по Московской области" data-delivery-price="<?= prices_delivery_attr('московская_область') ?>" data-delivery-title="Доставка по Московской области" data-delivery-details="true" />
          <span>
            <strong>Доставка по Московской области</strong>
            <em>Курьер или транспортная компания · от <?= prices_delivery_money('московская_область') ?></em>
          </span>
        </label>
        <label class="delivery-option">
          <input type="radio" name="cartDelivery" value="Доставка по России" data-delivery-price="<?= prices_delivery_attr('россия') ?>" data-delivery-title="Доставка по России: СДЭК или другая транспортная компания" data-delivery-details="true" />
          <span>
            <strong>Доставка по России</strong>
            <em>СДЭК, транспортная компания, Яндекс Маркет, Ozon или Wildberries · по тарифам службы доставки</em>
          </span>
        </label>
      </section>
      <div class="cart-delivery-fields" data-delivery-fields>
        <label>
          Город или населенный пункт
          <input type="text" data-delivery-city placeholder="Например, Москва, Подольск, Казань" />
        </label>
        <label>
          Адрес, пункт выдачи или пожелание
          <textarea data-delivery-address rows="3" placeholder="Адрес доставки, пункт СДЭК или удобный способ получения"></textarea>
        </label>
        <label>
          Комментарий по доставке
          <input type="text" data-delivery-comment placeholder="Например: СДЭК до ПВЗ, Яндекс Маркет, Ozon, ТК Деловые линии" />
        </label>
      </div>
      <dl class="cart-totals">
        <div>
          <dt>Товары</dt>
          <dd data-cart-subtotal>0 ₽</dd>
        </div>
        <div>
          <dt>Доставка</dt>
          <dd data-cart-delivery-total>Самовывоз бесплатно</dd>
        </div>
        <div class="cart-grand-total">
          <dt>Итого</dt>
          <dd data-cart-grand-total>0 ₽</dd>
        </div>
      </dl>
      <p class="cart-error" data-cart-error role="status" aria-live="polite"></p>
      <button class="button primary cart-checkout" type="button" data-cart-checkout>Оформить заказ</button>
      <p class="cart-note">После оформления менеджер подтвердит наличие, доставку, документы и способ оплаты в рабочее время.</p>
    </aside>

    <main id="top">
      <section class="hero" aria-labelledby="hero-title">
        <img
          class="hero-image"
          src="/public/assets/xeon-hero.webp"
          alt="Процессор Xeon 6980P на профессиональной рабочей поверхности"
          width="1600"
          height="960"
          fetchpriority="high"
          decoding="async"
        />
        <div class="hero-overlay"></div>
        <div class="hero-content">
          <p class="eyebrow">Комплектующие для серверов, NAS и рабочих станций</p>
          <h1 id="hero-title">Серверные процессоры и диски для серверов, NAS и рабочих станций</h1>
          <p class="hero-copy">
            Серверные процессоры Intel Xeon и жесткие диски Seagate для серверов, NAS, RAID-массивов и рабочих
            станций. Поможем подобрать совместимое оборудование под вашу систему — по сокету и ядрам для процессора,
            по интерфейсу и объему для накопителя.
          </p>
          <div class="hero-actions">
            <a class="button primary" href="/processors/">Процессоры</a>
            <a class="button primary" href="/drives/">Диски и накопители</a>
            <a class="button secondary" href="#selection">Помощь в подборе</a>
          </div>
          <dl class="hero-stats" aria-label="Ключевые преимущества">
            <div>
              <dt>12</dt>
              <dd>позиций уже в продаже</dd>
            </div>
            <div>
              <dt>2</dt>
              <dd>направления: процессоры и диски</dd>
            </div>
            <div>
              <dt>48 ч</dt>
              <dd>резерв партии для организаций</dd>
            </div>
          </dl>
        </div>
      </section>

      <section class="section trust-strip" aria-label="Коротко о сервисе">
        <div>
          <span>Ассортимент</span>
          <strong>серверные процессоры, HDD и SSD в наличии</strong>
        </div>
        <div>
          <span>Системы</span>
          <strong>серверы, NAS, RAID-массивы и рабочие станции</strong>
        </div>
        <div>
          <span>Перед отправкой</span>
          <strong>осмотр, проверка работоспособности и совместимости</strong>
        </div>
      </section>

      <section class="section category-split" aria-label="Категории каталога">
        <article class="category-card">
          <p class="section-label">Категория</p>
          <h2>Серверные процессоры</h2>
          <p>
            Процессоры Intel Xeon для серверов и рабочих станций. Подбор по сокету, поколению, количеству ядер
            и модели оборудования.
          </p>
          <span class="category-meta">7 моделей в наличии · LGA 2011-3</span>
          <a class="button primary" href="/processors/">Смотреть процессоры</a>
        </article>
        <article class="category-card">
          <p class="section-label">Категория</p>
          <h2>Диски и накопители</h2>
          <p>
            Жесткие диски Seagate для серверов, NAS, RAID-массивов и рабочих станций. Подбор по интерфейсу,
            объему, форм-фактору и совместимости.
          </p>
          <span class="category-meta">5 моделей в наличии · 3–10 ТБ, SATA III</span>
          <a class="button primary" href="/drives/">Смотреть накопители</a>
        </article>
      </section>

      <section class="section inventory" id="stock" aria-labelledby="stock-title">
        <div class="section-heading">
          <p class="section-label">Категория 1 из 2</p>
          <h2 id="stock-title">Серверные процессоры Intel Xeon</h2>
          <p>
            Ниже - текущая витрина моделей Intel Xeon E5, которые чаще всего покупают для X99-платформ, производительных
            рабочих станций, домашних серверов, монтажа, 3D-рендера, виртуализации и готовых ПК на продажу. Если нужна другая
            модель Xeon, подберем альтернативу по ядрам, частоте, бюджету и совместимой материнской плате.
          </p>
        </div>
        <p class="category-jump"><a href="/processors/">Все процессоры — отдельная страница категории</a></p>
        <div class="model-grid">
          <article class="model-card featured" id="e5-2699-v4"<?= prices_card_attrs('e5-2699-v4') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Xeon E5-2699 V4">
              <?= product_image_tag('e5-2699-v4', 'Процессор Xeon E5-2699 V4 LGA 2011-3', true) ?>
            </figure>
            <?= prices_stock_badge('e5-2699-v4') ?>

            <h3>Процессор Intel Xeon E5-2699 V4</h3>
            <?= prices_card_html('e5-2699-v4') ?>

            <p>Флагманская модель для тяжелой многопоточности: рендер, виртуализация, инженерные задачи, серверные нагрузки и производительные рабочие станции на X99.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>4946900889</dd></div>
              <div><dt>Архитектура</dt><dd>Haswell-E</dd></div>
              <div><dt>Сокет</dt><dd>LGA 2011-3</dd></div>
              <div><dt>Ядра / потоки</dt><dd>22 / 44</dd></div>
              <div><dt>Частота</dt><dd>2.2-3.6 ГГц</dd></div>
              <div><dt>Кэш L3</dt><dd>55 МБ</dd></div>
              <div><dt>Техпроцесс</dt><dd>14 нм</dd></div>
              <div><dt>TDP</dt><dd>145 Вт</dd></div>
              <div><dt>Память</dt><dd>DDR4, до 2400 МГц</dd></div>
              <div><dt>Макс. ОЗУ</dt><dd>1 ТБ и более</dd></div>
              <div><dt>Комплектация</dt><dd>OEM, без кулера</dd></div>
              <div><dt>Страна</dt><dd>Малайзия</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Xeon E5-2699 V4">
              <li>Turbo Boost</li>
              <li>ECC</li>
              <li>Virtualization Technology</li>
              <li>Intel vPro</li>
            </ul>
          </article>
          <article class="model-card" id="e5-2699-v3"<?= prices_card_attrs('e5-2699-v3') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Xeon E5-2699 V3">
              <?= product_image_tag('e5-2699-v3', 'Процессор Xeon E5-2699 V3 LGA 2011-3', true) ?>
            </figure>
            <?= prices_stock_badge('e5-2699-v3') ?>

            <h3>Процессор Intel Xeon E5-2699 V3</h3>
            <?= prices_card_html('e5-2699-v3') ?>

            <p>Производительный Xeon поколения Haswell для сборок, где нужно много потоков за разумный бюджет: монтаж, стриминг, домашний сервер и рабочие задачи.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>4946900090</dd></div>
              <div><dt>Архитектура</dt><dd>Haswell</dd></div>
              <div><dt>Сокет</dt><dd>LGA 2011-3</dd></div>
              <div><dt>Ядра / потоки</dt><dd>18 / 36</dd></div>
              <div><dt>Частота</dt><dd>2.3-3.6 ГГц</dd></div>
              <div><dt>Кэш L3</dt><dd>45 МБ</dd></div>
              <div><dt>Техпроцесс</dt><dd>22 нм</dd></div>
              <div><dt>TDP</dt><dd>145 Вт</dd></div>
              <div><dt>Память</dt><dd>DDR4, до 2133 МГц</dd></div>
              <div><dt>Макс. ОЗУ</dt><dd>768 ГБ</dd></div>
              <div><dt>Комплектация</dt><dd>OEM, без кулера</dd></div>
              <div><dt>Страна</dt><dd>Малайзия</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Xeon E5-2699 V3">
              <li>Turbo Boost</li>
              <li>ECC</li>
              <li>Virtualization Technology</li>
              <li>Intel vPro</li>
            </ul>
          </article>
          <article class="model-card" id="e5-2697-v4"<?= prices_card_attrs('e5-2697-v4') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Xeon E5-2697 V4">
              <?= product_image_tag('e5-2697-v4', 'Процессор Xeon E5-2697 V4 LGA 2011-3', true) ?>
            </figure>
            <?= prices_stock_badge('e5-2697-v4') ?>

            <h3>Процессор Intel Xeon E5-2697 V4</h3>
            <?= prices_card_html('e5-2697-v4') ?>

            <p>Сбалансированная V4-модель для стабильной рабочей станции: много потоков, DDR4-память и предсказуемая работа под длительной нагрузкой.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>4946900711</dd></div>
              <div><dt>Архитектура</dt><dd>Broadwell-E</dd></div>
              <div><dt>Сокет</dt><dd>LGA 2011-3</dd></div>
              <div><dt>Ядра / потоки</dt><dd>18 / 36</dd></div>
              <div><dt>Частота</dt><dd>2.3-3.6 ГГц</dd></div>
              <div><dt>Кэш L3</dt><dd>45 МБ</dd></div>
              <div><dt>Техпроцесс</dt><dd>14 нм</dd></div>
              <div><dt>TDP</dt><dd>145 Вт</dd></div>
              <div><dt>Память</dt><dd>DDR4, до 2400 МГц</dd></div>
              <div><dt>Макс. ОЗУ</dt><dd>1 ТБ и более</dd></div>
              <div><dt>Комплектация</dt><dd>OEM, без кулера</dd></div>
              <div><dt>Страна</dt><dd>Малайзия</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Xeon E5-2697 V4">
              <li>Turbo Boost</li>
              <li>ECC</li>
              <li>Virtualization Technology</li>
              <li>Intel vPro</li>
            </ul>
          </article>
          <article class="model-card" id="e5-2696-v3"<?= prices_card_attrs('e5-2696-v3') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Xeon E5-2696 V3">
              <?= product_image_tag('e5-2696-v3', 'Процессор Xeon E5-2696 V3 LGA 2011-3', true) ?>
            </figure>
            <?= prices_stock_badge('e5-2696-v3') ?>

            <h3>Процессор Intel Xeon E5-2696 V3</h3>
            <?= prices_card_html('e5-2696-v3') ?>

            <p>Популярная позиция для производительных X99-сборок: много потоков, высокий Turbo Boost и понятная экономика для сборщиков ПК.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>4946900352</dd></div>
              <div><dt>Архитектура</dt><dd>Haswell</dd></div>
              <div><dt>Сокет</dt><dd>LGA 2011-3</dd></div>
              <div><dt>Ядра / потоки</dt><dd>18 / 36</dd></div>
              <div><dt>Частота</dt><dd>2.3-3.8 ГГц</dd></div>
              <div><dt>Кэш L3</dt><dd>45 МБ</dd></div>
              <div><dt>Техпроцесс</dt><dd>22 нм</dd></div>
              <div><dt>TDP</dt><dd>135 Вт</dd></div>
              <div><dt>Память</dt><dd>DDR4, до 2133 МГц</dd></div>
              <div><dt>Макс. ОЗУ</dt><dd>768 ГБ</dd></div>
              <div><dt>Комплектация</dt><dd>OEM, без кулера</dd></div>
              <div><dt>Страна</dt><dd>Малайзия</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Xeon E5-2696 V3">
              <li>Turbo Boost</li>
              <li>ECC</li>
              <li>Virtualization Technology</li>
            </ul>
          </article>
          <article class="model-card" id="e5-2690-v4"<?= prices_card_attrs('e5-2690-v4') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Xeon E5-2690 V4">
              <?= product_image_tag('e5-2690-v4', 'Процессор Xeon E5-2690 V4 LGA 2011-3', true) ?>
            </figure>
            <?= prices_stock_badge('e5-2690-v4') ?>

            <h3>Процессор Intel Xeon E5-2690 V4</h3>
            <?= prices_card_html('e5-2690-v4') ?>

            <p>Универсальный процессор для рабочей станции: монтаж, разработка, 3D-пакеты, виртуализация и повседневные профессиональные нагрузки.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>4946900697</dd></div>
              <div><dt>Архитектура</dt><dd>Broadwell-E</dd></div>
              <div><dt>Сокет</dt><dd>LGA 2011-3</dd></div>
              <div><dt>Ядра / потоки</dt><dd>14 / 28</dd></div>
              <div><dt>Частота</dt><dd>2.6-3.5 ГГц</dd></div>
              <div><dt>Кэш L3</dt><dd>35 МБ</dd></div>
              <div><dt>Техпроцесс</dt><dd>14 нм</dd></div>
              <div><dt>TDP</dt><dd>135 Вт</dd></div>
              <div><dt>Память</dt><dd>DDR4, до 2400 МГц</dd></div>
              <div><dt>Макс. ОЗУ</dt><dd>1 ТБ и более</dd></div>
              <div><dt>Комплектация</dt><dd>OEM, без кулера</dd></div>
              <div><dt>Страна</dt><dd>Малайзия</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Xeon E5-2690 V4">
              <li>Turbo Boost</li>
              <li>ECC</li>
              <li>Virtualization Technology</li>
              <li>Intel vPro</li>
            </ul>
          </article>
          <article class="model-card" id="e5-2673-v3"<?= prices_card_attrs('e5-2673-v3') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Xeon E5-2673 V3">
              <?= product_image_tag('e5-2673-v3', 'Процессор Xeon E5-2673 V3 LGA 2011-3', true) ?>
            </figure>
            <?= prices_stock_badge('e5-2673-v3') ?>

            <h3>Процессор Intel Xeon E5-2673 V3</h3>
            <?= prices_card_html('e5-2673-v3') ?>

            <p>Практичный Xeon для бюджетных производительных сборок: хорош, когда нужно получить 12 ядер и понятную конфигурацию под продажу.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>4946900718</dd></div>
              <div><dt>Архитектура</dt><dd>Haswell</dd></div>
              <div><dt>Сокет</dt><dd>LGA 2011-3</dd></div>
              <div><dt>Ядра / потоки</dt><dd>12 / 24</dd></div>
              <div><dt>Частота</dt><dd>2.4-3.2 ГГц</dd></div>
              <div><dt>Кэш L3</dt><dd>30 МБ</dd></div>
              <div><dt>Техпроцесс</dt><dd>22 нм</dd></div>
              <div><dt>TDP</dt><dd>105 Вт</dd></div>
              <div><dt>Память</dt><dd>DDR4, до 2133 МГц</dd></div>
              <div><dt>Макс. ОЗУ</dt><dd>768 ГБ</dd></div>
              <div><dt>Комплектация</dt><dd>OEM, без кулера</dd></div>
              <div><dt>Страна</dt><dd>Малайзия</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Xeon E5-2673 V3">
              <li>Turbo Boost</li>
              <li>ECC</li>
              <li>Virtualization Technology</li>
              <li>Intel vPro</li>
            </ul>
          </article>
          <article class="model-card" id="e5-2667-v3"<?= prices_card_attrs('e5-2667-v3') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Xeon E5-2667 V3">
              <?= product_image_tag('e5-2667-v3', 'Процессор Xeon E5-2667 V3 LGA 2011-3', true) ?>
            </figure>
            <?= prices_stock_badge('e5-2667-v3') ?>

            <h3>Процессор Intel Xeon E5-2667 V3</h3>
            <?= prices_card_html('e5-2667-v3') ?>

            <p>Модель с акцентом на высокую частоту на ядро: отзывчивые рабочие станции, прикладные задачи и точечные апгрейды X99.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>4946899626</dd></div>
              <div><dt>Архитектура</dt><dd>Haswell</dd></div>
              <div><dt>Сокет</dt><dd>LGA 2011-3</dd></div>
              <div><dt>Ядра / потоки</dt><dd>8 / 16</dd></div>
              <div><dt>Частота</dt><dd>3.2-3.6 ГГц</dd></div>
              <div><dt>Кэш L3</dt><dd>20 МБ</dd></div>
              <div><dt>Техпроцесс</dt><dd>22 нм</dd></div>
              <div><dt>TDP</dt><dd>135 Вт</dd></div>
              <div><dt>Память</dt><dd>DDR4, до 2133 МГц</dd></div>
              <div><dt>Макс. ОЗУ</dt><dd>768 ГБ</dd></div>
              <div><dt>Комплектация</dt><dd>OEM, без кулера</dd></div>
              <div><dt>Страна</dt><dd>Малайзия</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Xeon E5-2667 V3">
              <li>Turbo Boost</li>
              <li>ECC</li>
              <li>Virtualization Technology</li>
            </ul>
          </article>
          <article class="model-card request-card">
            <div class="chip-sketch ghost" aria-hidden="true"><span>Xeon</span></div>
            <span>Под заказ</span>
            <h3>Нужен другой Xeon?</h3>
            <p>
              Подберем процессор Intel Xeon под материнскую плату, память, охлаждение, бюджет, срок поставки и сценарий:
              рабочая станция, сервер, учебный класс, офис, рендер-ферма или партия готовых ПК.
            </p>
            <ul class="model-tags">
              <li>подбор по совместимости</li>
              <li>альтернативы по бюджету</li>
              <li>поставка для организаций</li>
            </ul>
            <a class="model-detail-button request-link" href="#order" data-goal="Подобрать сборку">Подобрать процессор</a>
          </article>
        </div>
      </section>

      <section class="section inventory" id="hdd" aria-labelledby="hdd-title">
        <div class="section-heading">
          <p class="section-label">Категория 2 из 2</p>
          <h2 id="hdd-title">Серверные диски и накопители</h2>
          <p>
            Корпоративные HDD Seagate объемом от 3 до 10 ТБ для серверов, NAS-хранилищ, RAID-массивов, резервного
            копирования и систем видеонаблюдения. Если нужен другой объем или партия для организации, поможем
            подобрать модель под задачу.
          </p>
        </div>
        <p class="category-jump"><a href="/drives/">Все накопители — отдельная страница категории</a></p>
        <div class="model-grid">
          <article class="model-card featured" id="st10000nm0016"<?= prices_card_attrs('st10000nm0016') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Seagate Exos X10 10TB ST10000NM0016">
              <?= product_image_tag('st10000nm0016', 'Жесткий диск Seagate Exos X10 10TB ST10000NM0016', true) ?>
            </figure>
            <?= prices_stock_badge('st10000nm0016') ?>

            <h3>Жесткий диск Seagate Exos X10 10TB ST10000NM0016</h3>
            <?= prices_card_html('st10000nm0016') ?>

            <p>Флагманский серверный жесткий диск на 10 ТБ для дата-центров: RAID-массивы, NAS-хранилища, резервное копирование, видеонаблюдение и корпоративные файловые серверы.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>ST10000NM0016</dd></div>
              <div><dt>Серия</dt><dd>Exos X10</dd></div>
              <div><dt>Форм-фактор</dt><dd>3.5&quot;</dd></div>
              <div><dt>Объем</dt><dd>10 ТБ</dd></div>
              <div><dt>Интерфейс</dt><dd>SATA III, 6 Гбит/с</dd></div>
              <div><dt>Скорость вращения</dt><dd>7200 об/мин</dd></div>
              <div><dt>Кэш-память</dt><dd>256 МБ</dd></div>
              <div><dt>Наработка на отказ</dt><dd>2 500 000 ч</dd></div>
              <div><dt>Потребляемая мощность</dt><dd>8 Вт</dd></div>
              <div><dt>Комплектация</dt><dd>Жесткий диск — 1 шт.</dd></div>
              <div><dt>Гарантия</dt><dd>3 месяца</dd></div>
              <div><dt>Страна-изготовитель</dt><dd>Таиланд</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Seagate Exos X10 10TB ST10000NM0016">
              <li>Enterprise-класс</li>
              <li>RAID Ready</li>
              <li>кэш 256 МБ</li>
            </ul>
          </article>
          <article class="model-card" id="st6000nm0115"<?= prices_card_attrs('st6000nm0115') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Seagate Exos 7E8 6TB ST6000NM0115">
              <?= product_image_tag('st6000nm0115', 'Жесткий диск Seagate Exos 7E8 6TB ST6000NM0115', true) ?>
            </figure>
            <?= prices_stock_badge('st6000nm0115') ?>

            <h3>Жесткий диск Seagate Exos 7E8 6TB ST6000NM0115</h3>
            <?= prices_card_html('st6000nm0115') ?>

            <p>Диск корпоративного класса для непрерывной нагрузки 24/7: серверы, RAID-массивы, NAS-системы, видеонаблюдение и резервное копирование данных.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>ST6000NM0115</dd></div>
              <div><dt>Серия</dt><dd>Exos 7E8</dd></div>
              <div><dt>Форм-фактор</dt><dd>3.5&quot;</dd></div>
              <div><dt>Объем</dt><dd>6 ТБ</dd></div>
              <div><dt>Интерфейс</dt><dd>SATA III, 6 Гбит/с</dd></div>
              <div><dt>Скорость вращения</dt><dd>7200 об/мин</dd></div>
              <div><dt>Кэш-память</dt><dd>256 МБ</dd></div>
              <div><dt>Наработка на отказ</dt><dd>2 000 000 ч</dd></div>
              <div><dt>Потребляемая мощность</dt><dd>8.59 Вт</dd></div>
              <div><dt>Комплектация</dt><dd>Жесткий диск — 1 шт.</dd></div>
              <div><dt>Гарантия</dt><dd>3 месяца</dd></div>
              <div><dt>Страна-изготовитель</dt><dd>Таиланд</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Seagate Exos 7E8 6TB ST6000NM0115">
              <li>Enterprise-класс</li>
              <li>RAID Ready</li>
              <li>кэш 256 МБ</li>
            </ul>
          </article>
          <article class="model-card" id="st4000nm0033"<?= prices_card_attrs('st4000nm0033') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Seagate Constellation ES.3 4TB ST4000NM0033">
              <?= product_image_tag('st4000nm0033', 'Жесткий диск Seagate Constellation ES.3 4TB ST4000NM0033', true) ?>
            </figure>
            <?= prices_stock_badge('st4000nm0033') ?>

            <h3>Жесткий диск Seagate Constellation ES.3 4TB ST4000NM0033</h3>
            <?= prices_card_html('st4000nm0033') ?>

            <p>Проверенный временем серверный HDD на 4 ТБ для RAID-массивов, NAS и файловых серверов, где важна предсказуемая долгосрочная надежность.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>ST4000NM0033</dd></div>
              <div><dt>Серия</dt><dd>Constellation ES.3</dd></div>
              <div><dt>Форм-фактор</dt><dd>3.5&quot;</dd></div>
              <div><dt>Объем</dt><dd>4 ТБ</dd></div>
              <div><dt>Интерфейс</dt><dd>SATA III, 6 Гбит/с</dd></div>
              <div><dt>Скорость вращения</dt><dd>7200 об/мин</dd></div>
              <div><dt>Кэш-память</dt><dd>128 МБ</dd></div>
              <div><dt>Наработка на отказ</dt><dd>1 400 000 ч</dd></div>
              <div><dt>Потребляемая мощность</dt><dd>11.27 Вт</dd></div>
              <div><dt>Комплектация</dt><dd>Жесткий диск — 1 шт.</dd></div>
              <div><dt>Гарантия</dt><dd>3 месяца</dd></div>
              <div><dt>Страна-изготовитель</dt><dd>Таиланд</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Seagate Constellation ES.3 4TB ST4000NM0033">
              <li>Enterprise-класс</li>
              <li>RAID Ready</li>
              <li>кэш 128 МБ</li>
            </ul>
          </article>
          <article class="model-card" id="st3000nm0005"<?= prices_card_attrs('st3000nm0005') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Seagate Enterprise Capacity 3.5 HDD v5 3TB ST3000NM0005">
              <?= product_image_tag('st3000nm0005', 'Жесткий диск Seagate Enterprise Capacity 3.5 HDD v5 3TB ST3000NM0005', true) ?>
            </figure>
            <?= prices_stock_badge('st3000nm0005') ?>

            <h3>Жесткий диск Seagate Enterprise Capacity 3.5 HDD v5 3TB ST3000NM0005</h3>
            <?= prices_card_html('st3000nm0005') ?>

            <p>Диск нового поколения на 3 ТБ с повышенной скоростью и наработкой на отказ: серверы, рабочие станции, RAID и системы хранения данных.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>ST3000NM0005</dd></div>
              <div><dt>Серия</dt><dd>Enterprise Capacity 3.5 HDD v5</dd></div>
              <div><dt>Форм-фактор</dt><dd>3.5&quot;</dd></div>
              <div><dt>Объем</dt><dd>3 ТБ</dd></div>
              <div><dt>Интерфейс</dt><dd>SATA III, 6 Гбит/с</dd></div>
              <div><dt>Скорость вращения</dt><dd>7200 об/мин</dd></div>
              <div><dt>Кэш-память</dt><dd>128 МБ</dd></div>
              <div><dt>Наработка на отказ</dt><dd>2 000 000 ч</dd></div>
              <div><dt>Потребляемая мощность</dt><dd>6.9 Вт</dd></div>
              <div><dt>Комплектация</dt><dd>Жесткий диск — 1 шт.</dd></div>
              <div><dt>Гарантия</dt><dd>3 месяца</dd></div>
              <div><dt>Страна-изготовитель</dt><dd>Таиланд</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Seagate Enterprise Capacity 3.5 HDD v5 3TB ST3000NM0005">
              <li>Enterprise-класс</li>
              <li>RAID Ready</li>
              <li>повышенная скорость</li>
              <li>кэш 128 МБ</li>
            </ul>
          </article>
          <article class="model-card" id="st3000nm0033"<?= prices_card_attrs('st3000nm0033') ?>>
            <figure class="model-photo" role="button" tabindex="0" aria-label="Увеличить фото Seagate Constellation ES.3 3TB ST3000NM0033">
              <?= product_image_tag('st3000nm0033', 'Жесткий диск Seagate Constellation ES.3 3TB ST3000NM0033', true) ?>
            </figure>
            <?= prices_stock_badge('st3000nm0033') ?>

            <h3>Жесткий диск Seagate Constellation ES.3 3TB ST3000NM0033</h3>
            <?= prices_card_html('st3000nm0033') ?>

            <p>Надежный корпоративный HDD на 3 ТБ для серверов, NAS, RAID-массивов и резервного копирования, когда важна не игровая скорость, а стабильность хранения.</p>
            <dl class="model-specs">
              <div><dt>Артикул</dt><dd>ST3000NM0033</dd></div>
              <div><dt>Серия</dt><dd>Constellation ES.3</dd></div>
              <div><dt>Форм-фактор</dt><dd>3.5&quot;</dd></div>
              <div><dt>Объем</dt><dd>3 ТБ</dd></div>
              <div><dt>Интерфейс</dt><dd>SATA III, 6 Гбит/с</dd></div>
              <div><dt>Скорость вращения</dt><dd>7200 об/мин</dd></div>
              <div><dt>Кэш-память</dt><dd>128 МБ</dd></div>
              <div><dt>Наработка на отказ</dt><dd>1 400 000 ч</dd></div>
              <div><dt>Потребляемая мощность</dt><dd>11.27 Вт</dd></div>
              <div><dt>Комплектация</dt><dd>Жесткий диск — 1 шт.</dd></div>
              <div><dt>Гарантия</dt><dd>3 месяца</dd></div>
              <div><dt>Страна-изготовитель</dt><dd>Таиланд</dd></div>
            </dl>
            <ul class="model-tags" aria-label="Особенности Seagate Constellation ES.3 3TB ST3000NM0033">
              <li>Enterprise-класс</li>
              <li>RAID Ready</li>
              <li>кэш 128 МБ</li>
            </ul>
          </article>
          <article class="model-card request-card">
            <div class="chip-sketch ghost" aria-hidden="true"><span>HDD</span></div>
            <span>Под заказ</span>
            <h3>Нужен другой диск?</h3>
            <p>
              Подберем жесткий диск Seagate под объем, бюджет, RAID-массив и сценарий использования: сервер, NAS,
              видеонаблюдение, резервное копирование или партия для организации.
            </p>
            <ul class="model-tags">
              <li>подбор по объему</li>
              <li>альтернативы по бюджету</li>
              <li>поставка для организаций</li>
            </ul>
            <a class="model-detail-button request-link" href="#order" data-goal="Подобрать жесткий диск">Подобрать диск</a>
          </article>
        </div>
      </section>

      <section class="section seo-panel" aria-labelledby="seo-title">
        <div class="seo-copy">
          <p class="section-label">Для каких систем</p>
          <h2 id="seo-title">Комплектующие для серверов, NAS, RAID-массивов и рабочих станций</h2>
          <p>
            Мы поставляем два направления серверных комплектующих. Процессоры Intel Xeon E5 — вычислительная основа для
            серверных платформ, X99-компьютеров, рабочих станций и партий готовых ПК: подбираем по сокету, числу ядер,
            частоте, поддержке памяти и охлаждению. Жесткие диски Seagate корпоративных серий — хранилище для файловых
            серверов, NAS, RAID-массивов, систем видеонаблюдения и резервного копирования: подбираем по объему,
            интерфейсу, форм-фактору и совместимости с контроллером.
          </p>
          <p>
            Чаще всего к нам обращаются, когда нужно закрыть конкретную задачу: добавить потоков серверу под виртуализацию,
            собрать рабочую станцию под монтаж и рендер, расширить дисковый массив без смены платформы, заменить диск в уже
            собранном RAID совместимой моделью или закупить одинаковую партию под несколько машин. Для организаций
            подготовим спецификацию, счет и закрывающие документы.
          </p>
        </div>
        <div class="seo-badges">
          <span>серверные процессоры</span>
          <span>диски для серверов</span>
          <span>накопители для NAS</span>
          <span>диски для RAID</span>
          <span>подбор по сокету</span>
          <span>подбор по объему</span>
          <span>проверка совместимости</span>
          <span>безналичный расчет</span>
        </div>
      </section>

      <section class="section selection-guide" id="selection" aria-labelledby="selection-title">
        <div class="section-heading">
          <p class="section-label">Подбор оборудования</p>
          <h2 id="selection-title">Поможем подобрать процессор или накопитель под вашу систему</h2>
          <p>
            Напишите, что у вас за оборудование и какую задачу нужно закрыть — предложим подходящие позиции из наличия
            и объясним, почему именно они. Подбор бесплатный, отвечает специалист, а не робот.
          </p>
        </div>
        <div class="guide-grid selection-split">
          <article class="selection-card">
            <h3>Подобрать серверный процессор</h3>
            <p>
              Поможем выбрать Intel Xeon по сокету, поколению, количеству ядер и частоте — под конкретный сервер,
              материнскую плату или рабочую станцию. Учитываем бюджет, охлаждение и поддержку памяти.
            </p>
            <ul class="selection-points">
              <li>сокет, поколение и поддержка платой</li>
              <li>ядра, потоки и частота под задачу</li>
              <li>тип и максимальный объем памяти</li>
              <li>бюджет и запас на будущее</li>
            </ul>
            <div class="selection-actions">
              <a class="button primary request-link" href="#order" data-goal="Подобрать серверный процессор">Запросить подбор</a>
              <a class="button secondary" href="/processors/">Смотреть процессоры</a>
            </div>
          </article>
          <article class="selection-card">
            <h3>Подобрать диск или накопитель</h3>
            <p>
              Поможем выбрать серверный жесткий диск по объему, интерфейсу и форм-фактору — под сервер, NAS,
              RAID-массив или рабочую станцию. Учитываем нагрузку, число дисков в массиве и бюджет.
            </p>
            <ul class="selection-points">
              <li>объем и требуемый запас хранилища</li>
              <li>интерфейс SATA и форм-фактор 3.5"</li>
              <li>совместимость с сервером, NAS и RAID</li>
              <li>режим работы и постоянная нагрузка</li>
            </ul>
            <div class="selection-actions">
              <a class="button primary request-link" href="#order" data-goal="Подобрать диск или накопитель">Запросить подбор</a>
              <a class="button secondary" href="/drives/">Смотреть накопители</a>
            </div>
          </article>
        </div>
      </section>

      <section class="section story" id="about" aria-labelledby="about-title">
        <div class="section-heading">
          <p class="section-label">Почему покупают у нас</p>
          <h2 id="about-title">Мы работаем как поставщик, которому важно закрыть задачу клиента</h2>
        </div>
        <div class="story-grid">
          <article>
            <h3>Опыт в реальных сборках</h3>
            <p>
              Подбираем железо для рабочих станций, мастерских и продавцов готовых ПК: рендер, виртуализация, монтаж,
              компиляция, инженерные задачи. Поэтому говорим не только про модель, но и про совместимость.
            </p>
          </article>
          <article>
            <h3>Не отдаем вслепую</h3>
            <p>
              Не продаем процессоры как безымянный складской остаток. Смотрим состояние, уточняем платформу, предупреждаем
              о требованиях к плате, BIOS, памяти и охлаждению.
            </p>
          </article>
          <article>
            <h3>Умеем работать с юрлицами</h3>
            <p>
              Готовим счет, резервируем товар, согласуем поставку и закрывающие документы. Для организаций сохраняем фокус на
              легальном происхождении, понятной спецификации и удобной оплате.
            </p>
          </article>
        </div>
      </section>

      <section class="feature-band" id="testing" aria-labelledby="testing-title">
        <div class="feature-copy">
          <p class="section-label">Проверка совместимости</p>
          <h2 id="testing-title">Проверяем совместимость и работоспособность до отправки</h2>
          <p>
            Проверяем и процессоры, и накопители: оборудование должно запускаться, определяться системой и соответствовать
            заявленной модели. Перед покупкой поможем сверить совместимость с вашим сервером, платой, NAS или RAID-контроллером —
            для сборщиков это меньше возвратов, для организаций спокойнее закупка.
          </p>
        </div>
        <div class="test-column">
          <div>
            <strong>Процессор: сокет и старт</strong>
            <span>осмотр контактов, запуск на совместимой плате, проверка определения в BIOS</span>
          </div>
          <div>
            <strong>Процессор: нагрузка</strong>
            <span>прогон под стабильной температурой, контроль частот и поведения под длительной задачей</span>
          </div>
          <div>
            <strong>Диск: определение и состояние</strong>
            <span>подключение по SATA, определение системой, чтение S.M.A.R.T. и контроль заявленного объема</span>
          </div>
          <div>
            <strong>Совместимость до покупки</strong>
            <span>сверим сокет и поддержку платой для процессора, интерфейс, форм-фактор и работу с RAID для диска</span>
          </div>
        </div>
      </section>

      <section class="section kits" aria-labelledby="kits-title">
        <div class="section-heading">
          <p class="section-label">Сценарии покупки</p>
          <h2 id="kits-title">Подбираем под то, как вы зарабатываете или используете компьютер</h2>
        </div>
        <div class="kit-grid">
          <article class="kit-card">
            <span class="kit-icon">01</span>
            <h3>Для личной сборки</h3>
            <p>Подбор процессора или накопителя под задачу, бюджет и вашу платформу. Помогаем не купить лишнее и не упереться в совместимость.</p>
            <a href="#order">Запросить подбор</a>
          </article>
          <article class="kit-card">
            <span class="kit-icon">02</span>
            <h3>Для продажи готовых ПК</h3>
            <p>Повторяемые партии процессоров и дисков для мастерских и продавцов: одинаковые модели, понятный остаток, документы и проверка перед выдачей.</p>
            <a href="#order">Узнать условия партии</a>
          </article>
          <article class="kit-card">
            <span class="kit-icon">03</span>
            <h3>Для организаций</h3>
            <p>Серверные комплектующие для организаций: безналичный расчет, счет, резерв, закрывающие документы и спецификация под закупку отдела.</p>
            <a href="#corporate">Корпоративные условия</a>
          </article>
        </div>
      </section>

      <section class="section corporate" id="corporate" aria-labelledby="corporate-title">
        <div class="corporate-panel">
          <div>
            <p class="section-label">Премия для юрлиц</p>
            <h2 id="corporate-title">Бонус за безналичный расчет для организаций</h2>
            <p>
              При оплате по безналичному расчету мы добавляем корпоративную премию: приоритетный резерв партии на 48 часов,
              расширенную спецификацию, подготовку документов и аккуратную комплектацию для выдачи или внутреннего учета.
            </p>
          </div>
          <ul class="corporate-list">
            <li>счет и договор для согласования закупки</li>
            <li>закрывающие документы после отгрузки</li>
            <li>отдельная комплектация партии под одну спецификацию</li>
            <li>помощь с подбором совместимых плат, памяти, охлаждения и дисков</li>
          </ul>
        </div>
      </section>

      <section class="section delivery-section" id="delivery" aria-labelledby="delivery-title">
        <div class="section-heading">
          <p class="section-label">Доставка</p>
          <h2 id="delivery-title">Отправляем серверные комплектующие по всей России</h2>
          <p>
            Доставка зависит от региона, выбранного способа и состава заказа: для одних направлений она может быть бесплатной,
            для других - доходить до половины стоимости позиции. Точную стоимость и удобный вариант отправки уточнит менеджер.
          </p>
        </div>
        <div class="delivery-grid">
          <article>
            <span>01</span>
            <h3>Транспортные компании</h3>
            <p>Отправим удобной транспортной компанией по согласованию, в том числе СДЭК. Подберем вариант под сроки, стоимость и город получателя.</p>
          </article>
          <article>
            <span>02</span>
            <h3>Маркетплейсы</h3>
            <p>Доступны варианты доставки через Яндекс Маркет, Ozon и Wildberries, если покупателю удобнее получить заказ через привычную площадку.</p>
          </article>
          <article>
            <span>03</span>
            <h3>В любую точку</h3>
            <p>Отправляем процессоры и накопители по России: для личной сборки, сервисной мастерской, продавца готовых ПК или организации с безналичной оплатой.</p>
          </article>
        </div>
      </section>

      <section class="location-band" id="contact" aria-labelledby="location-title">
        <div class="location-copy">
          <p class="section-label">Контакты и выдача</p>
          <h2 id="location-title">Адрес выдачи рядом с м. Варшавская</h2>
          <p>
            Адрес: 117556, Москва, м. Варшавская, Болотниковская ул., д.5к3. Перед приездом лучше позвонить
            или оставить заявку, чтобы мы подготовили процессоры, счет, резерв партии и документы для выдачи.
          </p>
        </div>
        <div class="location-list">
          <div>
            <strong>Адрес</strong>
            <span>117556, Москва, м. Варшавская, Болотниковская ул., д.5к3</span>
          </div>
          <div>
            <strong>Телефон</strong>
            <span><a href="tel:+74993221311">+7 (499) 322-13-11</a></span>
          </div>
          <div>
            <strong>Эл. почта</strong>
            <span><a href="mailto:info@comp-uter.ru">info@comp-uter.ru</a></span>
          </div>
          <div>
            <strong>Режим работы</strong>
            <span>Понедельник-пятница, с 9:00 до 18:00</span>
          </div>
        </div>
        <a
          class="location-map"
          href="https://yandex.ru/maps/?text=Москва%2C%20Болотниковская%20ул.%2C%20д.5к3"
          target="_blank"
          rel="noopener noreferrer"
          aria-label="Открыть карту проезда до пункта выдачи Comp-Uter на Яндекс.Картах"
        >
          <img
            src="/public/assets/contact-map.webp"
            alt="Карта проезда до пункта выдачи Comp-Uter: Москва, м. Варшавская, Болотниковская ул., д.5к3"
            width="1003"
            height="380"
            loading="lazy"
            decoding="async"
          />
        </a>
      </section>

      <section class="section faq-section" id="faq" aria-labelledby="faq-title">
        <div class="section-heading">
          <p class="section-label">Частые вопросы</p>
          <h2 id="faq-title">Что чаще всего спрашивают про процессоры и диски</h2>
        </div>
        <div class="faq-list">
          <details class="faq-item">
            <summary>Как проверить совместимость процессора с сервером или платой?</summary>
            <p>
              Сначала сверьте сокет — все процессоры в нашем каталоге используют LGA 2011-3. Затем проверьте список
              поддерживаемых процессоров на сайте производителя вашей материнской платы и обновите BIOS до актуальной
              версии. Если списка под рукой нет, напишите нам модель платы или сервера — сверим вместе.
            </p>
          </details>
          <details class="faq-item">
            <summary>Как подобрать Intel Xeon по количеству ядер?</summary>
            <p>
              Ориентируйтесь на характер нагрузки. Для рендера, виртуализации и задач, которые хорошо распараллеливаются,
              выигрывают модели с 18–22 ядрами. Если приложение почти не использует больше шести–восьми потоков,
              заметнее будет высокая частота на ядро, а не количество ядер.
            </p>
          </details>
          <details class="faq-item">
            <summary>Чем серверный жесткий диск отличается от обычного?</summary>
            <p>
              Корпоративные серии рассчитаны на круглосуточную работу и постоянную нагрузку, имеют более высокую
              заявленную наработку на отказ и устойчивость к вибрации в многодисковых корзинах. Обычный настольный диск
              проектируется под периодический бытовой режим и в массиве из нескольких дисков ведет себя хуже.
            </p>
          </details>
          <details class="faq-item">
            <summary>Подойдет ли серверный диск для NAS?</summary>
            <p>
              Да. Все диски в каталоге подключаются по SATA III и выполнены в форм-факторе 3.5 дюйма, поэтому
              устанавливаются в большинство NAS-корпусов. Перед покупкой стоит свериться со списком совместимости
              вашей модели NAS — особенно по максимальному поддерживаемому объему диска.
            </p>
          </details>
          <details class="faq-item">
            <summary>Можно ли использовать в RAID диски разного объема?</summary>
            <p>
              Технически можно, но в большинстве уровней RAID массив будет считать все диски по объему наименьшего,
              и разница окажется потерянной. Для предсказуемого результата в одном массиве лучше использовать
              одинаковые модели одного объема.
            </p>
          </details>
          <details class="faq-item">
            <summary>Как проверить совместимость диска с RAID-контроллером?</summary>
            <p>
              Проверьте, что контроллер поддерживает интерфейс SATA III и нужный вам объем диска — у части старых
              контроллеров есть ограничение на максимальную емкость. Отдельные серверные платформы также ведут список
              проверенных моделей накопителей. Напишите нам модель контроллера или сервера — поможем сверить.
            </p>
          </details>
          <details class="faq-item">
            <summary>Помогаете ли вы подобрать комплектующие?</summary>
            <p>
              Да, подбор бесплатный. Напишите модель сервера, материнской платы или NAS, текущую конфигурацию и задачу —
              предложим подходящие позиции из наличия и объясним выбор. Для организаций подготовим спецификацию и счет.
            </p>
          </details>
        </div>
      </section>

      <section class="section order-section" id="order" aria-labelledby="order-title">
        <div class="order-copy">
          <p class="section-label">Форма заказа</p>
          <h2 id="order-title">Оставьте заявку на оборудование или партию</h2>
          <p>
            Напишите, что собираете или какое оборудование нужно заменить, и как планируете оплату. Ответим с наличием,
            рекомендацией по совместимости и условиями корпоративной премии для безналичного расчета.
          </p>
          <div class="contact-methods">
            <a href="tel:+74993221311">+7 (499) 322-13-11</a>
            <a href="mailto:info@comp-uter.ru">info@comp-uter.ru</a>
          </div>
        </div>
        <form class="lead-form" id="email">
          <div class="form-head">
            <span>Корпоративный заказ</span>
            <strong>безналичный расчет приветствуется</strong>
          </div>
          <div class="order-cart-summary" data-order-cart-summary hidden>
            <strong>Состав заказа из корзины</strong>
            <div data-order-cart-lines></div>
          </div>
          <input type="hidden" name="cart_items" data-cart-items />
          <input type="hidden" name="cart_total" data-cart-total />
          <input type="hidden" name="delivery_method" data-delivery-method />
          <input type="hidden" name="delivery_price" data-delivery-price />
          <input type="hidden" name="delivery_city" data-delivery-city-hidden />
          <input type="hidden" name="delivery_address" data-delivery-address-hidden />
          <input type="hidden" name="delivery_comment" data-delivery-comment-hidden />
          <input type="hidden" name="order_total" data-order-total />
          <label>
            Имя
            <input name="name" type="text" autocomplete="name" placeholder="Как к вам обращаться" required />
          </label>
          <label>
            Email
            <input name="email" type="email" autocomplete="email" placeholder="you@example.com" required />
          </label>
          <label>
            Телефон
            <input
              name="phone"
              type="tel"
              autocomplete="tel"
              inputmode="tel"
              placeholder="+7 (___) ___-__-__"
              pattern="^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$"
              data-phone-mask
              required
            />
          </label>
          <label>
            Компания или ИНН
            <input name="company" type="text" placeholder="Для счета и резерва партии" />
          </label>
          <div class="form-row">
            <label>
              Количество
              <input name="quantity" type="number" min="1" placeholder="Например, 12" />
            </label>
            <label>
              Оплата
              <select name="payment" required>
                <option value="">Выберите</option>
                <option>Безналичный расчет для организации</option>
                <option>Оплата как физлицо</option>
                <option>Нужна консультация</option>
              </select>
            </label>
          </div>
          <label>
            Что необходимо подобрать
            <select name="category" data-order-category>
              <option value="">Не важно / уже определился</option>
              <option>Серверный процессор</option>
              <option>Диск или накопитель</option>
              <option>Процессор и накопитель</option>
              <option>Нужна консультация</option>
            </select>
          </label>
          <label>
            Модель или задача
            <select name="goal" required>
            <option value="">Выберите вариант</option>
            <option>Процессор Intel Xeon E5-2699 V4</option>
            <option>Процессор Intel Xeon E5-2699 V3</option>
            <option>Процессор Intel Xeon E5-2697 V4</option>
            <option>Процессор Intel Xeon E5-2696 V3</option>
            <option>Процессор Intel Xeon E5-2690 V4</option>
            <option>Процессор Intel Xeon E5-2673 V3</option>
            <option>Процессор Intel Xeon E5-2667 V3</option>
            <option>Жесткий диск Seagate Exos X10 10TB ST10000NM0016</option>
            <option>Жесткий диск Seagate Exos 7E8 6TB ST6000NM0115</option>
            <option>Жесткий диск Seagate Constellation ES.3 4TB ST4000NM0033</option>
            <option>Жесткий диск Seagate Enterprise Capacity 3.5 HDD v5 3TB ST3000NM0005</option>
            <option>Жесткий диск Seagate Constellation ES.3 3TB ST3000NM0033</option>
            <option>Заказ из корзины</option>
            <option>Купить один процессор</option>
            <option>Подобрать сборку</option>
            <option>Подобрать серверный процессор</option>
            <option>Подобрать диск или накопитель</option>
            <option>Заказать партию для продажи</option>
            <option>Закупить для организации</option>
            </select>
          </label>
          <label>
            Комментарий
            <textarea name="message" rows="4" placeholder="Модель сервера, плата или NAS, текущее оборудование, нужный объем, количество, сроки или задача"></textarea>
          </label>
          <label class="consent-label">
            <input name="privacy" type="checkbox" required />
            <span>
              Я прочитал и соглашаюсь с правилами: <a href="/privacy_policy/" target="_blank" rel="noopener">Политики конфиденциальности</a>.
            </span>
          </label>
          <label class="consent-label">
            <input name="terms" type="checkbox" required />
            <span>
              Я прочитал и соглашаюсь с правилами: <a href="/polzovatelskoe-soglashenie/" target="_blank" rel="noopener">Пользовательского соглашения</a>.
            </span>
          </label>
          <div class="form-trap" aria-hidden="true">
            <label>
              Не заполняйте это поле
              <input name="company_website" type="text" tabindex="-1" autocomplete="off" />
            </label>
          </div>
          <input type="hidden" name="form_rendered_at" data-form-rendered-at />
          <button class="button primary" type="submit">Оформить заказ</button>
          <p class="form-note">Для юрлиц подготовим счет, резерв, спецификацию и закрывающие документы.</p>
          <p class="form-status" role="status" aria-live="polite"></p>
        </form>
      </section>

      <section class="section requisites" id="requisites" aria-labelledby="requisites-title">
        <div class="section-heading">
          <p class="section-label">Реквизиты</p>
          <h2 id="requisites-title">Данные для счета и безналичной оплаты</h2>
          <p>
            Реквизиты можно использовать для подготовки счета, договора и документов по корпоративной закупке процессоров Xeon.
          </p>
        </div>
        <div class="requisites-grid">
          <article>
            <h3>Продавец</h3>
            <dl>
              <div><dt>Наименование</dt><dd>ИП Михайловский Виталий Геннадьевич</dd></div>
              <div><dt>Юридический адрес</dt><dd>115561, Россия, г. Москва, ш. Каширское, д.128, корп. 2, кв.446</dd></div>
              <div><dt>ИНН</dt><dd>772703413513</dd></div>
              <div><dt>ОГРН</dt><dd>322774600279660</dd></div>
            </dl>
          </article>
          <article>
            <h3>Банковские реквизиты</h3>
            <dl>
              <div><dt>Банк</dt><dd>АО «АЛЬФА-БАНК»</dd></div>
              <div><dt>р/с</dt><dd>40802810101760002333</dd></div>
              <div><dt>к/с</dt><dd>30101810200000000593</dd></div>
              <div><dt>БИК</dt><dd>044525593</dd></div>
            </dl>
          </article>
          <article>
            <h3>Связь</h3>
            <dl>
              <div><dt>Адрес</dt><dd>117556, Москва, м. Варшавская, Болотниковская ул., д.5к3</dd></div>
              <div><dt>Телефон</dt><dd><a href="tel:+74993221311">+7 (499) 322-13-11</a></dd></div>
              <div><dt>Эл. почта</dt><dd><a href="mailto:info@comp-uter.ru">info@comp-uter.ru</a></dd></div>
              <div><dt>Режим работы</dt><dd>Понедельник-пятница, с 9:00 до 18:00</dd></div>
            </dl>
          </article>
        </div>
      </section>

      <section class="section privacy-section" id="privacy" aria-labelledby="privacy-title">
        <div class="section-heading">
          <p class="section-label">Документы и согласия</p>
          <h2 id="privacy-title">Отдельные страницы для политики и соглашения</h2>
          <p>
            Перед отправкой заказа покупатель подтверждает согласие с двумя документами: политикой конфиденциальности
            и пользовательским соглашением. Оба документа оформлены под ИП Михайловский Виталий Геннадьевич.
          </p>
        </div>
        <div class="privacy-grid">
          <article>
            <h3>Политика конфиденциальности</h3>
            <p>Описание обработки персональных данных, cookie, целей обработки, прав пользователя и контактов оператора.</p>
            <a class="legal-card-link" href="/privacy_policy/">Открыть политику</a>
          </article>
          <article>
            <h3>Пользовательское соглашение</h3>
            <p>Правила использования сайта, оформления заявки, уточнения цены, доставки, оплаты, прав на материалы и ответственности сторон.</p>
            <a class="legal-card-link" href="/polzovatelskoe-soglashenie/">Открыть соглашение</a>
          </article>
          <article>
            <h3>Согласие в форме</h3>
            <p>Заказ не отправится без двух обязательных отметок: согласие с политикой конфиденциальности и пользовательским соглашением.</p>
          </article>
          <article>
            <h3>Cookie</h3>
            <p>Cookie используются для корректной работы сайта и сохранения согласия. Уведомление можно закрыть кнопкой «Согласен».</p>
          </article>
        </div>
      </section>
    </main>

    <div class="photo-viewer" aria-hidden="true">
      <button class="photo-viewer-close" type="button" aria-label="Закрыть карточку товара">×</button>
      <article class="photo-viewer-frame product-dialog" role="dialog" aria-modal="true" aria-labelledby="product-dialog-title">
        <div class="product-dialog-media">
          <img alt="" />
        </div>
        <div class="product-dialog-copy">
          <p class="section-label">Карточка товара</p>
          <h3 id="product-dialog-title"></h3>
          <p class="product-dialog-price"></p>
          <p class="product-dialog-lead"></p>
          <dl class="product-dialog-specs"></dl>
          <ul class="product-dialog-tags" aria-label="Особенности модели"></ul>
          <div class="product-page-actions">
            <button class="button primary product-dialog-order" type="button">В корзину</button>
            <a class="button secondary product-dialog-full-link" data-product-full-link href="/#stock">Открыть карточку товара</a>
          </div>
        </div>
      </article>
    </div>

    <div class="cookie-banner" role="dialog" aria-live="polite" aria-label="Согласие на использование cookie">
      <div>
        <strong>Cookie и персональные данные</strong>
        <p>
          Мы используем cookie для работы сайта и обработки заявок. Нажимая «Согласен», вы подтверждаете согласие
          с использованием cookie и можете ознакомиться с <a href="/privacy_policy/">политикой конфиденциальности</a>.
        </p>
      </div>
      <button class="button primary cookie-accept" type="button">Согласен</button>
    </div>

    <div class="order-success" aria-hidden="true">
      <button class="order-success-close" type="button" aria-label="Закрыть сообщение о заказе">×</button>
      <article class="order-success-card" role="dialog" aria-modal="true" aria-labelledby="order-success-title">
        <p class="section-label">Заказ оформлен</p>
        <h2 id="order-success-title">ЗАЯВКА ПРИНЯТА</h2>
        <p class="order-success-number">Номер заявки: <strong></strong></p>
        <p>Мы свяжемся с вами в ближайшее время, уточним детали заказа, доставку и документы.</p>
        <button class="button primary order-success-ok" type="button">Хорошо</button>
      </article>
    </div>
    <!-- /order-success -->

    <footer class="site-footer">
      <div class="footer-brand">
        <a class="brand footer-logo" href="#top" aria-label="Comp-Uter">
          <span class="brand-image brand-image-full">
            <img src="/public/assets/comp-uter-logo-full.webp" alt="" />
          </span>
        </a>
        <p>Серверные процессоры Intel Xeon и жесткие диски Seagate для серверов, NAS, RAID-массивов, рабочих станций и корпоративных закупок.</p>
      </div>
      <div class="footer-links">
        <a href="/processors/">Серверные процессоры</a>
        <a href="/drives/">Диски и накопители</a>
        <a href="/privacy_policy/">Политика конфиденциальности</a>
        <a href="/polzovatelskoe-soglashenie/">Пользовательское соглашение</a>
        <a href="#requisites">Реквизиты для счета</a>
        <a href="#order">Оформить заказ</a>
      </div>
      <div class="footer-bottom">
        <span>ИП Михайловский В.Г. · +7 (499) 322-13-11 · info@comp-uter.ru</span>
        <span>© 2026 Comp-Uter. Все права защищены. Копирование материалов сайта запрещено.</span>
      </div>
    </footer>

    <script src="/src/nav.js?v=2"></script>
    <script src="/src/scroll-lock.js?v=3"></script>
    <script src="/src/gallery.js?v=3"></script>
    <script src="/src/main.js?v=3"></script>
  </body>
</html>
