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


def setup_console() -> None:
    """Корректный вывод кириллицы в консоли Windows при любой кодовой странице."""
    for stream in (sys.stdout, sys.stderr):
        try:
            stream.reconfigure(encoding="utf-8", errors="replace")
        except (AttributeError, ValueError):
            pass


def check_python_version() -> bool:
    if sys.version_info < (3, 11):
        print("ОШИБКА: требуется Python 3.11 или новее.")
        print(f"Установлена версия {sys.version.split()[0]}.")
        print("Скачайте свежую версию: https://www.python.org/downloads/")
        return False
    return True


def ensure_env_file() -> bool:
    """Создаёт .env из примера. Возвращает True, если файл только что создан."""
    env_file = BASE_DIR / ".env"
    example = BASE_DIR / ".env.example"
    if env_file.exists():
        return False
    if not example.exists():
        print("ПРЕДУПРЕЖДЕНИЕ: не найдены файлы .env и .env.example.")
        return False
    env_file.write_bytes(example.read_bytes())
    print("Создан файл настроек .env (копия .env.example).")
    print()
    print("  Чтобы работать с реальной почтой, откройте .env в Блокноте и заполните:")
    print("    YANDEX_EMAIL         — ваш адрес корпоративной почты")
    print("    YANDEX_APP_PASSWORD  — пароль приложения Яндекса (не основной пароль!)")
    print("    OPENAI_API_KEY       — ключ OpenAI (можно оставить пустым)")
    print()
    print("  Без этих настроек доступен демонстрационный режим —")
    print("  раздел «Демо-режим» в интерфейсе программы.")
    print()
    return True


def port_is_busy(host: str, port: int) -> bool:
    """Проверяет, занят ли адрес, до запуска сервера."""
    import socket

    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        try:
            sock.bind((host, port))
        except OSError:
            return True
    return False


def report_busy_port(host: str, port: int, url: str) -> None:
    print()
    print(f"ОШИБКА: порт {port} уже занят.")
    print()
    print("Скорее всего, программа уже запущена. Что делать:")
    print(f"  1. Откройте в браузере {url} — возможно, всё уже работает.")
    print("  2. Либо запустите stop.bat, чтобы закрыть предыдущую копию.")
    print("  3. Либо укажите другой порт: измените APP_PORT в файле .env")
    print("     или запустите с параметром --port 8080")
    print()


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

    setup_console()
    if not check_python_version():
        return 1

    print("=" * 62)
    print("  Аналитика корпоративной почты")
    print("  Режим только для чтения: письма не изменяются")
    print("=" * 62)
    print()

    ensure_env_file()

    try:
        import uvicorn
    except ImportError:
        print("ОШИБКА: не установлены зависимости программы.")
        print("Запустите start.bat — он установит всё необходимое.")
        print("Вручную: .venv\\Scripts\\python.exe -m pip install -r requirements.txt")
        return 1

    from app.core.config import settings
    from app.core.logging_setup import setup_logging

    setup_logging()

    host = args.host or settings.app_host
    port = args.port or settings.app_port

    if port_is_busy(host, port):
        report_busy_port(host, port, f"http://{host}:{port}/")
        return 1

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
    print(f"  Интерфейс открыт: {url}")
    print("  Остановка: Ctrl+C или закройте это окно")
    print("=" * 62)

    if settings.open_browser and not args.no_browser:
        open_browser_later(url)

    try:
        uvicorn.run(
            "app.main:app",
            host=host,
            port=port,
            reload=args.reload,
            log_level=settings.log_level.lower(),
            access_log=False,
        )
    except KeyboardInterrupt:
        print("\nПрограмма остановлена.")
        return 0
    except OSError as exc:
        # Чаще всего порт уже занят другой копией программы
        print()
        print(f"ОШИБКА: не удалось занять адрес {host}:{port}.")
        print(f"Причина: {exc}")
        print()
        print("Что делать:")
        print("  1. Возможно, программа уже запущена — проверьте другие окна")
        print(f"     или откройте в браузере {url}")
        print("  2. Запустите stop.bat, чтобы закрыть предыдущую копию.")
        print("  3. Либо укажите другой порт: измените APP_PORT в файле .env")
        print("     или запустите с параметром --port 8080")
        return 1
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except SystemExit:
        raise
    except Exception as exc:  # ошибка не должна закрывать окно без объяснения
        setup_console()
        print()
        print(f"НЕПРЕДВИДЕННАЯ ОШИБКА: {type(exc).__name__}: {exc}")
        print()
        print("Подробности записаны в файл logs\\app.log")
        print("Полная информация об ошибке:")
        print()
        import traceback

        traceback.print_exc()
        raise SystemExit(1)
