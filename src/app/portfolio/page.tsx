import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { buildMetadata } from "@/lib/seo";
import { Breadcrumbs } from "@/components/seo/Breadcrumbs";
import { Container } from "@/components/ui/Container";
import { Section } from "@/components/ui/Section";
import { PortfolioGallery } from "@/components/portfolio/PortfolioGallery";
import { LeadFormSection } from "@/components/shared/LeadFormSection";

const title = "Портфолио — примеры установленных ворот";
const description =
  "Примеры выполненных работ: откатные, секционные и распашные ворота, автоматика и ремонт. Фотографии объектов и краткое описание работ.";

export const metadata: Metadata = buildMetadata({ title, description, path: "/portfolio" });

export default function PortfolioPage() {
  return (
    <>
      <Container>
        <Breadcrumbs items={[{ name: "Портфолио", href: "/portfolio" }]} />
      </Container>

      <Section className="pt-2">
        <h1 className="text-3xl font-extrabold tracking-tight text-text sm:text-4xl">Портфолио</h1>
        <p className="mt-4 max-w-2xl text-lg text-text-muted">
          Примеры выполненных работ по установке и ремонту ворот в {siteConfig.cityAndRegion}. Районы указаны
          условно — точные адреса объектов клиентов не публикуются.
        </p>
        <div className="mt-10">
          <PortfolioGallery />
        </div>
      </Section>

      <LeadFormSection title="Хотите похожий результат?" submitLabel="Получить расчёт" />
    </>
  );
}
