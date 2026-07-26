@echo off
chcp 65001 >nul
setlocal
title Обновление программы
cd /d "%~dp0"

echo ============================================================
echo   Обновление программы "Аналитика корпоративной почты"
echo ============================================================
echo.

if not exist ".venv\Scripts\python.exe" (
    echo [ОШИБКА] Виртуальное окружение не найдено. Сначала запустите start.bat.
    pause
    exit /b 1
)

echo [1/4] Резервная копия базы данных...
if not exist "backup" mkdir "backup"
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value 2^>nul') do set DT=%%I
if not defined DT set DT=00000000000000
set STAMP=%DT:~0,8%_%DT:~8,6%
if exist "data\mail_assistant.db" (
    copy "data\mail_assistant.db" "backup\mail_assistant_%STAMP%.db" >nul
    echo     Создана копия: backup\mail_assistant_%STAMP%.db
) else (
    echo     База данных ещё не создана - копировать нечего.
)

echo [2/4] Обновление исходного кода...
git --version >nul 2>&1
if errorlevel 1 (
    echo     Git не установлен - обновите файлы программы вручную.
) else (
    git pull
)

echo [3/4] Обновление зависимостей...
.venv\Scripts\python.exe -m pip install -r requirements.txt --quiet --upgrade

echo [4/4] Обновление структуры базы данных...
.venv\Scripts\python.exe -m alembic upgrade head
if errorlevel 1 (
    echo [ОШИБКА] Не удалось обновить базу данных.
    echo Восстановите копию из папки backup и обратитесь за помощью.
    pause
    exit /b 1
)

echo.
echo Обновление завершено успешно. Запустите start.bat.
pause
endlocal
