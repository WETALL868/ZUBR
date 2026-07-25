"use client";

import { useId, useState, type FormEvent } from "react";
import Link from "next/link";
import { gateTypes } from "@/data/gate-types";
import { fillOptions } from "@/data/fill-options";
import { siteConfig } from "@/config/site";
import { Button } from "@/components/ui/Button";
import { Input, Checkbox } from "@/components/ui/Field";
import { PhoneInput } from "@/components/ui/PhoneInput";
import { cn, isValidRuPhone } from "@/lib/utils";
import { useLeadSubmit } from "@/hooks/useLeadSubmit";
import { trackGoal } from "@/lib/analytics";

const widthOptions = ["до 3 м", "3–4 м", "4–5 м", "5–6 м", "более 6 м"];
const heightOptions = ["до 1,5 м", "1,5–2 м", "2–2,5 м", "более 2,5 м"];
const automationOptions = ["Нужна", "Не нужна", "Пока не уверен(а)"];
const fillOptionLabels = [...fillOptions.map((f) => f.title), "Ещё не решил(а)"];

const quizGateTypes = gateTypes.filter((g) => g.isLandingPage);

const TOTAL_STEPS = 7;

interface OptionGridProps {
  options: string[];
  value: string;
  onChange: (value: string) => void;
  columns?: string;
}

function OptionGrid({ options, value, onChange, columns = "sm:grid-cols-2" }: OptionGridProps) {
  return (
    <div className={cn("grid grid-cols-1 gap-3", columns)}>
      {options.map((option) => (
        <button
          key={option}
          type="button"
          onClick={() => onChange(option)}
          aria-pressed={value === option}
          className={cn(
            "rounded-xl border-2 px-4 py-3.5 text-left text-[15px] font-medium transition-colors",
            value === option
              ? "border-brand bg-brand/5 text-brand"
              : "border-border text-text hover:border-brand/50",
          )}
        >
          {option}
        </button>
      ))}
    </div>
  );
}

interface QuizProps {
  onDone?: () => void;
  initialGateType?: string;
}

export function Quiz({ onDone, initialGateType }: QuizProps) {
  const formId = useId();
  const { status, errorMessage, submit, isSubmitting } = useLeadSubmit();
  const [step, setStep] = useState(0);

  const [gateType, setGateType] = useState(initialGateType ?? "");
  const [width, setWidth] = useState("");
  const [height, setHeight] = useState("");
  const [automation, setAutomation] = useState("");
  const [fillType, setFillType] = useState("");
  const [city, setCity] = useState("");
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [consent, setConsent] = useState(false);
  const [phoneError, setPhoneError] = useState("");
  const [consentError, setConsentError] = useState("");

  const stepValid: Record<number, boolean> = {
    0: !!gateType,
    1: !!width,
    2: !!height,
    3: !!automation,
    4: !!fillType,
    5: !!city.trim(),
    6: true,
  };

  function goNext() {
    if (!stepValid[step]) return;
    if (step === 0) trackGoal("calculator_open");
    if (step < TOTAL_STEPS - 1) setStep((s) => s + 1);
  }

  function goBack() {
    if (step > 0) setStep((s) => s - 1);
  }

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
    trackGoal("quiz_complete");
    void submit(formData).then(() => onDone?.());
  }

  const progress = ((step + 1) / TOTAL_STEPS) * 100;

  return (
    <div>
      <div className="mb-6">
        <div className="mb-2 flex items-center justify-between text-sm font-medium text-text-muted">
          <span>
            Шаг {step + 1} из {TOTAL_STEPS}
          </span>
          <span>{Math.round(progress)}%</span>
        </div>
        <div className="h-1.5 w-full overflow-hidden rounded-full bg-border">
          <div
            className="h-full rounded-full bg-accent transition-[width] duration-300"
            style={{ width: `${progress}%` }}
          />
        </div>
      </div>

      <form onSubmit={handleSubmit}>
        <input type="hidden" name="source" value="quiz" />
        <input type="hidden" name="gateType" value={gateType} />
        <input type="hidden" name="width" value={width} />
        <input type="hidden" name="height" value={height} />
        <input type="hidden" name="automationNeeded" value={automation} />
        <input type="hidden" name="fillType" value={fillType} />
        <input type="hidden" name="city" value={city} />
        <input
          type="text"
          name="company"
          tabIndex={-1}
          autoComplete="off"
          className="absolute h-0 w-0 opacity-0"
          aria-hidden="true"
        />

        {step === 0 ? (
          <fieldset>
            <legend className="mb-4 text-lg font-bold text-text">Какой тип ворот вам нужен?</legend>
            <OptionGrid options={quizGateTypes.map((g) => g.title)} value={gateType} onChange={setGateType} />
          </fieldset>
        ) : null}

        {step === 1 ? (
          <fieldset>
            <legend className="mb-4 text-lg font-bold text-text">Примерная ширина проёма?</legend>
            <OptionGrid options={widthOptions} value={width} onChange={setWidth} />
          </fieldset>
        ) : null}

        {step === 2 ? (
          <fieldset>
            <legend className="mb-4 text-lg font-bold text-text">Примерная высота проёма?</legend>
            <OptionGrid options={heightOptions} value={height} onChange={setHeight} />
          </fieldset>
        ) : null}

        {step === 3 ? (
          <fieldset>
            <legend className="mb-4 text-lg font-bold text-text">Нужна ли автоматика?</legend>
            <OptionGrid options={automationOptions} value={automation} onChange={setAutomation} columns="" />
          </fieldset>
        ) : null}

        {step === 4 ? (
          <fieldset>
            <legend className="mb-4 text-lg font-bold text-text">Заполнение или внешний вид</legend>
            <OptionGrid options={fillOptionLabels} value={fillType} onChange={setFillType} />
          </fieldset>
        ) : null}

        {step === 5 ? (
          <fieldset>
            <legend className="mb-4 text-lg font-bold text-text">Город или район объекта</legend>
            <Input
              id={`${formId}-city`}
              label="Город или район"
              placeholder={siteConfig.cityAndRegion}
              value={city}
              onChange={(e) => setCity(e.target.value)}
              autoFocus
            />
          </fieldset>
        ) : null}

        {step === 6 ? (
          <fieldset>
            <legend className="mb-1 text-lg font-bold text-text">Остался последний шаг</legend>
            <p className="mb-4 text-sm text-text-muted">
              Оставьте номер телефона. Специалист уточнит детали и подготовит предварительный расчёт.
            </p>
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
          </fieldset>
        ) : null}

        <div className="mt-6 flex items-center justify-between gap-3">
          {step > 0 ? (
            <Button type="button" variant="secondary" onClick={goBack}>
              Назад
            </Button>
          ) : (
            <span />
          )}

          {step < TOTAL_STEPS - 1 ? (
            <Button type="button" onClick={goNext} disabled={!stepValid[step]}>
              Далее
            </Button>
          ) : (
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Отправляем…" : "Получить расчёт"}
            </Button>
          )}
        </div>

        <p className="mt-3 text-xs text-text-muted">
          {status === "success" ? "Заявка отправлена, переходим на страницу благодарности…" : null}
        </p>
      </form>
    </div>
  );
}
