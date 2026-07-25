import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { faqAvtomatika } from "@/data/faq";
import { automationFeatures, automationByGateType } from "@/data/automation-features";
import { buildMetadata } from "@/lib/seo";
import { buildServiceSchema } from "@/lib/structured-data";
import { JsonLd } from "@/components/seo/JsonLd";
import { Breadcrumbs } from "@/components/seo/Breadcrumbs";
import { Container } from "@/components/ui/Container";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Card } from "@/components/ui/Card";
import { PageHero } from "@/components/shared/PageHero";
import { FaqSection } from "@/components/shared/FaqSection";
import { LeadFormSection } from "@/components/shared/LeadFormSection";

const title = "Установка автоматики для ворот";
const description =
  "Монтаж и настройка автоматики для откатных, распашных и секционных ворот: приводы, пульты, фотоэлементы, сигнальные лампы, обслуживание и ремонт.";

export const metadata: Metadata = buildMetadata({ title, description, path: "/avtomatika" });

const equipment = [
  { title: "Пульты управления", description: "Компактные пульты для въезда без выхода из автомобиля." },
  { title: "Фотоэлементы", description: "Останавливают движение створки при появлении препятствия в проёме." },
  { title: "Сигнальные лампы", description: "Предупреждают о движении ворот — важно на проезжей части." },
  { title: "GSM- и Wi-Fi-модули", description: "Управление со смартфона при использовании совместимого оборудования." },
];

const process = [
  { title: "Монтаж", description: "Устанавливаем привод, кронштейны и элементы безопасности по проекту." },
  { title: "Настройка", description: "Задаём усилие, скорость и конечные положения створки." },
  { title: "Обслуживание", description: "Плановая проверка и смазка узлов для долгой работы автоматики." },
  { title: "Ремонт", description: "Диагностика и восстановление автоматики, включая установленную не нами." },
];

export default function AvtomatikaPage() {
  return (
    <>
      <JsonLd data={buildServiceSchema({ name: title, description, url: `${siteConfig.seo.siteUrl}/avtomatika` })} />
      <Container>
        <Breadcrumbs items={[{ name: "Автоматика", href: "/avtomatika" }]} />
      </Container>

      <PageHero
        eyebrow="Автоматизация"
        title="Автоматика для ворот"
        description="Подберём и установим привод, пульты и элементы безопасности под тип ваших ворот."
        image="/images/hero-automation.svg"
        imageAlt="Привод автоматики на воротах"
        primaryLabel="Подобрать автоматику"
      />

      <Section surface="surface">
        <SectionHeading eyebrow="По типу ворот" title="Автоматика под конструкцию" />
        <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
          {Object.values(automationByGateType).map((item) => (
            <Card key={item.title} className="p-6">
              <h3 className="font-bold text-text">{item.title}</h3>
              <p className="mt-2 text-sm text-text-muted">{item.description}</p>
            </Card>
          ))}
        </div>
      </Section>

      <Section>
        <SectionHeading eyebrow="Комплектация" title="Оборудование и функции" />
        <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {[...equipment, ...automationFeatures.filter((f) => !equipment.some((e) => e.title === f.title))]
            .slice(0, 8)
            .map((item) => (
              <div key={item.title} className="rounded-2xl border border-border bg-surface p-5">
                <h3 className="text-sm font-bold text-text">{item.title}</h3>
                <p className="mt-2 text-sm text-text-muted">{item.description}</p>
              </div>
            ))}
        </div>
      </Section>

      <Section surface="surface">
        <SectionHeading eyebrow="Сервис" title="Монтаж, настройка, обслуживание и ремонт" />
        <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {process.map((item, index) => (
            <div key={item.title}>
              <span className="text-2xl font-extrabold text-accent">{String(index + 1).padStart(2, "0")}</span>
              <h3 className="mt-2 font-bold text-text">{item.title}</h3>
              <p className="mt-1 text-sm text-text-muted">{item.description}</p>
            </div>
          ))}
        </div>
      </Section>

      {siteConfig.automationBrands.length > 0 ? (
        <Section>
          <SectionHeading eyebrow="Оборудование" title="С каким оборудованием мы работаем" />
          <ul className="mt-6 flex flex-wrap gap-3">
            {siteConfig.automationBrands.map((brand) => (
              <li key={brand} className="rounded-full border border-border px-4 py-2 text-sm text-text">
                {brand}
              </li>
            ))}
          </ul>
        </Section>
      ) : null}

      <FaqSection items={faqAvtomatika} title="Вопросы об автоматике" />

      <LeadFormSection
        title="Подобрать автоматику"
        description="Расскажите о ваших воротах — подберём подходящий привод и комплектацию."
        submitLabel="Получить расчёт"
      />
    </>
  );
}
