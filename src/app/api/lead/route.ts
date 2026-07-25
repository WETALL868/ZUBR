import { NextRequest, NextResponse } from "next/server";
import { leadSchema } from "@/lib/validation";
import { sendLeadEmail, sendLeadTelegram, formatLeadText } from "@/lib/notify";
import { isRateLimited } from "@/lib/rate-limit";

export const runtime = "nodejs";

const MIN_SUBMIT_TIME_MS = 1500;
const MAX_ATTACHMENTS = 5;
const MAX_ATTACHMENT_SIZE = 8 * 1024 * 1024;

function getClientIp(request: NextRequest): string {
  const forwarded = request.headers.get("x-forwarded-for");
  return forwarded?.split(",")[0]?.trim() || "unknown";
}

export async function POST(request: NextRequest) {
  const ip = getClientIp(request);

  if (isRateLimited(ip)) {
    return NextResponse.json(
      { ok: false, message: "Слишком много запросов. Попробуйте немного позже." },
      { status: 429 },
    );
  }

  let formData: FormData;
  try {
    formData = await request.formData();
  } catch {
    return NextResponse.json({ ok: false, message: "Некорректный формат запроса." }, { status: 400 });
  }

  const raw: Record<string, unknown> = {};
  for (const [key, value] of formData.entries()) {
    if (key === "photos") continue;
    if (key === "consent") {
      raw[key] = value === "true" || value === "on";
      continue;
    }
    if (key === "formRenderedAt") {
      raw[key] = Number(value);
      continue;
    }
    raw[key] = value;
  }

  // Honeypot: если скрытое поле заполнено — это бот.
  if (typeof raw.company === "string" && raw.company.length > 0) {
    return NextResponse.json({ ok: true });
  }

  // Форма отправлена слишком быстро для человека — вероятно, бот.
  if (typeof raw.formRenderedAt === "number" && raw.formRenderedAt > 0) {
    if (Date.now() - raw.formRenderedAt < MIN_SUBMIT_TIME_MS) {
      return NextResponse.json({ ok: true });
    }
  }

  const photoFiles = formData
    .getAll("photos")
    .filter((f): f is File => f instanceof File && f.size > 0)
    .slice(0, MAX_ATTACHMENTS);

  raw.photosCount = photoFiles.length;

  const parsed = leadSchema.safeParse(raw);
  if (!parsed.success) {
    const firstIssue = parsed.error.issues[0];
    return NextResponse.json(
      { ok: false, message: firstIssue?.message ?? "Проверьте правильность заполнения формы." },
      { status: 400 },
    );
  }

  const lead = parsed.data;

  const attachments = await Promise.all(
    photoFiles
      .filter((file) => file.size <= MAX_ATTACHMENT_SIZE)
      .map(async (file) => ({
        filename: file.name || "photo.jpg",
        content: Buffer.from(await file.arrayBuffer()),
      })),
  );

  const [emailSent, telegramSent] = await Promise.all([
    sendLeadEmail(lead, attachments).catch(() => false),
    sendLeadTelegram(lead).catch(() => false),
  ]);

  if (!emailSent && !telegramSent) {
    // Каналы не настроены (нет переменных окружения) — фиксируем заявку в логах сервера,
    // чтобы форма всё равно считалась рабочей в режиме разработки.
    console.warn("[lead] Каналы уведомлений не настроены. Получена заявка:\n" + formatLeadText(lead));
  }

  return NextResponse.json({ ok: true });
}
