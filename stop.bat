@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
title Остановка программы
cd /d "%~dp0"

echo Остановка программы "Аналитика корпоративной почты"...
echo.

set PORT=8000
if exist ".env" (
    for /f "usebackq tokens=2 delims==" %%v in (`findstr /b /c:"APP_PORT" ".env"`) do set PORT=%%v
)
set PORT=%PORT: =%
echo Проверяется порт !PORT!

set STOPPED=0
for /f "tokens=5" %%p in ('netstat -ano ^| findstr /r /c:"TCP.*:!PORT! .*LISTENING"') do (
    echo Завершение процесса PID %%p
    taskkill /PID %%p /F >nul 2>&1
    set STOPPED=1
)

if "!STOPPED!"=="0" (
    echo Запущенная программа на порту !PORT! не найдена.
    echo Возможно, она уже остановлена - просто закройте окно программы.
) else (
    echo Программа остановлена.
)
echo.
pause
endlocal
