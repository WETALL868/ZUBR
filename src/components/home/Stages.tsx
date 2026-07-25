import { stages } from "@/data/stages";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Reveal } from "@/components/ui/Reveal";

export function Stages() {
  return (
    <Section surface="surface">
      <SectionHeading eyebrow="Как мы работаем" title="Путь от заявки до сдачи объекта" />
      <div className="mt-10 grid grid-cols-1 gap-0 sm:grid-cols-2 lg:grid-cols-5 lg:gap-6">
        {stages.map((stage, index) => (
          <Reveal key={stage.number} delay={index * 60}>
            <div className="relative flex gap-4 border-b border-border py-6 lg:flex-col lg:gap-0 lg:border-b-0 lg:border-t-2 lg:border-brand lg:pt-6">
              <span className="text-2xl font-extrabold text-brand lg:mb-3">
                {String(stage.number).padStart(2, "0")}
              </span>
              <div>
                <h3 className="text-base font-bold text-text">{stage.title}</h3>
                <p className="mt-1.5 text-sm text-text-muted">{stage.description}</p>
              </div>
            </div>
          </Reveal>
        ))}
      </div>
    </Section>
  );
}
