import type { Metadata } from "next";
import { siteConfig } from "@/config/site";
import { Container } from "@/components/ui/Container";
import { ButtonLink } from "@/components/ui/Button";

export const metadata: Metadata = {
  title: "Заявка отправлена",
  description: "Спасибо за заявку — специалист свяжется с вами в ближайшее время.",
  robots: { index: false, follow: false },
};

export default function ThankYouPage() {
  return (
    <Container className="flex min-h-[60vh] flex-col items-center justify-center py-20 text-center">
      <div className="flex h-16 w-16 items-center justify-center rounded-full bg-brand/10 text-brand">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M5 13l4 4L19 7" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      </div>
      <h1 className="mt-6 text-3xl font-extrabold tracking-tight text-text sm:text-4xl">Заявка отправлена</h1>
      <p className="mt-4 max-w-lg text-lg text-text-muted">
        Спасибо! Специалист свяжется с вами по указанному номеру телефона в рабочее время —{" "}
        {siteConfig.workingHours.toLowerCase()}.
      </p>
      <p className="mt-2 max-w-lg text-text-muted">
        Если вопрос срочный, позвоните нам напрямую: {" "}
        <a href={siteConfig.phoneHref} className="font-semibold text-brand hover:text-brand-hover">
          {siteConfig.phone}
        </a>
      </p>
      <div className="mt-8 flex flex-col gap-3 sm:flex-row">
        <ButtonLink href="/" size="lg">
          На главную
        </ButtonLink>
        <ButtonLink href="/portfolio" size="lg" variant="secondary">
          Смотреть портфолио
        </ButtonLink>
      </div>
    </Container>
  );
}
