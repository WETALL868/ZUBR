import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { faqSektsionnye } from "@/data/faq";
import { automationByGateType } from "@/data/automation-features";
import { buildMetadata } from "@/lib/seo";
import { buildServiceSchema } from "@/lib/structured-data";
import { JsonLd } from "@/components/seo/JsonLd";
import { Breadcrumbs } from "@/components/seo/Breadcrumbs";
import { Container } from "@/components/ui/Container";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Card } from "@/components/ui/Card";
import { PageHero } from "@/components/shared/PageHero";
import { PriceNote } from "@/components/shared/PriceNote";
import { FaqSection } from "@/components/shared/FaqSection";
import { LeadFormSection } from "@/components/shared/LeadFormSection";
import { CalculatorButton } from "@/components/lead/CalculatorButton";
import { ButtonLink } from "@/components/ui/Button";
import { PortfolioGallery } from "@/components/portfolio/PortfolioGallery";

const title = `Секционные ворота для гаража в ${siteConfig.cityAndRegion}`;
const description =
  "Установка секционных гаражных ворот: экономия пространства, теплоизоляция, ручное и автоматическое управление, монтаж и настройка автоматики.";

export const metadata: Metadata = buildMetadata({ title, description, path: "/sektsionnye-vorota" });

const advantages = [
  { title: "Экономия пространства", description: "Полотно поднимается под потолок и не занимает место перед гаражом." },
  { title: "Теплоизоляция", description: "Панели с утеплителем снижают теплопотери отапливаемого гаража." },
  { title: "Ручное и автоматическое управление", description: "Можно открывать вручную или с приводом и пультом." },
  { title: "Безопасная эксплуатация", description: "Фотоэлементы и датчики останавливают полотно при препятствии." },
];

const panelTypes = [
  { title: "Стальные сэндвич-панели", description: "Прочные панели с утеплителем — базовый вариант для гаража." },
  { title: "Панели с зеркальным профилем", description: "Аккуратная гладкая поверхность без выраженного рельефа." },
  { title: "Панели с рельефным профилем", description: "Классический рельеф «под доску» — распространённый выбор." },
];

const colorNote =
  "Стандартная линейка цветов и палитра RAL по каталогу — подберём оттенок под фасад дома на этапе расчёта.";

export default function SektsionnyeVorotaPage() {
  return (
    <>
      <JsonLd data={buildServiceSchema({ name: title, description, url: `${siteConfig.seo.siteUrl}/sektsionnye-vorota` })} />
      <Container>
        <Breadcrumbs items={[{ name: "Секционные ворота", href: "/sektsionnye-vorota" }]} />
      </Container>

      <PageHero
        eyebrow="Для гаража"
        title="Секционные ворота для гаража"
        description={`Изготовим и установим секционные ворота с утеплённой панелью и автоматикой в ${siteConfig.cityAndRegion}.`}
        image="/images/hero-sectional.svg"
        imageAlt="Секционные гаражные ворота"
        gateType="Секционные ворота"
        primaryLabel="Рассчитать секционные ворота"
      />

      <Section surface="surface">
        <SectionHeading eyebrow="Преимущества" title="Почему секционные ворота удобны для гаража" />
        <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2">
          {advantages.map((item) => (
            <Card key={item.title} className="p-6">
              <h3 className="font-bold text-text">{item.title}</h3>
              <p className="mt-2 text-sm text-text-muted">{item.description}</p>
            </Card>
          ))}
        </div>
      </Section>

      <Section>
        <SectionHeading eyebrow="Панели" title="Виды панелей и цвета" />
        <div className="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-3">
          {panelTypes.map((item) => (
            <Card key={item.title} className="p-6">
              <h3 className="font-bold text-text">{item.title}</h3>
              <p className="mt-2 text-sm text-text-muted">{item.description}</p>
            </Card>
          ))}
        </div>
        <p className="mt-6 max-w-2xl text-text-muted">{colorNote}</p>
      </Section>

      <Section surface="surface">
        <SectionHeading eyebrow="Автоматика" title={automationByGateType.sektsionnye.title} />
        <p className="mt-4 max-w-2xl text-text-muted">{automationByGateType.sektsionnye.description}</p>
        <div className="mt-6 flex flex-col gap-3 sm:flex-row">
          <CalculatorButton size="lg" gateType="Секционные ворота">
            Подобрать автоматику
          </CalculatorButton>
          <ButtonLink href="/avtomatika" size="lg" variant="secondary">
            Подробнее об автоматике
          </ButtonLink>
        </div>
      </Section>

      <Section>
        <SectionHeading eyebrow="Сервис" title="Обслуживание и ремонт" />
        <p className="mt-4 max-w-2xl text-text-muted">
          Проводим плановое обслуживание и ремонт секционных ворот: регулировку полотна, замену роликов,
          направляющих и настройку автоматики.
        </p>
        <ButtonLink href="/remont-vorot" size="lg" variant="secondary" className="mt-6">
          Подробнее о ремонте
        </ButtonLink>
      </Section>

      <Section surface="surface">
        <SectionHeading eyebrow="Портфолио" title="Примеры готовых работ" />
        <div className="mt-8">
          <PortfolioGallery initialFilter="sektsionnye" showFilters={false} showViewAllLink />
        </div>
      </Section>

      <Section>
        <SectionHeading eyebrow="Стоимость" title="От чего зависит цена" />
        <PriceNote className="mt-4 max-w-2xl text-text-muted" />
      </Section>

      <FaqSection items={faqSektsionnye} title="Вопросы о секционных воротах" />

      <LeadFormSection
        title="Рассчитать секционные ворота"
        defaultGateType="Секционные ворота"
        submitLabel="Получить расчёт"
      />
    </>
  );
}
