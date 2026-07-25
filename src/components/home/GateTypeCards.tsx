import Image from "next/image";
import Link from "next/link";
import { gateTypes } from "@/data/gate-types";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Card } from "@/components/ui/Card";
import { Reveal } from "@/components/ui/Reveal";
import { CalculatorButton } from "@/components/lead/CalculatorButton";
import { cn } from "@/lib/utils";

interface GateTypeCardsProps {
  eyebrow?: string;
  title?: string;
  description?: string;
}

export function GateTypeCards({
  eyebrow = "Виды ворот",
  title = "Подберите конструкцию под ваш объект",
  description,
}: GateTypeCardsProps) {
  return (
    <Section surface="surface">
      <SectionHeading eyebrow={eyebrow} title={title} description={description} />

      <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {gateTypes.map((gate, index) => (
          <Reveal key={gate.slug} delay={index * 60}>
            <Card
              className={cn(
                "group flex h-full flex-col overflow-hidden transition-shadow hover:shadow-lg",
                gate.featured && "sm:col-span-2 lg:col-span-1 ring-2 ring-brand",
              )}
            >
              <div className="relative aspect-[4/3] w-full overflow-hidden">
                <Image
                  src={gate.image}
                  alt={gate.imageAlt}
                  fill
                  loading="lazy"
                  sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw"
                  className="object-cover transition-transform duration-300 group-hover:scale-105"
                />
                {gate.featured ? (
                  <span className="absolute left-3 top-3 rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white">
                    Основное направление
                  </span>
                ) : null}
              </div>
              <div className="flex flex-1 flex-col p-6">
                <h3 className="text-lg font-bold text-text">{gate.title}</h3>
                <p className="mt-2 flex-1 text-sm text-text-muted">{gate.shortDescription}</p>
                <div className="mt-5 flex items-center justify-between gap-3">
                  <Link href={gate.href} className="text-sm font-semibold text-brand hover:text-brand-hover">
                    Подробнее →
                  </Link>
                  <CalculatorButton size="md" variant="ghost" gateType={gate.title}>
                    Рассчитать
                  </CalculatorButton>
                </div>
              </div>
            </Card>
          </Reveal>
        ))}
      </div>
    </Section>
  );
}
