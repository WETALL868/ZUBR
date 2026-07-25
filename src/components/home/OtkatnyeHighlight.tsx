import Image from "next/image";
import { Section } from "@/components/ui/Section";
import { Reveal } from "@/components/ui/Reveal";
import { CalculatorButton } from "@/components/lead/CalculatorButton";

const points = [
  "Не занимают место при открывании",
  "Легко автоматизируются",
  "Удобны зимой — не нужно расчищать место открывания",
  "Современно выглядят и подходят под разные фасады",
  "Подходят для широких въездов",
  "Можно подобрать заполнение под фасад дома",
];

const gallery = [
  { src: "/images/otkatnye-gallery-1.svg", alt: "Откатные ворота в закрытом положении" },
  { src: "/images/otkatnye-gallery-2.svg", alt: "Откатные ворота в процессе открывания" },
  { src: "/images/otkatnye-gallery-3.svg", alt: "Автоматика на откатных воротах крупным планом" },
];

export function OtkatnyeHighlight() {
  return (
    <Section id="otkatnye" className="scroll-mt-24">
      <div className="grid gap-10 lg:grid-cols-2 lg:items-center lg:gap-14">
        <Reveal>
          <span className="mb-3 inline-block text-sm font-semibold uppercase tracking-wide text-accent">
            Главное направление
          </span>
          <h2 className="text-3xl font-bold tracking-tight text-text sm:text-4xl">
            Откатные ворота — удобное решение для частного дома
          </h2>
          <ul className="mt-6 space-y-3">
            {points.map((point) => (
              <li key={point} className="flex items-start gap-3 text-[15px] text-text">
                <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-accent" aria-hidden="true" />
                {point}
              </li>
            ))}
          </ul>
          <CalculatorButton size="lg" gateType="Откатные ворота" className="mt-8">
            Рассчитать откатные ворота
          </CalculatorButton>
        </Reveal>

        <Reveal delay={120} className="grid grid-cols-2 gap-4">
          <div className="relative col-span-2 aspect-[16/10] overflow-hidden rounded-2xl sm:col-span-1 sm:aspect-square">
            <Image src={gallery[0].src} alt={gallery[0].alt} fill loading="lazy" sizes="50vw" className="object-cover" />
          </div>
          <div className="relative aspect-square overflow-hidden rounded-2xl">
            <Image src={gallery[1].src} alt={gallery[1].alt} fill loading="lazy" sizes="25vw" className="object-cover" />
          </div>
          <div className="relative aspect-square overflow-hidden rounded-2xl">
            <Image src={gallery[2].src} alt={gallery[2].alt} fill loading="lazy" sizes="25vw" className="object-cover" />
          </div>
        </Reveal>
      </div>
    </Section>
  );
}
