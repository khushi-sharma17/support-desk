@echo off

cd /d C:\xampp\htdocs\support-desk\backend

C:\xampp\php\php.exe yii stale-ticket-digest >> runtime\digests\scheduled.log 2>&1