@echo off
setlocal

cd /d "%~dp0"

echo.
echo ========================================
echo  ArgoMi BestShop - project checks
echo ========================================
echo.

echo [1/4] Nette Tester
call composer run test
if errorlevel 1 goto :failed

echo.
echo [2/4] PHPStan
call composer run phpstan
if errorlevel 1 goto :failed

echo.
echo [3/4] PHP-CS-Fixer
call composer run cs
if errorlevel 1 goto :failed

echo.
echo [4/4] Deptrac
call composer run arch
if errorlevel 1 goto :failed

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
