import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { buildMetadata } from "@/lib/seo";
import { Breadcrumbs } from "@/components/seo/Breadcrumbs";
import { Container } from "@/components/ui/Container";
import { Section } from "@/components/ui/Section";

const title = "Политика конфиденциальности";
const description = "Политика конфиденциальности и обработки персональных данных пользователей сайта.";

export const metadata: Metadata = buildMetadata({
  title,
  description,
  path: "/politika-konfidentsialnosti",
});

export default function PrivacyPolicyPage() {
  return (
    <>
      <Container>
        <Breadcrumbs items={[{ name: "Политика конфиденциальности", href: "/politika-konfidentsialnosti" }]} />
      </Container>

      <Section className="pt-2">
        <div className="prose-content mx-auto max-w-3xl">
          <h1 className="text-3xl font-extrabold tracking-tight text-text sm:text-4xl">
            Политика конфиденциальности
          </h1>
          <p className="mt-4 text-text-muted">Действует в отношении сайта {siteConfig.seo.siteUrl}.</p>

          <div className="mt-8 space-y-8 text-[15px] leading-relaxed text-text">
            <section>
              <h2 className="text-xl font-bold text-text">1. Общие положения</h2>
              <p className="mt-3 text-text-muted">
                Настоящая Политика конфиденциальности определяет порядок обработки персональных данных
                пользователей сайта {siteConfig.companyName} ({siteConfig.legalName}, далее — «Оператор»). Используя
                сайт и отправляя формы обратной связи, пользователь соглашается с условиями настоящей Политики.
              </p>
            </section>

            <section>
              <h2 className="text-xl font-bold text-text">2. Какие данные собираются</h2>
              <p className="mt-3 text-text-muted">
                При заполнении форм на сайте (расчёт стоимости, вызов замерщика, вызов мастера, форма обратной
                связи) Оператор получает: имя, номер телефона, а также дополнительные сведения, которые
                пользователь указывает добровольно — тип ворот, комментарий, адрес объекта и приложенные фотографии.
              </p>
            </section>

            <section>
              <h2 className="text-xl font-bold text-text">3. Цели обработки</h2>
              <p className="mt-3 text-text-muted">
                Персональные данные обрабатываются исключительно для связи с пользователем по его заявке:
                уточнения деталей, подготовки предварительного расчёта, согласования выезда специалиста и
                выполнения работ.
              </p>
            </section>

            <section>
              <h2 className="text-xl font-bold text-text">4. Передача данных третьим лицам</h2>
              <p className="mt-3 text-text-muted">
                Для обработки заявок Оператор может передавать данные формы сервисам отправки уведомлений
                (электронная почта, Telegram), используемым исключительно для получения заявки Оператором.
                Данные не передаются третьим лицам в рекламных целях и не продаются.
              </p>
            </section>

            <section>
              <h2 className="text-xl font-bold text-text">5. Хранение и защита данных</h2>
              <p className="mt-3 text-text-muted">
                Оператор принимает организационные и технические меры для защиты персональных данных от
                неправомерного доступа, изменения, раскрытия или уничтожения.
              </p>
            </section>

            <section>
              <h2 className="text-xl font-bold text-text">6. Файлы cookie и аналитика</h2>
              <p className="mt-3 text-text-muted">
                Сайт может использовать файлы cookie и сервисы веб-аналитики (например, Яндекс.Метрику и Google
                Analytics) для оценки посещаемости и работы форм. Аналитика не используется для установления
                личности пользователя.
              </p>
            </section>

            <section>
              <h2 className="text-xl font-bold text-text">7. Права пользователя</h2>
              <p className="mt-3 text-text-muted">
                Пользователь вправе запросить уточнение, исправление или удаление своих персональных данных,
                а также отозвать согласие на их обработку, направив запрос на электронную почту {siteConfig.email}{" "}
                или по телефону {siteConfig.phone}.
              </p>
            </section>

            <section>
              <h2 className="text-xl font-bold text-text">8. Контакты оператора</h2>
              <p className="mt-3 text-text-muted">
                {siteConfig.legalName}, ИНН {siteConfig.requisites.inn}, ОГРНИП {siteConfig.requisites.ogrnip}.
                <br />
                Адрес: {siteConfig.requisites.legalAddress}.
                <br />
                Электронная почта: {siteConfig.email}. Телефон: {siteConfig.phone}.
              </p>
            </section>
          </div>
        </div>
      </Section>
    </>
  );
}
