"""Общие фикстуры тестов: изолированная база в памяти."""

from __future__ import annotations

import os
import sys
from pathlib import Path

import pytest

BASE_DIR = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(BASE_DIR))

# База для тестов создаётся во временном файле — рабочая база не затрагивается.
os.environ.setdefault("DATABASE_URL", "sqlite:///./data/test_mail_assistant.db")
os.environ.setdefault("DEMO_MODE", "true")
os.environ.setdefault("AI_ENABLED", "false")
os.environ.setdefault("AI_TEST_MODE", "true")
os.environ.setdefault("YANDEX_EMAIL", "")
os.environ.setdefault("YANDEX_APP_PASSWORD", "")
os.environ.setdefault("OPENAI_API_KEY", "")


@pytest.fixture(scope="session")
def engine(tmp_path_factory):
    from sqlalchemy import create_engine

    from app.core.db import Base
    import app.models  # noqa: F401

    db_path = tmp_path_factory.mktemp("db") / "test.db"
    eng = create_engine(f"sqlite:///{db_path}", connect_args={"check_same_thread": False})
    Base.metadata.create_all(eng)
    return eng


@pytest.fixture
def db(engine):
    from sqlalchemy.orm import sessionmaker

    from app.core.db import Base

    Base.metadata.drop_all(engine)
    Base.metadata.create_all(engine)
    Session = sessionmaker(bind=engine, expire_on_commit=False, future=True)
    session = Session()
    try:
        yield session
    finally:
        session.close()
