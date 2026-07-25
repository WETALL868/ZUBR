import type { Metadata } from "next";
import Image from "next/image";
import { siteConfig } from "@/config/site";
import { faqOtkatnye } from "@/data/faq";
import { fillOptions } from "@/data/fill-options";
import { automationByGateType } from "@/data/automation-features";
import { buildMetadata } from "@/lib/seo";
import { buildServiceSchema } from "@/lib/structured-data";
import { JsonLd } from "@/components/seo/JsonLd";
import { Breadcrumbs } from "@/components/seo/Breadcrumbs";
import { Container } from "@/components/ui/Container";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Card } from "@/components/ui/Card";
import { Reveal } from "@/components/ui/Reveal";
import { PageHero } from "@/components/shared/PageHero";
import { PriceNote } from "@/components/shared/PriceNote";
import { FaqSection } from "@/components/shared/FaqSection";
import { LeadFormSection } from "@/components/shared/LeadFormSection";
import { CalculatorButton } from "@/components/lead/CalculatorButton";
import { ButtonLink } from "@/components/ui/Button";
import { PortfolioGallery } from "@/components/portfolio/PortfolioGallery";

const title = `Откатные ворота под ключ в ${siteConfig.cityAndRegion}`;
const description =
  "Изготовление, монтаж и автоматизация откатных ворот для частного дома, гаража и коммерческого объекта. Замер, фундамент под направляющую и настройка автоматики.";

export const metadata: Metadata = buildMetadata({ title, description, path: "/otkatnye-vorota" });

const advantages = [
  { title: "Не занимают место при открывании", description: "Створка уходит вдоль забора — перед проёмом всегда свободно." },
  { title: "Легко автоматизируются", description: "Подходят практически под любой привод для откатных ворот." },
  { title: "Удобны зимой", description: "Не нужно расчищать сектор открывания от снега, как у распашных ворот." },
  { title: "Современно выглядят", description: "Хорошо сочетаются с современной архитектурой фасада и забора." },
  { title: "Подходят для широких въездов", description: "Удобное решение для проёмов от 4 м и шире." },
  { title: "Заполнение под фасад дома", description: "Профнастил, штакетник, комбинированные варианты и цвет по RAL." },
];

const constructions = [
  { title: "На роликовых опорах", description: "Классическая схема с нижней направляющей и роликовой тележкой." },
  { title: "Консольные (без нижней направляющей)", description: "Подходят для участков, где нельзя разместить рельс в грунте." },
  { title: "На рельсовой направляющей", description: "Надёжная схема для тяжёлых створок и интенсивной эксплуатации." },
];

const siteRequirements = [
  "Свободный участок забора сбоку от проёма для отката створки",
  "Ровное и достаточно твёрдое основание под фундамент",
  "Возможность заливки фундамента под направляющую или консольную балку",
  "Электропитание в зоне установки автоматики (при необходимости)",
];

const measurementSteps = [
  "Выезд специалиста на объект в согласованное время",
  "Замер ширины и высоты проёма, проверка грунта и уровня",
  "Согласование конструкции, заполнения и комплектации автоматики",
];

const priceFactors = [
  "Ширина и высота проёма",
  "Тип и толщина заполнения",
  "Необходимость заливки фундамента",
  "Наличие и класс автоматики",
  "Сложность монтажа и удалённость объекта",
];

export default function OtkatnyeVorotaPage() {
  return (
    <>
      <JsonLd data={buildServiceSchema({ name: title, description, url: `${siteConfig.seo.siteUrl}/otkatnye-vorota` })} />
      <Container>
        <Breadcrumbs items={[{ name: "Откатные ворота", href: "/otkatnye-vorota" }]} />
      </Container>

      <PageHero
        eyebrow="Главное направление"
        title="Откатные ворота под ключ"
        description={`Подберём конструкцию, изготовим и установим откатные ворота с автоматикой в ${siteConfig.cityAndRegion}.`}
        image="/images/hero-sliding-gate.svg"
        imageAlt="Откатные ворота у частного дома"
        gateType="Откатные ворота"
        primaryLabel="Рассчитать откатные ворота"
      />

      <Section surface="surface">
        <SectionHeading eyebrow="Преимущества" title="Почему откатные ворота выбирают чаще всего" />
        <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {advantages.map((item, index) => (
            <Reveal key={item.title} delay={index * 60}>
              <div className="h-full rounded-2xl border border-border bg-bg p-6">
                <h3 className="font-bold text-text">{item.title}</h3>
                <p className="mt-2 text-sm text-text-muted">{item.description}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      <Section>
        <SectionHeading eyebrow="Конструкция" title="Виды откатных ворот" />
        <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
          {constructions.map((item) => (
            <Card key={item.title} className="p-6">
              <h3 className="font-bold text-text">{item.title}</h3>
              <p className="mt-2 text-sm text-text-muted">{item.description}</p>
            </Card>
          ))}
        </div>
      </Section>

      <Section surface="surface">
        <SectionHeading eyebrow="Внешний вид" title="Варианты заполнения" />
        <div className="mt-10 grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-6">
          {fillOptions.map((option) => (
            <div key={option.title} className="text-center">
              <div className="relative aspect-square w-full overflow-hidden rounded-2xl">
                <Image src={option.image} alt={option.title} fill loading="lazy" sizes="16vw" className="object-cover" />
              </div>
              <h3 className="mt-3 text-sm font-bold text-text">{option.title}</h3>
            </div>
          ))}
        </div>
      </Section>

      <Section>
        <SectionHeading eyebrow="Автоматика" title={automationByGateType.otkatnye.title} />
        <p className="mt-4 max-w-2xl text-text-muted">{automationByGateType.otkatnye.description}</p>
        <div className="mt-6 flex flex-col gap-3 sm:flex-row">
          <CalculatorButton size="lg" gateType="Откатные ворота">
            Подобрать автоматику
          </CalculatorButton>
          <ButtonLink href="/avtomatika" size="lg" variant="secondary">
            Подробнее об автоматике
          </ButtonLink>
        </div>
      </Section>

      <Section surface="surface">
        <div className="grid gap-10 lg:grid-cols-2">
          <div>
            <SectionHeading eyebrow="Подготовка" title="Требования к участку" />
            <ul className="mt-6 space-y-3">
              {siteRequirements.map((item) => (
                <li key={item} className="flex items-start gap-3 text-[15px] text-text">
                  <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-accent" aria-hidden="true" />
                  {item}
                </li>
              ))}
            </ul>
          </div>
          <div>
            <SectionHeading eyebrow="Перед заказом" title="Как проходит замер" />
            <ol className="mt-6 space-y-3">
              {measurementSteps.map((item, index) => (
                <li key={item} className="flex items-start gap-3 text-[15px] text-text">
                  <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-xs font-bold text-white">
                    {index + 1}
                  </span>
                  {item}
                </li>
              ))}
            </ol>
          </div>
        </div>
      </Section>

      <Section>
        <SectionHeading eyebrow="Портфолио" title="Примеры готовых работ" />
        <div className="mt-8">
          <PortfolioGallery initialFilter="otkatnye" showFilters={false} showViewAllLink />
        </div>
      </Section>

      <Section surface="surface">
        <SectionHeading eyebrow="Стоимость" title="От чего зависит цена откатных ворот" />
        <ul className="mt-6 grid gap-3 sm:grid-cols-2">
          {priceFactors.map((item) => (
            <li key={item} className="flex items-start gap-3 text-[15px] text-text">
              <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-accent" aria-hidden="true" />
              {item}
            </li>
          ))}
        </ul>
        <PriceNote className="mt-6 max-w-2xl text-text-muted" />
      </Section>

      <FaqSection items={faqOtkatnye} title="Вопросы об откатных воротах" />

      <LeadFormSection
        title="Рассчитать откатные ворота"
        defaultGateType="Откатные ворота"
        submitLabel="Получить расчёт"
      />
    </>
  );
}
