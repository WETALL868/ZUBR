import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  images: {
    // Временные иллюстрации в public/images — SVG. Разрешаем их оптимизатору Next.js
    // безопасным способом (только для локальных файлов сайта, без внешних источников).
    dangerouslyAllowSVG: true,
    contentDispositionType: "inline",
    contentSecurityPolicy: "default-src 'self'; script-src 'none'; sandbox;",
  },
};

export default nextConfig;
