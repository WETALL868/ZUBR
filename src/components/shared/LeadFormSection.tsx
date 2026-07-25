import { Section } from "@/components/ui/Section";
import { Card } from "@/components/ui/Card";
import { LeadForm } from "@/components/lead/LeadForm";

interface LeadFormSectionProps {
  title?: string;
  description?: string;
  defaultGateType?: string;
  submitLabel?: string;
}

export function LeadFormSection({
  title = "Рассчитать стоимость",
  description = "Оставьте заявку — специалист уточнит детали и подготовит предварительный расчёт.",
  defaultGateType,
  submitLabel = "Получить расчёт",
}: LeadFormSectionProps) {
  return (
    <Section id="raschet" surface="surface" className="scroll-mt-24">
      <Card className="mx-auto max-w-2xl p-6 sm:p-10">
        <h2 className="text-center text-2xl font-bold text-text sm:text-3xl">{title}</h2>
        <p className="mt-3 text-center text-text-muted">{description}</p>
        <LeadForm source="contact" submitLabel={submitLabel} defaultGateType={defaultGateType} className="mt-8" />
      </Card>
    </Section>
  );
}
