import Image from "next/image";
import type { ReactNode } from "react";
import { Container } from "@/components/ui/Container";
import { CalculatorButton } from "@/components/lead/CalculatorButton";
import { MeasurementButton } from "@/components/lead/MeasurementButton";

interface PageHeroProps {
  eyebrow?: string;
  title: string;
  description: string;
  image: string;
  imageAlt: string;
  gateType?: string;
  primaryLabel?: string;
  children?: ReactNode;
}

export function PageHero({
  eyebrow,
  title,
  description,
  image,
  imageAlt,
  gateType,
  primaryLabel = "Рассчитать стоимость",
  children,
}: PageHeroProps) {
  return (
    <section className="bg-bg">
      <Container className="grid gap-10 pb-12 pt-2 sm:pb-16 lg:grid-cols-2 lg:items-center lg:pb-20">
        <div>
          {eyebrow ? (
            <span className="mb-3 inline-block text-sm font-semibold uppercase tracking-wide text-accent">
              {eyebrow}
            </span>
          ) : null}
          <h1 className="text-3xl font-extrabold tracking-tight text-text sm:text-4xl lg:text-[2.75rem] lg:leading-tight">
            {title}
          </h1>
          <p className="mt-4 max-w-xl text-lg text-text-muted">{description}</p>
          {children}
          <div className="mt-8 flex flex-col gap-3 sm:flex-row">
            <CalculatorButton size="lg" gateType={gateType}>
              {primaryLabel}
            </CalculatorButton>
            <MeasurementButton size="lg">Вызвать замерщика</MeasurementButton>
          </div>
        </div>
        <div className="relative aspect-[4/3] w-full overflow-hidden rounded-3xl">
          <Image src={image} alt={imageAlt} fill priority sizes="(min-width: 1024px) 50vw, 100vw" className="object-cover" />
        </div>
      </Container>
    </section>
  );
}
