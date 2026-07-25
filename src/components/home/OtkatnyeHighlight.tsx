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
  "Учитываем реальные условия участка: грунт, уклон, фундамент под забором",
  "Можно подобрать заполнение под фасад дома",
];

const gallery = [
  { src: "/images/otkatnye-work-1.svg", alt: "Монтаж направляющей и фундамента под откатные ворота" },
  { src: "/images/otkatnye-work-2.svg", alt: "Установка привода и автоматики на откатные ворота" },
  { src: "/images/otkatnye-work-3.svg", alt: "Готовые откатные ворота на объекте" },
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
            Откатные ворота — основное, что мы делаем
          </h2>
          <p className="mt-4 max-w-xl text-text-muted">
            Большинство наших выездов — это замер, изготовление и монтаж именно откатных ворот для частных
            домов и участков. Знаем, с чем реально приходится иметь дело на объекте: разный грунт, уклон,
            старый забор, необходимость заливать фундамент под направляющую.
          </p>
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
          <div className="relative col-span-2 aspect-[16/10] overflow-hidden rounded-xl sm:col-span-1 sm:aspect-square">
            <Image src={gallery[0].src} alt={gallery[0].alt} fill loading="lazy" sizes="50vw" className="object-cover" />
          </div>
          <div className="relative aspect-square overflow-hidden rounded-xl">
            <Image src={gallery[1].src} alt={gallery[1].alt} fill loading="lazy" sizes="25vw" className="object-cover" />
          </div>
          <div className="relative aspect-square overflow-hidden rounded-xl">
            <Image src={gallery[2].src} alt={gallery[2].alt} fill loading="lazy" sizes="25vw" className="object-cover" />
          </div>
        </Reveal>
      </div>
    </Section>
  );
}
