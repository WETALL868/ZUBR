"use client";

import { useState } from "react";
import { siteConfig } from "@/config/site";
import { cn } from "@/lib/utils";
import { trackGoal } from "@/lib/analytics";

export function FloatingContact() {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <div className="fixed right-4 bottom-24 z-30 flex flex-col items-end gap-3 xl:right-6 xl:bottom-6">
      <div
        className={cn(
          "flex flex-col items-end gap-3 transition-all duration-200",
          isOpen ? "translate-y-0 opacity-100" : "pointer-events-none translate-y-2 opacity-0",
        )}
      >
        <a
          href={siteConfig.social.whatsapp}
          target="_blank"
          rel="noopener noreferrer"
          onClick={() => trackGoal("messenger_click", { channel: "whatsapp" })}
          aria-label="Написать в WhatsApp"
          className="flex h-12 w-12 items-center justify-center rounded-full bg-surface text-brand shadow-lg ring-1 ring-border hover:text-brand-hover"
        >
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2Z" stroke="currentColor" strokeWidth="1.5" />
            <path
              d="M8.5 8.3c.2-.5.5-.5.8-.5h.6c.2 0 .5 0 .7.5s.8 2 .9 2.1c.1.2.1.4 0 .6-.2.3-.3.5-.5.7s-.4.4-.2.8c.2.4.9 1.5 2 2.4 1.3 1.1 2.1 1.4 2.4 1.5.3.1.5.1.7-.1.2-.2.8-.9 1-1.2.2-.3.4-.3.7-.2s1.9.9 2.2 1c.3.2.5.2.6.4.1.2.1 1-.3 1.9-.4.9-2 1.7-2.8 1.8-.7.1-1.6.2-5.1-1.2-3.6-1.6-5.9-5.3-6.1-5.6-.2-.3-1.5-2-1.5-3.8 0-1.8 1-2.7 1.3-3z"
              fill="currentColor"
            />
          </svg>
        </a>
        <a
          href={siteConfig.social.telegram}
          target="_blank"
          rel="noopener noreferrer"
          onClick={() => trackGoal("messenger_click", { channel: "telegram" })}
          aria-label="Написать в Telegram"
          className="flex h-12 w-12 items-center justify-center rounded-full bg-surface text-brand shadow-lg ring-1 ring-border hover:text-brand-hover"
        >
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
              d="M21 4L2.5 11.5c-1 .4-1 1.7.1 2l4.6 1.5 1.8 5.5c.3.9 1.4 1.1 2 .4l2.6-2.9 4.9 3.6c.8.6 2 .2 2.2-.8L23 5.3c.3-1-.8-1.7-2-1.3z"
              stroke="currentColor"
              strokeWidth="1.5"
              strokeLinejoin="round"
            />
            <path d="M8.5 15l9-8.5-11 6.8" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" />
          </svg>
        </a>
        <a
          href={siteConfig.phoneHref}
          onClick={() => trackGoal("phone_click")}
          aria-label="Позвонить"
          className="flex h-12 w-12 items-center justify-center rounded-full bg-surface text-brand shadow-lg ring-1 ring-border hover:text-brand-hover"
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
      </div>

      <button
        type="button"
        onClick={() => setIsOpen((v) => !v)}
        aria-label={isOpen ? "Скрыть контакты" : "Показать контакты"}
        aria-expanded={isOpen}
        className="flex h-14 w-14 items-center justify-center rounded-full bg-brand text-white shadow-xl transition-transform hover:bg-brand-hover"
      >
        <svg
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          aria-hidden="true"
          className={cn("transition-transform duration-200", isOpen && "rotate-45")}
        >
          <path d="M12 5v14M5 12h14" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        </svg>
      </button>
    </div>
  );
}
