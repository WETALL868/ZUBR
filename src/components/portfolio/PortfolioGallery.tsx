"use client";

import { useMemo, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { portfolioFilters, portfolioItems } from "@/data/portfolio";
import type { PortfolioCategory } from "@/types";
import { Card } from "@/components/ui/Card";
import { Lightbox } from "./Lightbox";
import { cn } from "@/lib/utils";

interface PortfolioGalleryProps {
  limit?: number;
  showFilters?: boolean;
  showViewAllLink?: boolean;
  initialFilter?: PortfolioCategory | "all";
}

export function PortfolioGallery({
  limit,
  showFilters = true,
  showViewAllLink = false,
  initialFilter = "all",
}: PortfolioGalleryProps) {
  const [activeFilter, setActiveFilter] = useState<PortfolioCategory | "all">(initialFilter);
  const [lightbox, setLightbox] = useState<{ images: { src: string; alt: string }[]; index: number } | null>(null);

  const filtered = useMemo(() => {
    const items =
      activeFilter === "all" ? portfolioItems : portfolioItems.filter((item) => item.category === activeFilter);
    return typeof limit === "number" ? items.slice(0, limit) : items;
  }, [activeFilter, limit]);

  function openLightbox(images: string[], alt: string, startIndex = 0) {
    setLightbox({ images: images.map((src) => ({ src, alt })), index: startIndex });
  }

  return (
    <div>
      {showFilters ? (
        <div className="flex flex-wrap gap-2" role="tablist" aria-label="Фильтр портфолио">
          {portfolioFilters.map((filter) => (
            <button
              key={filter.value}
              type="button"
              role="tab"
              aria-selected={activeFilter === filter.value}
              onClick={() => setActiveFilter(filter.value)}
              className={cn(
                "rounded-full border px-4 py-2 text-sm font-medium transition-colors",
                activeFilter === filter.value
                  ? "border-brand bg-brand text-white"
                  : "border-border text-text-muted hover:border-brand/50 hover:text-brand",
              )}
            >
              {filter.label}
            </button>
          ))}
        </div>
      ) : null}

      <div className="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {filtered.map((item, index) => {
          const allImages = item.beforeAfter
            ? [item.beforeAfter.before, item.beforeAfter.after, ...item.images]
            : item.images;

          return (
            <Card key={item.id} className="overflow-hidden">
              <button
                type="button"
                onClick={() => openLightbox(allImages, item.title)}
                className="relative block aspect-square w-full overflow-hidden"
                aria-label={`Открыть фотографии: ${item.title}`}
              >
                <Image
                  src={item.images[0]}
                  alt={item.title}
                  fill
                  loading={index === 0 ? undefined : "lazy"}
                  priority={index === 0}
                  sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                  className="object-cover transition-transform duration-300 hover:scale-105"
                />
                <span className="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-brand">
                  {item.categoryLabel}
                </span>
                {item.beforeAfter ? (
                  <span className="absolute right-3 top-3 rounded-full bg-accent px-3 py-1 text-xs font-semibold text-white">
                    До / После
                  </span>
                ) : null}
              </button>
              <div className="p-5">
                <h3 className="font-bold text-text">{item.title}</h3>
                <dl className="mt-3 space-y-1 text-sm text-text-muted">
                  <div className="flex justify-between gap-2">
                    <dt>Район</dt>
                    <dd className="text-right text-text">{item.district}</dd>
                  </div>
                  <div className="flex justify-between gap-2">
                    <dt>Размер</dt>
                    <dd className="text-right text-text">{item.size}</dd>
                  </div>
                  <div className="flex justify-between gap-2">
                    <dt>Автоматика</dt>
                    <dd className="text-right text-text">{item.automation}</dd>
                  </div>
                </dl>
                <p className="mt-3 text-sm text-text-muted">{item.works}</p>
              </div>
            </Card>
          );
        })}
      </div>

      {filtered.length === 0 ? (
        <p className="mt-8 text-center text-text-muted">В этой категории пока нет примеров работ.</p>
      ) : null}

      {showViewAllLink ? (
        <div className="mt-10 text-center">
          <Link href="/portfolio" className="text-sm font-semibold text-brand hover:text-brand-hover">
            Смотреть все проекты →
          </Link>
        </div>
      ) : null}

      {lightbox ? (
        <Lightbox
          images={lightbox.images}
          index={lightbox.index}
          onClose={() => setLightbox(null)}
          onNavigate={(i) => setLightbox((prev) => (prev ? { ...prev, index: i } : prev))}
        />
      ) : null}
    </div>
  );
}
