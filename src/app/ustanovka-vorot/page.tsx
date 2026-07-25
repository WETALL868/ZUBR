import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { services } from "@/data/services";
import { faqGeneral } from "@/data/faq";
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
import { Stages } from "@/components/home/Stages";
import { GateTypeCards } from "@/components/home/GateTypeCards";

const title = `Установка ворот в ${siteConfig.cityAndRegion}`;
const description =
  "Установка ворот под ключ: замер, изготовление по размерам, монтаж и подключение автоматики. Откатные, секционные и распашные ворота.";

export const metadata: Metadata = buildMetadata({ title, description, path: "/ustanovka-vorot" });

export default function UstanovkaVorotPage() {
  return (
    <>
      <JsonLd data={buildServiceSchema({ name: title, description, url: `${siteConfig.seo.siteUrl}/ustanovka-vorot` })} />
      <Container>
        <Breadcrumbs items={[{ name: "Установка ворот", href: "/ustanovka-vorot" }]} />
      </Container>

      <PageHero
        eyebrow="Под ключ"
        title="Установка ворот под ключ"
        description={`Замер, изготовление по размерам, монтаж и подключение автоматики — весь комплекс работ в ${siteConfig.cityAndRegion}.`}
        image="/images/hero-ustanovka.svg"
        imageAlt="Установка ворот у частного дома"
      />

      <Section surface="surface">
        <SectionHeading eyebrow="Что входит" title="Услуги по установке ворот" />
        <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {services.map((service) => (
            <Card key={service.slug} className="p-6">
              <h3 className="font-bold text-text">{service.title}</h3>
              <p className="mt-2 text-sm text-text-muted">{service.description}</p>
            </Card>
          ))}
        </div>
      </Section>

      <GateTypeCards />

      <Stages />

      <FaqSection items={faqGeneral.slice(0, 6)} title="Частые вопросы об установке" />

      <LeadFormSection title="Рассчитать установку ворот" submitLabel="Получить расчёт" />
    </>
  );
}
