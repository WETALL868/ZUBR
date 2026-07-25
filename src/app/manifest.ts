import type { MetadataRoute } from "next";
import { siteConfig } from "@/config/site";

export default function manifest(): MetadataRoute.Manifest {
  return {
    name: siteConfig.companyName,
    short_name: siteConfig.companyShortName,
    description: siteConfig.seo.defaultDescription,
    start_url: "/",
    display: "standalone",
    background_color: "#F5F3EE",
    theme_color: "#174C48",
    icons: [
      { src: "/icon", sizes: "32x32", type: "image/png" },
      { src: "/apple-icon", sizes: "180x180", type: "image/png" },
    ],
  };
}
