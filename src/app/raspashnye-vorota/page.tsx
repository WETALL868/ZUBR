import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { faqRaspashnye } from "@/data/faq";
import { fillOptions } from "@/data/fill-options";
import { automationByGateType } from "@/data/automation-features";
import { buildMetadata } from "@/lib/seo";
import { buildServiceSchema } from "@/lib/structured-data";
import { JsonLd } from "@/components/seo/JsonLd";
import { Breadcrumbs } from "@/components/seo/Breadcrumbs";
import { Container } from "@/components/ui/Container";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Card } from "@/components/ui/Card";
import { PageHero } from "@/components/shared/PageHero";
import { PriceNote } from "@/components/shared/PriceNote";
import { FaqSection } from "@/components/shared/FaqSection";
import { LeadFormSection } from "@/components/shared/LeadFormSection";
import { CalculatorButton } from "@/components/lead/CalculatorButton";
import { ButtonLink } from "@/components/ui/Button";
import { PortfolioGallery } from "@/components/portfolio/PortfolioGallery";

const title = `Распашные ворота с автоматикой в ${siteConfig.cityAndRegion}`;
const description =
  "Изготовление и монтаж распашных ворот: простая и надёжная конструкция, автоматизация, установка на новые или существующие столбы.";

export const metadata: Metadata = buildMetadata({ title, description, path: "/raspashnye-vorota" });

const highlights = [
  { title: "Простая и надёжная конструкция", description: "Минимум механических узлов — меньше точек отказа." },
  { title: "Монтаж на готовые столбы", description: "Если существующие опоры в хорошем состоянии, используем их." },
  { title: "Монтаж на новые столбы", description: "Проектируем и заливаем опоры под расчётную нагрузку." },
  { title: "Автоматизация", description: "Линейные или подземные приводы с пультом и фотоэлементами." },
];

const spaceRequirements = [
  "Свободное пространство по дуге открывания створок",
  "Ровное основание перед проёмом без перепадов высоты",
  "Для автоматики — электропитание в зоне монтажа приводов",
];

const winterNotes =
  "При обильном снегопаде важно расчищать зону перед створками. Для автоматики рекомендуем приводы с запасом по мощности — это обсуждается на замере.";

export default function RaspashnyeVorotaPage() {
  return (
    <>
      <JsonLd data={buildServiceSchema({ name: title, description, url: `${siteConfig.seo.siteUrl}/raspashnye-vorota` })} />
      <Container>
        <Breadcrumbs items={[{ name: "Распашные ворота", href: "/raspashnye-vorota" }]} />
      </Container>

      <PageHero
        eyebrow="Классическое решение"
        title="Распашные ворота под ключ"
        description={`Простая и надёжная конструкция с возможностью автоматизации — установим в ${siteConfig.cityAndRegion}.`}
        image="/images/hero-swing.svg"
        imageAlt="Распашные ворота на участке"
        gateType="Распашные ворота"
        primaryLabel="Рассчитать распашные ворота"
      />

      <Section surface="surface">
        <SectionHeading eyebrow="Преимущества" title="Почему выбирают распашные ворота" />
        <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2">
          {highlights.map((item) => (
            <Card key={item.title} className="p-6">
              <h3 className="font-bold text-text">{item.title}</h3>
              <p className="mt-2 text-sm text-text-muted">{item.description}</p>
            </Card>
          ))}
        </div>
      </Section>

      <Section>
        <SectionHeading eyebrow="Внешний вид" title="Варианты заполнения" />
        <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
          {fillOptions.map((option) => (
            <div key={option.title} className="rounded-xl border border-border bg-surface p-4 text-center">
              <h3 className="text-sm font-bold text-text">{option.title}</h3>
            </div>
          ))}
        </div>
      </Section>

      <Section surface="surface">
        <div className="grid gap-10 lg:grid-cols-2">
          <div>
            <SectionHeading eyebrow="Подготовка" title="Требования к свободному месту" />
            <ul className="mt-6 space-y-3">
              {spaceRequirements.map((item) => (
                <li key={item} className="flex items-start gap-3 text-[15px] text-text">
                  <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-accent" aria-hidden="true" />
                  {item}
                </li>
              ))}
            </ul>
          </div>
          <div>
            <SectionHeading eyebrow="Зимняя эксплуатация" title="Особенности зимой" />
            <p className="mt-6 text-text-muted">{winterNotes}</p>
          </div>
        </div>
      </Section>

      <Section>
        <SectionHeading eyebrow="Автоматика" title={automationByGateType.raspashnye.title} />
        <p className="mt-4 max-w-2xl text-text-muted">{automationByGateType.raspashnye.description}</p>
        <div className="mt-6 flex flex-col gap-3 sm:flex-row">
          <CalculatorButton size="lg" gateType="Распашные ворота">
            Подобрать автоматику
          </CalculatorButton>
          <ButtonLink href="/avtomatika" size="lg" variant="secondary">
            Подробнее об автоматике
          </ButtonLink>
        </div>
      </Section>

      <Section surface="surface">
        <SectionHeading eyebrow="Портфолио" title="Примеры готовых работ" />
        <div className="mt-8">
          <PortfolioGallery initialFilter="raspashnye" showFilters={false} showViewAllLink />
        </div>
      </Section>

      <Section>
        <SectionHeading eyebrow="Стоимость" title="От чего зависит цена" />
        <PriceNote className="mt-4 max-w-2xl text-text-muted" />
      </Section>

      <FaqSection items={faqRaspashnye} title="Вопросы о распашных воротах" />

      <LeadFormSection
        title="Рассчитать распашные ворота"
        defaultGateType="Распашные ворота"
        submitLabel="Получить расчёт"
      />
    </>
  );
}
