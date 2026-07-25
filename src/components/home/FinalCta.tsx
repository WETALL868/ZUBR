import { Section } from "@/components/ui/Section";
import { LeadForm } from "@/components/lead/LeadForm";

export function FinalCta() {
  return (
    <Section id="final-cta" surface="surface" className="scroll-mt-24">
      <div className="mx-auto max-w-2xl rounded-3xl border border-border bg-bg p-8 sm:p-12">
        <h2 className="text-center text-3xl font-bold tracking-tight text-text sm:text-4xl">
          Рассчитаем стоимость ворот для вашего объекта
        </h2>
        <p className="mt-4 text-center text-text-muted">
          Расскажите, какие ворота вам нужны. Специалист уточнит размеры, предложит подходящую конструкцию и
          подготовит предварительный расчёт.
        </p>
        <LeadForm source="final-cta" submitLabel="Получить расчёт" className="mt-8" />
      </div>
    </Section>
  );
}
