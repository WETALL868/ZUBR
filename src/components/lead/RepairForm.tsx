"use client";

import { useId, useRef, useState, type FormEvent } from "react";
import Link from "next/link";
import { gateTypes } from "@/data/gate-types";
import { Button } from "@/components/ui/Button";
import { Input, Textarea, Select, Checkbox } from "@/components/ui/Field";
import { PhoneInput } from "@/components/ui/PhoneInput";
import { isValidRuPhone } from "@/lib/utils";
import { useLeadSubmit } from "@/hooks/useLeadSubmit";

const MAX_PHOTOS = 5;

export function RepairForm() {
  const formId = useId();
  const { status, errorMessage, submit, isSubmitting } = useLeadSubmit();
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [gateType, setGateType] = useState("");
  const [address, setAddress] = useState("");
  const [issue, setIssue] = useState("");
  const [consent, setConsent] = useState(false);
  const [photoNames, setPhotoNames] = useState<string[]>([]);

  const [phoneError, setPhoneError] = useState("");
  const [consentError, setConsentError] = useState("");
  const [issueError, setIssueError] = useState("");

  function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();

    let hasError = false;
    if (!isValidRuPhone(phone)) {
      setPhoneError("Введите корректный номер телефона");
      hasError = true;
    } else {
      setPhoneError("");
    }
    if (issue.trim().length < 5) {
      setIssueError("Опишите неисправность подробнее");
      hasError = true;
    } else {
      setIssueError("");
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
    <form onSubmit={handleSubmit} noValidate>
      <input type="hidden" name="source" value="repair" />
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
          required
          value={name}
          onChange={(e) => setName(e.target.value)}
        />
        <PhoneInput id={`${formId}-phone`} value={phone} onChange={setPhone} error={phoneError} required />
      </div>

      <Select
        id={`${formId}-gate-type`}
        name="gateType"
        label="Тип ворот"
        required
        className="mt-4"
        value={gateType}
        onChange={(e) => setGateType(e.target.value)}
      >
        <option value="">Выберите тип ворот</option>
        {gateTypes
          .filter((g) => g.isLandingPage)
          .map((g) => (
            <option key={g.slug} value={g.title}>
              {g.title}
            </option>
          ))}
        <option value="Другое">Другое / не знаю</option>
      </Select>

      <Input
        id={`${formId}-address`}
        name="address"
        label="Адрес объекта"
        placeholder="Город, улица, дом"
        required
        className="mt-4"
        value={address}
        onChange={(e) => setAddress(e.target.value)}
      />

      <Textarea
        id={`${formId}-issue`}
        name="issue"
        label="Опишите неисправность"
        placeholder="Например: не работает пульт, ворота останавливаются на середине пути"
        required
        error={issueError}
        className="mt-4"
        value={issue}
        onChange={(e) => setIssue(e.target.value)}
      />

      <div className="mt-4">
        <label htmlFor={`${formId}-photos`} className="mb-1.5 block text-sm font-semibold text-text">
          Фотографии неисправности (необязательно)
        </label>
        <input
          ref={fileInputRef}
          id={`${formId}-photos`}
          name="photos"
          type="file"
          accept="image/*"
          multiple
          onChange={(e) => {
            const files = Array.from(e.target.files ?? []).slice(0, MAX_PHOTOS);
            setPhotoNames(files.map((f) => f.name));
          }}
          className="w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-text-muted file:mr-4 file:rounded-full file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white"
        />
        {photoNames.length > 0 ? (
          <p className="mt-1.5 text-sm text-text-muted">Выбрано файлов: {photoNames.length}</p>
        ) : null}
        <p className="mt-1 text-xs text-text-muted">Можно приложить до {MAX_PHOTOS} фотографий.</p>
      </div>

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
        {isSubmitting ? "Отправляем…" : "Вызвать мастера"}
      </Button>

      <p className="mt-3 text-xs text-text-muted">
        {status === "success" ? "Заявка отправлена, переходим на страницу благодарности…" : null}
      </p>
    </form>
  );
}
