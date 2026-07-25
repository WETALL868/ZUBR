export interface NavItem {
  label: string;
  /** Короткая подпись для шапки сайта, где ширина меню ограничена. */
  shortLabel?: string;
  href: string;
}

export const mainNav: NavItem[] = [
  { label: "Установка ворот", shortLabel: "Установка", href: "/ustanovka-vorot" },
  { label: "Откатные ворота", shortLabel: "Откатные", href: "/otkatnye-vorota" },
  { label: "Секционные ворота", shortLabel: "Секционные", href: "/sektsionnye-vorota" },
  { label: "Распашные ворота", shortLabel: "Распашные", href: "/raspashnye-vorota" },
  { label: "Автоматика", href: "/avtomatika" },
  { label: "Ремонт ворот", shortLabel: "Ремонт", href: "/remont-vorot" },
  { label: "Портфолио", href: "/portfolio" },
  { label: "О компании", href: "/o-kompanii" },
  { label: "Контакты", href: "/kontakty" },
];

export const gateTypesNav: NavItem[] = [
  { label: "Откатные ворота", href: "/otkatnye-vorota" },
  { label: "Секционные ворота", href: "/sektsionnye-vorota" },
  { label: "Распашные ворота", href: "/raspashnye-vorota" },
  { label: "Автоматика", href: "/avtomatika" },
];

export const footerLegalNav: NavItem[] = [
  { label: "Политика конфиденциальности", href: "/politika-konfidentsialnosti" },
  {
    label: "Согласие на обработку персональных данных",
    href: "/soglasie-na-obrabotku-personalnyh-dannyh",
  },
];
