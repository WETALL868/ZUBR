import Image from "next/image";
import { testimonials } from "@/data/testimonials";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Card } from "@/components/ui/Card";
import { Reveal } from "@/components/ui/Reveal";

export function Testimonials() {
  return (
    <Section surface="surface">
      <SectionHeading
        eyebrow="Отзывы"
        title="Что говорят клиенты"
        description="Ниже — демонстрационные карточки для примера вёрстки. Перед публикацией замените их на реальные отзывы клиентов."
      />
      <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {testimonials.map((item) => (
          <Reveal key={item.id}>
            <Card className="flex h-full flex-col p-6">
              <div className="flex items-center gap-3">
                <div className="relative h-12 w-12 shrink-0 overflow-hidden rounded-full">
                  <Image src={item.image} alt="" fill sizes="48px" className="object-cover" />
                </div>
                <div>
                  <p className="font-bold text-text">{item.name}</p>
                  <p className="text-xs text-text-muted">{item.district}</p>
                </div>
              </div>
              <p className="mt-4 flex-1 text-sm text-text-muted">{item.text}</p>
              <div className="mt-4 flex items-center justify-between border-t border-border pt-4 text-xs">
                <span className="font-medium text-brand">{item.workType}</span>
                {item.sourceUrl ? (
                  <a href={item.sourceUrl} target="_blank" rel="noopener noreferrer" className="text-text-muted underline hover:text-brand">
                    Источник
                  </a>
                ) : null}
              </div>
            </Card>
          </Reveal>
        ))}
      </div>
      <p className="mt-6 text-center text-xs text-text-muted">
        Демонстрационные карточки — не являются реальными отзывами клиентов.
      </p>
    </Section>
  );
}
