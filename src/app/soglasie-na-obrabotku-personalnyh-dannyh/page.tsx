import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { buildMetadata } from "@/lib/seo";
import { Breadcrumbs } from "@/components/seo/Breadcrumbs";
import { Container } from "@/components/ui/Container";
import { Section } from "@/components/ui/Section";

const title = "Согласие на обработку персональных данных";
const description = "Текст согласия пользователя на обработку персональных данных при отправке форм на сайте.";

export const metadata: Metadata = buildMetadata({
  title,
  description,
  path: "/soglasie-na-obrabotku-personalnyh-dannyh",
});

export default function ConsentPage() {
  return (
    <>
      <Container>
        <Breadcrumbs
          items={[
            {
              name: "Согласие на обработку персональных данных",
              href: "/soglasie-na-obrabotku-personalnyh-dannyh",
            },
          ]}
        />
      </Container>

      <Section className="pt-2">
        <div className="mx-auto max-w-3xl">
          <h1 className="text-3xl font-extrabold tracking-tight text-text sm:text-4xl">
            Согласие на обработку персональных данных
          </h1>

          <div className="mt-8 space-y-6 text-[15px] leading-relaxed text-text-muted">
            <p>
              Отправляя любую форму на сайте {siteConfig.seo.siteUrl} (расчёт стоимости, вызов замерщика, вызов
              мастера, форма обратной связи), пользователь подтверждает, что:
            </p>
            <ol className="list-decimal space-y-3 pl-5">
              <li>
                Ознакомлен с{" "}
                <a href="/politika-konfidentsialnosti" className="text-brand underline hover:text-brand-hover">
                  Политикой конфиденциальности
                </a>{" "}
                и согласен с условиями обработки персональных данных.
              </li>
              <li>
                Даёт согласие {siteConfig.legalName} на обработку указанных в форме персональных данных (имя,
                номер телефона и иные добровольно предоставленные сведения) для связи с ним по заявке, подготовки
                расчёта и оказания услуг.
              </li>
              <li>
                Согласие даётся на срок до момента его отзыва пользователем. Отозвать согласие можно, направив
                запрос на электронную почту {siteConfig.email} или по телефону {siteConfig.phone}.
              </li>
              <li>
                Обработка персональных данных осуществляется с использованием и без использования средств
                автоматизации, включая сбор, запись, систематизацию, хранение и уничтожение данных.
              </li>
              <li>
                Персональные данные не передаются третьим лицам, за исключением сервисов, обеспечивающих
                получение заявки Оператором (электронная почта, Telegram).
              </li>
            </ol>
            <p>
              Оператор персональных данных: {siteConfig.legalName}, ИНН {siteConfig.requisites.inn}, ОГРНИП{" "}
              {siteConfig.requisites.ogrnip}, адрес: {siteConfig.requisites.legalAddress}.
            </p>
          </div>
        </div>
      </Section>
    </>
  );
}
