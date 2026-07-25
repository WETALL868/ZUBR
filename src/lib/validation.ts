import { z } from "zod";

export const phoneSchema = z
  .string()
  .trim()
  .min(1, "Укажите номер телефона")
  .refine(
    (v) => {
      const digits = v.replace(/\D/g, "");
      return digits.length === 11 && (digits.startsWith("7") || digits.startsWith("8"));
    },
    { message: "Введите корректный номер телефона" },
  );

export const nameSchema = z
  .string()
  .trim()
  .min(2, "Укажите имя")
  .max(60, "Слишком длинное имя");

export const consentSchema = z.literal(true, {
  message: "Необходимо согласие на обработку персональных данных",
});

/** Базовые поля, общие для всех форм заявок. */
const baseLeadFields = {
  name: nameSchema,
  phone: phoneSchema,
  consent: consentSchema,
  // Honeypot-поле — должно оставаться пустым. Заполняется ботами.
  company: z.string().max(0).optional().default(""),
  // Время рендера формы в мс от epoch — используется для базовой антиспам-проверки.
  formRenderedAt: z.number().optional(),
};

export const quizLeadSchema = z.object({
  ...baseLeadFields,
  source: z.literal("quiz"),
  gateType: z.string().min(1, "Выберите тип ворот"),
  width: z.string().optional().default(""),
  height: z.string().optional().default(""),
  automationNeeded: z.string().optional().default(""),
  fillType: z.string().optional().default(""),
  city: z.string().optional().default(""),
});

export const measurementLeadSchema = z.object({
  ...baseLeadFields,
  source: z.literal("measurement"),
  gateType: z.string().optional().default(""),
  comment: z.string().max(600).optional().default(""),
});

export const contactLeadSchema = z.object({
  ...baseLeadFields,
  source: z.literal("contact"),
  gateType: z.string().optional().default(""),
  comment: z.string().max(600).optional().default(""),
});

export const finalCtaLeadSchema = z.object({
  ...baseLeadFields,
  source: z.literal("final-cta"),
  gateType: z.string().optional().default(""),
  comment: z.string().max(600).optional().default(""),
});

export const repairLeadSchema = z.object({
  ...baseLeadFields,
  source: z.literal("repair"),
  gateType: z.string().min(1, "Выберите тип ворот"),
  issue: z.string().trim().min(5, "Опишите неисправность").max(800),
  address: z.string().trim().min(3, "Укажите адрес объекта").max(300),
  photosCount: z.number().optional().default(0),
});

export const leadSchema = z.discriminatedUnion("source", [
  quizLeadSchema,
  measurementLeadSchema,
  contactLeadSchema,
  finalCtaLeadSchema,
  repairLeadSchema,
]);

export type LeadPayload = z.infer<typeof leadSchema>;
export type QuizLeadPayload = z.infer<typeof quizLeadSchema>;
export type RepairLeadPayload = z.infer<typeof repairLeadSchema>;
