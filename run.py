#!/usr/bin/env python3
"""Запуск локального веб-приложения.

Использование:
    python run.py                 обычный запуск
    python run.py --demo          загрузить демо-данные и запустить
    python run.py --no-browser    не открывать браузер
    python run.py --port 8080     другой порт
"""

from __future__ import annotations

import argparse
import sys
import threading
import time
import webbrowser
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(BASE_DIR))


def open_browser_later(url: str, delay: float = 1.5) -> None:
    def _open() -> None:
        time.sleep(delay)
        try:
            webbrowser.open(url)
        except Exception:
            pass

    threading.Thread(target=_open, daemon=True).start()


def main() -> int:
    parser = argparse.ArgumentParser(description="Аналитика корпоративной почты")
    parser.add_argument("--host", default=None, help="адрес (по умолчанию из .env)")
    parser.add_argument("--port", type=int, default=None, help="порт (по умолчанию из .env)")
    parser.add_argument("--demo", action="store_true", help="загрузить демонстрационные данные")
    parser.add_argument("--no-browser", action="store_true", help="не открывать браузер")
    parser.add_argument("--reload", action="store_true", help="автоперезагрузка (для разработки)")
    args = parser.parse_args()

    if not (BASE_DIR / ".env").exists():
        example = BASE_DIR / ".env.example"
        print("ВНИМАНИЕ: файл .env не найден.")
        if example.exists():
            print(f"Скопируйте {example.name} в .env и заполните настройки подключения.")
        print("Программа запустится, но синхронизация почты будет недоступна.\n")

    import uvicorn

    from app.core.config import settings
    from app.core.logging_setup import setup_logging

    setup_logging()

    host = args.host or settings.app_host
    port = args.port or settings.app_port

    if args.demo:
        from app.main import run_migrations
        from app.core.db import session_scope
        from app.services.analysis_service import analyze_threads
        from app.services.demo_data import load_demo_data

        run_migrations()
        print("Загрузка демонстрационных данных…")
        with session_scope() as db:
            result = load_demo_data(db)
        with session_scope() as db:
            stats = analyze_threads(db, force=True)
        print(
            f"Загружено цепочек: {result['threads']}, писем: {result['messages']}, "
            f"проанализировано: {stats.analyzed}"
        )

    url = f"http://{host}:{port}/"
    print("=" * 62)
    print("  Аналитика корпоративной почты — режим только для чтения")
    print(f"  Интерфейс: {url}")
    print("  Остановка: Ctrl+C")
    print("=" * 62)

    if settings.open_browser and not args.no_browser:
        open_browser_later(url)

    uvicorn.run(
        "app.main:app",
        host=host,
        port=port,
        reload=args.reload,
        log_level=settings.log_level.lower(),
        access_log=False,
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
