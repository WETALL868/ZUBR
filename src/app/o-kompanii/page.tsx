import type { Metadata } from "next";
import Image from "next/image";
import { siteConfig } from "@/config/site";
import { advantages } from "@/data/advantages";
import { buildMetadata } from "@/lib/seo";
import { Breadcrumbs } from "@/components/seo/Breadcrumbs";
import { Container } from "@/components/ui/Container";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Reveal } from "@/components/ui/Reveal";
import { PageHero } from "@/components/shared/PageHero";
import { Stages } from "@/components/home/Stages";
import { LeadFormSection } from "@/components/shared/LeadFormSection";

const title = "О компании";
const description = `${siteConfig.companyName} — установка, изготовление и автоматизация ворот в ${siteConfig.cityAndRegion}. Полный цикл работ: от замера до гарантийного обслуживания.`;

export const metadata: Metadata = buildMetadata({ title, description, path: "/o-kompanii" });

const trustBlocks = [
  {
    key: "installer",
    show: siteConfig.trust.showInstallerPhoto,
    image: "/images/trust-installer.svg",
    alt: "Монтажник компании за работой",
    title: "Собственная монтажная бригада",
    description: "Установку и настройку автоматики выполняют штатные специалисты компании.",
  },
  {
    key: "van",
    show: siteConfig.trust.showVanPhoto,
    image: "/images/trust-van.svg",
    alt: "Автомобиль компании",
    title: "Доставка конструкции и инструмента",
    description: "Выезжаем на объект со всем необходимым для монтажа за один визит.",
  },
  {
    key: "warranty",
    show: siteConfig.trust.showWarrantyDocument,
    image: "/images/trust-warranty.svg",
    alt: "Гарантийные документы",
    title: "Договор и гарантия на работы",
    description: `Условия фиксируются в договоре, гарантия на монтаж — ${siteConfig.warrantyYears} года.`,
  },
  {
    key: "object",
    show: siteConfig.trust.showObjectPhoto,
    image: "/images/trust-object.svg",
    alt: "Сданный объект",
    title: "Сдача объекта с демонстрацией работы",
    description: "Показываем клиенту работу ворот и автоматики перед подписанием акта.",
  },
].filter((block) => block.show);

export default function OKompaniiPage() {
  return (
    <>
      <Container>
        <Breadcrumbs items={[{ name: "О компании", href: "/o-kompanii" }]} />
      </Container>

      <PageHero
        eyebrow="О компании"
        title={siteConfig.companyName}
        description={`Устанавливаем, изготавливаем и обслуживаем ворота в ${siteConfig.cityAndRegion}. Замер, монтаж и настройка автоматики — одной командой.`}
        image="/images/hero-about.svg"
        imageAlt="Специалист компании на объекте"
      />

      <Section surface="surface">
        <SectionHeading
          eyebrow="Подход к работе"
          title="Полный цикл: от замера до гарантийного обслуживания"
          description="Работаем без посредников: замер, изготовление, монтаж и сервис выполняет одна команда, что позволяет контролировать качество на каждом этапе."
        />
      </Section>

      {trustBlocks.length > 0 ? (
        <Section>
          <SectionHeading eyebrow="Почему нам можно доверять" title="Как мы работаем на объекте" />
          <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {trustBlocks.map((block) => (
              <Reveal key={block.key}>
                <div className="overflow-hidden rounded-2xl border border-border bg-surface">
                  <div className="relative aspect-[4/3] w-full">
                    <Image src={block.image} alt={block.alt} fill loading="lazy" sizes="25vw" className="object-cover" />
                  </div>
                  <div className="p-5">
                    <h3 className="text-sm font-bold text-text">{block.title}</h3>
                    <p className="mt-1.5 text-sm text-text-muted">{block.description}</p>
                  </div>
                </div>
              </Reveal>
            ))}
          </div>
        </Section>
      ) : null}

      <Section surface="surface">
        <SectionHeading eyebrow="Гарантии" title="Что мы гарантируем" />
        <div className="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-3">
          {advantages.slice(0, 3).map((item) => (
            <div key={item.title} className="rounded-2xl border border-border bg-bg p-6">
              <h3 className="font-bold text-text">{item.title}</h3>
              <p className="mt-2 text-sm text-text-muted">{item.description}</p>
            </div>
          ))}
        </div>
      </Section>

      <Stages />

      <Section surface="surface">
        <SectionHeading eyebrow="Реквизиты" title="Юридическая информация" />
        <dl className="mt-8 grid max-w-2xl grid-cols-1 gap-4 text-sm sm:grid-cols-2">
          <div>
            <dt className="text-text-muted">Наименование</dt>
            <dd className="font-medium text-text">{siteConfig.legalName}</dd>
          </div>
          <div>
            <dt className="text-text-muted">ИНН</dt>
            <dd className="font-medium text-text">{siteConfig.requisites.inn}</dd>
          </div>
          <div>
            <dt className="text-text-muted">ОГРНИП</dt>
            <dd className="font-medium text-text">{siteConfig.requisites.ogrnip}</dd>
          </div>
          <div className="sm:col-span-2">
            <dt className="text-text-muted">Юридический адрес</dt>
            <dd className="font-medium text-text">{siteConfig.requisites.legalAddress}</dd>
          </div>
        </dl>
      </Section>

      <LeadFormSection title="Обсудить проект" submitLabel="Получить расчёт" />
    </>
  );
}
