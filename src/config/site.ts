/**
 * Единый конфигурационный файл компании.
 * Все контактные данные, реквизиты и ключевые тексты сайта хранятся только здесь.
 * Замените плейсхтолдеры на реальные данные компании перед публикацией сайта.
 */

export const siteConfig = {
  // Основное
  companyName: "Премиум Ворота",
  companyShortName: "Премиум Ворота",
  legalName: "ИП Михайловский Виталий Геннадьевич",
  tagline: "Установка откатных ворот под ключ",

  // География (плейсхолдер — легко заменить, например, на «Москва и Московская область»)
  city: "[ГОРОД]",
  region: "[РЕГИОН]",
  cityAndRegion: "[ГОРОД И РЕГИОН]",
  serviceAreaNote: "Работаем в [ГОРОД] и в пределах [РЕГИОН]",

  // Контакты
  phone: "+7 (900) 000-00-00",
  phoneHref: "tel:+79000000000",
  email: "info@premium-vorota.example",
  telegram: "premium_vorota_bot",
  telegramUrl: "https://t.me/premium_vorota_bot",
  whatsapp: "79000000000",
  whatsappUrl: "https://wa.me/79000000000",
  vkUrl: "https://vk.com/premium_vorota",

  // Адрес и офис
  address: "[ГОРОД], ул. Примерная, д. 1, офис 101",
  officeCoordinates: {
    lat: 55.751244,
    lng: 37.618423,
  },

  // Часы работы
  workingHours: "Ежедневно с 8:00 до 21:00",
  workingHoursSchema: {
    opens: "08:00",
    closes: "21:00",
  },

  // Реквизиты индивидуального предпринимателя
  requisites: {
    fullName: "Михайловский Виталий Геннадьевич",
    legalAddress: "115561, Россия, г. Москва, Каширское шоссе, д. 128, корп. 2, кв. 446",
    inn: "772703413513",
    ogrnip: "322774600279660",
    bankName: 'АО «Альфа-Банк»',
    bik: "044525593",
    settlementAccount: "40802810101760002333",
    correspondentAccount: "30101810200000000593",
  },

  // Ключевые условия сервиса — задаются централизованно и переиспользуются на сайте
  warrantyYears: 2,
  manufacturingDays: "10–20 рабочих дней",
  installationDays: "1–3 дня",
  minPrice: 45000,
  minPriceLabel: "от 45 000 ₽",

  // Соцсети
  social: {
    vk: "https://vk.com/premium_vorota",
    telegram: "https://t.me/premium_vorota_bot",
    whatsapp: "https://wa.me/79000000000",
  },

  // SEO по умолчанию
  seo: {
    defaultTitle: "Премиум Ворота — установка откатных ворот под ключ в [ГОРОД И РЕГИОН]",
    defaultDescription:
      "Замер, изготовление, монтаж и автоматизация откатных ворот в [ГОРОД И РЕГИОН]. Также устанавливаем секционные и распашные ворота, подключаем автоматику и выполняем ремонт.",
    siteUrl: "https://premium-vorota.example",
    ogImageAlt: "Премиум Ворота — установка откатных ворот под ключ",
  },

  // Карта (можно заменить на Яндекс.Карты или 2ГИС)
  map: {
    provider: "yandex" as "yandex" | "2gis",
    yandexEmbedUrl:
      "https://yandex.ru/map-widget/v1/?ll=37.618423%2C55.751244&z=14&pt=37.618423,55.751244,pm2rdm",
    twoGisEmbedUrl: "https://2gis.ru/widget?center=37.618423,55.751244&zoom=14",
  },

  // Аналитика — идентификаторы задаются через переменные окружения
  analytics: {
    yandexMetrikaId: process.env.NEXT_PUBLIC_YANDEX_METRIKA_ID ?? "",
    googleAnalyticsId: process.env.NEXT_PUBLIC_GA_ID ?? "",
  },

  // Реальные бренды автоматики, с которыми компания официально работает.
  // Пусто по умолчанию — заполните перед публикацией, если сотрудничество подтверждено.
  automationBrands: [] as string[],

  // Услуга аварийного открытия ворот — включите, только если компания реально её оказывает.
  emergencyOpeningAvailable: false,

  // Блоки доверия — включайте только когда реальные материалы готовы (см. IMAGE_REQUIREMENTS.md)
  trust: {
    showInstallerPhoto: true,
    showVanPhoto: true,
    showWarrantyDocument: true,
    showObjectPhoto: true,
    showExternalReviews: false,
  },
} as const;

export type SiteConfig = typeof siteConfig;
