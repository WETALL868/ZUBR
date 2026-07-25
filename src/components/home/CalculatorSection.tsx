import { Section, SectionHeading } from "@/components/ui/Section";
import { Card } from "@/components/ui/Card";
import { Quiz } from "@/components/lead/Quiz";

export function CalculatorSection() {
  return (
    <Section id="calculator" surface="surface" className="scroll-mt-24">
      <SectionHeading
        eyebrow="Расчёт стоимости"
        title="Предварительный расчёт за пару минут"
        description="Ответьте на несколько вопросов о воротах — специалист уточнит детали и подготовит расчёт."
        align="center"
        className="mx-auto"
      />
      <Card className="mx-auto mt-10 max-w-2xl p-6 sm:p-10">
        <Quiz />
      </Card>
    </Section>
  );
}
