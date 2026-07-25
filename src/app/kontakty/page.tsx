import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { serviceAreas } from "@/data/services";
import { buildMetadata } from "@/lib/seo";
import { Breadcrumbs } from "@/components/seo/Breadcrumbs";
import { Container } from "@/components/ui/Container";
import { Section, SectionHeading } from "@/components/ui/Section";
import { Card } from "@/components/ui/Card";
import { Map } from "@/components/contact/Map";
import { LeadForm } from "@/components/lead/LeadForm";

const title = "Контакты";
const description = `Телефон, мессенджеры, адрес офиса и режим работы ${siteConfig.companyName} в ${siteConfig.cityAndRegion}.`;

export const metadata: Metadata = buildMetadata({ title, description, path: "/kontakty" });

export default function KontaktyPage() {
  return (
    <>
      <Container>
        <Breadcrumbs items={[{ name: "Контакты", href: "/kontakty" }]} />
      </Container>

      <Section className="pt-2">
        <h1 className="text-3xl font-extrabold tracking-tight text-text sm:text-4xl">Контакты</h1>
        <p className="mt-4 max-w-2xl text-lg text-text-muted">
          Свяжитесь с нами удобным способом — по телефону, в мессенджере или через форму ниже.
        </p>

        <div className="mt-10 grid gap-10 lg:grid-cols-2">
          <div className="space-y-6">
            <Card className="p-6">
              <h2 className="text-lg font-bold text-text">Телефон</h2>
              <a href={siteConfig.phoneHref} className="mt-2 block text-xl font-bold text-brand hover:text-brand-hover">
                {siteConfig.phone}
              </a>
              <p className="mt-1 text-sm text-text-muted">{siteConfig.workingHours}</p>
            </Card>

            <Card className="p-6">
              <h2 className="text-lg font-bold text-text">Мессенджеры и почта</h2>
              <ul className="mt-3 space-y-2 text-[15px]">
                <li>
                  <a href={siteConfig.social.telegram} target="_blank" rel="noopener noreferrer" className="text-brand hover:text-brand-hover">
                    Telegram: @{siteConfig.telegram}
                  </a>
                </li>
                <li>
                  <a href={siteConfig.social.whatsapp} target="_blank" rel="noopener noreferrer" className="text-brand hover:text-brand-hover">
                    WhatsApp
                  </a>
                </li>
                <li>
                  <a href={`mailto:${siteConfig.email}`} className="text-brand hover:text-brand-hover">
                    {siteConfig.email}
                  </a>
                </li>
              </ul>
            </Card>

            <Card className="p-6">
              <h2 className="text-lg font-bold text-text">Адрес офиса</h2>
              <p className="mt-2 text-[15px] text-text">{siteConfig.address}</p>
            </Card>

            <Card className="p-6">
              <h2 className="text-lg font-bold text-text">Зона обслуживания</h2>
              <p className="mt-2 text-sm text-text-muted">{siteConfig.serviceAreaNote}</p>
              <ul className="mt-3 flex flex-wrap gap-2">
                {serviceAreas.map((area) => (
                  <li key={area} className="rounded-full border border-border px-3 py-1 text-sm text-text-muted">
                    {area}
                  </li>
                ))}
              </ul>
            </Card>
          </div>

          <Map />
        </div>
      </Section>

      <Section surface="surface">
        <SectionHeading eyebrow="Форма обратной связи" title="Оставить заявку" align="center" className="mx-auto" />
        <Card className="mx-auto mt-8 max-w-2xl p-6 sm:p-10">
          <LeadForm source="contact" submitLabel="Отправить заявку" />
        </Card>
      </Section>
    </>
  );
}
