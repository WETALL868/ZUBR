/**
 * Простое ограничение частоты запросов в памяти процесса.
 * Подходит для базовой защиты от спама на небольшом сайте.
 * При развёртывании на нескольких инстансах рассмотрите внешнее хранилище (Redis).
 */

const WINDOW_MS = 60_000;
const MAX_REQUESTS_PER_WINDOW = 5;

const hits = new Map<string, number[]>();

export function isRateLimited(key: string): boolean {
  const now = Date.now();
  const timestamps = (hits.get(key) ?? []).filter((t) => now - t < WINDOW_MS);

  if (timestamps.length >= MAX_REQUESTS_PER_WINDOW) {
    hits.set(key, timestamps);
    return true;
  }

  timestamps.push(now);
  hits.set(key, timestamps);

  if (hits.size > 5000) {
    const cutoff = now - WINDOW_MS;
    for (const [k, v] of hits) {
      if (v.every((t) => t < cutoff)) hits.delete(k);
    }
  }

  return false;
}
