import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { faqRemont } from "@/data/faq";
import { buildMetadata } from "@/lib/seo";
import { buildServiceSchema } from "@/lib/structured-data";
import { JsonLd } from "@/components/seo/JsonLd";
import { Breadcrumbs } from "@/components/seo/Breadcrumbs";
import { Container } from "@/components/ui/Container";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Card } from "@/components/ui/Card";
import { PageHero } from "@/components/shared/PageHero";
import { FaqSection } from "@/components/shared/FaqSection";
import { RepairForm } from "@/components/lead/RepairForm";

const title = "Ремонт автоматических и механических ворот";
const description =
  "Диагностика и ремонт откатных, распашных и секционных ворот: привод, автоматика, ролики, направляющие и комплектующие.";

export const metadata: Metadata = buildMetadata({ title, description, path: "/remont-vorot" });

const gateTypesWeFix = ["Откатные ворота", "Распашные ворота", "Секционные ворота", "Гаражные ворота"];

const commonIssues = [
  "Ворота не реагируют на пульт",
  "Привод гудит, но створка не двигается",
  "Ворота останавливаются на середине пути",
  "Появился перекос или заедание при движении",
  "Ворота не закрываются до конца",
  "Автоматика срабатывает с задержкой или сбоями",
];

const repairAreas = [
  {
    title: "Диагностика",
    description: "Определяем причину неисправности: механика, электрика или настройки автоматики.",
  },
  {
    title: "Ремонт автоматики",
    description: "Замена платы управления, двигателя, концевых выключателей, настройка параметров.",
  },
  {
    title: "Ремонт механики",
    description: "Устранение перекоса, замена роликов, направляющих и других изнашиваемых узлов.",
  },
  {
    title: "Замена комплектующих",
    description: "Пульты, фотоэлементы, сигнальные лампы и другие элементы автоматики.",
  },
];

const visitSteps = [
  "Оставляете заявку с описанием неисправности и фото (если есть)",
  "Уточняем детали по телефону и согласовываем время выезда",
  "Мастер выезжает на объект, проводит диагностику и озвучивает варианты ремонта",
  "Выполняем ремонт и проверяем работу ворот перед сдачей",
];

export default function RemontVorotPage() {
  return (
    <>
      <JsonLd data={buildServiceSchema({ name: title, description, url: `${siteConfig.seo.siteUrl}/remont-vorot` })} />
      <Container>
        <Breadcrumbs items={[{ name: "Ремонт ворот", href: "/remont-vorot" }]} />
      </Container>

      <PageHero
        eyebrow="Дополнительная услуга"
        title="Ремонт автоматических и механических ворот"
        description="Диагностика и ремонт ворот любых производителей — в том числе тех, что устанавливали не мы."
        image="/images/hero-repair.svg"
        imageAlt="Диагностика привода ворот"
        primaryLabel="Вызвать мастера"
      />

      <Section surface="surface">
        <SectionHeading eyebrow="Что ремонтируем" title="Какие ворота обслуживаем" />
        <ul className="mt-8 flex flex-wrap gap-3">
          {gateTypesWeFix.map((item) => (
            <li key={item} className="rounded-full border border-border bg-bg px-4 py-2 text-sm font-medium text-text">
              {item}
            </li>
          ))}
        </ul>
      </Section>

      <Section>
        <SectionHeading eyebrow="Диагностика" title="Типичные неисправности" />
        <ul className="mt-8 grid gap-3 sm:grid-cols-2">
          {commonIssues.map((issue) => (
            <li key={issue} className="flex items-start gap-3 text-[15px] text-text">
              <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-accent" aria-hidden="true" />
              {issue}
            </li>
          ))}
        </ul>
        {siteConfig.emergencyOpeningAvailable ? (
          <p className="mt-6 rounded-xl border border-accent/30 bg-accent/5 p-4 text-sm text-text">
            Если ворота заклинило в открытом или закрытом положении, сообщите об этом при обращении — доступно
            аварийное открытие.
          </p>
        ) : null}
      </Section>

      <Section surface="surface">
        <SectionHeading eyebrow="Что делаем" title="Направления ремонта" />
        <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {repairAreas.map((item) => (
            <Card key={item.title} className="p-6">
              <h3 className="font-bold text-text">{item.title}</h3>
              <p className="mt-2 text-sm text-text-muted">{item.description}</p>
            </Card>
          ))}
        </div>
      </Section>

      <Section>
        <SectionHeading eyebrow="Порядок работы" title="Как проходит выезд мастера" />
        <ol className="mt-8 space-y-4">
          {visitSteps.map((step, index) => (
            <li key={step} className="flex items-start gap-4">
              <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-bold text-white">
                {index + 1}
              </span>
              <p className="pt-1 text-[15px] text-text">{step}</p>
            </li>
          ))}
        </ol>
      </Section>

      <FaqSection items={faqRemont} title="Вопросы о ремонте ворот" />

      <Section id="raschet" surface="surface" className="scroll-mt-24">
        <Card className="mx-auto max-w-2xl p-6 sm:p-10">
          <h2 className="text-center text-2xl font-bold text-text sm:text-3xl">Вызвать мастера</h2>
          <p className="mt-3 text-center text-text-muted">
            Опишите неисправность и оставьте контакты — уточним детали и согласуем время выезда.
          </p>
          <RepairForm />
        </Card>
      </Section>
    </>
  );
}
