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

function ground(w, h, y) {
  return `<rect x="0" y="${y}" width="${w}" height="${h - y}" fill="#E4E0D4"/><line x1="0" y1="${y}" x2="${w}" y2="${y}" stroke="${LINE}" stroke-width="2"/>`;
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
    inner += `<circle cx="${w * 0.68}" cy="${baseY - h * 0.16}" r="34" fill="${GRAPHITE}"/>`;
    inner += `<rect x="${w * 0.6}" y="${baseY - h * 0.1}" width="${w * 0.16}" height="${h * 0.1}" rx="10" fill="${GRAPHITE_DARK}"/>`;
    inner += `<rect x="${w * 0.64}" y="${baseY - h * 0.03}" width="${w * 0.09}" height="${h * 0.03}" fill="${BRONZE}"/>`;
  }
  return svgWrap(w, h, inner);
}

// ---- Манифест файлов ----

const files = [];

files.push(["hero-sliding-gate.svg", sceneHouseGate(1600, 1100, { open: 0.05 }), "иллюстрация"]);
files.push(["hero-sectional.svg", sceneSectional(1600, 1100), "иллюстрация"]);
files.push(["hero-swing.svg", sceneSwing(1600, 1100, 16), "иллюстрация"]);
files.push(["hero-automation.svg", sceneAutomation(1600, 1100), "иллюстрация"]);
files.push(["hero-repair.svg", sceneRepair(1600, 1100, "before"), "иллюстрация"]);
files.push(["hero-ustanovka.svg", sceneHouseGate(1600, 1100, { open: 0.2 }), "иллюстрация"]);
files.push(["hero-about.svg", sceneTeam(1600, 1100, "installer"), "иллюстрация"]);

files.push(["gate-sliding-card.svg", sceneHouseGate(800, 600, { open: 0 }), "иллюстрация"]);
files.push(["gate-sectional-card.svg", sceneSectional(800, 600), "иллюстрация"]);
files.push(["gate-swing-card.svg", sceneSwing(800, 600, 22), "иллюстрация"]);
files.push(["gate-automation-card.svg", sceneAutomation(800, 600), "иллюстрация"]);
files.push(["gate-garage-card.svg", sceneSectional(800, 600), "иллюстрация"]);
files.push(["gate-industrial-card.svg", sceneIndustrial(800, 600), "иллюстрация"]);

files.push(["otkatnye-gallery-1.svg", sceneHouseGate(900, 700, { open: 0 }), "иллюстрация"]);
files.push(["otkatnye-gallery-2.svg", sceneHouseGate(900, 700, { open: 0.5 }), "иллюстрация"]);
files.push(["otkatnye-gallery-3.svg", sceneAutomation(900, 700), "иллюстрация"]);

files.push(["fill-ral.svg", swatchPattern("ral"), ""]);
files.push(["fill-profnastil.svg", swatchPattern("profnastil"), ""]);
files.push(["fill-shtaketnik.svg", swatchPattern("shtaketnik"), ""]);
files.push(["fill-zhalyuzi.svg", swatchPattern("zhalyuzi"), ""]);
files.push(["fill-sandwich.svg", swatchPattern("sandwich"), ""]);
files.push(["fill-combo.svg", swatchPattern("combo"), ""]);

files.push(["automation-showcase.svg", sceneAutomation(1000, 760), "иллюстрация"]);
files.push(["repair-showcase.svg", sceneRepair(1000, 760, "normal"), "иллюстрация"]);

files.push(["trust-installer.svg", sceneTeam(900, 700, "installer"), "фото"]);
files.push(["trust-van.svg", sceneTeam(900, 700, "van"), "фото"]);
files.push(["trust-warranty.svg", sceneTeam(900, 700, "warranty"), "фото"]);
files.push(["trust-object.svg", sceneHouseGate(900, 700, { open: 0 }), "фото"]);

// Портфолио
files.push(["portfolio-otkatnye-1.svg", sceneHouseGate(800, 800, { open: 0 }), "фото"]);
files.push(["portfolio-otkatnye-1b.svg", sceneHouseGate(800, 800, { open: 0.6 }), "фото"]);
files.push(["portfolio-otkatnye-2.svg", sceneHouseGate(800, 800, { open: 0.3 }), "фото"]);
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
