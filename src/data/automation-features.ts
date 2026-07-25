export interface AutomationFeature {
  title: string;
  description: string;
}

export const automationFeatures: AutomationFeature[] = [
  {
    title: "Открытие с пульта",
    description: "Компактный пульт для въезда без выхода из автомобиля.",
  },
  {
    title: "Управление со смартфона",
    description:
      "Доступно при использовании совместимого оборудования — уточняем индивидуально под модель привода.",
  },
  {
    title: "Фотоэлементы безопасности",
    description: "Останавливают движение створки при появлении препятствия в проёме.",
  },
  {
    title: "Сигнальная лампа",
    description: "Предупреждает о движении ворот — особенно важно на проезжей части.",
  },
  {
    title: "Ручная разблокировка",
    description: "Позволяет открыть ворота вручную при отключении электричества.",
  },
  {
    title: "Настройка усилия и конечных положений",
    description: "Привод останавливается точно в заданных точках без рывков и перегрузки.",
  },
];

export interface AutomationSection {
  title: string;
  description: string;
}

export const automationByGateType: Record<string, AutomationSection> = {
  otkatnye: {
    title: "Автоматика для откатных ворот",
    description:
      "Привод перемещает створку вдоль направляющей. Подбираем мощность под вес и ширину проёма.",
  },
  raspashnye: {
    title: "Автоматика для распашных ворот",
    description:
      "Линейные или подземные приводы для одной или двух створок с синхронным управлением.",
  },
  sektsionnye: {
    title: "Автоматика для секционных ворот",
    description:
      "Потолочный привод поднимает полотно вертикально, с плавным стартом и остановкой.",
  },
};
