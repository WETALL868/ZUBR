#!/usr/bin/env node
/**
 * Генератор временных SVG-иллюстраций для public/images.
 * Создаёт согласованные по стилю плейсхолдеры (плоская иллюстрация в фирменной палитре),
 * которые нужно заменить на реальные фотографии — см. IMAGE_REQUIREMENTS.md.
 */
import { mkdirSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";

const __dirname = dirname(fileURLToPath(import.meta.url));
const OUT_DIR = join(__dirname, "..", "public", "images");
mkdirSync(OUT_DIR, { recursive: true });

const GRAPHITE = "#174C48";
const GRAPHITE_DARK = "#103936";
const BRONZE = "#B98242";
const LINE = "#DDD9D1";
const STEEL = "#6E756F";
const STEEL_DARK = "#4B514C";
const SOIL = "#7A6449";
const SOIL_DARK = "#54432E";
const SKIN = "#C9977A";
const DUSK_SKY_TOP = "#1B2422";
const DUSK_SKY_BOTTOM = "#2C3A34";

function svgWrap(w, h, inner, tag) {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}" role="img">
<defs>
<linearGradient id="bg" x1="0" y1="0" x2="0" y2="1">
<stop offset="0" stop-color="#F8F6F1"/>
<stop offset="1" stop-color="#ECE7DC"/>
</linearGradient>
<linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
<stop offset="0" stop-color="#FBF9F4"/>
<stop offset="1" stop-color="#F1EDE3"/>
</linearGradient>
<linearGradient id="duskSky" x1="0" y1="0" x2="0" y2="1">
<stop offset="0" stop-color="${DUSK_SKY_TOP}"/>
<stop offset="1" stop-color="${DUSK_SKY_BOTTOM}"/>
</linearGradient>
<radialGradient id="workLight" cx="50%" cy="30%" r="70%">
<stop offset="0" stop-color="${BRONZE}" stop-opacity="0.35"/>
<stop offset="1" stop-color="${BRONZE}" stop-opacity="0"/>
</radialGradient>
</defs>
<rect width="${w}" height="${h}" fill="url(#bg)"/>
${inner}
${
  tag
    ? `<g opacity="0.8"><rect x="${w - tag.length * 7.6 - 28}" y="${h - 40}" width="${
        tag.length * 7.6 + 16
      }" height="24" rx="12" fill="#ffffff" opacity="0.55"/><text x="${w - 20}" y="${h - 24}" text-anchor="end" font-family="Arial, sans-serif" font-size="13" fill="${BRONZE}" font-weight="600">${tag}</text></g>`
    : ""
}
</svg>`;
}

function ground(w, h, y, { dark = false } = {}) {
  const base = dark ? "#232B27" : "#E4E0D4";
  const lineColor = dark ? "rgba(255,255,255,0.12)" : LINE;
  let texture = "";
  const speckleCount = Math.round(((w * (h - y)) / 9000) * 3);
  for (let i = 0; i < speckleCount; i++) {
    const px = Math.random() * w;
    const py = y + Math.random() * (h - y);
    const r = 1.2 + Math.random() * 2.6;
    const tone = Math.random() > 0.5 ? SOIL : SOIL_DARK;
    texture += `<ellipse cx="${px.toFixed(1)}" cy="${py.toFixed(1)}" rx="${r.toFixed(1)}" ry="${(r * 0.6).toFixed(1)}" fill="${tone}" opacity="${(dark ? 0.18 : 0.14).toFixed(2)}"/>`;
  }
  // Едва заметные следы протектора — жизненная, «рабочая» деталь участка.
  const trackY = y + (h - y) * 0.55;
  const track = `<g opacity="${dark ? 0.14 : 0.1}" stroke="${SOIL_DARK}" stroke-width="10" fill="none" stroke-linecap="round">
    <path d="M ${w * 0.05} ${trackY + 14} Q ${w * 0.5} ${trackY - 10} ${w * 0.95} ${trackY + 18}"/>
    <path d="M ${w * 0.05} ${trackY + 34} Q ${w * 0.5} ${trackY + 10} ${w * 0.95} ${trackY + 38}"/>
  </g>`;
  return `<rect x="0" y="${y}" width="${w}" height="${h - y}" fill="${base}"/>${track}${texture}<line x1="0" y1="${y}" x2="${w}" y2="${y}" stroke="${lineColor}" stroke-width="2"/>`;
}

function house(cx, baseY, scale = 1) {
  const w = 220 * scale;
  const h = 130 * scale;
  const roofH = 55 * scale;
  const x = cx - w / 2;
  const y = baseY - h;
  return `
<g>
  <rect x="${x}" y="${y}" width="${w}" height="${h}" fill="#ffffff" stroke="${LINE}" stroke-width="2"/>
  <polygon points="${x - 14},${y} ${x + w + 14},${y} ${x + w / 2},${y - roofH}" fill="${GRAPHITE}"/>
  <rect x="${x + w * 0.12}" y="${y + h * 0.28}" width="${w * 0.22}" height="${h * 0.3}" fill="${GRAPHITE}" opacity="0.15"/>
  <rect x="${x + w * 0.66}" y="${y + h * 0.28}" width="${w * 0.22}" height="${h * 0.3}" fill="${GRAPHITE}" opacity="0.15"/>
  <rect x="${x + w * 0.42}" y="${y + h * 0.45}" width="${w * 0.16}" height="${h * 0.55}" fill="${GRAPHITE_DARK}" opacity="0.4"/>
</g>`;
}

function slidingGate(cx, baseY, w = 380, h = 130, openRatio = 0) {
  const x = cx - w / 2 + openRatio * w * 0.55;
  const slats = 10;
  const slatW = (w * (1 - openRatio * 0.55)) / slats;
  let rects = "";
  for (let i = 0; i < slats; i++) {
    rects += `<rect x="${x + i * slatW + 2}" y="${baseY - h}" width="${slatW - 4}" height="${h}" fill="${
      i % 2 === 0 ? GRAPHITE : GRAPHITE_DARK
    }" opacity="${i % 2 === 0 ? 0.92 : 0.8}"/>`;
  }
  return `
<g>
  <rect x="${cx - w * 0.9}" y="${baseY + 6}" width="${w * 1.8}" height="6" rx="3" fill="${BRONZE}" opacity="0.6"/>
  ${rects}
  <rect x="${x - 6}" y="${baseY - h - 8}" width="${w * (1 - openRatio * 0.55) + 12}" height="10" fill="${GRAPHITE_DARK}"/>
  <circle cx="${x + 10}" cy="${baseY + 6}" r="7" fill="${GRAPHITE_DARK}"/>
  <circle cx="${x + w * (1 - openRatio * 0.55) - 10}" cy="${baseY + 6}" r="7" fill="${GRAPHITE_DARK}"/>
</g>`;
}

function pillars(cx, baseY, gap = 300, pH = 150, pW = 26) {
  return `
<rect x="${cx - gap / 2 - pW}" y="${baseY - pH}" width="${pW}" height="${pH}" fill="${GRAPHITE_DARK}"/>
<rect x="${cx + gap / 2}" y="${baseY - pH}" width="${pW}" height="${pH}" fill="${GRAPHITE_DARK}"/>
`;
}

function swingGate(cx, baseY, gap = 300, openDeg = 18) {
  const leafW = gap / 2 - 6;
  const bars = 6;
  let barsL = "";
  let barsR = "";
  for (let i = 0; i <= bars; i++) {
    const px = i * (leafW / bars);
    barsL += `<line x1="${px}" y1="0" x2="${px}" y2="120" stroke="${GRAPHITE}" stroke-width="6"/>`;
    barsR += `<line x1="${px}" y1="0" x2="${px}" y2="120" stroke="${GRAPHITE}" stroke-width="6"/>`;
  }
  return `
${pillars(cx, baseY, gap)}
<g transform="translate(${cx - gap / 2 + 6} ${baseY - 120}) rotate(-${openDeg} 0 120)">
  <rect x="0" y="0" width="${leafW}" height="120" fill="${GRAPHITE}" opacity="0.08"/>
  ${barsL}
  <line x1="0" y1="0" x2="${leafW}" y2="0" stroke="${GRAPHITE}" stroke-width="6"/>
  <line x1="0" y1="120" x2="${leafW}" y2="120" stroke="${GRAPHITE}" stroke-width="6"/>
</g>
<g transform="translate(${cx + gap / 2 - 6} ${baseY - 120}) rotate(${openDeg} ${leafW} 120) translate(${-leafW} 0)">
  <rect x="0" y="0" width="${leafW}" height="120" fill="${GRAPHITE}" opacity="0.08"/>
  ${barsR}
  <line x1="0" y1="0" x2="${leafW}" y2="0" stroke="${GRAPHITE}" stroke-width="6"/>
  <line x1="0" y1="120" x2="${leafW}" y2="120" stroke="${GRAPHITE}" stroke-width="6"/>
</g>`;
}

function sectionalDoor(cx, baseY, w = 260, h = 190) {
  const x = cx - w / 2;
  const rows = 5;
  const rowH = h / rows;
  let rects = "";
  for (let i = 0; i < rows; i++) {
    rects += `<rect x="${x}" y="${baseY - h + i * rowH + 2}" width="${w}" height="${rowH - 4}" fill="${
      i % 2 === 0 ? GRAPHITE : GRAPHITE_DARK
    }" opacity="0.9"/>`;
  }
  return `
<rect x="${x - 18}" y="${baseY - h - 18}" width="${w + 36}" height="${h + 18}" fill="#ffffff" stroke="${LINE}" stroke-width="2"/>
${rects}
<rect x="${x - 18}" y="${baseY - h - 18}" width="${w + 36}" height="12" fill="${BRONZE}" opacity="0.5"/>
`;
}

function driveBox(cx, cy, scale = 1) {
  return `
<g transform="translate(${cx} ${cy}) scale(${scale})">
  <rect x="-70" y="-50" width="140" height="100" rx="10" fill="${GRAPHITE_DARK}"/>
  <rect x="-58" y="-38" width="116" height="42" rx="6" fill="${GRAPHITE}"/>
  <circle cx="40" cy="30" r="9" fill="${BRONZE}"/>
  <rect x="-58" y="18" width="70" height="10" rx="5" fill="#ffffff" opacity="0.5"/>
  <line x1="70" y1="10" x2="130" y2="-30" stroke="${GRAPHITE_DARK}" stroke-width="6" stroke-linecap="round"/>
  <line x1="-70" y1="10" x2="-130" y2="40" stroke="${GRAPHITE_DARK}" stroke-width="6" stroke-linecap="round"/>
</g>`;
}

/** Простая фигура монтажника — приземистая рабочая поза, инструмент в руке. */
function worker(cx, baseY, { scale = 1, pose = "crouch", dark = false, mirror = false } = {}) {
  const s = scale;
  const bodyColor = GRAPHITE_DARK;
  const vestColor = BRONZE;
  const flip = mirror ? -1 : 1;
  if (pose === "crouch") {
    return `
<g transform="translate(${cx} ${baseY}) scale(${flip * s} ${s})">
  <ellipse cx="0" cy="2" rx="34" ry="7" fill="#000000" opacity="${dark ? 0.35 : 0.14}"/>
  <path d="M -14 -8 L -22 0 L -10 0 Z" fill="${bodyColor}"/>
  <path d="M 10 -10 L 20 0 L 6 0 Z" fill="${bodyColor}"/>
  <path d="M -16 -42 Q -18 -18 -6 -8 L 14 -10 Q 20 -26 12 -46 Q -4 -54 -16 -42 Z" fill="${vestColor}"/>
  <circle cx="-6" cy="-58" r="11" fill="${SKIN}"/>
  <path d="M -16 -60 Q -6 -70 4 -60 L 2 -63 Q -6 -68 -14 -63 Z" fill="${bodyColor}"/>
  <path d="M 12 -44 Q 30 -40 34 -24" stroke="${bodyColor}" stroke-width="7" fill="none" stroke-linecap="round"/>
  <rect x="30" y="-30" width="6" height="22" rx="2" fill="${STEEL}" transform="rotate(20 33 -19)"/>
  <path d="M -14 -40 Q -26 -34 -26 -18" stroke="${bodyColor}" stroke-width="7" fill="none" stroke-linecap="round"/>
</g>`;
  }
  return `
<g transform="translate(${cx} ${baseY}) scale(${flip * s} ${s})">
  <ellipse cx="0" cy="2" rx="26" ry="6" fill="#000000" opacity="${dark ? 0.35 : 0.14}"/>
  <rect x="-10" y="-8" width="10" height="10" fill="${bodyColor}"/>
  <rect x="4" y="-8" width="10" height="10" fill="${bodyColor}"/>
  <path d="M -14 -70 L 14 -70 L 12 -10 L -12 -10 Z" fill="${vestColor}"/>
  <circle cx="0" cy="-82" r="11" fill="${SKIN}"/>
  <path d="M -11 -84 Q 0 -94 11 -84 L 9 -88 Q 0 -95 -9 -88 Z" fill="${bodyColor}"/>
  <path d="M -14 -64 Q -30 -54 -32 -34" stroke="${bodyColor}" stroke-width="7" fill="none" stroke-linecap="round"/>
  <path d="M 14 -64 Q 26 -50 24 -30" stroke="${bodyColor}" stroke-width="7" fill="none" stroke-linecap="round"/>
  <rect x="16" y="-36" width="6" height="26" rx="2" fill="${STEEL}" transform="rotate(-18 19 -23)"/>
</g>`;
}

/** Инструмент, разбросанный у места монтажа — гаечный ключ, уровень, рулетка. */
function toolsScatter(cx, baseY, scale = 1) {
  const s = scale;
  return `
<g transform="translate(${cx} ${baseY}) scale(${s})">
  <rect x="-46" y="-6" width="70" height="10" rx="3" fill="${BRONZE}" transform="rotate(-8 -11 -1)"/>
  <rect x="30" y="-16" width="8" height="34" rx="2" fill="${STEEL_DARK}" transform="rotate(24 34 1)"/>
  <circle cx="52" cy="4" r="10" fill="${STEEL}" opacity="0.9"/>
  <circle cx="52" cy="4" r="4" fill="${GRAPHITE_DARK}"/>
</g>`;
}

/** Траншея под направляющую откатных ворот — с уложенным рельсом и грунтом по краям. */
function trench(cx, baseY, width = 260) {
  const depth = 22;
  return `
<g>
  <rect x="${cx - width / 2}" y="${baseY - depth}" width="${width}" height="${depth + 6}" rx="4" fill="${SOIL_DARK}"/>
  <rect x="${cx - width / 2 - 10}" y="${baseY - depth - 6}" width="${width + 20}" height="8" rx="4" fill="${SOIL}" opacity="0.8"/>
  <rect x="${cx - width / 2 + 6}" y="${baseY - depth + 4}" width="${width - 12}" height="7" rx="3" fill="${STEEL}"/>
  ${Array.from({ length: Math.round(width / 30) })
    .map((_, i) => {
      const bx = cx - width / 2 + 16 + i * 30;
      return `<rect x="${bx}" y="${baseY - depth - 2}" width="4" height="12" fill="${STEEL_DARK}"/>`;
    })
    .join("")}
</g>`;
}

/** Тачка с раствором — деталь подготовки фундамента под направляющую. */
function wheelbarrow(cx, baseY, scale = 1) {
  const s = scale;
  return `
<g transform="translate(${cx} ${baseY}) scale(${s})">
  <ellipse cx="0" cy="4" rx="30" ry="6" fill="#000000" opacity="0.12"/>
  <circle cx="-18" cy="0" r="9" fill="${STEEL_DARK}"/>
  <path d="M -30 -22 L 26 -26 L 20 -2 L -24 2 Z" fill="${STEEL}"/>
  <path d="M -26 -20 L 18 -24 L 14 -6 L -20 -2 Z" fill="${SOIL}"/>
  <line x1="20" y1="-2" x2="40" y2="6" stroke="${STEEL_DARK}" stroke-width="5" stroke-linecap="round"/>
  <line x1="26" y1="-26" x2="42" y2="2" stroke="${STEEL_DARK}" stroke-width="5" stroke-linecap="round"/>
</g>`;
}

/** Рабочий фургон компании — используется в сценах об автопарке и на общем плане объекта. */
function van(cx, baseY, scale = 1) {
  const s = scale;
  const w = 240;
  const h = 96;
  return `
<g transform="translate(${cx - w / 2} ${baseY - h}) scale(${s})">
  <rect x="0" y="16" width="${w}" height="${h - 16}" rx="10" fill="${GRAPHITE}"/>
  <rect x="${w - 64}" y="0" width="64" height="${h}" rx="8" fill="${GRAPHITE_DARK}"/>
  <rect x="${w - 54}" y="14" width="40" height="30" rx="4" fill="#CFE3E0" opacity="0.55"/>
  <circle cx="40" cy="${h + 4}" r="17" fill="${STEEL_DARK}"/>
  <circle cx="${w - 46}" cy="${h + 4}" r="17" fill="${STEEL_DARK}"/>
  <rect x="14" y="${h * 0.42}" width="${w * 0.45}" height="${h * 0.3}" fill="${BRONZE}" opacity="0.4"/>
</g>`;
}

function warehouse(cx, baseY, w = 520, h = 220) {
  const x = cx - w / 2;
  const doorW = w / 3 - 16;
  let doors = "";
  for (let i = 0; i < 3; i++) {
    const dx = x + 16 + i * (doorW + 8);
    doors += `<rect x="${dx}" y="${baseY - h + 30}" width="${doorW}" height="${h - 50}" fill="${GRAPHITE}" opacity="${
      0.85 - i * 0.08
    }"/>`;
    for (let r = 1; r < 5; r++) {
      doors += `<line x1="${dx}" y1="${baseY - h + 30 + r * ((h - 50) / 5)}" x2="${dx + doorW}" y2="${
        baseY - h + 30 + r * ((h - 50) / 5)
      }" stroke="${GRAPHITE_DARK}" stroke-width="2" opacity="0.5"/>`;
    }
  }
  return `
<rect x="${x}" y="${baseY - h}" width="${w}" height="${h}" fill="#ffffff" stroke="${LINE}" stroke-width="2"/>
<rect x="${x}" y="${baseY - h}" width="${w}" height="16" fill="${BRONZE}" opacity="0.5"/>
${doors}
`;
}

function swatchPattern(kind) {
  const w = 640;
  const h = 480;
  let inner = "";
  if (kind === "profnastil") {
    for (let i = 0; i < 16; i++) {
      inner += `<rect x="${i * 42}" y="0" width="22" height="${h}" fill="${i % 2 ? GRAPHITE : GRAPHITE_DARK}" opacity="0.85"/>`;
    }
  } else if (kind === "shtaketnik") {
    for (let i = 0; i < 22; i++) {
      inner += `<rect x="${i * 30}" y="60" width="14" height="${h - 120}" rx="7" fill="${GRAPHITE}"/>`;
    }
  } else if (kind === "zhalyuzi") {
    for (let i = 0; i < 14; i++) {
      inner += `<rect x="0" y="${i * 34}" width="${w}" height="22" fill="${i % 2 ? GRAPHITE_DARK : GRAPHITE}" opacity="0.85" transform="skewY(-4)"/>`;
    }
  } else if (kind === "sandwich") {
    for (let i = 0; i < 8; i++) {
      inner += `<rect x="0" y="${i * 60}" width="${w}" height="48" fill="${i % 2 ? GRAPHITE_DARK : GRAPHITE}" opacity="0.85"/>`;
    }
  } else if (kind === "combo") {
    for (let i = 0; i < 8; i++) {
      inner += `<rect x="0" y="${i * 60}" width="${w * 0.55}" height="48" fill="${GRAPHITE}" opacity="0.85"/>`;
    }
    for (let i = 0; i < 22; i++) {
      inner += `<rect x="${w * 0.6 + i * 16}" y="40" width="8" height="${h - 80}" fill="${BRONZE}" opacity="0.7"/>`;
    }
  } else if (kind === "ral") {
    const colors = [GRAPHITE, GRAPHITE_DARK, BRONZE, "#8C8578", "#3E4A45", "#5B6B66"];
    colors.forEach((c, i) => {
      const col = i % 3;
      const row = Math.floor(i / 3);
      inner += `<rect x="${col * (w / 3) + 20}" y="${row * (h / 2) + 20}" width="${w / 3 - 40}" height="${
        h / 2 - 40
      }" rx="14" fill="${c}"/>`;
    });
  }
  return svgWrap(w, h, inner);
}

function monogram(letter, seed) {
  const colors = [GRAPHITE, BRONZE, GRAPHITE_DARK];
  const color = colors[seed % colors.length];
  return `<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
<circle cx="100" cy="100" r="100" fill="${color}"/>
<text x="100" y="122" text-anchor="middle" font-family="Arial, sans-serif" font-size="76" fill="#ffffff" font-weight="700">${letter}</text>
</svg>`;
}

// ---- Композиции сцен ----

function sceneHouseGate(w, h, { open = 0, garden = true } = {}) {
  const baseY = h * 0.72;
  let inner = "";
  inner += `<rect width="${w}" height="${baseY}" fill="url(#sky)"/>`;
  inner += ground(w, h, baseY);
  if (garden) {
    inner += `<rect x="0" y="${baseY - 6}" width="${w}" height="6" fill="${BRONZE}" opacity="0.35"/>`;
  }
  inner += house(w * 0.28, baseY, w / 900);
  inner += slidingGate(w * 0.66, baseY, w * 0.42, h * 0.16, open);
  return svgWrap(w, h, inner);
}

function sceneSectional(w, h) {
  const baseY = h * 0.78;
  let inner = `<rect width="${w}" height="${baseY}" fill="url(#sky)"/>`;
  inner += ground(w, h, baseY);
  inner += sectionalDoor(w * 0.5, baseY, w * 0.42, h * 0.42);
  return svgWrap(w, h, inner);
}

function sceneSwing(w, h, openDeg = 20) {
  const baseY = h * 0.76;
  let inner = `<rect width="${w}" height="${baseY}" fill="url(#sky)"/>`;
  inner += ground(w, h, baseY);
  inner += swingGate(w * 0.5, baseY, w * 0.5, openDeg);
  return svgWrap(w, h, inner);
}

function sceneAutomation(w, h) {
  const baseY = h * 0.74;
  let inner = `<rect width="${w}" height="${baseY}" fill="url(#sky)"/>`;
  inner += ground(w, h, baseY);
  inner += slidingGate(w * 0.62, baseY, w * 0.36, h * 0.14, 0.15);
  inner += driveBox(w * 0.3, baseY - h * 0.1, Math.min(w, h) / 420);
  return svgWrap(w, h, inner);
}

function sceneIndustrial(w, h) {
  const baseY = h * 0.82;
  let inner = `<rect width="${w}" height="${baseY}" fill="url(#sky)"/>`;
  inner += ground(w, h, baseY);
  inner += warehouse(w * 0.5, baseY, w * 0.66, h * 0.5);
  return svgWrap(w, h, inner);
}

function sceneRepair(w, h, tone = "normal") {
  const baseY = h * 0.74;
  let inner = `<rect width="${w}" height="${baseY}" fill="url(#sky)"/>`;
  inner += ground(w, h, baseY);
  inner += slidingGate(w * 0.5, baseY, w * 0.4, h * 0.15, tone === "before" ? 0.05 : 0.02);
  inner += driveBox(w * 0.78, baseY - h * 0.12, Math.min(w, h) / 500);
  if (tone === "before") {
    inner += `<g stroke="${BRONZE}" stroke-width="4" opacity="0.85"><line x1="${w * 0.3}" y1="${baseY - h * 0.12}" x2="${
      w * 0.42
    }" y2="${baseY - h * 0.02}"/><line x1="${w * 0.42}" y1="${baseY - h * 0.12}" x2="${w * 0.3}" y2="${
      baseY - h * 0.02
    }"/></g>`;
  } else {
    inner += `<circle cx="${w * 0.78 + 40}" cy="${baseY - h * 0.12 - 40}" r="10" fill="${BRONZE}"/>`;
  }
  return svgWrap(w, h, inner);
}

function sceneTeam(w, h, variant = "installer") {
  const baseY = h * 0.76;
  let inner = `<rect width="${w}" height="${baseY}" fill="url(#sky)"/>`;
  inner += ground(w, h, baseY);
  if (variant === "van") {
    const x = w * 0.2;
    const vw = w * 0.5;
    const vh = h * 0.22;
    inner += `<rect x="${x}" y="${baseY - vh}" width="${vw}" height="${vh}" rx="14" fill="${GRAPHITE}"/>`;
    inner += `<rect x="${x + vw - vw * 0.28}" y="${baseY - vh - h * 0.08}" width="${vw * 0.28}" height="${
      vh + h * 0.08
    }" rx="10" fill="${GRAPHITE_DARK}"/>`;
    inner += `<circle cx="${x + vw * 0.22}" cy="${baseY + 6}" r="16" fill="${GRAPHITE_DARK}"/>`;
    inner += `<circle cx="${x + vw * 0.78}" cy="${baseY + 6}" r="16" fill="${GRAPHITE_DARK}"/>`;
    inner += `<rect x="${x + vw * 0.08}" y="${baseY - vh * 0.7}" width="${vw * 0.5}" height="${vh * 0.3}" fill="${BRONZE}" opacity="0.4"/>`;
  } else if (variant === "warranty") {
    const x = w * 0.32;
    const dw = w * 0.36;
    const dh = h * 0.42;
    inner += `<rect x="${x}" y="${baseY - dh}" width="${dw}" height="${dh}" fill="#ffffff" stroke="${LINE}" stroke-width="2"/>`;
    for (let i = 0; i < 6; i++) {
      inner += `<rect x="${x + dw * 0.15}" y="${baseY - dh + dh * 0.18 + i * 18}" width="${dw * 0.7}" height="8" fill="${
        i === 0 ? BRONZE : LINE
      }"/>`;
    }
    inner += `<circle cx="${x + dw * 0.78}" cy="${baseY - dh * 0.22}" r="26" fill="${GRAPHITE}" opacity="0.15"/>`;
  } else {
    inner += house(w * 0.32, baseY, w / 1000);
    inner += toolsScatter(w * 0.6, baseY + 8, w / 1400);
    inner += worker(w * 0.66, baseY, { scale: w / 1050, pose: "stand" });
  }
  return svgWrap(w, h, inner);
}

function sceneOtkatnyeInstall(w, h, { stage = "trench" } = {}) {
  const baseY = h * 0.74;
  let inner = "";
  inner += `<rect width="${w}" height="${baseY}" fill="url(#sky)"/>`;
  inner += ground(w, h, baseY);
  inner += house(w * 0.22, baseY, w / 1050);

  if (stage === "trench") {
    inner += trench(w * 0.62, baseY, w * 0.34);
    inner += slidingGate(w * 0.62, baseY, w * 0.4, h * 0.14, 0.05);
    inner += wheelbarrow(w * 0.36, baseY + 6, w / 1300);
    inner += toolsScatter(w * 0.5, baseY + 10, w / 1400);
    inner += worker(w * 0.68, baseY, { scale: w / 1250, pose: "crouch" });
  } else if (stage === "mount") {
    inner += slidingGate(w * 0.68, baseY, w * 0.38, h * 0.15, 0.1);
    inner += driveBox(w * 0.475, baseY - h * 0.11, Math.min(w, h) / 620);
    inner += toolsScatter(w * 0.62, baseY + 8, w / 1500);
    inner += worker(w * 0.54, baseY, { scale: w / 1250, pose: "stand" });
  } else {
    inner += slidingGate(w * 0.62, baseY, w * 0.42, h * 0.16, 0);
  }

  return svgWrap(w, h, inner);
}

function sceneAutomationMount(w, h) {
  const baseY = h * 0.72;
  let inner = "";
  inner += `<rect width="${w}" height="${baseY}" fill="url(#sky)"/>`;
  inner += ground(w, h, baseY);
  inner += slidingGate(w * 0.7, baseY, w * 0.34, h * 0.15, 0.08);
  inner += driveBox(w * 0.47, baseY - h * 0.13, Math.min(w, h) / 560);
  inner += toolsScatter(w * 0.58, baseY + 10, w / 1300);
  inner += worker(w * 0.33, baseY, { scale: w / 1100, pose: "stand" });
  return svgWrap(w, h, inner);
}

function sceneJobsiteDusk(w, h) {
  const baseY = h * 0.7;
  let inner = "";
  inner += `<rect width="${w}" height="${baseY}" fill="url(#duskSky)"/>`;
  inner += `<rect width="${w}" height="${baseY}" fill="url(#workLight)"/>`;
  inner += ground(w, h, baseY, { dark: true });

  inner += van(w * 0.16, baseY, w / 900);
  inner += trench(w * 0.48, baseY, w * 0.22);
  inner += slidingGate(w * 0.78, baseY, w * 0.3, h * 0.22, 0.05);
  inner += toolsScatter(w * 0.36, baseY + 10, w / 1200);
  inner += worker(w * 0.46, baseY, { scale: w / 1000, pose: "crouch", dark: true });
  inner += worker(w * 0.68, baseY, { scale: w / 950, pose: "stand", dark: true, mirror: true });

  return svgWrap(w, h, inner);
}

// ---- Манифест файлов ----

const files = [];

files.push(["hero-otkatnye-install.svg", sceneOtkatnyeInstall(1600, 1100, { stage: "trench" }), "иллюстрация"]);
files.push(["hero-sliding-gate.svg", sceneHouseGate(1600, 1100, { open: 0.05 }), "иллюстрация"]);
files.push(["hero-sectional.svg", sceneSectional(1600, 1100), "иллюстрация"]);
files.push(["hero-swing.svg", sceneSwing(1600, 1100, 16), "иллюстрация"]);
files.push(["hero-automation.svg", sceneAutomation(1600, 1100), "иллюстрация"]);
files.push(["hero-repair.svg", sceneRepair(1600, 1100, "before"), "иллюстрация"]);
files.push(["hero-ustanovka.svg", sceneOtkatnyeInstall(1600, 1100, { stage: "mount" }), "иллюстрация"]);
files.push(["hero-about.svg", sceneTeam(1600, 1100, "installer"), "иллюстрация"]);

files.push(["gate-sliding-card.svg", sceneHouseGate(800, 600, { open: 0 }), "иллюстрация"]);
files.push(["gate-sectional-card.svg", sceneSectional(800, 600), "иллюстрация"]);
files.push(["gate-swing-card.svg", sceneSwing(800, 600, 22), "иллюстрация"]);
files.push(["gate-automation-card.svg", sceneAutomation(800, 600), "иллюстрация"]);
files.push(["gate-garage-card.svg", sceneSectional(800, 600), "иллюстрация"]);
files.push(["gate-industrial-card.svg", sceneIndustrial(800, 600), "иллюстрация"]);

files.push(["otkatnye-work-1.svg", sceneOtkatnyeInstall(900, 700, { stage: "trench" }), "иллюстрация"]);
files.push(["otkatnye-work-2.svg", sceneOtkatnyeInstall(900, 700, { stage: "mount" }), "иллюстрация"]);
files.push(["otkatnye-work-3.svg", sceneHouseGate(900, 700, { open: 0 }), "иллюстрация"]);
files.push(["automation-otkatnye.svg", sceneAutomationMount(1000, 760), "иллюстрация"]);
files.push(["footer-jobsite.svg", sceneJobsiteDusk(1920, 900), ""]);

files.push(["fill-ral.svg", swatchPattern("ral"), ""]);
files.push(["fill-profnastil.svg", swatchPattern("profnastil"), ""]);
files.push(["fill-shtaketnik.svg", swatchPattern("shtaketnik"), ""]);
files.push(["fill-zhalyuzi.svg", swatchPattern("zhalyuzi"), ""]);
files.push(["fill-sandwich.svg", swatchPattern("sandwich"), ""]);
files.push(["fill-combo.svg", swatchPattern("combo"), ""]);

files.push(["trust-installer.svg", sceneTeam(900, 700, "installer"), "фото"]);
files.push(["trust-van.svg", sceneTeam(900, 700, "van"), "фото"]);
files.push(["trust-warranty.svg", sceneTeam(900, 700, "warranty"), "фото"]);
files.push(["trust-object.svg", sceneHouseGate(900, 700, { open: 0 }), "фото"]);

// Портфолио
files.push(["portfolio-otkatnye-1.svg", sceneOtkatnyeInstall(800, 800, { stage: "trench" }), "фото"]);
files.push(["portfolio-otkatnye-1b.svg", sceneHouseGate(800, 800, { open: 0.6 }), "фото"]);
files.push(["portfolio-otkatnye-2.svg", sceneOtkatnyeInstall(800, 800, { stage: "mount" }), "фото"]);
files.push(["portfolio-otkatnye-3.svg", sceneHouseGate(800, 800, { open: 0.1 }), "фото"]);
files.push(["portfolio-sektsionnye-1.svg", sceneSectional(800, 800), "фото"]);
files.push(["portfolio-sektsionnye-2.svg", sceneSectional(800, 800), "фото"]);
files.push(["portfolio-raspashnye-1.svg", sceneSwing(800, 800, 24), "фото"]);
files.push(["portfolio-raspashnye-2.svg", sceneSwing(800, 800, 0), "фото"]);
files.push(["portfolio-avtomatika-1.svg", sceneAutomation(800, 800), "фото"]);
files.push(["portfolio-remont-1.svg", sceneRepair(800, 800, "normal"), "фото"]);
files.push(["portfolio-remont-1-before.svg", sceneRepair(800, 800, "before"), "фото · до"]);
files.push(["portfolio-remont-1-after.svg", sceneRepair(800, 800, "normal"), "фото · после"]);
files.push(["portfolio-remont-2.svg", sceneSectional(800, 800), "фото"]);

files.push(["testimonial-demo-1.svg", monogram("А", 0), ""]);
files.push(["testimonial-demo-2.svg", monogram("М", 1), ""]);
files.push(["testimonial-demo-3.svg", monogram("Д", 2), ""]);

function injectTag(svg, tag) {
  if (!tag) return svg;
  const widthMatch = svg.match(/width="(\d+(?:\.\d+)?)"/);
  const heightMatch = svg.match(/height="(\d+(?:\.\d+)?)"/);
  const w = widthMatch ? Number(widthMatch[1]) : 800;
  const h = heightMatch ? Number(heightMatch[1]) : 600;
  const label = `<g opacity="0.85"><rect x="${w - tag.length * 7.4 - 26}" y="${
    h - 38
  }" width="${tag.length * 7.4 + 16}" height="22" rx="11" fill="#ffffff" opacity="0.6"/><text x="${
    w - 18
  }" y="${h - 23}" text-anchor="end" font-family="Arial, sans-serif" font-size="12" fill="${BRONZE}" font-weight="600">${tag}</text></g>`;
  return svg.replace("</svg>", `${label}</svg>`);
}

for (const [name, content, tag] of files) {
  writeFileSync(join(OUT_DIR, name), injectTag(content, tag), "utf8");
}

console.log(`Сгенерировано изображений: ${files.length}`);
