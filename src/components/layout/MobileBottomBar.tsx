"use client";

import { siteConfig } from "@/config/site";
import { CalculatorButton } from "@/components/lead/CalculatorButton";
import { trackGoal } from "@/lib/analytics";

export function MobileBottomBar() {
  return (
    <div className="fixed inset-x-0 bottom-0 z-30 flex gap-3 border-t border-border bg-surface/95 px-4 py-3 backdrop-blur-sm xl:hidden [padding-bottom:calc(0.75rem+env(safe-area-inset-bottom))]">
      <a
        href={siteConfig.phoneHref}
        onClick={() => trackGoal("phone_click")}
        className="flex flex-1 items-center justify-center gap-2 rounded-full border-2 border-text/80 py-3 text-[15px] font-semibold text-text"
      >
        Позвонить
      </a>
      <CalculatorButton className="flex-1 justify-center">Рассчитать</CalculatorButton>
    </div>
  );
}
