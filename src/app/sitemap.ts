import type { MetadataRoute } from "next";
import { siteConfig } from "@/config/site";

const routes = [
  "",
  "/ustanovka-vorot",
  "/otkatnye-vorota",
  "/sektsionnye-vorota",
  "/raspashnye-vorota",
  "/avtomatika",
  "/remont-vorot",
  "/portfolio",
  "/o-kompanii",
  "/kontakty",
  "/politika-konfidentsialnosti",
  "/soglasie-na-obrabotku-personalnyh-dannyh",
];

export default function sitemap(): MetadataRoute.Sitemap {
  const lastModified = new Date();

  return routes.map((route) => ({
    url: `${siteConfig.seo.siteUrl}${route}`,
    lastModified,
    changeFrequency: route === "" ? "weekly" : "monthly",
    priority: route === "" ? 1 : route === "/otkatnye-vorota" ? 0.9 : 0.7,
  }));
}
