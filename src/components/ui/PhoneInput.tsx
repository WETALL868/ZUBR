"use client";

import { forwardRef } from "react";
import { formatRuPhone } from "@/lib/utils";
import { FieldWrapper } from "./Field";

interface PhoneInputProps {
  id?: string;
  name?: string;
  label?: string;
  error?: string;
  required?: boolean;
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
}

export const PhoneInput = forwardRef<HTMLInputElement, PhoneInputProps>(function PhoneInput(
  { id = "phone", name = "phone", label = "Телефон", error, required, value, onChange, placeholder = "+7 (___) ___-__-__" },
  ref,
) {
  return (
    <FieldWrapper label={label} htmlFor={id} error={error} required={required}>
      <input
        ref={ref}
        id={id}
        name={name}
        type="tel"
        inputMode="tel"
        autoComplete="tel"
        required={required}
        aria-invalid={!!error}
        placeholder={placeholder}
        value={value}
        onChange={(e) => onChange(formatRuPhone(e.target.value))}
        onFocus={(e) => {
          if (!e.target.value) onChange("+7 (");
        }}
        className={`w-full rounded-xl border bg-white px-4 py-3 text-base text-text placeholder:text-text-muted focus:border-brand outline-none transition-colors ${
          error ? "border-red-400" : "border-border"
        }`}
      />
    </FieldWrapper>
  );
});
