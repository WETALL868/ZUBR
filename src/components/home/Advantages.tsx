import { advantages } from "@/data/advantages";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Reveal } from "@/components/ui/Reveal";

export function Advantages() {
  return (
    <Section>
      <SectionHeading eyebrow="Почему нам доверяют" title="Всё для установки ворот в одном месте" />
      <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {advantages.map((item, index) => (
          <Reveal key={item.title} delay={index * 60}>
            <div className="h-full rounded-2xl border border-border bg-surface p-6">
              <span className="text-2xl font-extrabold text-accent">{String(index + 1).padStart(2, "0")}</span>
              <h3 className="mt-3 text-lg font-bold text-text">{item.title}</h3>
              <p className="mt-2 text-sm text-text-muted">{item.description}</p>
            </div>
          </Reveal>
        ))}
      </div>
    </Section>
  );
}
