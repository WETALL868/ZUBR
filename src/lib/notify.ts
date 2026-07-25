import nodemailer from "nodemailer";
import type { LeadPayload } from "./validation";

const sourceLabels: Record<LeadPayload["source"], string> = {
  quiz: "Расчёт стоимости (квиз)",
  measurement: "Вызов замерщика",
  contact: "Форма обратной связи",
  "final-cta": "Финальная форма расчёта",
  repair: "Вызов мастера по ремонту",
};

function formatLeadLines(lead: LeadPayload): string[] {
  const lines = [`Источник: ${sourceLabels[lead.source]}`, `Имя: ${lead.name}`, `Телефон: ${lead.phone}`];

  if ("gateType" in lead && lead.gateType) lines.push(`Тип ворот: ${lead.gateType}`);
  if ("width" in lead && lead.width) lines.push(`Ширина проёма: ${lead.width}`);
  if ("height" in lead && lead.height) lines.push(`Высота проёма: ${lead.height}`);
  if ("automationNeeded" in lead && lead.automationNeeded)
    lines.push(`Автоматика: ${lead.automationNeeded}`);
  if ("fillType" in lead && lead.fillType) lines.push(`Заполнение: ${lead.fillType}`);
  if ("city" in lead && lead.city) lines.push(`Город/район: ${lead.city}`);
  if ("address" in lead && lead.address) lines.push(`Адрес объекта: ${lead.address}`);
  if ("issue" in lead && lead.issue) lines.push(`Неисправность: ${lead.issue}`);
  if ("photosCount" in lead && lead.photosCount) lines.push(`Приложено фото: ${lead.photosCount}`);
  if ("comment" in lead && lead.comment) lines.push(`Комментарий: ${lead.comment}`);

  return lines;
}

export function formatLeadText(lead: LeadPayload): string {
  return ["Новая заявка с сайта", "", ...formatLeadLines(lead)].join("\n");
}

interface EmailAttachment {
  filename: string;
  content: Buffer;
}

export async function sendLeadEmail(lead: LeadPayload, attachments: EmailAttachment[] = []): Promise<boolean> {
  const { LEADS_EMAIL, SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD } = process.env;

  if (!LEADS_EMAIL || !SMTP_HOST || !SMTP_USER || !SMTP_PASSWORD) {
    return false;
  }

  const transporter = nodemailer.createTransport({
    host: SMTP_HOST,
    port: Number(SMTP_PORT) || 587,
    secure: Number(SMTP_PORT) === 465,
    auth: { user: SMTP_USER, pass: SMTP_PASSWORD },
  });

  await transporter.sendMail({
    from: `"Сайт — заявки" <${SMTP_USER}>`,
    to: LEADS_EMAIL,
    replyTo: SMTP_USER,
    subject: `Новая заявка: ${sourceLabels[lead.source]}`,
    text: formatLeadText(lead),
    attachments,
  });

  return true;
}

export async function sendLeadTelegram(lead: LeadPayload): Promise<boolean> {
  const { TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID } = process.env;

  if (!TELEGRAM_BOT_TOKEN || !TELEGRAM_CHAT_ID) {
    return false;
  }

  const text = formatLeadText(lead);

  const response = await fetch(`https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ chat_id: TELEGRAM_CHAT_ID, text }),
  });

  return response.ok;
}
