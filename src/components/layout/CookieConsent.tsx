"use client";

import { useState, useSyncExternalStore } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/Button";

const STORAGE_KEY = "cookie-consent-accepted";

function subscribe() {
  // Значение меняется только по клику пользователя в этом же компоненте,
  // внешних событий для подписки не требуется.
  return () => {};
}

function getSnapshot() {
  return window.localStorage.getItem(STORAGE_KEY) === "1";
}

function getServerSnapshot() {
  // На сервере и при гидратации считаем согласие уже полученным,
  // чтобы избежать расхождения разметки — сразу после гидратации React
  // сверится с реальным значением из localStorage.
  return true;
}

export function CookieConsent() {
  const alreadyAccepted = useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot);
  const [dismissed, setDismissed] = useState(false);
  const isVisible = !alreadyAccepted && !dismissed;

  function accept() {
    window.localStorage.setItem(STORAGE_KEY, "1");
    setDismissed(true);
  }

  if (!isVisible) return null;

  return (
    <div className="fixed inset-x-0 bottom-0 z-50 border-t border-border bg-surface px-4 py-4 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] sm:px-6">
      <div className="mx-auto flex max-w-5xl flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-sm text-text-muted">
          Мы используем файлы cookie для корректной работы сайта и аналитики. Продолжая пользоваться сайтом, вы
          соглашаетесь с{" "}
          <Link href="/politika-konfidentsialnosti" className="underline hover:text-brand">
            политикой конфиденциальности
          </Link>
          .
        </p>
        <div className="flex shrink-0 gap-3">
          <Button size="md" onClick={accept}>
            Понятно
          </Button>
        </div>
      </div>
    </div>
  );
}
