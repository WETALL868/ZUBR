"use client";

import Image from "next/image";
import Link from "next/link";
import { siteConfig } from "@/config/site";
import { footerCompanyNav, gateTypesNav, footerLegalNav } from "@/data/nav";
import { Container } from "@/components/ui/Container";
import { trackGoal } from "@/lib/analytics";

export function Footer() {
  const year = new Date().getFullYear();

  return (
    <footer className="section-dark relative overflow-hidden border-t border-border bg-bg text-text">
      <div className="absolute inset-0" aria-hidden="true">
        <Image
          src="/images/footer-jobsite.svg"
          alt=""
          fill
          loading="lazy"
          sizes="100vw"
          className="object-cover opacity-60"
        />
        <div className="absolute inset-0 bg-gradient-to-b from-bg/65 via-bg/80 to-bg/95" />
      </div>

      <Container className="relative py-12 sm:py-16">
        <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <span className="text-xl font-extrabold tracking-tight text-text">{siteConfig.companyShortName}</span>
            <p className="mt-3 max-w-xs text-sm text-text-muted">
              Замеряем, изготавливаем и монтируем откатные ворота в {siteConfig.cityAndRegion} — сами, на
              объекте, с гарантией на выполненные работы. Также ставим секционные и распашные ворота и чиним
              автоматику.
            </p>
            <div className="mt-5 flex gap-3">
              <a
                href={siteConfig.social.telegram}
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Telegram"
                className="flex h-10 w-10 items-center justify-center rounded-full border border-border text-text-muted hover:border-accent hover:text-accent"
              >
                <TelegramIcon />
              </a>
              <a
                href={siteConfig.social.whatsapp}
                target="_blank"
                rel="noopener noreferrer"
                aria-label="WhatsApp"
                className="flex h-10 w-10 items-center justify-center rounded-full border border-border text-text-muted hover:border-accent hover:text-accent"
              >
                <WhatsAppIcon />
              </a>
              <a
                href={siteConfig.social.vk}
                target="_blank"
                rel="noopener noreferrer"
                aria-label="ВКонтакте"
                className="flex h-10 w-10 items-center justify-center rounded-full border border-border text-text-muted hover:border-accent hover:text-accent"
              >
                <VkIcon />
              </a>
            </div>
          </div>

          <nav aria-label="Разделы сайта">
            <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-text-muted">Компания</h3>
            <ul className="space-y-2.5 text-sm">
              {footerCompanyNav.map((item) => (
                <li key={item.href}>
                  <Link href={item.href} className="text-text hover:text-accent">
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </nav>

          <nav aria-label="Виды ворот">
            <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-text-muted">Виды ворот</h3>
            <ul className="space-y-2.5 text-sm">
              {gateTypesNav.map((item) => (
                <li key={item.href}>
                  <Link href={item.href} className="text-text hover:text-accent">
                    {item.label}
                  </Link>
                </li>
              ))}
              <li>
                <Link href="/remont-vorot" className="text-text hover:text-accent">
                  Ремонт ворот
                </Link>
              </li>
            </ul>
          </nav>

          <div>
            <h3 className="mb-4 text-sm font-semibold uppercase tracking-wide text-text-muted">Контакты</h3>
            <ul className="space-y-2.5 text-sm text-text">
              <li>
                <a href={siteConfig.phoneHref} onClick={() => trackGoal("phone_click")} className="hover:text-accent">
                  {siteConfig.phone}
                </a>
              </li>
              <li>
                <a href={`mailto:${siteConfig.email}`} className="hover:text-accent">
                  {siteConfig.email}
                </a>
              </li>
              <li className="text-text-muted">{siteConfig.workingHours}</li>
              <li className="text-text-muted">{siteConfig.address}</li>
            </ul>
          </div>
        </div>

        <div className="mt-12 border-t border-border pt-8">
          <div className="flex flex-col gap-4 text-xs leading-relaxed text-text-muted sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p>{siteConfig.legalName}</p>
              <p>ИНН {siteConfig.requisites.inn}, ОГРНИП {siteConfig.requisites.ogrnip}</p>
              <p className="mt-2">© {year} {siteConfig.companyName}. Все права защищены.</p>
            </div>
            <nav aria-label="Правовая информация" className="flex flex-col gap-2 sm:items-end">
              {footerLegalNav.map((item) => (
                <Link key={item.href} href={item.href} className="hover:text-accent">
                  {item.label}
                </Link>
              ))}
            </nav>
          </div>
        </div>
      </Container>
    </footer>
  );
}

function TelegramIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path
        d="M21 4L2.5 11.5c-1 .4-1 1.7.1 2l4.6 1.5 1.8 5.5c.3.9 1.4 1.1 2 .4l2.6-2.9 4.9 3.6c.8.6 2 .2 2.2-.8L23 5.3c.3-1-.8-1.7-2-1.3z"
        stroke="currentColor"
        strokeWidth="1.5"
        strokeLinejoin="round"
      />
      <path d="M8.5 15l9-8.5-11 6.8" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" />
    </svg>
  );
}

function WhatsAppIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path
        d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2Z"
        stroke="currentColor"
        strokeWidth="1.5"
      />
      <path
        d="M8.5 8.3c.2-.5.5-.5.8-.5h.6c.2 0 .5 0 .7.5s.8 2 .9 2.1c.1.2.1.4 0 .6-.2.3-.3.5-.5.7s-.4.4-.2.8c.2.4.9 1.5 2 2.4 1.3 1.1 2.1 1.4 2.4 1.5.3.1.5.1.7-.1.2-.2.8-.9 1-1.2.2-.3.4-.3.7-.2s1.9.9 2.2 1c.3.2.5.2.6.4.1.2.1 1-.3 1.9-.4.9-2 1.7-2.8 1.8-.7.1-1.6.2-5.1-1.2-3.6-1.6-5.9-5.3-6.1-5.6-.2-.3-1.5-2-1.5-3.8 0-1.8 1-2.7 1.3-3z"
        fill="currentColor"
      />
    </svg>
  );
}

function VkIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path
        d="M13.2 17.2c-4.9 0-7.7-3.4-7.8-9h2.6c.1 4 1.8 5.7 3.2 6V8.2h2.5v3.6c1.3-.1 2.7-1.7 3.2-3.6h2.5c-.4 2.3-2 3.9-3.2 4.6 1.2.5 3 2 3.7 4.4h-2.7c-.5-1.8-1.9-3.2-3.5-3.4v3.4h-.3z"
        fill="currentColor"
      />
    </svg>
  );
}
