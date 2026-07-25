export interface NavItem {
  label: string;
  href: string;
}

/** Компактное меню в шапке сайта. «Установка ворот» открывается отдельным выпадающим списком. */
export const headerNav: NavItem[] = [
  { label: "Главная", href: "/" },
  { label: "Автоматика", href: "/avtomatika" },
  { label: "Ремонт", href: "/remont-vorot" },
  { label: "Портфолио", href: "/portfolio" },
  { label: "Контакты", href: "/kontakty" },
];

/** Выпадающее меню пункта «Установка ворот» в шапке. */
export const installationDropdown: NavItem[] = [
  { label: "Все услуги по установке", href: "/ustanovka-vorot" },
  { label: "Откатные ворота", href: "/otkatnye-vorota" },
  { label: "Секционные ворота", href: "/sektsionnye-vorota" },
  { label: "Распашные ворота", href: "/raspashnye-vorota" },
  { label: "Автоматические ворота", href: "/avtomatika" },
];

/** Виды ворот — колонка в подвале сайта. */
export const gateTypesNav: NavItem[] = [
  { label: "Откатные ворота", href: "/otkatnye-vorota" },
  { label: "Секционные ворота", href: "/sektsionnye-vorota" },
  { label: "Распашные ворота", href: "/raspashnye-vorota" },
  { label: "Автоматика", href: "/avtomatika" },
];

/** Компания — колонка в подвале сайта (включает пункты, убранные из компактной шапки). */
export const footerCompanyNav: NavItem[] = [
  { label: "Установка ворот", href: "/ustanovka-vorot" },
  { label: "Автоматика", href: "/avtomatika" },
  { label: "Ремонт ворот", href: "/remont-vorot" },
  { label: "Портфолио", href: "/portfolio" },
  { label: "О компании", href: "/o-kompanii" },
  { label: "Контакты", href: "/kontakty" },
];

export const footerLegalNav: NavItem[] = [
  { label: "Политика конфиденциальности", href: "/politika-konfidentsialnosti" },
  {
    label: "Согласие на обработку персональных данных",
    href: "/soglasie-na-obrabotku-personalnyh-dannyh",
  },
];
