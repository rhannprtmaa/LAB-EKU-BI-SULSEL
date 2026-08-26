@echo off
cd /d E:\PROJECT\lab-eku-sulsel

C:\xampp\php\php.exe artisan queue:work --sleep=3 --tries=3 --timeout=90 --stop-when-empty
