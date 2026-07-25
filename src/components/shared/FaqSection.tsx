import type { FaqItem } from "@/types";
import { Section, SectionHeading } from "@/components/ui/Section";
import { FaqAccordion } from "./FaqAccordion";
import { JsonLd } from "@/components/seo/JsonLd";
import { buildFaqSchema } from "@/lib/structured-data";

interface FaqSectionProps {
  items: FaqItem[];
  title?: string;
  id?: string;
}

export function FaqSection({ items, title = "Частые вопросы", id = "faq" }: FaqSectionProps) {
  return (
    <Section id={id} className="scroll-mt-24">
      <JsonLd data={buildFaqSchema(items)} />
      <SectionHeading eyebrow="FAQ" title={title} align="center" className="mx-auto" />
      <div className="mx-auto mt-10 max-w-3xl">
        <FaqAccordion items={items} />
      </div>
    </Section>
  );
}
