import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { faqGeneral } from "@/data/faq";
import { Hero } from "@/components/home/Hero";
import { GateTypeCards } from "@/components/home/GateTypeCards";
import { OtkatnyeHighlight } from "@/components/home/OtkatnyeHighlight";
import { CalculatorSection } from "@/components/home/CalculatorSection";
import { Advantages } from "@/components/home/Advantages";
import { Stages } from "@/components/home/Stages";
import { PortfolioPreview } from "@/components/home/PortfolioPreview";
import { FillOptionsSection } from "@/components/home/FillOptionsSection";
import { AutomationSection } from "@/components/home/AutomationSection";
import { RepairSection } from "@/components/home/RepairSection";
import { Testimonials } from "@/components/home/Testimonials";
import { FaqSection } from "@/components/shared/FaqSection";
import { FinalCta } from "@/components/home/FinalCta";

export const metadata: Metadata = {
  title: siteConfig.seo.defaultTitle,
  description: siteConfig.seo.defaultDescription,
  alternates: { canonical: "/" },
  openGraph: {
    title: siteConfig.seo.defaultTitle,
    description: siteConfig.seo.defaultDescription,
    url: siteConfig.seo.siteUrl,
  },
};

export default function HomePage() {
  return (
    <>
      {/* Основное направление: откатные ворота — от первого экрана до автоматики */}
      <Hero />
      <OtkatnyeHighlight />
      <CalculatorSection />
      <Stages
        eyebrow="Установка откатных ворот"
        title="Как проходит установка откатных ворот"
      />
      <PortfolioPreview />
      <AutomationSection />
      <FillOptionsSection />

      {/* Второй план: остальные направления и общие преимущества компании */}
      <Advantages />
      <GateTypeCards
        eyebrow="Также устанавливаем"
        title="Другие виды ворот"
        description="Основной фокус компании — откатные ворота, но мы выполняем полный цикл работ и для других конструкций."
      />
      <RepairSection />
      <Testimonials />
      <FaqSection items={faqGeneral} />
      <FinalCta />
    </>
  );
}
