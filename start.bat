@echo off
setlocal
cd /d "%~dp0"
title Mail Analytics

echo ============================================================
echo   Mail Analytics / Analitika korporativnoy pochty
echo   Read-only mode
echo ============================================================
echo.

if exist ".venv\Scripts\python.exe" goto :check_deps

rem ---------- Step 1: find Python ----------
echo [1/4] Looking for Python...
set "BOOT="

py -3 --version >nul 2>&1
if not errorlevel 1 set "BOOT=py -3"
if defined BOOT goto :make_venv

python --version >nul 2>&1
if not errorlevel 1 set "BOOT=python"
if defined BOOT goto :make_venv

goto :no_python

rem ---------- Step 2: create virtual environment ----------
:make_venv
echo [2/4] Creating virtual environment (one time, 1-2 minutes)...
%BOOT% -m venv .venv
if errorlevel 1 goto :venv_failed
if not exist ".venv\Scripts\python.exe" goto :venv_failed

echo [3/4] Installing dependencies, please wait...
".venv\Scripts\python.exe" -m pip install --upgrade pip --quiet
".venv\Scripts\python.exe" -m pip install -r requirements.txt
if errorlevel 1 goto :deps_failed
goto :launch

rem ---------- Step 3: verify dependencies ----------
:check_deps
".venv\Scripts\python.exe" -c "import fastapi, sqlalchemy, openai, openpyxl, jinja2" >nul 2>&1
if not errorlevel 1 goto :launch
echo [3/4] Installing missing dependencies...
".venv\Scripts\python.exe" -m pip install -r requirements.txt
if errorlevel 1 goto :deps_failed

rem ---------- Step 4: run ----------
:launch
echo [4/4] Starting the application...
echo.
".venv\Scripts\python.exe" run.py %*
if errorlevel 1 goto :run_failed
goto :finished

rem ---------- Error handlers ----------
:no_python
echo.
echo [ERROR] Python not found / Python ne naiden.
echo.
echo Install Python 3.12 from https://www.python.org/downloads/
echo IMPORTANT: check the box "Add python.exe to PATH" during setup.
echo.
echo Ustanovite Python 3.12 s sayta https://www.python.org/downloads/
echo VAZHNO: pri ustanovke otmette galochku "Add python.exe to PATH".
echo.
pause
exit /b 1

:venv_failed
echo.
echo [ERROR] Could not create the virtual environment.
echo Ne udalos sozdat virtualnoe okruzhenie.
echo.
echo Possible reasons / Vozmozhnye prichiny:
echo   - no write permission in this folder (move it to C:\mail-analytics)
echo   - antivirus blocked the operation
echo   - Python installed without the venv module
echo.
pause
exit /b 1

:deps_failed
echo.
echo [ERROR] Could not install dependencies.
echo Ne udalos ustanovit zavisimosti. Proverte internet-soedinenie.
echo.
echo If your company uses a proxy, see README.md section 15.
echo.
pause
exit /b 1

:run_failed
echo.
echo [ERROR] The application stopped with an error.
echo Programma zavershilas s oshibkoy. Podrobnosti vyshe i v logs\app.log
echo.
pause
exit /b 1

:finished
echo.
echo Application stopped / Programma ostanovlena.
pause
endlocal
