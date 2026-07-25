export function PriceNote({ className }: { className?: string }) {
  return (
    <p className={className ?? "text-sm text-text-muted"}>
      Стоимость рассчитывается индивидуально и зависит от ширины проёма, типа заполнения, фундамента,
      автоматики и условий монтажа.
    </p>
  );
}
