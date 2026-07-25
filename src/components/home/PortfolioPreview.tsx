import { Section, SectionHeading } from "@/components/ui/Section";
import { PortfolioGallery } from "@/components/portfolio/PortfolioGallery";

export function PortfolioPreview() {
  return (
    <Section surface="surface" id="portfolio" className="scroll-mt-24">
      <SectionHeading
        eyebrow="Портфолио"
        title="Готовые объекты"
        description="Примеры выполненных работ. Районы указаны условно — см. полное портфолио."
      />
      <div className="mt-8">
        <PortfolioGallery limit={6} showViewAllLink />
      </div>
    </Section>
  );
}
