import Link from "next/link";
import { siteConfig } from "@/config/site";
import { JsonLd } from "./JsonLd";
import { buildBreadcrumbSchema } from "@/lib/structured-data";

export interface Crumb {
  name: string;
  href: string;
}

export function Breadcrumbs({ items }: { items: Crumb[] }) {
  const allItems = [{ name: "Главная", href: "/" }, ...items];
  const schema = buildBreadcrumbSchema(
    allItems.map((item) => ({ name: item.name, url: `${siteConfig.seo.siteUrl}${item.href}` })),
  );

  return (
    <nav aria-label="Хлебные крошки" className="py-4 text-sm">
      <JsonLd data={schema} />
      <ol className="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-text-muted">
        {allItems.map((item, index) => (
          <li key={item.href} className="flex min-w-0 items-center gap-1.5">
            {index > 0 ? <span aria-hidden="true">/</span> : null}
            {index === allItems.length - 1 ? (
              <span className="font-medium text-text break-words" aria-current="page">
                {item.name}
              </span>
            ) : (
              <Link href={item.href} className="break-words hover:text-brand">
                {item.name}
              </Link>
            )}
          </li>
        ))}
      </ol>
    </nav>
  );
}
