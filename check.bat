@echo off
setlocal

cd /d "%~dp0"

echo.
echo ========================================
echo  ArgoMi BestShop - project checks
echo ========================================
echo.

echo [1/3] PHPStan
call composer run phpstan
if errorlevel 1 goto :failed

echo.
echo [2/3] PHP-CS-Fixer
call composer run cs
if errorlevel 1 goto :failed

echo.
echo [3/3] Deptrac
call composer run arch
if errorlevel 1 goto :failed

REM Nette Tester is not part of the active check suite yet.
REM Enable it when the test suite is ready:
REM call composer run tester
REM if errorlevel 1 goto :failed

echo.
echo ========================================
echo  All active checks passed.
echo ========================================
exit /b 0

:failed
echo.
echo ========================================
echo  CHECK FAILED - see the output above.
echo  Remaining checks were skipped.
echo ========================================
exit /b 1
