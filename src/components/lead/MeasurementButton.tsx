"use client";

import type { ButtonHTMLAttributes } from "react";
import { buttonClasses, type ButtonSize, type ButtonVariant } from "@/components/ui/Button";
import { useLeadModal } from "./LeadModalProvider";

interface MeasurementButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
}

export function MeasurementButton({
  variant = "secondary",
  size = "md",
  className,
  children,
  ...props
}: MeasurementButtonProps) {
  const { openMeasurement } = useLeadModal();

  return (
    <button
      type="button"
      className={buttonClasses(variant, size, className)}
      onClick={openMeasurement}
      {...props}
    >
      {children ?? "Вызвать замерщика"}
    </button>
  );
}
