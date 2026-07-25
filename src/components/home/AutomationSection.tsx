import Image from "next/image";
import { automationFeatures } from "@/data/automation-features";
import { Section } from "@/components/ui/Section";
import { Reveal } from "@/components/ui/Reveal";
import { CalculatorButton } from "@/components/lead/CalculatorButton";

export function AutomationSection() {
  return (
    <Section id="avtomatika" surface="surface" className="scroll-mt-24">
      <div className="grid gap-10 lg:grid-cols-2 lg:items-center lg:gap-14">
        <Reveal className="relative order-2 aspect-[4/3] w-full overflow-hidden rounded-3xl lg:order-1">
          <Image
            src="/images/automation-showcase.svg"
            alt="Привод автоматики на воротах"
            fill
            loading="lazy"
            sizes="(min-width: 1024px) 50vw, 100vw"
            className="object-cover"
          />
        </Reveal>

        <Reveal delay={100} className="order-1 lg:order-2">
          <span className="mb-3 inline-block text-sm font-semibold uppercase tracking-wide text-accent">
            Автоматика
          </span>
          <h2 className="text-3xl font-bold tracking-tight text-text sm:text-4xl">
            Автоматика для комфортного въезда в любую погоду
          </h2>
          <ul className="mt-6 grid gap-4 sm:grid-cols-2">
            {automationFeatures.map((feature) => (
              <li key={feature.title} className="rounded-xl border border-border bg-surface p-4 lg:bg-bg">
                <p className="text-sm font-bold text-text">{feature.title}</p>
                <p className="mt-1 text-sm text-text-muted">{feature.description}</p>
              </li>
            ))}
          </ul>
          <CalculatorButton size="lg" className="mt-8">
            Подобрать автоматику
          </CalculatorButton>
        </Reveal>
      </div>
    </Section>
  );
}
