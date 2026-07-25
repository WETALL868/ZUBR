import type { HTMLAttributes } from "react";
import { cn } from "@/lib/utils";
import { Container } from "./Container";

export type SectionSurface = "bg" | "surface" | "dark";

interface SectionProps extends HTMLAttributes<HTMLElement> {
  containerClassName?: string;
  surface?: SectionSurface;
}

const surfaceClasses: Record<SectionSurface, string | undefined> = {
  bg: undefined,
  surface: "bg-surface",
  dark: "section-dark bg-bg text-text",
};

export function Section({
  className,
  containerClassName,
  surface = "bg",
  children,
  ...props
}: SectionProps) {
  return (
    <section className={cn("py-14 sm:py-20", surfaceClasses[surface], className)} {...props}>
      <Container className={containerClassName}>{children}</Container>
    </section>
  );
}

interface SectionHeadingProps {
  eyebrow?: string;
  title: string;
  description?: string;
  align?: "left" | "center";
  className?: string;
}

export function SectionHeading({
  eyebrow,
  title,
  description,
  align = "left",
  className,
}: SectionHeadingProps) {
  return (
    <div className={cn("max-w-2xl", align === "center" && "mx-auto text-center", className)}>
      {eyebrow ? (
        <span className="mb-3 inline-block text-sm font-semibold uppercase tracking-wide text-accent">
          {eyebrow}
        </span>
      ) : null}
      <h2 className="text-3xl font-bold tracking-tight text-text sm:text-4xl">{title}</h2>
      {description ? <p className="mt-4 text-lg text-text-muted">{description}</p> : null}
    </div>
  );
}
