import Image from "next/image";
import { fillOptions } from "@/data/fill-options";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Reveal } from "@/components/ui/Reveal";
import { CalculatorButton } from "@/components/lead/CalculatorButton";

export function FillOptionsSection() {
  return (
    <Section>
      <SectionHeading
        eyebrow="Внешний вид"
        title="Подберите заполнение под фасад дома"
        description="Материал, цвет и рисунок заполнения обсуждаются на этапе расчёта — покажем варианты и поможем выбрать."
      />
      <div className="mt-10 grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-6">
        {fillOptions.map((option, index) => (
          <Reveal key={option.title} delay={index * 50}>
            <div className="text-center">
              <div className="relative aspect-square w-full overflow-hidden rounded-2xl">
                <Image
                  src={option.image}
                  alt={option.title}
                  fill
                  loading="lazy"
                  sizes="(min-width: 1024px) 16vw, 33vw"
                  className="object-cover"
                />
              </div>
              <h3 className="mt-3 text-sm font-bold text-text">{option.title}</h3>
              <p className="mt-1 text-xs text-text-muted">{option.description}</p>
            </div>
          </Reveal>
        ))}
      </div>
      <div className="mt-10 text-center">
        <CalculatorButton size="lg">Рассчитать с подбором заполнения</CalculatorButton>
      </div>
    </Section>
  );
}
