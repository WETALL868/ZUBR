import { Section } from "@/components/ui/Section";
import { Reveal } from "@/components/ui/Reveal";
import { Badge } from "@/components/ui/Card";
import { ButtonLink } from "@/components/ui/Button";

const repairServices = [
  "Диагностика",
  "Ремонт привода",
  "Замена роликов и направляющих",
  "Настройка автоматики",
  "Замена пультов и фотоэлементов",
  "Устранение перекоса",
  "Ремонт секционных ворот",
];

export function RepairSection() {
  return (
    <Section id="remont" className="scroll-mt-24">
      <Reveal>
        <div className="rounded-3xl border border-border bg-surface p-8 sm:p-12">
          <Badge>Дополнительная услуга</Badge>
          <h2 className="mt-4 text-2xl font-bold tracking-tight text-text sm:text-3xl">
            Ворота уже установлены, но перестали работать?
          </h2>
          <p className="mt-3 max-w-2xl text-text-muted">
            Проводим диагностику и ремонт автоматики и механики ворот — в том числе тех, что устанавливали не мы.
          </p>
          <ul className="mt-6 flex flex-wrap gap-2.5">
            {repairServices.map((service) => (
              <li key={service} className="rounded-full border border-border px-3.5 py-1.5 text-sm text-text-muted">
                {service}
              </li>
            ))}
          </ul>
          <div className="mt-8 flex flex-col gap-3 sm:flex-row">
            <ButtonLink href="/remont-vorot" size="lg" variant="secondary">
              Вызвать мастера
            </ButtonLink>
            <ButtonLink href="/remont-vorot" size="lg" variant="ghost">
              Подробнее о ремонте
            </ButtonLink>
          </div>
        </div>
      </Reveal>
    </Section>
  );
}
