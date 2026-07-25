import { siteConfig } from "@/config/site";
import type { FaqItem } from "@/types";

export function buildLocalBusinessSchema() {
  return {
    "@context": "https://schema.org",
    "@type": "HomeAndConstructionBusiness",
    name: siteConfig.companyName,
    image: `${siteConfig.seo.siteUrl}/images/hero-sliding-gate.svg`,
    "@id": siteConfig.seo.siteUrl,
    url: siteConfig.seo.siteUrl,
    telephone: siteConfig.phoneHref.replace("tel:", ""),
    email: siteConfig.email,
    address: {
      "@type": "PostalAddress",
      streetAddress: siteConfig.address,
      addressLocality: siteConfig.city,
      addressRegion: siteConfig.region,
      addressCountry: "RU",
    },
    geo: {
      "@type": "GeoCoordinates",
      latitude: siteConfig.officeCoordinates.lat,
      longitude: siteConfig.officeCoordinates.lng,
    },
    openingHoursSpecification: {
      "@type": "OpeningHoursSpecification",
      dayOfWeek: [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday",
      ],
      opens: siteConfig.workingHoursSchema.opens,
      closes: siteConfig.workingHoursSchema.closes,
    },
    priceRange: siteConfig.minPriceLabel,
    areaServed: siteConfig.cityAndRegion,
  };
}

export function buildServiceSchema(params: { name: string; description: string; url: string }) {
  return {
    "@context": "https://schema.org",
    "@type": "Service",
    serviceType: params.name,
    name: params.name,
    description: params.description,
    url: params.url,
    provider: {
      "@type": "HomeAndConstructionBusiness",
      name: siteConfig.companyName,
      telephone: siteConfig.phoneHref.replace("tel:", ""),
    },
    areaServed: siteConfig.cityAndRegion,
  };
}

export function buildFaqSchema(items: FaqItem[]) {
  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: items.map((item) => ({
      "@type": "Question",
      name: item.question,
      acceptedAnswer: {
        "@type": "Answer",
        text: item.answer,
      },
    })),
  };
}

export function buildBreadcrumbSchema(items: { name: string; url: string }[]) {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: items.map((item, index) => ({
      "@type": "ListItem",
      position: index + 1,
      name: item.name,
      item: item.url,
    })),
  };
}
