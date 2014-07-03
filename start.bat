@echo off

:loop
php-cgi -q index.php
if errorlevel 1 (
    timeout /t %errorlevel% >NUL
    goto loop
)

pause