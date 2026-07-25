import Image from "next/image";
import { automationFeatures } from "@/data/automation-features";
import { Section } from "@/components/ui/Section";
import { Reveal } from "@/components/ui/Reveal";
import { CalculatorButton } from "@/components/lead/CalculatorButton";
import { ButtonLink } from "@/components/ui/Button";

export function AutomationSection() {
  return (
    <Section id="avtomatika" surface="dark" className="scroll-mt-24">
      <div className="grid gap-10 lg:grid-cols-2 lg:items-center lg:gap-14">
        <Reveal className="relative order-2 aspect-[4/3] w-full overflow-hidden rounded-xl lg:order-1">
          <Image
            src="/images/automation-otkatnye.svg"
            alt="Установка привода автоматики на откатные ворота"
            fill
            loading="lazy"
            sizes="(min-width: 1024px) 50vw, 100vw"
            className="object-cover"
          />
        </Reveal>

        <Reveal delay={100} className="order-1 lg:order-2">
          <span className="mb-3 inline-block text-sm font-semibold uppercase tracking-wide text-accent">
            Автоматика для откатных ворот
          </span>
          <h2 className="text-3xl font-bold tracking-tight text-text sm:text-4xl">
            Комфортный въезд в любую погоду
          </h2>
          <p className="mt-4 max-w-xl text-text-muted">
            Подбираем привод под вес и ширину створки, монтируем на объекте и настраиваем усилие, скорость и
            конечные положения. Также устанавливаем автоматику для распашных и секционных ворот.
          </p>
          <ul className="mt-6 grid gap-4 sm:grid-cols-2">
            {automationFeatures.map((feature) => (
              <li key={feature.title} className="rounded-xl border border-border bg-surface p-4 lg:bg-bg">
                <p className="text-sm font-bold text-text">{feature.title}</p>
                <p className="mt-1 text-sm text-text-muted">{feature.description}</p>
              </li>
            ))}
          </ul>
          <div className="mt-8 flex flex-col gap-3 sm:flex-row">
            <CalculatorButton size="lg" gateType="Откатные ворота">
              Подобрать автоматику для откатных ворот
            </CalculatorButton>
            <ButtonLink href="/avtomatika" size="lg" variant="secondary">
              Автоматика для других ворот
            </ButtonLink>
          </div>
        </Reveal>
      </div>
    </Section>
  );
}
