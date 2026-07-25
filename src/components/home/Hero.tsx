import { siteConfig } from "@/config/site";
import { Container } from "@/components/ui/Container";
import { ButtonLink } from "@/components/ui/Button";
import { MeasurementButton } from "@/components/lead/MeasurementButton";
import { HeroVideoBackground } from "./HeroVideoBackground";

const quickPoints = [
  { title: "Выезд на замер", description: "Проверяем проём и грунт на объекте перед изготовлением" },
  { title: "Монтаж под ключ", description: "Фундамент, направляющая, полотно и автоматика — одной бригадой" },
  { title: "Гарантия на работы", description: `${siteConfig.warrantyYears} года на монтаж и автоматику` },
];

export function Hero() {
  return (
    <section className="section-dark relative isolate flex min-h-[560px] items-center overflow-hidden text-text sm:min-h-[620px] lg:min-h-[720px]">
      <HeroVideoBackground
        posterSrc="/images/hero-otkatnye-install.svg"
        posterAlt="Монтаж откатных ворот на объекте"
        webmSrc="/videos/hero-otkatnye.webm"
        mp4Src="/videos/hero-otkatnye.mp4"
      />
      <div
        className="absolute inset-0 bg-gradient-to-r from-black/80 via-black/55 to-black/25"
        aria-hidden="true"
      />

      <Container className="relative z-[2] py-14 sm:py-16 lg:py-20">
        <div className="max-w-2xl animate-fade-up">
          <span className="mb-3 inline-block text-sm font-semibold uppercase tracking-wide text-accent">
            Основное направление
          </span>
          <h1 className="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-[3.25rem] lg:leading-[1.08]">
            Установка откатных ворот под ключ
          </h1>
          <p className="mt-5 max-w-xl text-lg text-white/85">
            Выезжаем на объект, замеряем проём, изготавливаем конструкцию, монтируем и подключаем автоматику —
            в реальных условиях участка, а не по каталожным чертежам. Работаем в {siteConfig.cityAndRegion}.
          </p>
          <p className="mt-3 max-w-xl text-base text-white/75">
            Также устанавливаем секционные и распашные ворота, автоматику и выполняем ремонт.
          </p>

          <div className="mt-8 flex flex-col gap-3 sm:flex-row">
            <ButtonLink href="#calculator" size="lg">
              Рассчитать откатные ворота
            </ButtonLink>
            <MeasurementButton size="lg">Вызвать замерщика</MeasurementButton>
          </div>

          <dl className="mt-10 grid grid-cols-1 gap-6 border-t border-white/20 pt-8 sm:grid-cols-3">
            {quickPoints.map((point) => (
              <div key={point.title}>
                <dt className="text-[15px] font-bold text-accent">{point.title}</dt>
                <dd className="mt-1 text-sm text-white/75">{point.description}</dd>
              </div>
            ))}
          </dl>
        </div>
      </Container>
    </section>
  );
}
