"use client";

import { useId, useState, type FormEvent } from "react";
import { gateTypes } from "@/data/gate-types";
import { Button } from "@/components/ui/Button";
import { Input, Textarea, Select, Checkbox } from "@/components/ui/Field";
import { PhoneInput } from "@/components/ui/PhoneInput";
import { isValidRuPhone } from "@/lib/utils";
import { useLeadSubmit } from "@/hooks/useLeadSubmit";
import Link from "next/link";

interface LeadFormProps {
  source: "measurement" | "contact" | "final-cta";
  submitLabel: string;
  showGateType?: boolean;
  showComment?: boolean;
  commentLabel?: string;
  commentPlaceholder?: string;
  defaultGateType?: string;
  className?: string;
}

export function LeadForm({
  source,
  submitLabel,
  showGateType = true,
  showComment = true,
  commentLabel = "Комментарий",
  commentPlaceholder = "Расскажите о задаче: тип ворот, проём, сроки",
  defaultGateType = "",
  className,
}: LeadFormProps) {
  const formId = useId();
  const { status, errorMessage, submit, isSubmitting } = useLeadSubmit();

  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [gateType, setGateType] = useState(defaultGateType);
  const [comment, setComment] = useState("");
  const [consent, setConsent] = useState(false);
  const [phoneError, setPhoneError] = useState("");
  const [consentError, setConsentError] = useState("");

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();

    let hasError = false;
    if (!isValidRuPhone(phone)) {
      setPhoneError("Введите корректный номер телефона");
      hasError = true;
    } else {
      setPhoneError("");
    }
    if (!consent) {
      setConsentError("Необходимо согласие на обработку персональных данных");
      hasError = true;
    } else {
      setConsentError("");
    }
    if (hasError) return;

    const formData = new FormData(e.currentTarget);
    formData.set("phone", phone);
    formData.set("consent", consent ? "true" : "false");
    void submit(formData);
  }

  return (
    <form onSubmit={handleSubmit} className={className} noValidate>
      <input type="hidden" name="source" value={source} />
      <input
        type="text"
        name="company"
        tabIndex={-1}
        autoComplete="off"
        className="absolute h-0 w-0 opacity-0"
        aria-hidden="true"
      />

      <div className="grid gap-4 sm:grid-cols-2">
        <Input
          id={`${formId}-name`}
          name="name"
          label="Имя"
          placeholder="Как к вам обращаться"
          required
          value={name}
          onChange={(e) => setName(e.target.value)}
        />
        <PhoneInput
          id={`${formId}-phone`}
          value={phone}
          onChange={setPhone}
          error={phoneError}
          required
        />
      </div>

      {showGateType ? (
        <Select
          id={`${formId}-gate-type`}
          name="gateType"
          label="Тип ворот"
          className="mt-4"
          value={gateType}
          onChange={(e) => setGateType(e.target.value)}
        >
          <option value="">Не выбрано</option>
          {gateTypes
            .filter((g) => g.isLandingPage)
            .map((g) => (
              <option key={g.slug} value={g.title}>
                {g.title}
              </option>
            ))}
          <option value="Не знаю, нужна консультация">Не знаю, нужна консультация</option>
        </Select>
      ) : null}

      {showComment ? (
        <Textarea
          id={`${formId}-comment`}
          name="comment"
          label={commentLabel}
          placeholder={commentPlaceholder}
          className="mt-4"
          value={comment}
          onChange={(e) => setComment(e.target.value)}
        />
      ) : null}

      <Checkbox
        id={`${formId}-consent`}
        name="consent"
        className="mt-4"
        checked={consent}
        onChange={(e) => setConsent(e.target.checked)}
        error={consentError}
        label={
          <>
            Согласен(на) на{" "}
            <Link href="/soglasie-na-obrabotku-personalnyh-dannyh" className="underline hover:text-brand">
              обработку персональных данных
            </Link>{" "}
            в соответствии с{" "}
            <Link href="/politika-konfidentsialnosti" className="underline hover:text-brand">
              политикой конфиденциальности
            </Link>
          </>
        }
      />

      {errorMessage ? (
        <p role="alert" className="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
          {errorMessage}
        </p>
      ) : null}

      <Button type="submit" size="lg" className="mt-5 w-full sm:w-auto" disabled={isSubmitting}>
        {isSubmitting ? "Отправляем…" : submitLabel}
      </Button>

      <p className="mt-3 text-xs text-text-muted">
        {status === "success" ? "Заявка отправлена, переходим на страницу благодарности…" : null}
      </p>
    </form>
  );
}
