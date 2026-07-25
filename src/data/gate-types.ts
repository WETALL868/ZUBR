import type { GateType } from "@/types";

export const gateTypes: GateType[] = [
  {
    slug: "otkatnye-vorota",
    href: "/otkatnye-vorota",
    title: "Откатные ворота",
    shortDescription:
      "Не занимают место при открывании, легко автоматизируются и подходят для широких проёмов.",
    image: "/images/gate-sliding-card.svg",
    imageAlt: "Откатные ворота у частного дома",
    featured: true,
    isLandingPage: true,
  },
  {
    slug: "sektsionnye-vorota",
    href: "/sektsionnye-vorota",
    title: "Секционные ворота",
    shortDescription:
      "Оптимальное решение для гаража: экономят пространство и держат тепло.",
    image: "/images/gate-sectional-card.svg",
    imageAlt: "Секционные гаражные ворота",
    isLandingPage: true,
  },
  {
    slug: "raspashnye-vorota",
    href: "/raspashnye-vorota",
    title: "Распашные ворота",
    shortDescription:
      "Простая и надёжная конструкция. Подходят для участков с готовыми столбами.",
    image: "/images/gate-swing-card.svg",
    imageAlt: "Распашные ворота на участке",
    isLandingPage: true,
  },
  {
    slug: "avtomatika",
    href: "/avtomatika",
    title: "Автоматические ворота",
    shortDescription:
      "Автоматизация любых ворот: приводы, пульты, фотоэлементы и настройка.",
    image: "/images/gate-automation-card.svg",
    imageAlt: "Привод автоматики на воротах",
    isLandingPage: true,
  },
  {
    slug: "garazhnye-vorota",
    href: "/sektsionnye-vorota",
    title: "Гаражные ворота",
    shortDescription:
      "Секционные и распашные конструкции для отдельно стоящих гаражей.",
    image: "/images/gate-garage-card.svg",
    imageAlt: "Гаражные ворота",
  },
  {
    slug: "promyshlennye-vorota",
    href: "/ustanovka-vorot",
    title: "Промышленные ворота",
    shortDescription:
      "Усиленные конструкции для складов, производств и коммерческих объектов.",
    image: "/images/gate-industrial-card.svg",
    imageAlt: "Промышленные ворота на складе",
  },
];

export const featuredGateType = gateTypes.find((g) => g.featured)!;
