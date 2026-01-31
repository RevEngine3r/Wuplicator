@echo off
REM Wuplicator Complete Builder
REM Builds both backupper and installer

echo ========================================
echo   Wuplicator Complete Build
echo ========================================
echo.
echo Building both backupper and installer...
echo.

cd /d "%~dp0"

REM Build backupper
echo ========================================
echo   Building Backupper
echo ========================================
echo.
call build-backupper.bat
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Backupper build failed!
    exit /b 1
)

echo.
echo.
echo ========================================
echo   Building Installer  
echo ========================================
echo.
call build-installer.bat
if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Installer build failed!
    exit /b 1
)

echo.
echo.
echo ========================================
echo   All Builds Completed Successfully!
echo ========================================
echo.
echo Both backupper and installer have been built.
echo Check the releases folder for output files.
echo.
echo Press any key to exit...
pause >nul