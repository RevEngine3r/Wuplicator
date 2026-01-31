@echo off
REM Wuplicator Installer Builder
REM Compiles modular installer sources into single installer.php

echo ========================================
echo   Wuplicator Installer Builder
echo ========================================
echo.

cd /d "%~dp0"

echo [1/3] Building installer...
echo.
php installer/build.php

if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Build failed!
    pause
    exit /b 1
)

echo.
echo [2/3] Validating PHP syntax...
echo.

REM Find the latest release directory
for /f "delims=" %%i in ('dir /b /ad /o-n "..\..\releases\v*" 2^>nul') do set "LATEST=%%i" & goto :found
:found

if "%LATEST%"=="" (
    echo [ERROR] No release directory found!
    pause
    exit /b 1
)

php -l "..\..\releases\%LATEST%\installer.php"

if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Syntax validation failed!
    pause
    exit /b 1
)

echo.
echo [3/3] Build complete!
echo.
echo Output: releases\%LATEST%\installer.php
echo.
echo Press any key to exit...
pause >nul