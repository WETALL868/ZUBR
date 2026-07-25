"use client";

import type { ButtonHTMLAttributes } from "react";
import { buttonClasses, type ButtonSize, type ButtonVariant } from "@/components/ui/Button";
import { useLeadModal } from "./LeadModalProvider";

interface CalculatorButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  gateType?: string;
}

export function CalculatorButton({
  variant = "primary",
  size = "md",
  gateType,
  className,
  children,
  ...props
}: CalculatorButtonProps) {
  const { openCalculator } = useLeadModal();

  return (
    <button
      type="button"
      className={buttonClasses(variant, size, className)}
      onClick={() => openCalculator(gateType)}
      {...props}
    >
      {children ?? "Рассчитать стоимость"}
    </button>
  );
}
