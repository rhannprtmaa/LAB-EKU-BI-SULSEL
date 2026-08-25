@echo off
cd /d E:\PROJECT\lab-eku-sulsel

echo [%date% %time%] Queue worker starting... >> storage\logs\queue-worker.log

C:\xampp\php\php.exe artisan queue:work --sleep=3 --tries=3 --timeout=90 >> storage\logs\queue-worker.log 2>&1

echo [%date% %time%] Queue worker stopped. Exit code: %ERRORLEVEL% >> storage\logs\queue-worker.log
