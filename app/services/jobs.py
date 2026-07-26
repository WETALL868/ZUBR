"""Простой менеджер фоновых задач (синхронизация, анализ).

Хранит состояние в памяти процесса: приложение локальное и однопользовательское.
"""

from __future__ import annotations

import threading
from dataclasses import dataclass, field
from datetime import datetime
from typing import Any, Callable

from app.core.logging_setup import get_logger

logger = get_logger(__name__)


@dataclass
class JobState:
    name: str
    running: bool = False
    started_at: datetime | None = None
    finished_at: datetime | None = None
    status: str = "idle"          # idle | running | ok | error
    message: str = ""
    result: dict[str, Any] = field(default_factory=dict)


class JobManager:
    def __init__(self) -> None:
        self._jobs: dict[str, JobState] = {}
        self._lock = threading.Lock()

    def get(self, name: str) -> JobState:
        with self._lock:
            return self._jobs.setdefault(name, JobState(name=name))

    def is_running(self, name: str) -> bool:
        return self.get(name).running

    def any_running(self) -> bool:
        with self._lock:
            return any(job.running for job in self._jobs.values())

    def run(self, name: str, func: Callable[[], dict[str, Any]], description: str = "") -> bool:
        """Запускает задачу в отдельном потоке. Возвращает False, если уже выполняется."""
        job = self.get(name)
        with self._lock:
            if job.running:
                return False
            job.running = True
            job.status = "running"
            job.started_at = datetime.now()
            job.finished_at = None
            job.message = description or "Выполняется…"
            job.result = {}

        def _worker() -> None:
            try:
                result = func() or {}
                job.result = result
                job.status = "ok"
                job.message = result.get("message", "Готово")
            except Exception as exc:
                logger.exception("Фоновая задача «%s» завершилась ошибкой", name)
                job.status = "error"
                job.message = f"{type(exc).__name__}: {exc}"
            finally:
                job.running = False
                job.finished_at = datetime.now()

        threading.Thread(target=_worker, name=f"job-{name}", daemon=True).start()
        return True

    def snapshot(self) -> dict[str, dict[str, Any]]:
        with self._lock:
            return {
                name: {
                    "running": job.running,
                    "status": job.status,
                    "message": job.message,
                    "started_at": job.started_at.isoformat() if job.started_at else None,
                    "finished_at": job.finished_at.isoformat() if job.finished_at else None,
                    "result": job.result,
                }
                for name, job in self._jobs.items()
            }


jobs = JobManager()
