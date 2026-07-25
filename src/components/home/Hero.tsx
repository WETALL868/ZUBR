import Image from "next/image";
import { siteConfig } from "@/config/site";
import { Container } from "@/components/ui/Container";
import { ButtonLink } from "@/components/ui/Button";
import { MeasurementButton } from "@/components/lead/MeasurementButton";

const quickPoints = [
  { title: "Точный замер", description: "Перед изготовлением проверяем размеры на месте" },
  { title: "Монтаж под ключ", description: "Изготовление, доставка и установка одной командой" },
  { title: "Гарантия на работы", description: `${siteConfig.warrantyYears} года на монтаж и автоматику` },
];

export function Hero() {
  return (
    <section className="section-dark relative overflow-hidden bg-bg text-text">
      <Container className="grid gap-10 py-12 sm:py-16 lg:grid-cols-2 lg:items-center lg:py-24">
        <div className="animate-fade-up">
          <h1 className="text-4xl font-extrabold tracking-tight text-text sm:text-5xl lg:text-[3.25rem] lg:leading-[1.08]">
            Установка автоматических ворот под ключ
          </h1>
          <p className="mt-5 max-w-xl text-lg text-text-muted">
            Подберём конструкцию, выполним точный замер, изготовим, установим и подключим автоматику в{" "}
            {siteConfig.cityAndRegion}.
          </p>
          <p className="mt-3 max-w-xl text-base text-text-muted">
            Откатные, секционные и распашные ворота для частных домов, гаражей и коммерческих объектов.
          </p>

          <div className="mt-8 flex flex-col gap-3 sm:flex-row">
            <ButtonLink href="#calculator" size="lg">
              Рассчитать стоимость
            </ButtonLink>
            <MeasurementButton size="lg">Вызвать замерщика</MeasurementButton>
          </div>

          <dl className="mt-10 grid grid-cols-1 gap-6 border-t border-border pt-8 sm:grid-cols-3">
            {quickPoints.map((point) => (
              <div key={point.title}>
                <dt className="text-[15px] font-bold text-accent">{point.title}</dt>
                <dd className="mt-1 text-sm text-text-muted">{point.description}</dd>
              </div>
            ))}
          </dl>
        </div>

        <div className="relative aspect-[4/3] w-full overflow-hidden rounded-3xl shadow-2xl ring-1 ring-white/10 lg:aspect-square">
          <Image
            src="/images/hero-sliding-gate.svg"
            alt="Современные откатные ворота у частного дома"
            fill
            priority
            sizes="(min-width: 1024px) 50vw, 100vw"
            className="object-cover"
          />
        </div>
      </Container>
    </section>
  );
}
