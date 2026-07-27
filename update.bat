@echo off
setlocal
cd /d "%~dp0"
title Mail Analytics - update

echo ============================================================
echo   Mail Analytics - update / obnovlenie
echo ============================================================
echo.

if not exist ".venv\Scripts\python.exe" goto :no_venv

echo [1/3] Database backup / rezervnaya kopiya bazy...
if not exist "backup" mkdir "backup"
if not exist "data\mail_assistant.db" goto :no_db
".venv\Scripts\python.exe" -c "import shutil,datetime,pathlib;p=pathlib.Path('backup')/('mail_assistant_'+datetime.datetime.now().strftime('%%Y%%m%%d_%%H%%M%%S')+'.db');shutil.copy2('data/mail_assistant.db',p);print('    ->',p)"
goto :deps

:no_db
echo     Database not created yet - nothing to back up.

:deps
echo [2/3] Updating dependencies...
".venv\Scripts\python.exe" -m pip install -r requirements.txt --quiet --upgrade
if errorlevel 1 goto :deps_failed

echo [3/3] Updating database structure...
".venv\Scripts\python.exe" -m alembic upgrade head
if errorlevel 1 goto :db_failed

echo.
echo Update finished / Obnovlenie zaversheno. Zapustite start.bat
pause
exit /b 0

:no_venv
echo [ERROR] Virtual environment not found. Run start.bat first.
echo Snachala zapustite start.bat
pause
exit /b 1

:deps_failed
echo [ERROR] Could not update dependencies. Check the internet connection.
pause
exit /b 1

:db_failed
echo [ERROR] Could not update the database.
echo Restore a copy from the "backup" folder.
pause
exit /b 1
