"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { siteConfig } from "@/config/site";
import { mainNav } from "@/data/nav";
import { Logo } from "./Logo";
import { CalculatorButton } from "@/components/lead/CalculatorButton";
import { cn } from "@/lib/utils";
import { trackGoal } from "@/lib/analytics";

export function Header() {
  const pathname = usePathname();
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [lastPathname, setLastPathname] = useState(pathname);

  // Закрываем мобильное меню при переходе на другую страницу.
  // Обновление состояния во время рендера — рекомендованный React-подход
  // для синхронизации state с изменившимся пропом/параметром маршрута.
  if (pathname !== lastPathname) {
    setLastPathname(pathname);
    setIsMenuOpen(false);
  }

  useEffect(() => {
    function onScroll() {
      setIsScrolled(window.scrollY > 8);
    }
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    document.documentElement.style.overflow = isMenuOpen ? "hidden" : "";
    return () => {
      document.documentElement.style.overflow = "";
    };
  }, [isMenuOpen]);

  return (
    <header
      className={cn(
        "sticky top-0 z-40 w-full border-b transition-colors duration-200",
        isScrolled ? "border-border bg-surface/95 backdrop-blur-sm shadow-sm" : "border-transparent bg-bg",
      )}
    >
      <div className="mx-auto flex min-h-18 max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <Logo />

        <nav className="hidden items-center gap-2.5 xl:flex" aria-label="Основная навигация">
          {mainNav.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                "whitespace-nowrap text-[15px] font-medium text-text-muted transition-colors hover:text-brand",
                pathname === item.href && "text-brand",
              )}
            >
              {item.shortLabel ?? item.label}
            </Link>
          ))}
        </nav>

        <div className="hidden items-center gap-3 xl:flex">
          <div className="whitespace-nowrap text-right">
            <a
              href={siteConfig.phoneHref}
              onClick={() => trackGoal("phone_click")}
              className="block text-lg font-bold text-text hover:text-brand"
            >
              {siteConfig.phone}
            </a>
            <span className="text-xs text-text-muted">{siteConfig.workingHours}</span>
          </div>
          <CalculatorButton size="md" className="whitespace-nowrap">
            Рассчитать
          </CalculatorButton>
        </div>

        <div className="flex items-center gap-3 xl:hidden">
          <a href={siteConfig.phoneHref} onClick={() => trackGoal("phone_click")} aria-label="Позвонить" className="flex h-11 w-11 items-center justify-center rounded-full border border-border text-brand">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path
                d="M6.6 10.8c1.2 2.4 3.2 4.4 5.6 5.6l1.9-1.9c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.5.6.6 0 1 .4 1 1V19c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.1c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.5.1.4 0 .8-.2 1L6.6 10.8z"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinejoin="round"
              />
            </svg>
          </a>
          <button
            type="button"
            aria-label={isMenuOpen ? "Закрыть меню" : "Открыть меню"}
            aria-expanded={isMenuOpen}
            onClick={() => setIsMenuOpen((v) => !v)}
            className="flex h-11 w-11 items-center justify-center rounded-full border border-border text-text"
          >
            {isMenuOpen ? (
              <svg width="18" height="18" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M1 1l14 14M15 1L1 15" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
              </svg>
            ) : (
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M2 5h16M2 10h16M2 15h16" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
              </svg>
            )}
          </button>
        </div>
      </div>

      {isMenuOpen ? (
        <div className="fixed inset-x-0 top-18 bottom-0 z-30 overflow-y-auto bg-surface xl:hidden">
          <nav className="flex flex-col gap-1 px-4 py-6" aria-label="Мобильная навигация">
            {mainNav.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className={cn(
                  "rounded-xl px-4 py-3.5 text-lg font-semibold text-text hover:bg-bg",
                  pathname === item.href && "bg-bg text-brand",
                )}
              >
                {item.label}
              </Link>
            ))}
          </nav>
          <div className="border-t border-border px-4 py-6">
            <p className="mb-1 text-lg font-bold text-text">{siteConfig.phone}</p>
            <p className="mb-5 text-sm text-text-muted">{siteConfig.workingHours}</p>
            <CalculatorButton size="lg" className="w-full">
              Рассчитать стоимость
            </CalculatorButton>
          </div>
        </div>
      ) : null}
    </header>
  );
}
