import { Section, SectionHeading } from "@/components/ui/Section";
import { PortfolioGallery } from "@/components/portfolio/PortfolioGallery";

export function PortfolioPreview() {
  return (
    <Section surface="surface" id="portfolio" className="scroll-mt-24">
      <SectionHeading
        eyebrow="Портфолио"
        title="Примеры выполненных откатных ворот"
        description="Показываем в первую очередь откатные ворота — основное направление работы. Остальные категории доступны через фильтр."
      />
      <div className="mt-8">
        <PortfolioGallery limit={6} initialFilter="otkatnye" showViewAllLink />
      </div>
    </Section>
  );
}
