import type { Metadata } from "next";
import { Container } from "@/components/ui/Container";
import { ButtonLink } from "@/components/ui/Button";

export const metadata: Metadata = {
  title: "Страница не найдена",
  robots: { index: false, follow: false },
};

export default function NotFound() {
  return (
    <Container className="flex min-h-[60vh] flex-col items-center justify-center py-20 text-center">
      <span className="text-sm font-semibold uppercase tracking-wide text-accent">Ошибка 404</span>
      <h1 className="mt-3 text-3xl font-extrabold tracking-tight text-text sm:text-4xl">Страница не найдена</h1>
      <p className="mt-4 max-w-md text-text-muted">
        Возможно, страница была перемещена или адрес введён с ошибкой. Перейдите на главную или посмотрите виды
        ворот.
      </p>
      <div className="mt-8 flex flex-col gap-3 sm:flex-row">
        <ButtonLink href="/" size="lg">
          На главную
        </ButtonLink>
        <ButtonLink href="/ustanovka-vorot" size="lg" variant="secondary">
          Установка ворот
        </ButtonLink>
      </div>
    </Container>
  );
}
