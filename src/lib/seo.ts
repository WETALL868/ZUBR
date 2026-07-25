import type { Metadata } from "next";
import { siteConfig } from "@/config/site";

interface BuildMetadataParams {
  title: string;
  description: string;
  path: string;
}

export function buildMetadata({ title, description, path }: BuildMetadataParams): Metadata {
  const url = `${siteConfig.seo.siteUrl}${path}`;

  return {
    title,
    description,
    alternates: { canonical: path },
    openGraph: {
      title,
      description,
      url,
    },
    twitter: {
      title,
      description,
    },
  };
}
