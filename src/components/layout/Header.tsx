"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { siteConfig } from "@/config/site";
import { headerNav, installationDropdown } from "@/data/nav";
import { Logo } from "./Logo";
import { CalculatorButton } from "@/components/lead/CalculatorButton";
import { cn } from "@/lib/utils";
import { trackGoal } from "@/lib/analytics";

function ChevronIcon({ open }: { open: boolean }) {
  return (
    <svg
      width="14"
      height="14"
      viewBox="0 0 16 16"
      fill="none"
      aria-hidden="true"
      className={cn("shrink-0 transition-transform duration-200", open && "rotate-180")}
    >
      <path d="M4 6l4 4 4-4" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

function InstallationNavItem({ pathname }: { pathname: string }) {
  const [isOpen, setIsOpen] = useState(false);
  const wrapperRef = useRef<HTMLDivElement>(null);
  const closeTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const isActive = installationDropdown.some((item) => item.href === pathname);

  function openNow() {
    if (closeTimer.current) clearTimeout(closeTimer.current);
    setIsOpen(true);
  }

  function closeSoon() {
    closeTimer.current = setTimeout(() => setIsOpen(false), 120);
  }

  useEffect(() => {
    if (!isOpen) return;
    function onKeyDown(e: KeyboardEvent) {
      if (e.key === "Escape") setIsOpen(false);
    }
    function onClickOutside(e: MouseEvent) {
      if (wrapperRef.current && !wrapperRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    }
    window.addEventListener("keydown", onKeyDown);
    document.addEventListener("click", onClickOutside);
    return () => {
      window.removeEventListener("keydown", onKeyDown);
      document.removeEventListener("click", onClickOutside);
    };
  }, [isOpen]);

  return (
    <div ref={wrapperRef} className="relative" onMouseEnter={openNow} onMouseLeave={closeSoon}>
      <button
        type="button"
        aria-haspopup="true"
        aria-expanded={isOpen}
        onClick={() => setIsOpen((v) => !v)}
        className={cn(
          "flex items-center gap-1 whitespace-nowrap text-[15px] font-medium text-text-muted transition-colors hover:text-brand",
          isActive && "text-brand",
        )}
      >
        Установка ворот
        <ChevronIcon open={isOpen} />
      </button>

      <div
        role="menu"
        className={cn(
          "absolute left-1/2 top-full z-10 w-64 -translate-x-1/2 pt-3 transition-all duration-150",
          isOpen ? "visible translate-y-0 opacity-100" : "invisible -translate-y-1 opacity-0",
        )}
      >
        <div className="overflow-hidden rounded-2xl border border-border bg-surface py-2 shadow-xl">
          {installationDropdown.map((item) => (
            <Link
              key={item.href}
              role="menuitem"
              href={item.href}
              className={cn(
                "block px-4 py-2.5 text-[15px] hover:bg-bg hover:text-brand",
                item.primary ? "font-bold text-text" : "text-text-muted",
                pathname === item.href && "text-brand",
              )}
            >
              {item.label}
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
}

export function Header() {
  const pathname = usePathname();
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isMobileInstallOpen, setIsMobileInstallOpen] = useState(false);
  const [lastPathname, setLastPathname] = useState(pathname);

  // Закрываем мобильное меню при переходе на другую страницу.
  // Обновление состояния во время рендера — рекомендованный React-подход
  // для синхронизации state с изменившимся пропом/параметром маршрута.
  if (pathname !== lastPathname) {
    setLastPathname(pathname);
    setIsMenuOpen(false);
    setIsMobileInstallOpen(false);
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

  const isInstallActive = installationDropdown.some((item) => item.href === pathname);

  return (
    <header
      className={cn(
        "sticky top-0 z-40 w-full border-b transition-colors duration-200",
        isScrolled ? "border-border bg-surface/95 backdrop-blur-sm shadow-sm" : "border-transparent bg-bg",
      )}
    >
      <div className="mx-auto flex min-h-18 max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <Logo />

        <nav className="hidden items-center gap-6 xl:flex" aria-label="Основная навигация">
          <Link
            href="/"
            className={cn(
              "whitespace-nowrap text-[15px] font-medium text-text-muted transition-colors hover:text-brand",
              pathname === "/" && "text-brand",
            )}
          >
            Главная
          </Link>
          <InstallationNavItem pathname={pathname} />
          {headerNav.slice(1).map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                "whitespace-nowrap text-[15px] font-medium text-text-muted transition-colors hover:text-brand",
                pathname === item.href && "text-brand",
              )}
            >
              {item.label}
            </Link>
          ))}
        </nav>

        <div className="hidden items-center gap-4 xl:flex">
          <a
            href={siteConfig.phoneHref}
            onClick={() => trackGoal("phone_click")}
            className="whitespace-nowrap text-lg font-bold text-text hover:text-brand"
          >
            {siteConfig.phone}
          </a>
          <CalculatorButton size="md" className="whitespace-nowrap">
            Рассчитать стоимость
          </CalculatorButton>
        </div>

        <div className="flex items-center gap-3 xl:hidden">
          <a
            href={siteConfig.phoneHref}
            onClick={() => trackGoal("phone_click")}
            aria-label="Позвонить"
            className="flex h-11 w-11 items-center justify-center rounded-full border border-border text-brand"
          >
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
            <Link
              href="/"
              className={cn(
                "rounded-xl px-4 py-3.5 text-lg font-semibold text-text hover:bg-bg",
                pathname === "/" && "bg-bg text-brand",
              )}
            >
              Главная
            </Link>

            <button
              type="button"
              aria-expanded={isMobileInstallOpen}
              onClick={() => setIsMobileInstallOpen((v) => !v)}
              className={cn(
                "flex items-center justify-between rounded-xl px-4 py-3.5 text-left text-lg font-semibold text-text hover:bg-bg",
                isInstallActive && "text-brand",
              )}
            >
              Установка ворот
              <ChevronIcon open={isMobileInstallOpen} />
            </button>
            <div
              className={cn(
                "grid overflow-hidden transition-all duration-200",
                isMobileInstallOpen ? "grid-rows-[1fr] pb-2" : "grid-rows-[0fr]",
              )}
            >
              <div className="flex flex-col gap-1 overflow-hidden pl-4">
                {installationDropdown.map((item) => (
                  <Link
                    key={item.href}
                    href={item.href}
                    className={cn(
                      "rounded-xl px-4 py-3 text-base hover:bg-bg hover:text-brand",
                      item.primary ? "font-bold text-text" : "font-medium text-text-muted",
                      pathname === item.href && "text-brand",
                    )}
                  >
                    {item.label}
                  </Link>
                ))}
              </div>
            </div>

            {headerNav.slice(1).map((item) => (
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

            <Link
              href="/o-kompanii"
              className={cn(
                "rounded-xl px-4 py-3.5 text-lg font-semibold text-text hover:bg-bg",
                pathname === "/o-kompanii" && "bg-bg text-brand",
              )}
            >
              О компании
            </Link>
          </nav>
          <div className="border-t border-border px-4 py-6">
            <p className="mb-4 text-lg font-bold text-text">{siteConfig.phone}</p>
            <CalculatorButton size="lg" className="w-full">
              Рассчитать стоимость
            </CalculatorButton>
          </div>
        </div>
      ) : null}
    </header>
  );
}
