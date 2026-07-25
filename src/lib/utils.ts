import clsx, { type ClassValue } from "clsx";

export function cn(...inputs: ClassValue[]) {
  return clsx(...inputs);
}

export function formatPhoneDigits(value: string): string {
  return value.replace(/\D/g, "");
}

/**
 * Форматирует ввод в маску российского телефона: +7 (___) ___-__-__
 */
export function formatRuPhone(value: string): string {
  let digits = formatPhoneDigits(value);

  if (digits.startsWith("8")) digits = "7" + digits.slice(1);
  if (!digits.startsWith("7")) digits = "7" + digits;
  digits = digits.slice(0, 11);

  const rest = digits.slice(1);
  let result = "+7";
  if (rest.length > 0) result += ` (${rest.slice(0, 3)}`;
  if (rest.length >= 3) result += `) ${rest.slice(3, 6)}`;
  if (rest.length >= 6) result += `-${rest.slice(6, 8)}`;
  if (rest.length >= 8) result += `-${rest.slice(8, 10)}`;

  return result;
}

export function isValidRuPhone(value: string): boolean {
  const digits = formatPhoneDigits(value);
  return digits.length === 11 && (digits.startsWith("7") || digits.startsWith("8"));
}

export function toTelHref(value: string): string {
  const digits = formatPhoneDigits(value);
  const normalized = digits.startsWith("8") ? "7" + digits.slice(1) : digits;
  return `+${normalized}`;
}
