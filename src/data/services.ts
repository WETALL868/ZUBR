import type { Service } from "@/types";

export const services: Service[] = [
  {
    slug: "ustanovka-vorot",
    title: "Установка ворот под ключ",
    description: "Замер, изготовление, монтаж и настройка автоматики за один заказ.",
  },
  {
    slug: "izgotovlenie-po-razmeram",
    title: "Изготовление ворот по размерам заказчика",
    description: "Подбираем конструкцию и заполнение под конкретный проём и участок.",
  },
  {
    slug: "ustanovka-avtomatiki",
    title: "Установка автоматики",
    description: "Монтаж и настройка приводов, пультов и элементов безопасности.",
  },
  {
    slug: "vyezd-zamershchika",
    title: "Выезд специалиста на замер",
    description: "Проверка проёма, грунта и условий монтажа перед изготовлением.",
  },
  {
    slug: "raschet-stoimosti",
    title: "Расчёт предварительной стоимости",
    description: "Ориентировочный расчёт по параметрам ворот до выезда на объект.",
  },
  {
    slug: "remont-vorot",
    title: "Ремонт ворот",
    description: "Диагностика и ремонт автоматики и механики любых производителей.",
  },
];

export const serviceAreas: string[] = [
  "[Район 1]",
  "[Район 2]",
  "[Район 3]",
  "[Район 4]",
  "[Прилегающие населённые пункты]",
];
