"use client";

import { siteConfig } from "@/config/site";

export type AnalyticsGoal =
  | "lead_submit"
  | "quiz_complete"
  | "phone_click"
  | "messenger_click"
  | "calculator_open";

declare global {
  interface Window {
    ym?: (id: number, method: string, target: string, params?: Record<string, unknown>) => void;
    gtag?: (...args: unknown[]) => void;
    dataLayer?: unknown[];
  }
}

/**
 * Отправляет цель в Яндекс.Метрику и Google Analytics, если они подключены.
 * При отсутствии идентификаторов аналитики не выполняет никаких действий и не выбрасывает ошибок.
 */
export function trackGoal(goal: AnalyticsGoal, params?: Record<string, unknown>) {
  if (typeof window === "undefined") return;

  try {
    const metrikaId = Number(siteConfig.analytics.yandexMetrikaId);
    if (metrikaId && window.ym) {
      window.ym(metrikaId, "reachGoal", goal, params);
    }
    if (siteConfig.analytics.googleAnalyticsId && window.gtag) {
      window.gtag("event", goal, params);
    }
  } catch {
    // Аналитика не должна ломать пользовательский сценарий.
  }
}
