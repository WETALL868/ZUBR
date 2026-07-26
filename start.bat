@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
title Аналитика корпоративной почты
cd /d "%~dp0"

echo ============================================================
echo   Аналитика корпоративной почты - запуск
echo   Режим: только чтение (письма не изменяются)
echo ============================================================
echo.

rem --- 1. Проверка Python ---
set PYTHON_CMD=
py -3 --version >nul 2>&1 && set PYTHON_CMD=py -3
if "!PYTHON_CMD!"=="" (
    python --version >nul 2>&1 && set PYTHON_CMD=python
)
if "!PYTHON_CMD!"=="" (
    echo [ОШИБКА] Python не найден.
    echo Установите Python 3.12 с https://www.python.org/downloads/
    echo При установке обязательно отметьте "Add Python to PATH".
    echo.
    pause
    exit /b 1
)
echo [1/5] Python найден.

rem --- 2. Виртуальное окружение ---
if not exist ".venv\Scripts\python.exe" (
    echo [2/5] Создание виртуального окружения (один раз, ~1 минута)...
    !PYTHON_CMD! -m venv .venv
    if errorlevel 1 (
        echo [ОШИБКА] Не удалось создать виртуальное окружение.
        pause
        exit /b 1
    )
    set FIRST_RUN=1
) else (
    echo [2/5] Виртуальное окружение найдено.
)

rem --- 3. Зависимости ---
if defined FIRST_RUN (
    echo [3/5] Установка зависимостей, подождите...
    .venv\Scripts\python.exe -m pip install --upgrade pip --quiet
    .venv\Scripts\python.exe -m pip install -r requirements.txt --quiet
    if errorlevel 1 (
        echo [ОШИБКА] Не удалось установить зависимости. Проверьте интернет.
        pause
        exit /b 1
    )
) else (
    .venv\Scripts\python.exe -c "import fastapi, sqlalchemy, openai, openpyxl" >nul 2>&1
    if errorlevel 1 (
        echo [3/5] Доустановка недостающих зависимостей...
        .venv\Scripts\python.exe -m pip install -r requirements.txt --quiet
    ) else (
        echo [3/5] Зависимости на месте.
    )
)

rem --- 4. Файл настроек ---
if not exist ".env" (
    if exist ".env.example" (
        copy ".env.example" ".env" >nul
        echo [4/5] Создан файл .env из примера.
        echo.
        echo ВНИМАНИЕ: откройте файл .env в Блокноте и заполните:
        echo   YANDEX_EMAIL         - ваш адрес почты
        echo   YANDEX_APP_PASSWORD  - пароль приложения Яндекса
        echo   OPENAI_API_KEY       - ключ OpenAI (можно оставить пустым)
        echo.
        echo Без этих настроек будет доступен только демонстрационный режим.
        echo.
        pause
    ) else (
        echo [ПРЕДУПРЕЖДЕНИЕ] Нет файлов .env и .env.example.
    )
) else (
    echo [4/5] Файл .env найден.
)

rem --- 5. Запуск ---
echo [5/5] Запуск программы...
echo.
echo Интерфейс откроется в браузере. Для остановки закройте это окно
echo или нажмите Ctrl+C.
echo.
.venv\Scripts\python.exe run.py %*

if errorlevel 1 (
    echo.
    echo [ОШИБКА] Программа завершилась с ошибкой.
    echo Подробности в файле logs\app.log
    pause
)
endlocal
