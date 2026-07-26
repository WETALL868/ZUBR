"""Точка входа веб-приложения."""

from __future__ import annotations

from contextlib import asynccontextmanager
from pathlib import Path

from fastapi import FastAPI, Request
from fastapi.responses import HTMLResponse, JSONResponse
from fastapi.staticfiles import StaticFiles

from app.core.config import BASE_DIR, settings
from app.core.db import session_scope
from app.core.logging_setup import get_logger, setup_logging
from app.services.settings_service import get_all_settings
from app.services.working_time import WorkSchedule
from app.web.routes import actions, pages
from app.web.templating import set_schedule, templates

logger = get_logger(__name__)

STARTUP_WARNINGS: list[str] = []


def run_migrations() -> None:
    """Приводит схему базы к актуальной версии (безопасно при повторном запуске)."""
    try:
        from alembic import command
        from alembic.config import Config

        cfg = Config(str(BASE_DIR / "alembic.ini"))
        cfg.set_main_option("script_location", str(BASE_DIR / "alembic"))
        command.upgrade(cfg, "head")
        logger.info("Миграции базы данных применены")
    except Exception as exc:
        logger.error("Не удалось применить миграции: %s: %s", type(exc).__name__, exc)
        STARTUP_WARNINGS.append(
            f"Не удалось применить миграции базы данных: {type(exc).__name__}. "
            "Проверьте файл базы и права доступа."
        )


@asynccontextmanager
async def lifespan(app: FastAPI):
    setup_logging()
    logger.info("Запуск приложения (режим только для чтения: %s)", settings.read_only_mode)
    run_migrations()

    STARTUP_WARNINGS.extend(settings.validate_startup())
    with session_scope() as db:
        data = get_all_settings(db)
    set_schedule(WorkSchedule.from_settings(data))

    if settings.demo_mode:
        logger.info("Включён демонстрационный режим")
    yield
    logger.info("Приложение остановлено")


app = FastAPI(
    title="Аналитика корпоративной почты",
    description="Локальный анализ клиентской переписки: получил ли клиент запрошенный результат.",
    version="1.0.0",
    lifespan=lifespan,
)

static_dir = Path(__file__).resolve().parent / "web" / "static"
app.mount("/static", StaticFiles(directory=str(static_dir)), name="static")

app.include_router(pages.router)
app.include_router(actions.router)


@app.get("/health", include_in_schema=False)
async def health() -> JSONResponse:
    return JSONResponse(
        {
            "status": "ok",
            "read_only": settings.read_only_mode,
            "demo_mode": settings.demo_mode,
            "warnings": STARTUP_WARNINGS,
        }
    )


@app.exception_handler(404)
async def not_found(request: Request, exc) -> HTMLResponse:
    return templates.TemplateResponse(
        request,
        "error.html",
        {"title": "Страница не найдена", "message": "Такой страницы нет.", "code": 404},
        status_code=404,
    )


@app.exception_handler(500)
async def server_error(request: Request, exc) -> HTMLResponse:  # pragma: no cover
    logger.exception("Внутренняя ошибка при обработке запроса %s", request.url.path)
    return templates.TemplateResponse(
        request,
        "error.html",
        {
            "title": "Внутренняя ошибка",
            "message": "Произошла ошибка. Подробности записаны в файл logs/app.log.",
            "code": 500,
        },
        status_code=500,
    )
